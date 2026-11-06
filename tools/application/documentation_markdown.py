"""Small source-document parser for local links and heading targets, not rendering."""

from __future__ import annotations

import html
import re
import unicodedata
from collections.abc import Iterator


def prose(text: str) -> Iterator[str]:
    """Exclude leading metadata, comments and fenced code examples."""
    text = re.sub(r"<!--.*?-->", "", text, flags=re.S)
    lines = text.splitlines()
    if lines[:1] == ["---"]:
        try:
            lines = lines[lines.index("---", 1) + 1 :]
        except ValueError:
            raise ValueError("Unclosed documentation frontmatter.") from None
    fence: tuple[str, int] | None = None
    for line in lines:
        marker = re.match(r"^ {0,3}(`{3,}|~{3,})(.*)$", line)
        if fence:
            if (
                marker
                and marker[1][0] == fence[0]
                and len(marker[1]) >= fence[1]
                and not marker[2].strip()
            ):
                fence = None
            yield ""
            continue
        if marker and (marker[1][0] != "`" or "`" not in marker[2]):
            fence = (marker[1][0], len(marker[1]))
            yield ""
        else:
            yield line
    if fence:
        raise ValueError("Unclosed documentation code fence.")


def headings(text: str) -> list[tuple[int, str]]:
    records: list[tuple[int, str]] = []
    previous = ""
    for line in prose(text):
        match = re.match(r"^ {0,3}(#{1,6})(?:[ \t]+(.*?)|[ \t]*)$", line)
        if match:
            title = re.sub(r"[ \t]+#+[ \t]*$", "", match[2] or "")
            records.append((len(match[1]), title))
        elif previous.strip() and re.fullmatch(r" {0,3}(=+|-+)[ \t]*", line):
            records.append((1 if line.lstrip().startswith("=") else 2, previous.strip()))
        previous = line if not match else ""
    return records


def anchors(text: str) -> set[str]:
    targets: set[str] = set()
    generated: set[str] = set()
    for _, title in headings(text):
        title = re.sub(r"!?\[([^\]]*)\]\([^)]*\)", r"\1", title)
        title = html.unescape(re.sub(r"<[^>]*>", "", title))
        title = re.sub(r"(?<!\w)_{1,2}(.+?)_{1,2}(?!\w)", r"\1", title)
        slug = "".join(
            c for c in title.lower() if c in " _-" or unicodedata.category(c)[0] in "LNM"
        ).replace(" ", "-")
        candidate, suffix = slug, 0
        while candidate in generated:
            suffix += 1
            candidate = f"{slug}-{suffix}"
        generated.add(candidate)
        targets.add(candidate)
    for line in prose(text):
        line = re.sub(r"(`+).*?\1", "", line)
        for match in re.finditer(r"<[^>]+\b(?:id|name)=[\"']([^\"']+)[\"'][^>]*>", line):
            targets.add(html.unescape(match[1]))
    return targets


def link_targets(text: str) -> Iterator[str]:
    """Inline links and reference definitions; code spans remain examples."""
    for line in prose(text):
        line = re.sub(r"(`+).*?\1", "", line)
        for match in re.finditer(r'!?\[[^\]]*\]\((<[^>]+>|[^\s)]+)(?:\s+"[^"]*")?\)', line):
            yield html.unescape(match[1].strip("<>"))
        reference = re.match(r"^ {0,3}\[(?!\^)[^\]]+\]:\s*(<[^>]+>|\S+)", line)
        if reference:
            yield html.unescape(reference[1].strip("<>"))
