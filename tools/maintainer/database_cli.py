#!/usr/bin/env python3
"""Private owner database transports, with fixed per-file purpose."""

import subprocess
import sys

from tools.maintainer.context import ROOT, container


def main():
    name = sys.argv.pop(1)
    commands = {
        "migrate": "migrate",
        "migrate-status": "migrate:status",
        "migrate-fresh": "migrate:fresh",
        "migrate-refresh": "migrate:refresh",
        "migrate-reset": "migrate:reset",
        "migrate-rollback": "migrate:rollback",
        "wipe": "db:wipe",
        "seed": "db:seed",
    }
    if name not in commands:
        raise SystemExit("Invoke a named owner database entrypoint.")
    args = sys.argv[1:]
    if args == ["--help"]:
        print(
            "Usage: python3 tools/maintainer/"
            + name
            + (
                " Runa\\Database\\Seeders\\ClassName"
                if name == "seed"
                else " [native operation options]"
            )
            + "\nPlatform scope only; resets retain the isolated test-database guard."
        )
        raise SystemExit(0)
    from tools.application.repository_boundary import assert_development_context

    assert_development_context(ROOT)
    if any(
        a.startswith(("--path", "--database", "--realpath", "--env", "--schema-path")) for a in args
    ):
        raise SystemExit("Platform scope cannot be overridden.")
    if name == "seed":
        if len(args) != 1 or not args[0].startswith("Runa\\Database\\Seeders\\"):
            raise SystemExit("Provide one explicit Runa\\Database\\Seeders\\ClassName.")
        args = ["--class=" + args[0]]
    container(ROOT / "tools/maintainer" / name, sys.argv[1:])
    raise SystemExit(
        subprocess.run(
            ["php", str(ROOT / "distribution/platform-database.php"), commands[name], *args],
            cwd=ROOT / "src",
        ).returncode
    )


if __name__ == "__main__":
    main()
