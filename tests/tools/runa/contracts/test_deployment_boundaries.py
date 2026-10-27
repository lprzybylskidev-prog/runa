"""Typed deployment boundaries preserve legacy evidence and refuse malformed responses."""

import json
import subprocess
from collections import deque
from pathlib import Path

import pytest

from tools.application.deployment_context import OperationContext
from tools.application.deployment_models import (
    compose_configuration,
    container_inspection,
    deployment_state,
    manifest_version,
    service_image,
    service_status,
)
from tools.application.deployment_service import DeploymentService
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import InputMode, ProcessResult
from tools.core.state import JsonValue


def release_record(directory: Path) -> dict[str, JsonValue]:
    return {
        "sha": "a" * 40,
        "path": str(directory / "releases" / ("a" * 40)),
        "version": "0.5.0",
        "application_version": "customer-version",
        "database_tree": "historical-tree-hash",
        "deployed_at": "2026-10-22T12:00:00+00:00",
        "simulation": True,
        "images": {"runtime": "sha256:recorded"},
        "operator_evidence": {"preserve": True},
    }


def test_legacy_release_evidence_roundtrips_without_granting_rollback(tmp_path: Path) -> None:
    context = OperationContext.create(WorkspaceContext(tmp_path))
    record = release_record(context.state)
    context.atomic(context.state / "deployment.json", {"current": record, "previous": None})
    state = context.read_state()
    current = state["current"]
    assert current is not None
    assert current["application_version"] == "customer-version"
    assert not current.get("rollback_safe")
    context.atomic(context.state / "deployment.json", state)
    assert json.loads((context.state / "deployment.json").read_text())["current"] == record


def test_malformed_release_refusal_keeps_the_original_journal(tmp_path: Path) -> None:
    context = OperationContext.create(WorkspaceContext(tmp_path))
    invalids: tuple[tuple[str, JsonValue], ...] = (
        ("simulation", "false"),
        ("rollback_safe", 1),
        ("images", {"runtime": 1}),
        ("version", {}),
        ("path", str(tmp_path / "foreign-release")),
    )
    for field, invalid in invalids:
        record = release_record(context.state)
        record[field] = invalid
        path = context.state / "deployment.json"
        context.atomic(path, {"current": record, "previous": None})
        before = path.read_bytes()
        with pytest.raises(ValueError, match="deployment|Deployment"):
            context.read_state()
        assert path.read_bytes() == before
    with pytest.raises(ValueError, match="journal"):
        deployment_state({"current": None}, context.state)


def test_compose_and_inspection_fields_are_validated_without_losing_extensions() -> None:
    config = {
        "services": {
            "app": {
                "image": "runa-production:fixture",
                "environment": {"KNOWLEDGE_ENABLED": "false"},
                "volumes": [{"source": "storage", "target": "/storage"}],
                "labels": {"customer.extension": "retained"},
            }
        },
        "networks": {"customer": {}},
    }
    assert compose_configuration(json.dumps(config)) == config
    optional = compose_configuration('{"services":{"debug":{"profiles":["debug"],"build":"."}}}')
    assert optional["services"]["debug"].get("profiles") == ["debug"]
    with pytest.raises(ValueError, match="image identity"):
        service_image(optional["services"]["debug"])
    for service in ({"image": []}, {"image": "runtime", "profiles": "debug"}):
        with pytest.raises(ValueError, match="Compose"):
            compose_configuration(json.dumps({"services": {"app": service}}))
    for payload in ("[]", "{}", '{"services":{"app":{"image":"runtime","privileged":1}}}'):
        with pytest.raises(ValueError, match="Compose"):
            compose_configuration(payload)
    inspection = [{"Image": "sha256:fixed", "HostConfig": {}, "Config": {"Env": None}}]
    assert container_inspection(json.dumps(inspection))["Config"]["Env"] is None
    for payload in ("[]", "[{},{}]", '[{"Image":"fixed","HostConfig":[],"Config":{}}]'):
        with pytest.raises(ValueError, match="inspection"):
            container_inspection(payload)


def test_compose_status_supports_array_and_line_formats_and_safe_diagnostics(
    tmp_path: Path,
) -> None:
    rows = [{"Service": "app", "State": "running", "Health": "healthy"}, {"Service": "nginx"}]
    assert service_status(json.dumps(rows)) == rows
    assert service_status("\n".join(json.dumps(row) for row in rows)) == rows
    assert service_status("") == []
    for payload in ('{"Service":1}', '[{"Service":"app","Health":[]}]'):
        with pytest.raises(ValueError, match="status"):
            service_status(payload)
    manifest = tmp_path / "application.json"
    manifest.write_text('{"version":"customer-version"}')
    assert manifest_version(manifest) == "customer-version"
    manifest.write_text('{"version": {"secret":"synthetic-private-content"}}')
    with pytest.raises(ValueError, match="manifest version") as caught:
        manifest_version(manifest)
    assert "synthetic-private-content" not in str(caught.value)


def test_operation_and_compose_adapters_preserve_text_bytes_and_explicit_stdin(
    tmp_path: Path,
) -> None:
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, b"text"),
                ProcessResult(0, b"bytes"),
                ProcessResult(7),
                ProcessResult(8),
            ]
        )
    )
    service = DeploymentService.for_workspace(
        tmp_path,
        processes=ApplicationProcesses(
            runner, {"VALUE": "literal$secret", "COMPOSE_FILE": "foreign"}
        ),
        location=ExecutionLocation.HOST,
    )
    (tmp_path / ".env.prod.compose").write_text("RUNA_IMAGE_TAG=" + "a" * 40 + "\n")
    assert service.context.run(["probe"], capture_output=True, text=True).stdout == "text"
    assert service.runtime.dc(["config"], capture_output=True, input=b"payload").stdout == b"bytes"
    assert runner.calls[0].cwd == tmp_path
    assert runner.calls[0].stdin is InputMode.INHERIT
    assert runner.calls[1].stdin is InputMode.BYTES
    assert runner.calls[1].input == b"payload"
    assert runner.calls[1].env["VALUE"] == "literal$secret"
    assert "COMPOSE_FILE" not in runner.calls[1].env
    assert service.context.run(["probe"], check=False).returncode == 7
    with pytest.raises(subprocess.CalledProcessError) as caught:
        service.context.run(["probe"])
    assert caught.value.returncode == 8
