"""Shared production composition keeps exact selection, phases and native failures."""

from __future__ import annotations

import copy
import json
from pathlib import Path

import pytest

from tests.tools.runa.acceptance.acceptance import Report
from tests.tools.runa.acceptance.installation_evidence import (
    SHARED_PHASES,
    FixtureEvidence,
    quality_binding,
    validate_shared,
)
from tests.tools.runa.support.quality import node
from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_models import quality_definition
from tools.application.quality_planning import graph_hash, resolve, select
from tools.core.state import JsonValue, json_value

ROOT = Path(__file__).resolve().parents[4]


def test_independent_and_combined_production_commands_keep_both_selected_results() -> None:
    reader = ConfigurationReader.for_workspace(ROOT)
    definition = quality_definition(reader.yaml(ROOT / "docs/runa/maintainer/quality/gates.yml"))
    production, _ = resolve(definition, [], groups=["production"])
    scans, _ = resolve(definition, [], groups=["containers"])
    security, _ = resolve(definition, [], groups=["security"])
    assert "repository-container-scan" not in production
    assert "--security-scan" not in production["production-acceptance"]["command"]
    for nodes in (scans, security):
        assert "production-acceptance" not in nodes
        assert "--production-only --security-scan" in nodes["repository-container-scan"]["command"]
        assert "--customer-deployment" not in nodes["repository-container-scan"]["command"]
    for groups in (["production", "containers"], ["production", "security"], None):
        nodes, scope = resolve(definition, [], groups=groups, all_groups=groups is None)
        assert (
            "--security-scan --shared-quality-producer" in nodes["production-acceptance"]["command"]
        )
        assert nodes["repository-container-scan"]["command"].endswith("--verify-shared-quality")
        assert "production-acceptance" in nodes["repository-container-scan"]["depends_on"]
        assert scope["mode"] != "legacy"
        assert scope["graph_hash"] == graph_hash(nodes)
    legacy = select(definition, ["production", "security"])
    assert legacy["repository-container-scan"]["command"].endswith("--verify-shared-quality")


def test_conditional_commands_reject_ambiguity_and_invalid_argv_before_execution() -> None:
    definition = quality_definition(
        dict(
            base=[
                dict(node("a"), command_if_selected={"b": "one", "c": "two"}),
                node("b"),
                node("c"),
            ],
            impact_areas={},
            final=[],
        )
    )
    with pytest.raises(ValueError, match="Ambiguous"):
        select(definition, [])
    for commands in (["bad"], {"../bad": "one"}, {"b": "'"}, {"b": False}):
        with pytest.raises(ValueError):
            quality_definition(
                dict(
                    base=[dict(node("a"), command_if_selected=commands)], impact_areas={}, final=[]
                )
            )


def shared_report() -> dict[str, JsonValue]:
    return {
        "result": "PASS",
        "quality_binding": {"run_id": "current"},
        "prod_sha": "a" * 40,
        "images": {"app": "sha256:" + "b" * 64},
        "cleanup": [{"project": "isolated"}],
        "phases": {
            name: {
                "result": "PASS",
                "started_at": f"2026-10-29T10:00:0{i}+00:00",
                "finished_at": f"2026-10-29T10:00:0{i}+00:00",
                "duration_seconds": 0.0,
            }
            for i, name in enumerate(SHARED_PHASES)
        },
    }


def test_shared_evidence_refuses_missing_failed_interrupted_and_different_run_phases() -> None:
    good = shared_report()
    binding: dict[str, JsonValue] = {"run_id": "current"}
    validate_shared(good, binding)
    for key, replacement in (
        ("result", "FAIL"),
        ("result", "INTERRUPTED"),
        ("quality_binding", {"run_id": "old"}),
        ("images", {}),
        ("prod_sha", "main"),
        ("cleanup", []),
    ):
        bad = copy.deepcopy(good)
        bad[key] = replacement
        with pytest.raises(ValueError):
            validate_shared(bad, binding)
    for name in SHARED_PHASES:
        for result in ("RUNNING", "FAIL", "INTERRUPTED", "missing"):
            bad = copy.deepcopy(good)
            phases = bad["phases"]
            assert isinstance(phases, dict)
            if result == "missing":
                phases.pop(name)
            else:
                phase = phases[name]
                assert isinstance(phase, dict)
                phase["result"] = result
            with pytest.raises(ValueError):
                validate_shared(bad, binding)
    bad = copy.deepcopy(good)
    phases = bad["phases"]
    assert isinstance(phases, dict)
    phases["cleanup"], phases["preparation"] = phases["preparation"], phases["cleanup"]
    with pytest.raises(ValueError, match="Unordered"):
        validate_shared(bad, binding)


@pytest.mark.parametrize("failure", [RuntimeError, KeyboardInterrupt])
def test_phase_failure_persists_recovery_inventory_and_never_passes(
    tmp_path: Path, failure: type[BaseException]
) -> None:
    report: Report = {"result": "RUNNING", "ownership": {"projects": ["owned-project"]}}
    path = tmp_path / ".runtime/ownership/report.json"
    evidence = FixtureEvidence(tmp_path, path, report)
    with pytest.raises(failure):
        with evidence.phase("preparation"):
            raise failure()
    with evidence.phase("cleanup"):
        pass
    actual = json.loads(path.read_text())
    assert actual["result"] == ("INTERRUPTED" if failure is KeyboardInterrupt else "FAIL")
    assert actual["ownership"] == {"projects": ["owned-project"]}
    assert actual["phases"]["preparation"]["result"] == actual["result"]
    assert actual["phases"]["cleanup"]["result"] == "PASS"
    assert path.stat().st_mode & 0o077 == 0
    with pytest.raises(ValueError, match="replayed"):
        with evidence.phase("preparation"):
            pass


def test_shared_result_cannot_be_consumed_outside_owner_coordinator(tmp_path: Path) -> None:
    with pytest.raises(ValueError, match="owner quality directory"):
        quality_binding(tmp_path, {}, "repository-container-scan")


def test_cleanup_failure_overrides_provisional_success(tmp_path: Path) -> None:
    report: Report = {"result": "PASS"}
    path = tmp_path / ".runtime/ownership/report.json"
    evidence = FixtureEvidence(tmp_path, path, report)
    with pytest.raises(RuntimeError):
        with evidence.phase("cleanup"):
            raise RuntimeError("cleanup failed")
    assert json.loads(path.read_text())["result"] == "FAIL"
    with pytest.raises(ValueError):
        validate_shared(json_value(report), {})
