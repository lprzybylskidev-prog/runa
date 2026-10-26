"""Explicit repository transport for the public PHP hook Actions."""

import os
import subprocess
import sys
from pathlib import Path

from tools.application.runtime import ROOT, compose, inside


def main(arguments):
    repository = Path(arguments[0])
    hook, *args = arguments[1:]
    if hook not in ("pre-commit", "commit-msg"):
        raise ValueError("Unknown hook alias.")
    if inside():
        return subprocess.call(
            ["php", "artisan", "runa:hook:" + hook, *args, "--repository=" + str(repository)],
            cwd=ROOT,
        )
    extra = []
    if os.environ.get("GIT_INDEX_FILE"):
        relative = Path(os.environ["GIT_INDEX_FILE"]).resolve().relative_to(ROOT)
        extra = ["-e", "GIT_INDEX_FILE=/workspace/" + relative.as_posix()]
    if args and Path(args[0]).is_absolute():
        args[0] = "/workspace/" + Path(args[0]).relative_to(ROOT).as_posix()
    target = "/workspace/" + repository.relative_to(ROOT).as_posix()
    return subprocess.call(
        compose()
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
    )


if __name__ == "__main__":
    try:
        raise SystemExit(main(sys.argv[1:]))
    except (ValueError, OSError, subprocess.SubprocessError):
        print("Hook transport rejected the repository or arguments.", file=sys.stderr)
        raise SystemExit(1)
