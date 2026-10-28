"""Private source identity and fixed owner container transport."""

import os
from collections.abc import Sequence
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]


def inside() -> bool:
    return execution_location() is ExecutionLocation.CONTAINER


def container(script: Path, arguments: Sequence[str]) -> None:
    if not inside():
        runtime = DevelopmentRuntime.for_workspace(ROOT)
        result = ApplicationProcesses(SubprocessRunner(), dict(os.environ)).run(
            runtime.app_command(("python3", str(script.relative_to(ROOT)), *arguments)),
            cwd=ROOT,
        )
        raise SystemExit(result.returncode)
