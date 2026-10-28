"""Injected quality contexts, graph safety and candidate evidence regressions."""

from __future__ import annotations

import json
import os
import signal
import subprocess
import sys
import time
from collections.abc import Sequence
from pathlib import Path

import pytest

from tests.tools.runa.support.quality import FixturePlanner, definition, node
from tests.tools.runa.support.quality import decoded_definition as quality_definition
from tools.application.quality_cli import service
from tools.application.quality_execution import QualityExecutor
from tools.application.quality_planning import arguments, resolve, select, validate_declared
from tools.application.quality_reporting import status
from tools.application.quality_service import QualityPlanner, QualityService
from tools.application.quality_state import run_record
from tools.core.context import ExecutionLocation, WorkspaceContext


def test_selection_prerequisites_hash_and_declared_scope() -> None:
    nodes, scope = resolve(definition(), [], groups=["docs"])
    assert set(nodes) == {"base", "closure"}
    assert scope["mode"] == "groups"
    assert scope["excluded_groups"] == ["backend"]
    validate_declared(dict(quality_groups="docs", quality_rationale="Selected docs"), scope)
    with pytest.raises(ValueError, match="differs"):
        validate_declared(dict(quality_groups="all", quality_rationale="all"), scope)
    with pytest.raises(ValueError, match="cycle"):
        select(
            quality_definition(
                dict(base=[node("a", deps=["b"]), node("b", deps=["a"])], impact_areas={}, final=[])
            ),
            [],
        )
    with pytest.raises(ValueError, match="Missing"):
        select(
            quality_definition(dict(base=[node("a", deps=["missing"])], impact_areas={}, final=[])),
            [],
        )
    for argv in [["status", "--all"], ["show", "--all", "--group=docs"], ["show", "--background"]]:
        with pytest.raises(ValueError):
            arguments(argv)


def test_owner_and_customer_composition_do_not_mutate_each_other(tmp_path: Path) -> None:
    owner = service(
        tmp_path,
        roadmaps=tmp_path / "owner",
        definition=tmp_path / "owner.yml",
        directory=tmp_path / ".runtime/quality",
    )
    customer = service(tmp_path)
    assert isinstance(owner.planner, QualityPlanner)
    assert isinstance(customer.planner, QualityPlanner)
    assert owner.planner.roadmaps != customer.planner.roadmaps
    assert customer.directory == tmp_path / ".runtime/application-quality"
    assert not customer.planner.definition.name == "owner.yml"
    assert owner.planner.allow_application_default is False


def test_failure_blocks_dependents_preserves_native_exit_and_host_transport(tmp_path: Path) -> None:
    calls: list[list[str]] = []

    def delegate(argv: Sequence[str]) -> list[str]:
        calls.append(list(argv))
        return ["true"]

    executor = QualityExecutor(WorkspaceContext(tmp_path), ExecutionLocation.HOST, delegate)
    state = run_record(
        dict(
            status="running",
            steps={
                n["id"]: dict(n, status="pending")
                for n in [
                    dict(
                        node("bad", "python3 -c 'import sys; sys.exit(7)'"),
                        execution_context="host",
                    ),
                    node("blocked", deps=["bad"]),
                    node("app", "application-command"),
                ]
            },
        )
    )
    assert executor.execute(tmp_path, state) == 1
    assert state["steps"]["bad"].get("exit_code") == 7
    assert state["steps"]["blocked"]["status"] == "blocked"
    assert state["steps"]["app"]["status"] == "passed"
    assert calls == [["application-command"]]
    assert not (tmp_path / "blocked.log").exists()


def test_exclusive_groups_serialize_independent_steps_overlap(tmp_path: Path) -> None:
    script = tmp_path / "step.py"
    script.write_text(
        "import time\nprint(time.time(), flush=True)\ntime.sleep(.15)\nprint(time.time())\n"
    )
    command = f"{sys.executable} {script}"
    state = run_record(
        dict(
            status="running",
            steps={
                n["id"]: dict(n, status="pending")
                for n in [
                    node("a", command, group="db"),
                    node("b", command, group="db"),
                    node("c", command),
                ]
            },
        )
    )
    assert (
        QualityExecutor(WorkspaceContext(tmp_path), ExecutionLocation.CONTAINER, tuple).execute(
            tmp_path, state
        )
        == 0
    )
    intervals = {
        key: list(map(float, (tmp_path / (key + ".log")).read_text().split()))
        for key in state["steps"]
    }
    assert intervals["b"][0] >= intervals["a"][1]
    assert intervals["c"][0] < intervals["a"][1]


def test_abandoned_status_marks_interruption_and_tails(
    tmp_path: Path, capsys: pytest.CaptureFixture[str]
) -> None:
    (tmp_path / "state.json").write_text(
        json.dumps(dict(status="running", steps={"step": dict(status="running")}))
    )
    (tmp_path / "step.log").write_text("\n".join(f"line-{i}" for i in range(40)))
    status(tmp_path, False)
    out = capsys.readouterr().out
    assert "line-9\n" not in out
    assert "line-10\n" in out
    assert "Overall status: interrupted" in out


def test_background_lock_signal_and_reaping(tmp_path: Path) -> None:
    subprocess.run(["git", "init", "-q", str(tmp_path)], check=True)
    subprocess.run(
        [
            "git",
            "-C",
            str(tmp_path),
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.test",
            "commit",
            "--allow-empty",
            "-qm",
            "fixture",
        ],
        check=True,
    )
    root = Path(__file__).resolve().parents[4]
    harness = tmp_path / "harness.py"
    harness.write_text(
        "from pathlib import Path\nimport sys\nfrom tests.tools.runa.support.quality import FixturePlanner\nfrom tools.application.quality_cli import main\nfrom tools.application.quality_service import QualityService\nfrom tools.application.quality_execution import QualityExecutor\nfrom tools.core.context import WorkspaceContext, ExecutionLocation\n"
        + f"root=Path({str(tmp_path)!r})\n"
        + "planner=FixturePlanner("
        + repr(
            dict(
                base=[node("wait", "python3 -c 'import time; time.sleep(30)'")],
                impact_areas={},
                final=[],
            )
        )
        + ")\n"
        + 'quality=QualityService(planner, QualityExecutor(WorkspaceContext(root),ExecutionLocation.CONTAINER,tuple),root/".runtime/quality","fixture ")\nraise SystemExit(main(sys.argv[1:],quality=quality))\n'
    )
    env = {key: value for key, value in os.environ.items() if key != "PYTHONPATH"}
    command = [sys.executable, "-c", harness.read_text()]
    started = subprocess.run(
        command + ["roadmap", "--background"], cwd=root, env=env, capture_output=True, text=True
    )
    assert started.returncode == 0, started.stderr
    statefile = tmp_path / ".runtime/quality/state.json"
    coordinator = None
    state = run_record({"status": "running", "steps": {}})
    try:
        for _ in range(100):
            state = run_record(json.loads(statefile.read_text()))
            if state["steps"]["wait"].get("pid"):
                break
            time.sleep(0.02)
        pid = state["steps"]["wait"].get("pid")
        assert isinstance(pid, int)
        coordinator = int(Path(f"/proc/{pid}/stat").read_text().split()[3])
        refused = subprocess.run(
            command + ["roadmap"], cwd=root, env=env, capture_output=True, text=True
        )
        assert refused.returncode == 1 and "Another quality run" in refused.stderr
        os.kill(coordinator, signal.SIGTERM)
        for _ in range(150):
            state = run_record(json.loads(statefile.read_text()))
            if state["status"] == "interrupted":
                break
            time.sleep(0.02)
        assert state["status"] == "interrupted"
        assert not Path(f"/proc/{pid}").exists()
    finally:
        if coordinator:
            try:
                os.kill(coordinator, signal.SIGTERM)
            except ProcessLookupError:
                pass


def test_invalid_selection_and_preview_never_replace_prior_evidence(
    tmp_path: Path, capsys: pytest.CaptureFixture[str]
) -> None:
    directory = tmp_path / ".runtime/quality"
    directory.mkdir(parents=True)
    sentinel = directory / "state.json"
    sentinel.write_text('{"sentinel": true}')

    quality = QualityService(
        FixturePlanner(definition()),
        QualityExecutor(WorkspaceContext(tmp_path), ExecutionLocation.CONTAINER, tuple),
        directory,
        "fixture ",
    )
    for argv in [["final", "--group=unknown"], ["final", "--all", "--group=docs"]]:
        with pytest.raises(ValueError):
            quality.run(argv)
    assert quality.run(["show", "--group=docs"]) == 0
    assert sentinel.read_text() == '{"sentinel": true}'
    assert not (directory.parent / "quality.lock").exists()
    assert "Excluded groups (not run): backend" in capsys.readouterr().out


def test_empty_command_and_unknown_execution_context_refused() -> None:
    for item in [node("a", ""), dict(node("a"), execution_context="arbitrary")]:
        with pytest.raises(ValueError):
            select(quality_definition(dict(base=[item], impact_areas={}, final=[])), [])
