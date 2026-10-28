"""Typed quality source decoding preserves graph identity and legacy status reads."""

import json
from collections import deque
from pathlib import Path

import pytest

from tests.tools.runa.support.quality import definition
from tools.application.configuration_reader import ConfigurationReader
from tools.application.development_runtime import DevelopmentRuntime
from tools.application.quality_models import quality_definition, quality_roadmap, quality_scope
from tools.application.quality_planning import graph_hash, resolve
from tools.application.quality_service import QualityPlanner
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.process import ProcessResult
from tools.core.state import JsonValue, json_value


def test_definition_decoder_preserves_commands_order_hash_and_conditional_dependencies() -> None:
    original = definition()
    source = json_value(original)
    assert isinstance(source, dict)
    decoded = quality_definition(source)
    before, before_scope = resolve(original, [], groups=["docs"])
    after, after_scope = resolve(decoded, [], groups=["docs"])
    assert list(before) == list(after)
    assert graph_hash(before) == graph_hash(after)
    assert before_scope == after_scope
    assert source == original

    steps = source["base"]
    assert isinstance(steps, list) and isinstance(steps[0], dict)
    steps[0]["depends_on_if_selected"] = ["runtime"]
    steps[0]["customer_annotation"] = {"retained": True}
    decoded = quality_definition(source)
    serialized = json_value(decoded)
    assert isinstance(serialized, dict)
    assert serialized["base"] == steps
    with pytest.raises(ValueError, match="cycle"):
        resolve(decoded, [], groups=["backend"])
    isolated, _ = resolve(decoded, [], groups=["docs"])
    assert isolated["base"]["depends_on"] == []


def test_invalid_source_shapes_refuse_before_graph_selection() -> None:
    changes: tuple[tuple[str, JsonValue], ...] = (
        ("base", "command"),
        ("impact_areas", []),
        ("groups", {"docs": "base"}),
        ("mandatory", [1]),
        ("group_steps", [{"id": "incomplete"}]),
    )
    for field, value in changes:
        source = json_value(definition())
        assert isinstance(source, dict)
        source[field] = value
        with pytest.raises(ValueError):
            quality_definition(source)


def test_named_and_legacy_scope_decode_without_claiming_excluded_groups_passed() -> None:
    _, scope = resolve(definition(), [], groups=["docs"])
    assert quality_scope(scope) == scope
    assert scope["mode"] == "groups"
    assert scope["excluded_groups"] == ["backend"]
    assert quality_scope({}) == {"mode": "legacy"}
    legacy: dict[str, JsonValue] = {"mode": "legacy", "impact_areas": ["tooling"], "final": True}
    assert quality_scope(legacy) == legacy
    invalid_scopes: tuple[dict[str, JsonValue], ...] = (
        {"mode": "all"},
        {"mode": "groups", "graph_hash": "fixed", "reasons": []},
    )
    for value in invalid_scopes:
        with pytest.raises(ValueError, match="selection"):
            quality_scope(value)


def test_roadmap_decoder_keeps_source_metadata_and_refuses_non_string_areas() -> None:
    source: dict[str, JsonValue] = {
        "id": "fixture",
        "impact_areas": ["tooling"],
        "owner_evidence": {"retained": True},
    }
    assert quality_roadmap(source) == source
    invalids: tuple[dict[str, JsonValue], ...] = (
        dict(source, id=[]),
        dict(source, impact_areas=[1]),
        dict(source, quality_selection=False),
    )
    for value in invalids:
        with pytest.raises(ValueError):
            quality_roadmap(value)


def test_planner_decodes_at_injected_boundary_without_starting_quality_work(tmp_path: Path) -> None:
    roadmaps = tmp_path / "active"
    roadmaps.mkdir()
    (roadmaps / "fixture.md").write_text("---\nid: fixture\nimpact_areas: []\n---\n")
    configuration = tmp_path / "quality.yml"
    configuration.write_text("version: 1\n")
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, b'{"id":"fixture","impact_areas":[]}'),
                ProcessResult(0, json.dumps(definition()).encode()),
            ]
        )
    )
    workspace = WorkspaceContext(tmp_path)
    reader = ConfigurationReader(
        workspace,
        DevelopmentRuntime(ExecutionContext(workspace, workspace, ExecutionLocation.CONTAINER)),
        runner,
        {},
    )
    roadmap, nodes = QualityPlanner(reader, roadmaps, configuration).plan(groups=["docs"])
    selection = roadmap.get("quality_selection")
    assert selection is not None and selection["mode"] == "groups"
    assert selection["requested_groups"] == ["docs"]
    assert set(nodes) == {"base", "closure"}
    assert len(runner.calls) == 2
    assert not (tmp_path / ".runtime").exists()
