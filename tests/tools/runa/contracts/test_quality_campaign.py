"""Explicit source validity and preserved attempt evidence without real quality runs."""

from __future__ import annotations

import json
import subprocess
from pathlib import Path

import pytest

from tests.tools.runa.support.quality import node
from tools.application.quality_campaign import archive, input_proofs, reuse_plan
from tools.application.quality_models import QualityGraph, quality_nodes
from tools.application.quality_reporting import save
from tools.application.quality_state import run_record
from tools.core.state import JsonStore, StateDirectory, json_value


def fixture(root: Path):
    subprocess.run(["git", "init", "--quiet", str(root)], check=True)
    (root / "backend.py").write_text("backend\n")
    (root / "frontend.ts").write_text("frontend\n")
    nodes: QualityGraph = {
        "base": dict(
            node("base"), reuse={"paths": ["base.txt"], "environment": [], "always_run": True}
        ),
        "backend": dict(
            node("backend", deps=["base"]),
            reuse={"paths": ["backend.py"], "environment": ["TEST_WORKERS"]},
        ),
        "frontend": dict(
            node("frontend", deps=["base"]), reuse={"paths": ["frontend.ts"], "environment": []}
        ),
    }
    state = run_record(
        {
            "status": "failed",
            "steps": {
                key: dict(value, status="passed", exit_code=0) for key, value in nodes.items()
            },
        }
    )
    latest = root / ".runtime/quality"
    save(latest, state, anchor=root)
    for key in nodes:
        (latest / (key + ".log")).write_text("native result\n")
    proofs = input_proofs(root, nodes, {"TEST_WORKERS": "4"})
    JsonStore(StateDirectory(latest, private=False, anchor=root), private_files=False).write(
        "proof.json", json_value(proofs)
    )
    retained = archive(latest, root)
    assert retained is not None
    evidence = latest / "attempts" / retained[0]
    return nodes, state, proofs, evidence, latest


def test_quality_campaign_scopes_source_changes_and_preserves_native_evidence(
    tmp_path: Path,
) -> None:
    nodes, state, proofs, evidence, latest = fixture(tmp_path)
    plan = reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)
    assert plan["frontend"].startswith("reusable")
    assert plan["backend"].startswith("reusable")
    assert plan["base"].startswith("rerun")
    (tmp_path / "backend.py").write_text("repaired backend\n")
    changed = input_proofs(tmp_path, nodes, {"TEST_WORKERS": "4"})
    plan = reuse_plan(nodes, changed, state, proofs, evidence, tmp_path)
    assert plan["backend"].startswith("stale")
    assert plan["frontend"].startswith("reusable")
    (latest / "backend.log").write_text("new attempt\n")
    assert (evidence / "backend.log").read_text() == "native result\n"
    assert json.loads((evidence / "state.json").read_text())["status"] == "failed"


def test_quality_campaign_shared_harness_and_environment_invalidate_inputs(tmp_path: Path) -> None:
    nodes, state, proofs, evidence, _ = fixture(tmp_path)
    changed = input_proofs(tmp_path, nodes, {"TEST_WORKERS": "2"})
    plan = reuse_plan(nodes, changed, state, proofs, evidence, tmp_path)
    assert plan["backend"].startswith("stale")
    assert plan["frontend"].startswith("reusable")
    harness = tmp_path / "tools/application/testing/runner.py"
    harness.parent.mkdir(parents=True)
    harness.write_text("changed shared runner\n")
    changed = input_proofs(tmp_path, nodes, {"TEST_WORKERS": "4"})
    plan = reuse_plan(nodes, changed, state, proofs, evidence, tmp_path)
    assert all(plan[key].startswith("stale") for key in ("backend", "frontend"))
    assert "TEST_WORKERS" not in json.dumps(changed)


def test_quality_campaign_refuses_tampered_missing_and_symlink_evidence(tmp_path: Path) -> None:
    nodes, state, proofs, evidence, _ = fixture(tmp_path)
    (evidence / "backend.log").write_text("altered\n")
    with pytest.raises(ValueError, match="missing or changed"):
        reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)
    (evidence / "backend.log").unlink()
    with pytest.raises(ValueError, match="missing or changed"):
        reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)
    (evidence / "backend.log").symlink_to(tmp_path / "backend.py")
    with pytest.raises(ValueError, match="symlinks"):
        reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)


def test_quality_campaign_failed_native_and_changed_graph_never_reuse(tmp_path: Path) -> None:
    nodes, state, proofs, evidence, latest = fixture(tmp_path)
    state["steps"]["backend"].update(status="failed", exit_code=1)
    with pytest.raises(ValueError, match="retained native state"):
        reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)
    save(latest, state, anchor=tmp_path)
    retained = archive(latest, tmp_path)
    assert retained is not None
    evidence = latest / "attempts" / retained[0]
    assert reuse_plan(nodes, proofs, state, proofs, evidence, tmp_path)["backend"].startswith(
        "rerun"
    )
    nodes["base"]["command"] = "changed-command"
    changed = input_proofs(tmp_path, nodes, {})
    assert reuse_plan(nodes, changed, state, proofs, evidence, tmp_path)["frontend"].startswith(
        "stale"
    )


@pytest.mark.parametrize("paths", [["../outside"], ["/absolute"], [".runtime/result"], []])
def test_quality_campaign_requires_reviewed_owned_inputs(paths: list[str]) -> None:
    with pytest.raises(ValueError, match="reuse"):
        quality_nodes([dict(node("a"), reuse={"paths": paths, "environment": []})])


def test_quality_campaign_service_preview_is_readonly_and_resume_runs_only_stale_nodes(
    tmp_path: Path, capsys: pytest.CaptureFixture[str]
) -> None:
    import sys

    from tests.tools.runa.support.quality import FixturePlanner
    from tools.application.quality_execution import QualityExecutor
    from tools.application.quality_service import QualityService
    from tools.core.context import ExecutionLocation, WorkspaceContext

    nodes, _, _, _, directory = fixture(tmp_path)
    # Replace the synthetic latest before this service-level scenario, keeping its archive.
    (directory / "state.json").unlink()
    subprocess.run(
        [
            "git",
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.test",
            "commit",
            "--allow-empty",
            "-qm",
            "fixture",
        ],
        cwd=tmp_path,
        check=True,
    )
    for value in nodes.values():
        value["command"] = f"{sys.executable} -c 'print(123)'"
    planner = FixturePlanner({"base": list(nodes.values()), "impact_areas": {}, "final": []})
    quality = QualityService(
        planner,
        QualityExecutor(WorkspaceContext(tmp_path), ExecutionLocation.CONTAINER, tuple),
        directory,
        "fixture-quality ",
    )
    assert quality.run(["roadmap"]) == 0
    before = {p.relative_to(directory): p.read_bytes() for p in directory.rglob("*") if p.is_file()}
    assert quality.run(["show", "--resume"]) == 0
    after = {p.relative_to(directory): p.read_bytes() for p in directory.rglob("*") if p.is_file()}
    assert before == after
    (tmp_path / "backend.py").write_text("backend fix\n")
    assert quality.run(["roadmap", "--resume"]) == 0
    state = json.loads((directory / "state.json").read_text())
    assert "reused_from" in state["steps"]["frontend"]
    assert "reused_from" not in state["steps"]["backend"]
    assert "reused_from" not in state["steps"]["base"]
    assert state["campaign_id"]
    assert "stale:" in capsys.readouterr().out
    native = directory / "attempts" / state["resumed_from"] / "state.json"
    assert json.loads(native.read_text())["steps"]["frontend"].get("reused_from") is None


def test_quality_campaign_fresh_failed_prerequisite_blocks_reused_dependents(
    tmp_path: Path,
) -> None:
    from tools.application.quality_execution import QualityExecutor
    from tools.core.context import ExecutionLocation, WorkspaceContext

    state = run_record(
        {
            "status": "running",
            "steps": {
                "base": dict(node("base", "false"), status="pending"),
                "reused": dict(
                    node("reused", "true", deps=["base"]),
                    status="passed",
                    exit_code=0,
                    reused_from="a" * 32,
                ),
            },
        }
    )
    result = QualityExecutor(
        WorkspaceContext(tmp_path), ExecutionLocation.CONTAINER, tuple
    ).execute(tmp_path, state)
    assert result == 1
    assert state["steps"]["reused"]["status"] == "blocked"
    assert not (tmp_path / "reused.log").exists()
