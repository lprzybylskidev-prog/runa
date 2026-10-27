#!/usr/bin/env python3
"""Reject temporary debug statements in application sources."""

from __future__ import annotations

import re
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def main(argv: Sequence[str] | None = None) -> None:
    if list(sys.argv[1:] if argv is None else argv) == ["--help"]:
        print(
            "Usage: ./bin/artisan runa:check:debug-residue\nReject debug statements in application/test sources and visible scratch files. No files are modified."
        )
        sys.exit(0)
    if list(sys.argv[1:] if argv is None else argv) != ["debug-residue"]:
        print("Unknown check. Use php artisan list runa and help <exact-command>.", file=sys.stderr)
        sys.exit(1)
    patterns = {
        ".php": re.compile(r"\b(?:dd|dump|var_dump|print_r|xdebug_break)\s*\(|\becho\s"),
        ".ts": re.compile(r"\bconsole\.(?:log|debug)\s*\(|\bdebugger\s*;"),
        ".js": re.compile(r"\bconsole\.(?:log|debug)\s*\(|\bdebugger\s*;"),
        ".vue": re.compile(r"\bconsole\.(?:log|debug)\s*\(|\bdebugger\s*;"),
    }
    # Reviewed permanent output, not debug logging. Match the exact source statement,
    # never exempt a whole file or another echo/debug expression in the same file.
    intentional_output = {
        "src/runa/resources/views/errors/page.php": "echo strtr($template, [",
        "tests/backend/runa/Fixtures/browser-document.php": "echo json_encode(['status' => $response->getStatusCode(), 'headers' => $response->headers->all(),",
    }
    failures = []
    paths = (
        subprocess.check_output(["git", "ls-files", "-co", "--exclude-standard", "-z"], cwd=ROOT)
        .decode()
        .split("\0")
    )
    for name in sorted(set(paths) - {""}):
        path = ROOT / name
        if name.startswith(("tools/scratch/", ".playwright/scratch/")):
            failures.append(name + ": remove or promote scratch source")
        if (
            name.split("/")[0] not in ("src", "resources", "tests")
            or path.suffix not in patterns
            or not path.is_file()
        ):
            continue
        for number, line in enumerate(path.read_text().splitlines(), 1):
            if patterns[path.suffix].search(line) and line.strip() != intentional_output.get(name):
                failures.append(
                    f"{name}:{number}: debug statement requires removal or a deliberately reviewed checker rule"
                )
    if failures:
        print("\n".join(failures), file=sys.stderr)
        sys.exit(1)
    print("PASS: application/test debug statements and visible scratch source absent.")


if __name__ == "__main__":
    main()
