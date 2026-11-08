"""Real public documentation inputs for isolated owner metadata validators."""

from __future__ import annotations

import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]


def copy_public(root: Path) -> None:
    for name in ("README.md", "docs/README.md"):
        target = root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / name, target)
    for name in ("docs/app", "docs/runa/handbook", ".agents"):
        shutil.copytree(ROOT / name, root / name, ignore=shutil.ignore_patterns("__pycache__"))

    copy_reference_sources(root)
    product = ROOT / "docs/runa/maintainer/customer-agent"
    shutil.copy2(product / "AGENTS.md", root / "AGENTS.md")
    shutil.copy2(product / "CLAUDE.md", root / "CLAUDE.md")
    shutil.rmtree(root / ".agents/skills")
    shutil.copytree(product / "skills", root / ".agents/skills")


def copy_reference_sources(root: Path) -> None:
    """Include the delivered source/test targets linked by reference documentation."""
    for name in (
        "src/app/modules/reference",
        "tests/backend/app/modules/reference",
        "tests/frontend/app/modules/reference",
    ):
        shutil.copytree(ROOT / name, root / name)
