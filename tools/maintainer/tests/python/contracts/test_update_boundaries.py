"""External runtime decoding and injected probes never execute customer tooling."""

import json
import subprocess
from collections import deque
from functools import partial
from pathlib import Path

import pytest

from distribution import update_runtime
from distribution.processes import ExternalProcesses
from distribution.update_contracts import Blocker, document
from distribution.update_docker import DockerRuntime
from distribution.update_execution import atomic
from distribution.update_models import (
    available_metadata,
    compose_configuration,
    container_inspection,
    database_metadata,
    module_records,
    module_steps,
    service_status,
)
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import InputMode, OutputMode, ProcessResult, ProcessTimeout
from tools.core.state import JsonValue


def test_external_process_policy_preserves_literals_bytes_native_exits_and_safe_errors(
    tmp_path: Path,
) -> None:
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, b"text", b"diagnostic"),
                ProcessResult(0, b"bytes"),
                ProcessResult(7),
                ProcessResult(8),
                ProcessTimeout(),
            ]
        )
    )
    processes = ExternalProcesses(runner, {"SYNTHETIC_PRIVATE": "literal$#"})
    assert (
        processes.run(
            ["probe", "$(literal)"], cwd=tmp_path, text=True, input="literal$#", capture_output=True
        ).stdout
        == "text"
    )
    assert processes.run(["probe"], cwd=tmp_path, capture_output=True).stdout == b"bytes"
    assert processes.run(["probe"], cwd=tmp_path).returncode == 7
    with pytest.raises(subprocess.CalledProcessError) as failed:
        processes.run(["private-argument"], cwd=tmp_path, check=True)
    with pytest.raises(subprocess.TimeoutExpired) as timed_out:
        processes.run(["private-argument"], cwd=tmp_path, timeout=3)
    assert failed.value.returncode == 8
    assert "private-argument" not in str(failed.value) + str(timed_out.value)
    assert runner.calls[0].argv == ("probe", "$(literal)")
    assert runner.calls[0].input == b"literal$#"
    assert runner.calls[0].stdin is InputMode.BYTES
    assert runner.calls[0].env["SYNTHETIC_PRIVATE"] == "literal$#"
    assert runner.calls[1].stdin is InputMode.CLOSED
    assert runner.calls[1].output is OutputMode.CAPTURE


def test_external_document_decoder_rejects_invalid_values_without_touching_inputs(
    tmp_path: Path,
) -> None:
    path = tmp_path / "input.json"
    path.write_text('{"extra":{"preserved":[true,null,"literal$#"]}}')
    assert document(tmp_path, path.name) == {"extra": {"preserved": [True, None, "literal$#"]}}
    for payload in ('{"secret":"sentinel","secret":"second"}', '{"secret":NaN}', '["sentinel"]'):
        path.write_text(payload)
        with pytest.raises(Blocker, match="^Invalid JSON object: input.json$") as caught:
            document(tmp_path, path.name)
        assert caught.value.code == "invalid-json"
        assert "sentinel" not in str(caught.value)
        assert path.read_text() == payload


def test_external_runtime_shapes_preserve_extensions_and_optional_docker_fields() -> None:
    config: dict[str, JsonValue] = {
        "name": "fixture",
        "services": {
            "app": {"image": "fixture", "environment": {"LITERAL": "value$#"}},
            "optional": {"profiles": ["debug"], "volumes": [{"type": "tmpfs", "target": "/tmp"}]},
        },
        "customer_extension": {"retained": True},
    }
    assert compose_configuration(json.dumps(config)) == config
    inspection = [{"Config": {"Env": None, "Labels": None}, "customer_extension": True}]
    assert container_inspection(json.dumps(inspection)) == inspection[0]
    rows = [{"Service": "app", "ID": "fixed", "State": "running", "Health": "healthy"}]
    assert service_status(json.dumps(rows)) == rows
    assert service_status("\n" + "\n".join(json.dumps(row) for row in rows)) == rows
    assert service_status("") == []
    assert available_metadata('{"modules":false,"steps":false}') == {
        "modules": False,
        "steps": False,
    }
    assert database_metadata('{"migrations":[],"seed_runs":[],"extension":true}') == {
        "migrations": [],
        "seed_runs": [],
        "modules": {},
        "module_steps": [],
        "extension": True,
    }
    records = {
        "runa.fixture": {
            "installed": None,
            "version": "0.1.0",
            "registered": True,
            "required": None,
        }
    }
    assert module_records(json.dumps(records)) == records
    assert module_steps("[]") == []


def test_invalid_external_runtime_fields_refuse_with_fixed_diagnostics() -> None:
    cases = (
        (compose_configuration, '{"services":{"app":{"privileged":"false","secret":"sentinel"}}}'),
        (compose_configuration, '{"services":{"app":{"volumes":[{"type":"bind","target":3}]}}}'),
        (container_inspection, '[{"Config":{"Labels":{"key":true}}}]'),
        (container_inspection, '[{"Config":{},"Mounts":[{"Type":"volume","RW":"false"}]}]'),
        (service_status, '[{"Service":"app","ID":false}]'),
        (database_metadata, '{"migrations":[true],"seed_runs":[]}'),
        (database_metadata, '{"migrations":[],"seed_runs":[{"release_sha":true,"seeder":"x"}]}'),
        (available_metadata, '{"modules":1,"steps":false}'),
        (
            module_records,
            '{"runa.fixture":{"installed":null,"version":"0.1.0","registered":1,"required":{}}}',
        ),
        (
            module_steps,
            '[{"module":"runa.fixture","step":"x","fingerprint":[],"status":"succeeded","operation":"x"}]',
        ),
    )
    for decode, payload in cases:
        with pytest.raises(Blocker) as caught:
            decode(payload)
        assert caught.value.code == "invalid-runtime-response"
        assert "sentinel" not in str(caught.value)


def test_runtime_probes_and_database_metadata_share_the_injected_transport(tmp_path: Path) -> None:
    for name in ("compose.yaml", ".devcontainer/compose.resources.yaml"):
        path = tmp_path / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text("services: {}\n")
    environment = tmp_path / ".env.dev.local"
    environment.write_text("VALUE=literal$#\n")
    environment.chmod(0o600)
    required = ["app", "nginx", "postgres", "queue", "scheduler", "mailpit"]
    config = json.dumps(
        {"name": "fixture", "services": {name: {"image": "fixture"} for name in required}}
    ).encode()
    rows = json.dumps(
        [
            {"Service": name, "ID": "fixture", "State": "running", "Health": "healthy"}
            for name in required
        ]
    ).encode()
    inspection = json.dumps(
        [
            {
                "Config": {
                    "Env": None,
                    "Labels": {
                        "com.docker.compose.project.working_dir": str(tmp_path),
                        "com.docker.compose.project": "fixture",
                    },
                },
                "Image": "sha256:fixture",
                "HostConfig": {},
            }
        ]
    ).encode()
    database_inspection = json.loads(inspection)
    database_inspection[0]["Mounts"] = [
        {
            "Type": "volume",
            "Name": "fixture-data",
            "Source": "/host/data",
            "Destination": "/database",
            "RW": True,
        },
        {"Type": "bind", "Source": "/host/config", "Destination": "/config", "RW": False},
    ]
    database = b'{"migrations":["initial"],"seed_runs":[]}'
    optional = b'{"modules":false,"steps":false}'
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, config),  # runtime composition
                ProcessResult(0, config),
                ProcessResult(0, rows),
                *[
                    result
                    for _ in required
                    for result in (
                        ProcessResult(0, inspection),
                        ProcessResult(0, b"sha256:fixture"),
                    )
                ],
                ProcessResult(0, database),
                ProcessResult(0, optional),
                ProcessResult(0, b"fixture"),
                ProcessResult(0, json.dumps(database_inspection).encode()),
                ProcessResult(0, database),
                ProcessResult(0, optional),
            ]
        )
    )
    runtime = DockerRuntime(
        tmp_path,
        command=partial(
            update_runtime.command,
            processes=ExternalProcesses(
                runner,
                {
                    "PATH": "/fixture",
                    "COMPOSE_FILE": "foreign",
                    "DOCKER_HOST": "foreign",
                    "RUNA_CONTEXT": "foreign",
                    "GIT_DIR": "foreign",
                    "LITERAL": "value$#",
                },
            ),
        ),
    )
    snapshot = runtime.probe()
    assert snapshot["project"] == "fixture" and snapshot["migrations"] == ["initial"]
    assert snapshot["modules"] == {} and snapshot["module_steps"] == []
    metadata = runtime.metadata()
    assert metadata["migrations"] == ["initial"]
    assert metadata["database_mounts"] == [
        ["bind", "/host/config", "/config"],
        ["volume", "fixture-data", "/database"],
    ]
    evidence = tmp_path / "operation" / "metadata.json"
    atomic(evidence, metadata)
    assert json.loads(evidence.read_text()) == metadata
    assert not runner.outcomes
    for call in runner.calls:
        assert call.stdin is InputMode.CLOSED and call.output is OutputMode.CAPTURE
        assert set(call.env) == {"PATH", "LITERAL"}
        assert call.env["LITERAL"] == "value$#"
        if "psql" in " ".join(call.argv):
            assert call.argv[-1].startswith("BEGIN READ ONLY;")
            assert call.argv[-1].endswith("ROLLBACK;")


def test_lock_probe_failure_uses_same_process_policy_and_cleans_only_its_worker(
    tmp_path: Path,
) -> None:
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, ("sha256:" + "a" * 64).encode()),
                ProcessResult(9),
                ProcessResult(0),
            ]
        )
    )
    with pytest.raises(Blocker) as caught:
        update_runtime.validate_locks(
            tmp_path, "fixture:toolchain", processes=ExternalProcesses(runner, {})
        )
    assert caught.value.code == "dependency-lock-review"
    probe, cleanup = runner.calls[1:]
    name = probe.argv[probe.argv.index("--name") + 1]
    assert name.startswith("runa-update-locks-")
    assert cleanup.argv == ("docker", "rm", "--force", name)
    assert not runner.outcomes
