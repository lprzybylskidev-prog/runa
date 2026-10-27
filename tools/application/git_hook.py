#!/usr/bin/env python3
"""Versioned Git hook entrypoint."""

import os
import subprocess
import sys
from pathlib import Path

from tools.application.development_runtime import development_compose, execution_location
from tools.application.hooks import commit_message, pre_commit
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.lifecycle import interrupt_signals
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]


def main(arguments, *, history=None, module="tools.application.git_hook"):
    processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    try:
        args = list(arguments)
        repository = Path(args.pop(0))
        if args == ["--help"]:
            print(
                "Usage: ./tools/application/bin/git-hook pre-commit|commit-msg <message-file>\nFast staged-content and Conventional/RM-aware checks."
            )
        elif execution_location() is not ExecutionLocation.CONTAINER:
            extra = []
            if os.environ.get("GIT_INDEX_FILE"):
                index = Path(os.environ["GIT_INDEX_FILE"]).resolve()
                relative = index.relative_to(ROOT)
                extra = ["-e", "GIT_INDEX_FILE=/workspace/" + relative.as_posix()]
            if len(args) == 2 and Path(args[1]).is_absolute():
                args[1] = "/workspace/" + Path(args[1]).relative_to(ROOT).as_posix()
            raise SystemExit(
                processes.run(
                    list(development_compose(WorkspaceContext(ROOT)).command())
                    + [
                        "exec",
                        "-T",
                        *extra,
                        "app",
                        "python3",
                        "-m",
                        module,
                        "/workspace/" + repository.relative_to(ROOT).as_posix(),
                        *args,
                    ],
                    cwd=ROOT,
                ).returncode
            )
        elif args == ["pre-commit"]:
            with interrupt_signals():
                pre_commit(repository, ROOT, history=history)
        elif len(args) == 2 and args[0] == "commit-msg":
            message = Path(args[1])
            commit_message(repository, message if message.is_absolute() else repository / message)
        else:
            raise ValueError("See ./tools/application/bin/git-hook --help.")
    except KeyboardInterrupt:
        print("Hook interrupted.", file=sys.stderr)
        raise SystemExit(130) from None
    except (ValueError, OSError, subprocess.CalledProcessError, SyntaxError) as error:
        print(f"Hook rejected: {error}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main(sys.argv[1:])
