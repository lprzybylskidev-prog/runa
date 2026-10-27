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
)
DEVELOPMENT_MODULES = ("src/app/modules/module_probe", "src/app/modules/reference")


def production_patterns() -> list[str]:
    return [
        "/*",
        *["!/" + name + "/" for name in DEVELOPMENT_MODULES],
        *[
            "!/" + name + ("/" if name in ("distribution", "tools/maintainer") else "")
            for name in OWNER_PATHS
        ],
    ]
