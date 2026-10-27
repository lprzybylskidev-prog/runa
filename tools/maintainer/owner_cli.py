#!/usr/bin/env python3
"""Fixed-purpose owner entrypoints, selected only by their versioned filename."""

import sys

from tools.maintainer.context import ROOT, container, inside


def main():
    name = sys.argv[1]
    arguments = sys.argv[2:]
    CHECKS = {
        "check-repository": "repository",
        "check-maintenance": "maintenance",
        "check-docs": "docs",
        "check-release": "release",
        "check-cleanliness": "cleanliness",
        "check-tooling": "tooling",
        "check-development": "development",
        "check-frontend": "frontend",
        "check-database-migrations": "database-migrations",
    }
    QUALITY = {
        "quality-plan": "show",
        "quality-roadmap": "roadmap",
        "quality-final": "final",
        "quality-status": "status",
    }
    TESTS = {"test-backend": "backend", "test-frontend": "frontend", "test-e2e": "e2e"}
    if name == "test" or name in TESTS:
        from tools.maintainer.test_cli import boundary
        from tools.maintainer.test_cli import main as test_main

        raise SystemExit(
            boundary(lambda: test_main([*([TESTS[name]] if name in TESTS else []), *arguments]))
        )
    if arguments == ["--help"]:
        print(
            "Usage: python3 tools/maintainer/"
            + name
            + (
                " [--group=NAME ... | --all]"
                + (" [--background]" if name != "quality-plan" else "")
                if name in QUALITY and name != "quality-status"
                else " [runner options]"
                if name in TESTS
                else ""
            )
        )
        raise SystemExit(0)
    if name in CHECKS:
        if arguments:
            raise SystemExit("This diagnostic takes no arguments.")
        if name == "check-frontend" and not inside():
            from tools.application.test_resources import endpoint

            with endpoint():
                container(ROOT / "tools/maintainer" / name, arguments)
        container(ROOT / "tools/maintainer" / name, arguments)
        from tools.maintainer import checks

        area = CHECKS[name]
        if area in ("frontend", "database-migrations"):
            import os

            from tools.application.processes import ApplicationProcesses
            from tools.core.process import SubprocessRunner

            processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))

            processes.run(
                ["node", "src/runa/tools/testing/frontend.mjs"]
                if area == "frontend"
                else [
                    "python3",
                    "tools/maintainer/test-python",
                    "--scope=contracts",
                    "--path=contracts/test_database_guards.py",
                ],
                cwd=ROOT,
                check=True,
            )
        else:
            getattr(checks, area)()
    elif name in QUALITY:
        from tools.application.quality_cli import main as quality_main
        from tools.application.quality_cli import service

        quality = service(
            ROOT,
            roadmaps=ROOT / "docs/runa/maintainer/roadmaps/active",
            definition=ROOT / "docs/runa/maintainer/quality/gates.yml",
            directory=ROOT / ".runtime/quality",
            command_prefix="python3 tools/maintainer/quality-",
        )
        raise SystemExit(quality_main([QUALITY[name], *arguments], quality=quality))
    else:
        raise SystemExit("Invoke a named owner entrypoint; this module has no public actions.")


if __name__ == "__main__":
    main()
