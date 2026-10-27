"""Explicit customer composition root; import never parses or starts a run."""

import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.configuration_reader import ConfigurationReader
from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.quality_execution import QualityExecutor
from tools.application.quality_service import QualityPlanner, QualityService
from tools.core.context import ExecutionContext, WorkspaceContext
from tools.core.reporting import SafeFailure


def service(
    root: Path,
    *,
    roadmaps: Path | None = None,
    definition: Path | None = None,
    directory: Path | None = None,
    command_prefix: str = "php artisan runa:quality:",
) -> QualityService:
    workspace = WorkspaceContext(root)
    location = execution_location()
    runtime = DevelopmentRuntime(ExecutionContext(workspace, workspace, location))
    planner = QualityPlanner(
        ConfigurationReader.for_workspace(root),
        roadmaps or root / "docs/app/roadmaps/active",
        definition or root / "tools/application/quality.yml",
        roadmaps is None,
    )
    return QualityService(
        planner,
        QualityExecutor(workspace, location, lambda args: runtime.app_command(tuple(args))),
        directory or root / ".runtime/application-quality",
        command_prefix,
    )


def main(argv: Sequence[str] | None = None, *, quality: QualityService | None = None) -> int:
    try:
        return (quality or service(Path(__file__).resolve().parents[2])).run(
            sys.argv[1:] if argv is None else argv
        )
    except (ValueError, OSError, KeyError, SafeFailure, subprocess.CalledProcessError) as error:
        print(
            "Quality failed: "
            + (
                str(error)
                if isinstance(error, ValueError)
                else "Required process or state is unavailable."
            ),
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
