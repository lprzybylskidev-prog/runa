"""Validated immutable quality node boundary; serialized evidence stays compatible."""

import re
import shlex
from collections.abc import Mapping
from dataclasses import dataclass
from typing import Literal, NotRequired, TypeAlias, TypedDict, cast

from tools.core.state import JsonValue, json_value


class QualityNode(TypedDict):
    id: str
    command: str
    depends_on: list[str]
    severity: Literal["hard_fail", "warning"]
    execution_context: NotRequired[Literal["application", "host"]]
    exclusive_group: NotRequired[str | None]
    depends_on_if_selected: NotRequired[list[str]]


class QualityDefinition(TypedDict):
    base: list[QualityNode]
    impact_areas: dict[str, list[QualityNode]]
    final: list[QualityNode]
    groups: NotRequired[dict[str, list[str]]]
    mandatory: NotRequired[list[str]]
    group_steps: NotRequired[list[QualityNode]]


class LegacyScope(TypedDict):
    mode: Literal["legacy"]
    impact_areas: NotRequired[list[str]]
    final: NotRequired[bool]


class SelectedScope(TypedDict):
    mode: Literal["all", "groups"]
    requested_groups: list[str]
    excluded_groups: list[str]
    mandatory: list[str]
    reasons: dict[str, list[str]]
    graph_hash: str


QualityScope: TypeAlias = LegacyScope | SelectedScope
QualityGraph: TypeAlias = dict[str, QualityNode]


class QualityRoadmap(TypedDict):
    id: str
    impact_areas: list[str]
    quality_selection: NotRequired[QualityScope]
    quality_groups: NotRequired[JsonValue]
    quality_rationale: NotRequired[JsonValue]


@dataclass(frozen=True)
class QualityStep:
    identifier: str
    command: tuple[str, ...]
    dependencies: tuple[str, ...]
    severity: str
    execution_context: str
    exclusive_group: str | None

    @classmethod
    def parse(cls, value: Mapping[str, object]) -> "QualityStep":
        identifier = value.get("id")
        command = value.get("command")
        dependencies = string_list(value.get("depends_on"))
        severity = value.get("severity")
        location = value.get("execution_context", "application")
        group = value.get("exclusive_group")
        if not isinstance(identifier, str) or not re.fullmatch(r"[a-z0-9-]+", identifier):
            raise ValueError("Invalid quality step identity.")
        if not isinstance(command, str) or not command or "\0" in command:
            raise ValueError("Invalid quality command.")
        argv = tuple(shlex.split(command))
        if not argv or not argv[0]:
            raise ValueError("Empty quality command.")
        if not all(re.fullmatch(r"[a-z0-9-]+", item) for item in dependencies):
            raise ValueError("Invalid quality dependencies.")
        if (
            not isinstance(severity, str)
            or not isinstance(location, str)
            or severity not in ("hard_fail", "warning")
            or location not in ("application", "host")
        ):
            raise ValueError("Invalid quality severity or execution context.")
        if group is not None and (
            not isinstance(group, str) or not re.fullmatch(r"[a-z0-9-]+", group)
        ):
            raise ValueError("Invalid quality exclusive group.")
        return cls(identifier, argv, tuple(dependencies), severity, location, group)


def string_list(value: object) -> list[str]:
    if not isinstance(value, list):
        raise ValueError("Quality configuration requires a list of strings.")
    items = cast(list[object], value)
    if not all(isinstance(item, str) for item in items):
        raise ValueError("Quality configuration requires a list of strings.")
    return cast(list[str], items)


def quality_nodes(value: JsonValue) -> list[QualityNode]:
    if not isinstance(value, list):
        raise ValueError("Quality definition requires a list of steps.")
    nodes: list[QualityNode] = []
    for item in value:
        if not isinstance(item, dict):
            raise ValueError("Quality step must be a mapping.")
        QualityStep.parse(item)
        if "depends_on_if_selected" in item:
            dependencies = string_list(item["depends_on_if_selected"])
            if any(not re.fullmatch(r"[a-z0-9-]+", dependency) for dependency in dependencies):
                raise ValueError("Invalid conditional quality dependencies.")
        nodes.append(cast(QualityNode, item))
    return nodes


def quality_definition(value: Mapping[str, JsonValue]) -> QualityDefinition:
    """Validate source-owned structure without resolving or executing its graph."""
    areas = value.get("impact_areas")
    if not isinstance(areas, dict):
        raise ValueError("Quality impact areas must be a mapping.")
    definition: QualityDefinition = {
        "base": quality_nodes(value.get("base")),
        "impact_areas": {area: quality_nodes(nodes) for area, nodes in areas.items()},
        "final": quality_nodes(value.get("final")),
    }
    if "group_steps" in value:
        definition["group_steps"] = quality_nodes(value["group_steps"])
    if "mandatory" in value:
        definition["mandatory"] = string_list(value["mandatory"])
    if "groups" in value:
        groups = value["groups"]
        if not isinstance(groups, dict):
            raise ValueError("Quality groups must be a mapping.")
        definition["groups"] = {group: string_list(steps) for group, steps in groups.items()}
    return definition


def quality_roadmap(value: Mapping[str, JsonValue]) -> QualityRoadmap:
    identifier = value.get("id")
    areas = string_list(value.get("impact_areas"))
    if not isinstance(identifier, str):
        raise ValueError("Quality roadmap requires a string identity.")
    # Keep all source metadata, including historical fields not consumed by planning.
    decoded = json_value(dict(value, id=identifier, impact_areas=areas))
    assert isinstance(decoded, dict)
    if "quality_selection" in decoded:
        selection = decoded["quality_selection"]
        if not isinstance(selection, dict):
            raise ValueError("Quality roadmap selection must be a mapping.")
        decoded["quality_selection"] = json_value(quality_scope(selection))
    return cast(QualityRoadmap, decoded)


def quality_scope(value: Mapping[str, object]) -> QualityScope:
    """Read complete named selections while retaining legacy status compatibility."""
    decoded = json_value(dict(value))
    assert isinstance(decoded, dict)
    if decoded.get("mode", "legacy") == "legacy":
        decoded["mode"] = "legacy"
        if "impact_areas" in decoded:
            string_list(decoded["impact_areas"])
        if "final" in decoded and type(decoded["final"]) is not bool:
            raise ValueError("Invalid legacy quality selection.")
        return cast(LegacyScope, decoded)
    reasons = decoded.get("reasons")
    if (
        decoded.get("mode") not in ("all", "groups")
        or not isinstance(decoded.get("graph_hash"), str)
        or not isinstance(reasons, dict)
    ):
        raise ValueError("Invalid named quality selection.")
    for key in ("requested_groups", "excluded_groups", "mandatory"):
        string_list(decoded.get(key))
    for items in reasons.values():
        string_list(items)
    return cast(SelectedScope, decoded)
