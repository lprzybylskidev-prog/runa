#!/usr/bin/env python3
"""Quick global and focused deterministic project diagnostics."""

import os
import subprocess
import sys
from collections.abc import Sequence
from functools import partial
from pathlib import Path

from tools.application.development_runtime import (
    DevelopmentRuntime,
    execution_location,
)
from tools.application.diagnostics import Diagnostics
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]

AREAS = (
    "repository",
    "templates",
    "config",
    "maintenance",
    "hooks",
    "frontend",
    "database-migrations",
    "database",
    "seeding",
    "boost",
    "tooling",
    "docs",
    "development",
)


def main(argv: Sequence[str] | None = None) -> int:
    checks = Diagnostics.for_workspace(ROOT)
    runtime = DevelopmentRuntime.for_workspace(ROOT)
    processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    run = partial(processes.run, cwd=ROOT, check=True)
    try:
        args = list(sys.argv[1:] if argv is None else argv)
        if args == ["--help"]:
            print(
                "Private diagnostic engine. Public focused commands: runa:doctor:"
                + ", ".join(AREAS)
                + ".\nChecks never repair or deploy. Database-migrations resets only the guarded testing database."
            )
        elif len(args) != 1 or args[0] not in AREAS:
            raise ValueError("A fixed diagnostic area is required.")
        elif execution_location() is not ExecutionLocation.CONTAINER:
            if args == ["development"]:
                ManagementService.for_workspace(ROOT).host_stack_health()
            raise SystemExit(
                processes.run(
                    runtime.app_command(["php", "artisan", "runa:doctor:" + args[0]]), cwd=ROOT
                ).returncode
            )
        else:
            area = args[0]
            if hasattr(checks, area):
                getattr(checks, area)()
            else:
                commands = {
                    "config": ["php", "artisan", "runa:config:validate"],
                    "templates": ["php", "artisan", "runa:config:templates"],
                    "hooks": ["python3", "-m", "tools.application.testing.hooks"],
                    "frontend": ["php", "artisan", "runa:typecheck"],
                    "database-migrations": [
                        "php",
                        "artisan",
                        "runa:test-db:guard",
                        "--env=testing",
                    ],
                    "database": [
                        "php",
                        "artisan",
                        "runa:test",
                        "--scope=backend",
                        "--filter=Database",
                    ],
                    "seeding": [
                        "php",
                        "artisan",
                        "runa:test",
                        "--scope=backend",
                        "--filter=Seeding",
                    ],
                }
                if area == "database-migrations":
                    run(commands[area], env=dict(os.environ, APP_ENV="testing"))
                else:
                    run(commands[area])
        return 0
    except (ValueError, OSError, KeyError, TypeError, subprocess.CalledProcessError) as error:
        print(f"Doctor failed: {error}", file=sys.stderr)
        sys.exit(getattr(error, "returncode", 1))


if __name__ == "__main__":
    main()
