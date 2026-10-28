"""Source catalog and development health boundaries without Docker execution."""

import json
from collections import deque
from pathlib import Path

import pytest

from tools.application.management_models import command_definitions
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import ProcessResult
from tools.core.state import JsonValue


def test_command_records_validate_consumed_fields_and_preserve_metadata() -> None:
    source = '[{"name":"runa:test","tool":"test","prefix":[],"description":"kept"}]'
    assert command_definitions(source) == json.loads(source)
    for payload in (
        "{}",
        "[null]",
        '[{"name":"runa:test","tool":[],"prefix":[]}]',
        '[{"name":"runa:test","tool":"test","prefix":[false]}]',
        '[{"name":"runa:test","tool":"test","prefix":[],"extra":NaN}]',
        '[{"name":"first","name":"second","tool":"test","prefix":[]}]',
    ):
        with pytest.raises(ValueError, match="management command"):
            command_definitions(payload)


def health_rows() -> list[dict[str, JsonValue]]:
    rows: list[dict[str, JsonValue]] = [
        {"Service": service, "State": "running", "Health": "healthy"}
        for service in ("app", "nginx", "postgres", "queue", "scheduler", "mailpit")
    ]
    rows.append({"Service": "setup", "State": "exited", "ExitCode": 0})
    return rows


@pytest.mark.parametrize("lines", [False, True])
def test_development_health_retains_array_and_line_responses(tmp_path: Path, lines: bool) -> None:
    rows = health_rows()
    payload = "\n".join(json.dumps(row) for row in rows) if lines else json.dumps(rows)
    runner = RecordingRunner(deque([ProcessResult(0, payload.encode(), b"")]))
    service = ManagementService(
        WorkspaceContext(tmp_path), ApplicationProcesses(runner, {}), ExecutionLocation.HOST
    )
    service.host_stack_health()
    assert len(runner.calls) == 1
    assert runner.calls[0].cwd == tmp_path


def test_development_health_refuses_boolean_or_failed_setup_exit(tmp_path: Path) -> None:
    for code in (False, "0", None, 1):
        rows = health_rows()
        rows[-1]["ExitCode"] = code
        runner = RecordingRunner(deque([ProcessResult(0, json.dumps(rows).encode(), b"")]))
        service = ManagementService(
            WorkspaceContext(tmp_path), ApplicationProcesses(runner, {}), ExecutionLocation.HOST
        )
        with pytest.raises(ValueError):
            service.host_stack_health()
        assert len(runner.calls) == 1
