"""Compatibility facade; new integrations compose explicit quality services."""

from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_evidence import check_evidence, closure_link_update
from tools.application.quality_planning import (
    arguments,
    graph_hash,
    resolve,
    select,
    show_scope,
    validate_declared,
)
from tools.application.quality_reporting import save, status
from tools.application.runtime import ROOT, app_command, inside


def yaml(path):
    return ConfigurationReader.for_workspace(ROOT).yaml(path)


def roadmap_metadata(path):
    return ConfigurationReader.for_workspace(ROOT).roadmap(path)


def plan(final=False, roadmap_root=None, definition_path=None, groups=None, all_groups=False):
    from tools.application.quality_service import QualityPlanner

    return QualityPlanner(
        ConfigurationReader.for_workspace(ROOT),
        roadmap_root or ROOT / "docs/app/roadmaps/active",
        definition_path or ROOT / "tools/application/quality.yml",
        roadmap_root is None,
    ).plan(final, groups=groups, all_groups=all_groups)


def execute(directory, state):
    from tools.application.quality_execution import QualityExecutor
    from tools.core.context import ExecutionLocation, WorkspaceContext

    return QualityExecutor(
        WorkspaceContext(ROOT),
        ExecutionLocation.CONTAINER if inside() else ExecutionLocation.HOST,
        app_command,
    ).execute(directory, state)


__all__ = [
    "select",
    "arguments",
    "resolve",
    "graph_hash",
    "show_scope",
    "validate_declared",
    "check_evidence",
    "closure_link_update",
    "save",
    "status",
    "yaml",
    "roadmap_metadata",
    "plan",
    "execute",
]
