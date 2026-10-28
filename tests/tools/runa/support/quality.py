"""Private source-owned quality fixtures; importing them never schedules a run."""

from collections.abc import Sequence
from dataclasses import dataclass
from pathlib import Path

from tools.application.quality_models import (
    QualityDefinition,
    QualityGraph,
    QualityNode,
    QualityRoadmap,
)
from tools.application.quality_planning import resolve
from tools.core.state import JsonValue, json_value


def node(
    key: str,
    command: str = "true",
    deps: Sequence[str] | None = None,
    group: str | None = None,
) -> QualityNode:
    return {
        "id": key,
        "command": command,
        "depends_on": list(deps or []),
        "severity": "hard_fail",
        "exclusive_group": group,
    }


def definition() -> QualityDefinition:
    return {
        "base": [node("base"), node("runtime", deps=["base"])],
        "impact_areas": {},
        "final": [node("closure")],
        "mandatory": ["base", "closure"],
        "groups": {"docs": ["base"], "backend": ["runtime"]},
    }


@dataclass(frozen=True)
class FixturePlanner:
    definition: QualityDefinition
    identifier: str = "fixture"
    impact_areas: tuple[str, ...] = ()

    def plan(
        self,
        final: bool = False,
        *,
        groups: Sequence[str] | None = None,
        all_groups: bool = False,
    ) -> tuple[QualityRoadmap, QualityGraph]:
        nodes, scope = resolve(self.definition, self.impact_areas, final, groups, all_groups)
        return {
            "id": self.identifier,
            "impact_areas": list(self.impact_areas),
            "quality_selection": scope,
        }, nodes


@dataclass(frozen=True)
class FixtureReader:
    definition: QualityDefinition

    def yaml(self, path: Path) -> dict[str, JsonValue]:
        value = json_value(self.definition)
        assert isinstance(value, dict)
        return value


def decoded_definition(value: object) -> QualityDefinition:
    from tools.application.quality_models import quality_definition

    data = json_value(value)
    assert isinstance(data, dict)
    return quality_definition(data)
