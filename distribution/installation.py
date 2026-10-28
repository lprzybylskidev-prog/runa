#!/usr/bin/env python3
"""External installation adapter to the selected application's versioned gateway."""

import os
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path


def main(arguments: Sequence[str] | None = None, *, workspace: Path | str | None = None) -> int:
    arguments = list(sys.argv[1:] if arguments is None else arguments)
    if not __package__:
        if not any(arg.startswith("--workspace=") for arg in arguments):
            arguments.append("--workspace=" + str(Path(workspace or Path.cwd()).resolve()))
        return subprocess.call(
            [sys.executable, "-m", "distribution.installation", *arguments],
            cwd=Path(__file__).resolve().parents[1],
        )
    from distribution.application_gateway import GatewayError, installation_command
    from tools.core.process import ProcessSpec, SubprocessRunner

    selected = [arg for arg in arguments if arg.startswith("--workspace=")]
    if selected:
        if len(selected) != 1:
            raise GatewayError("Select one installation workspace.")
        workspace = selected[0].partition("=")[2]
        arguments.remove(selected[0])
    workspace = Path(workspace or Path.cwd()).absolute()
    try:
        command = installation_command(workspace, arguments)
        return (
            SubprocessRunner()
            .run(ProcessSpec(tuple(command), workspace, dict(os.environ)))
            .returncode
        )
    except (GatewayError, ValueError, OSError):
        print(
            "Installation gateway is unavailable; use the reviewed tool bundle matching this application release.",
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
