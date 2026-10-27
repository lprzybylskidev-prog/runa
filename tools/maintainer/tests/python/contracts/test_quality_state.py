"""Quality evidence decoding refuses malformed consumed fields without losing history."""

import json
from pathlib import Path

import pytest

from tools.application.quality_reporting import read_state, save, status
from tools.application.quality_state import run_record
from tools.core.state import JsonValue


def test_legacy_state_and_unknown_evidence_round_trip(tmp_path: Path) -> None:
    source: dict[str, JsonValue] = {
        "status": "running",
        "steps": {"old": {"status": "running", "pid": None, "extra": ["retained"]}},
        "extra": {"retained": True},
    }
    state = run_record(source)
    assert state == source
    assert "selection" not in state
    save(tmp_path, state)
    assert read_state(tmp_path) == source
    status(tmp_path, False)
    updated = json.loads((tmp_path / "state.json").read_text())
    assert updated["status"] == "interrupted"
    assert updated["steps"]["old"] == {"status": "interrupted", "pid": None, "extra": ["retained"]}
    assert source["status"] == "running"


def test_invalid_consumed_state_fields_refuse_before_status_mutation(tmp_path: Path) -> None:
    changes: tuple[dict[str, JsonValue], ...] = (
        {"duration_seconds": True},
        {"duration_seconds": -1},
        {"duration_seconds": 10**400},
        {"started_at": "2026-10-27T12:00:00"},
        {"finished_at": "invalid"},
        {"selection": []},
        {"selection": {"mode": "groups"}},
        {"commit": []},
        {"rationale": False},
        {"steps": {"../outside": {"status": "running"}}},
        {"steps": {"step": {"status": "running", "pid": True}}},
        {"steps": {"step": {"status": "passed", "exit_code": False}}},
        {"steps": {"step": {"status": "running", "started_at": []}}},
        {"steps": {"step": {"status": "passed", "duration_seconds": "1"}}},
    )
    path = tmp_path / "state.json"
    for change in changes:
        original = json.dumps({"status": "running", "steps": {}, **change})
        path.write_text(original)
        with pytest.raises(ValueError):
            status(tmp_path, False)
        assert path.read_text() == original


def test_active_status_preserves_evidence_and_renders_native_exit_timing(
    tmp_path: Path, capsys: pytest.CaptureFixture[str]
) -> None:
    source = run_record(
        {
            "status": "running",
            "duration_seconds": None,
            "steps": {
                "running": {"status": "running", "started_at": "2026-10-27T12:00:00+00:00"},
                "failed": {"status": "failed", "exit_code": -15, "duration_seconds": 2},
            },
        }
    )
    save(tmp_path, source)
    before = (tmp_path / "state.json").read_bytes()
    status(tmp_path, True)
    output = capsys.readouterr().out
    assert "running: running (" in output
    assert "failed:" not in output
    assert "Process is still running." in output
    assert (tmp_path / "state.json").read_bytes() == before
    assert read_state(tmp_path)["steps"]["failed"].get("exit_code") == -15


def test_spawn_refusal_preserves_literal_transport_and_fixed_diagnostic(tmp_path: Path) -> None:
    import signal
    import subprocess
    from collections.abc import Sequence
    from typing import TextIO

    from tools.application.quality_execution import QualityExecutor
    from tools.core.context import ExecutionLocation, WorkspaceContext
    from tools.maintainer.tests.python.support.quality import node

    delegated: list[list[str]] = []
    logs: list[TextIO] = []

    def delegate(arguments: Sequence[str]) -> Sequence[str]:
        delegated.append(list(arguments))
        return ["container", *arguments]

    def refuse(
        args: Sequence[str],
        *,
        cwd: Path,
        env: dict[str, str],
        stdout: TextIO,
        stderr: int,
        start_new_session: bool,
    ) -> subprocess.Popen[bytes]:
        assert list(args) == ["container", "tool", "literal $value; keep"]
        assert cwd == tmp_path
        assert env == {"FIXTURE": "literal $value"}
        assert stderr == subprocess.STDOUT and start_new_session is True
        logs.append(stdout)
        raise OSError("private process payload")

    state = run_record(
        {
            "status": "running",
            "steps": {
                "refused": dict(node("refused", "tool 'literal $value; keep'"), status="pending"),
                "dependent": dict(node("dependent", deps=["refused"]), status="pending"),
            },
        }
    )
    signals = (signal.SIGTERM, signal.SIGINT, signal.SIGHUP)
    handlers = {number: signal.getsignal(number) for number in signals}
    executor = QualityExecutor(
        WorkspaceContext(tmp_path),
        ExecutionLocation.HOST,
        delegate,
        spawn=refuse,
        environment={"FIXTURE": "literal $value"},
    )
    assert executor.execute(tmp_path, state) == 1
    assert delegated == [["tool", "literal $value; keep"]]
    assert len(logs) == 1 and logs[0].closed
    assert state["steps"]["refused"].get("exit_code") == 127
    assert state["steps"]["dependent"]["status"] == "blocked"
    assert (
        tmp_path / "refused.log"
    ).read_text() == "Required quality command could not be started.\n"
    assert {number: signal.getsignal(number) for number in signals} == handlers
    assert read_state(tmp_path) == state
