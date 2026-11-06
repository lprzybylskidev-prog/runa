"""Customer documentation completeness, placement and local-link diagnostics."""

from __future__ import annotations

import re
from collections.abc import Mapping, Sequence
from pathlib import Path
from urllib.parse import unquote

from tools.application.documentation_markdown import anchors, headings, link_targets

REQUIRED = (
    ".agents/README.md",
    ".agents/skills/README.md",
    "AGENTS.md",
    "README.md",
    "docs/README.md",
    "docs/app/README.md",
    "docs/runa/handbook/README.md",
    "docs/runa/handbook/workflows/roadmaps.md",
    "docs/runa/handbook/workflows/git.md",
    "docs/runa/handbook/workflows/releases.md",
    "docs/runa/handbook/workflows/roadmap-template.md",
    "docs/runa/handbook/quality/README.md",
    "docs/runa/handbook/testing/strategy.md",
    "docs/runa/handbook/development/documentation.md",
    "docs/runa/handbook/security/baseline.md",
    "docs/runa/handbook/ai/knowledge-system.md",
    "docs/runa/handbook/ai/laravel-boost.md",
) + tuple(
    ".agents/skills/" + name + "/SKILL.md"
    for name in (
        "backend",
        "frontend",
        "laravel",
        "vue",
        "tailwind",
        "postgresql",
        "docker",
        "security",
        "rag",
    )
)


def links(
    root: Path,
    paths: Sequence[Path],
    public: bool = True,
    logical_paths: Mapping[Path, Path] | None = None,
) -> None:
    root = Path(root).resolve()
    anchor_cache: dict[Path, set[str]] = {}
    projected = {logical.resolve(): actual for actual, logical in (logical_paths or {}).items()}
    for path in paths:
        for target in link_targets(path.read_text()):
            if re.match(r"^[a-zA-Z][a-zA-Z0-9+.-]*:", target):
                continue
            filename, _, fragment = target.partition("#")
            logical = (logical_paths or {}).get(path, path)
            destination = (
                (logical.parent / unquote(filename)).resolve() if filename else logical.resolve()
            )
            if not destination.is_relative_to(root):
                raise ValueError(
                    "Documentation link escapes checkout: " + str(path.relative_to(root))
                )
            relative = destination.relative_to(root).as_posix()
            if public and relative.startswith(
                ("docs/runa/maintainer/", "tools/maintainer/", "distribution/")
            ):
                raise ValueError("Customer documentation links to owner-only material: " + relative)
            source = projected.get(destination, destination)
            if not source.exists():
                raise ValueError(
                    "Broken documentation link: " + str(path.relative_to(root)) + " -> " + target
                )
            if fragment and destination.suffix.lower() == ".md":
                if destination not in anchor_cache:
                    anchor_cache[destination] = anchors(source.read_text())
                if unquote(fragment) not in anchor_cache[destination]:
                    raise ValueError(
                        "Broken documentation anchor: "
                        + str(path.relative_to(root))
                        + " -> "
                        + target
                    )


def structure(paths: Sequence[Path]) -> None:
    for path in paths:
        records = headings(path.read_text())
        if (
            not records
            or records[0][0] != 1
            or not records[0][1].strip()
            or sum(level == 1 for level, _ in records) != 1
        ):
            raise ValueError("Documentation needs one leading level-one title: " + str(path))


def validate(root: Path, *, owner_guidance: bool = False) -> None:
    root = Path(root).resolve()
    for name in REQUIRED:
        path = root / name
        if (
            not path.is_file()
            or path.is_symlink()
            or any(p.is_symlink() for p in path.parents if p != root.parent)
        ):
            raise ValueError("Missing regular public documentation source: " + name)
    for folder, allowed in (
        ("docs", {"README.md", "app", "runa"}),
        ("docs/runa", {"handbook", "maintainer"}),
    ):
        for path in (root / folder).iterdir():
            if path.name not in allowed or path.is_symlink():
                raise ValueError("Invalid documentation placement: " + str(path.relative_to(root)))
    receipt = root / ".git/runa-bootstrap.json"
    if (receipt.exists() or receipt.is_symlink()) and (root / "docs/runa/maintainer").exists():
        raise ValueError("Customer source contains owner-only documentation.")
    for state in ("draft", "active", "paused", "closed"):
        path = root / "docs/app/roadmaps" / state
        if not path.is_dir() or path.is_symlink():
            raise ValueError("Missing regular application roadmap directory: " + state)
    if (root / "src/app/docs").exists() or (root / "src/app/docs").is_symlink():
        raise ValueError("Application documentation belongs in docs/app, not src/app/docs.")
    for owner in ("app", "runa"):
        for path in (root / ("src/" + owner + "/modules")).glob("*/docs"):
            raise ValueError(
                "Module documentation belongs in central docs directories, not "
                + str(path.relative_to(root))
            )
    paths = [root / name for name in ("AGENTS.md", "README.md", "docs/README.md")]
    for folder in ("docs/app", "docs/runa/handbook", ".agents"):
        for path in (root / folder).rglob("*.md"):
            if path.is_symlink() or any(p.is_symlink() for p in path.parents if p != root.parent):
                raise ValueError(
                    "Documentation source traverses a symlink: " + str(path.relative_to(root))
                )
            # Closed application records are immutable historical evidence.
            if not path.relative_to(root).as_posix().startswith("docs/app/roadmaps/closed/"):
                paths.append(path)
    owner_paths = (
        [
            path
            for path in paths
            if path == root / "AGENTS.md" or path.is_relative_to(root / ".agents")
        ]
        if owner_guidance
        else []
    )
    if owner_guidance and (
        receipt.exists() or receipt.is_symlink() or not (root / "docs/runa/maintainer").is_dir()
    ):
        raise ValueError("Owner guidance validation requires a receipt-free owner checkout.")
    structure(paths)
    links(root, [path for path in paths if path not in owner_paths])
    links(root, owner_paths, public=False)
    # Selected-scope application closure is checked on its owning branch. Existing
    # historical releases and unrelated branches retain immutable documentation.
    for path in (root / "docs/app/roadmaps/closed").glob("*.md"):
        text = path.read_text()
        if text.startswith("---\n") and "\n---\n" in text[4:]:
            front = dict(
                re.findall(r"^([a-z_]+): ([^\n]+)$", text.split("\n---\n", 1)[0][4:], re.M)
            )
            if "quality_groups" in front:
                import subprocess

                branch = subprocess.check_output(
                    ["git", "branch", "--show-current"], cwd=root, text=True
                ).strip()
                if branch == front.get("branch"):
                    from tools.application.configuration_reader import ConfigurationReader
                    from tools.application.quality_evidence import check_evidence

                    roadmap_metadata = ConfigurationReader.for_workspace(root).roadmap
                    check_evidence(
                        root,
                        roadmap_metadata(path),
                        root / "tools/application/quality.yml",
                        root / ".runtime/application-quality",
                    )
    print("PASS: application documentation, roadmap directories, placement and public local links.")
