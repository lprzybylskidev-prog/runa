#!/usr/bin/env python3
"""Fixed compatibility transport for the production package entrypoint."""

import subprocess
import sys
from pathlib import Path


def main(arguments=None):
    arguments = list(sys.argv[1:] if arguments is None else arguments)
    if not __package__:
        return subprocess.call(
            [sys.executable, "-m", "tools.application.executors.production", *arguments],
            cwd=Path(__file__).resolve().parents[3],
        )
    from tools.application.production import main as execute
    from tools.core.lifecycle import interrupt_signals
    from tools.core.reporting import SafeFailure

    try:
        with interrupt_signals():
            execute(arguments)
        return 0
    except KeyboardInterrupt:
        return 130
    except (
        SafeFailure,
        ValueError,
        OSError,
        EOFError,
        KeyError,
        subprocess.CalledProcessError,
    ) as error:
        print(
            "Operation failed: "
            + (
                f"child command exited {error.returncode}."
                if isinstance(error, subprocess.CalledProcessError)
                else str(error)
            ),
            file=sys.stderr,
        )
        return getattr(error, "returncode", 1)


if __name__ == "__main__":
    raise SystemExit(main())
