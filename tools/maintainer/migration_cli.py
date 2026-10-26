#!/usr/bin/env python3
"""Create a Runa migration; excluded from customer delivery."""

import subprocess
import sys
from pathlib import Path
from tools.maintainer.context import ROOT, container


def main():
    container(ROOT / "tools/maintainer/make-migration", sys.argv[1:])
    args = sys.argv[1:]
    if args == ["--help"]:
        print(
            "Create a Runa migration under src/runa/database/migrations."
        )
        raise SystemExit(0)
    if (
        not args
        or args[0].startswith("-")
        or any(a.startswith(("--path", "--realpath")) for a in args)
    ):
        raise SystemExit("Provide a migration name.")
    command = ["make:migration", args[0], "--path=runa/database/migrations", *args[1:]]
    raise SystemExit(subprocess.run(["php", "artisan", *command], cwd=ROOT / "src").returncode)


if __name__ == "__main__":
    main()
