"""Canonical source-owned customer projection; bootstrap embeds this exact policy."""

from __future__ import annotations

OWNER_PATHS = (
    "distribution",
    "tools/maintainer",
    "bootstrap.py",
    "bin/project",
    "bin/install-prod",
    "src/runa/tests",
    "src/runa/tools",
    "docs/runa/maintainer",
    "src/runa/management/InstallCommand.php",
    "src/runa/management/ProjectCommand.php",
    "tests/backend/runa",
    "tests/frontend/runa",
    "tests/tools/runa",
)
LEGACY_OWNER_PATHS = OWNER_PATHS[:-3]
PROJECTION_VERSION = 2
APPLICATION_TEST_ROOTS = ("tests/backend/app", "tests/frontend/app", "tests/tools/app")
DEVELOPMENT_MODULES = (
    "src/app/modules/module_probe",
    "src/app/modules/reference",
    "tests/backend/app/modules/module_probe",
    "tests/backend/app/modules/reference",
)


def production_patterns() -> list[str]:
    return [
        "/*",
        *["!/" + name + "/" for name in DEVELOPMENT_MODULES],
        *[
            "!/" + name + ("/" if name in ("distribution", "tools/maintainer") else "")
            for name in OWNER_PATHS
        ],
    ]
