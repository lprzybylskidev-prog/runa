"""Explicit repository transport for the public PHP hook Actions."""

import os
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.development_runtime import (
    development_compose,
    execution_location,
)
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]


def main(arguments: Sequence[str]) -> int:
    processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    repository = Path(arguments[0])
    hook, *args = arguments[1:]
    if hook not in ("pre-commit", "commit-msg"):
        raise ValueError("Unknown hook alias.")
    if execution_location() is ExecutionLocation.CONTAINER:
        return processes.run(
            ["php", "artisan", "runa:hook:" + hook, *args, "--repository=" + str(repository)],
            cwd=ROOT,
        ).returncode
    extra = []
    if os.environ.get("GIT_INDEX_FILE"):
        relative = Path(os.environ["GIT_INDEX_FILE"]).resolve().relative_to(ROOT)
        extra = ["-e", "GIT_INDEX_FILE=/workspace/" + relative.as_posix()]
    if args and Path(args[0]).is_absolute():
        args[0] = "/workspace/" + Path(args[0]).relative_to(ROOT).as_posix()
    target = "/workspace/" + repository.relative_to(ROOT).as_posix()
    return processes.run(
        list(development_compose(WorkspaceContext(ROOT)).command())
        + [
            "exec",
            "-T",
            *extra,
            "app",
            "python3",
            "-m",
            "tools.application.hook_cli",
            target,
            hook,
            *args,
        ],
        cwd=ROOT,
    ).returncode


if __name__ == "__main__":
    try:
        raise SystemExit(main(sys.argv[1:]))
    except (ValueError, OSError, subprocess.SubprocessError):
        print("Hook transport rejected the repository or arguments.", file=sys.stderr)
        raise SystemExit(1) from None
