"""Installer decoding and recovery identity refusal preserve private evidence."""

import json
from collections import deque
from pathlib import Path

import pytest

from tools.application.deployment_service import DeploymentService
from tools.application.installation_models import (
    InstallationError,
    container_labels,
    installation_compose,
    installation_journal,
    installation_resources,
    json_object,
)
from tools.application.installation_service import (
    InstallationService,
    private_json,
    validate_settings,
)
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation
from tools.core.process import ProcessResult
from tools.core.state import JsonValue


def test_private_settings_reject_ambiguous_nonfinite_and_nonobject_json(tmp_path: Path) -> None:
    path = tmp_path / "private.json"
    path.touch(mode=0o600)
    for payload in (
        b"[]",
        b'{"smtp":{"password":"first","password":"second"}}',
        b'{"extra":NaN}',
        b'{"extra":Infinity}',
        b'{"extra":"\xff"}',
        b"{" + b" " * 65536,
    ):
        path.write_bytes(payload)
        with pytest.raises(InstallationError):
            private_json(path)
        assert path.read_bytes() == payload
    payload = b'{"extra":{"literal":"$raw=kept","values":[1,null,true]}}'
    path.write_bytes(payload)
    assert private_json(path) == json.loads(payload)


def test_settings_types_fail_before_url_tls_or_secret_use() -> None:
    source: dict[str, JsonValue] = {
        "app_url": "https://customer.example",
        "tls": {"mode": "external"},
        "smtp": {
            "host": "smtp.customer.example",
            "port": 587,
            "scheme": "smtp",
            "from": "from@example.invalid",
            "recipient": "to@example.invalid",
        },
    }
    assert validate_settings(source, False)["smtp"]["port"] == 587
    for change in (
        {"app_url": []},
        {"http_port": True},
        {"simulation_project": []},
        {"tls": {"mode": "external", "ca_file": []}},
        {"smtp": {"host": "smtp.customer.example", "port": True}},
        {"administrator": {"email": [], "name": "Fixture"}},
    ):
        with pytest.raises(InstallationError):
            validate_settings(source | change, False)


def test_installation_journal_preserves_extra_and_legacy_optional_fields() -> None:
    value = json_object('{"sha":"target","simulation":false,"state":"failed","extra":1}')
    assert installation_journal(value) == value
    for change in (
        {"sha": []},
        {"simulation": 0},
        {"state": None},
        {"profile": []},
        {"administrator_invited": "false"},
        {"schema_version": True},
        {"started_at": []},
    ):
        with pytest.raises(InstallationError, match="journal"):
            installation_journal(json_object(json.dumps(value | change)))


def test_installation_runtime_decoders_preserve_optional_and_extra_metadata() -> None:
    assert installation_compose('{"name":"project","extra":1}') == {"name": "project", "extra": 1}
    assert installation_resources('{"sha":"target","project":"project","extra":1}') == {
        "sha": "target",
        "project": "project",
        "extra": 1,
    }
    assert container_labels('[{"Config":{"Labels":null}}]') == {}
    assert container_labels('[{"Config":{"Labels":{"owner":"workspace"}},"extra":1}]') == {
        "owner": "workspace",
    }
    for payload in ("{}", '{"name":[]}', '{"name":"project","volumes":{"db":{"name":[]}}}'):
        with pytest.raises(InstallationError):
            installation_compose(payload)
    for payload in ("[]", "[{}]", '[{"Config":{"Labels":{"owner":false}}}]'):
        with pytest.raises(InstallationError):
            container_labels(payload)


def test_invalid_container_identity_refuses_before_any_installation_mutation(
    tmp_path: Path,
) -> None:
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, b"owned-id\n", b""),
                ProcessResult(0, b'[{"Config":{"Labels":{"owner":false}}}]', b""),
            ]
        )
    )
    deployment = DeploymentService.for_workspace(
        tmp_path, processes=ApplicationProcesses(runner, {}), location=ExecutionLocation.HOST
    )
    service = InstallationService(
        deployment,
        ManagementService(
            deployment.context.workspace, deployment.context.processes, ExecutionLocation.HOST
        ),
    )
    with pytest.raises(InstallationError, match="labels"):
        service.development_context()
    assert len(runner.calls) == 2
    assert runner.calls[-1].argv == ("docker", "inspect", "owned-id")
    assert all(call.cwd == tmp_path for call in runner.calls)
    assert not (tmp_path / ".runtime").exists()
