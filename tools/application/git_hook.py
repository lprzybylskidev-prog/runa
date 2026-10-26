#!/usr/bin/env python3
"""Versioned Git hook entrypoint."""

from pathlib import Path
import subprocess
import os
import sys

ROOT = Path(__file__).resolve().parents[2]
from tools.application.runtime import compose, inside
from tools.application.hooks import commit_message, pre_commit


def main(arguments):
    try:
        args = list(arguments)
        repository = Path(args.pop(0))
        if args == ["--help"]:
            print(
                "Usage: ./tools/application/bin/git-hook pre-commit|commit-msg <message-file>\nFast staged-content and Conventional/RM-aware checks."
            )
        elif not inside():
            extra = []
            if os.environ.get("GIT_INDEX_FILE"):
                index = Path(os.environ["GIT_INDEX_FILE"]).resolve()
                relative = index.relative_to(ROOT)
                extra = ["-e", "GIT_INDEX_FILE=/workspace/" + relative.as_posix()]
            if len(args) == 2 and Path(args[1]).is_absolute():
                args[1] = "/workspace/" + Path(args[1]).relative_to(ROOT).as_posix()
            raise SystemExit(
                subprocess.run(
                    compose()
                    + [
                        "exec",
                        "-T",
                        *extra,
                        "app",
                        "python3",
                        "-m",
                        "tools.application.git_hook",
                        "/workspace/" + repository.relative_to(ROOT).as_posix(),
                        *args,
                    ],
                    cwd=ROOT,
                ).returncode
            )
        elif args == ["pre-commit"]:
            pre_commit(repository, ROOT)
        elif len(args) == 2 and args[0] == "commit-msg":
            message = Path(args[1])
            commit_message(repository, message if message.is_absolute() else repository / message)
        else:
            raise ValueError("See ./tools/application/bin/git-hook --help.")
    except (ValueError, OSError, subprocess.CalledProcessError, SyntaxError) as error:
        print(f"Hook rejected: {error}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main(sys.argv[1:])
