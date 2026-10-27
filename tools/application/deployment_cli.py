#!/usr/bin/env python3
"""Import-safe deployment and rollback process boundary."""

import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path


def main(arguments: Sequence[str] | None = None) -> int:
    arguments = list(sys.argv[1:] if arguments is None else arguments)
    from tools.application.deployment_service import DeploymentService

    deployment = DeploymentService.for_workspace(Path(__file__).resolve().parents[2])
    from tools.core.lifecycle import interrupt_signals
    from tools.core.reporting import SafeFailure

    try:
        with interrupt_signals():
            if not arguments:
                raise ValueError("An operation name is required.")
            deployment.main(arguments[0], arguments[1:])
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
        return (
            error.returncode
            if isinstance(error, subprocess.CalledProcessError)
            else (error.exit_code if isinstance(error, SafeFailure) else 1)
        )


if __name__ == "__main__":
    raise SystemExit(main())
