#!/usr/bin/env python3
"""Compatibility transport to the supported private pytest runner."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    root = Path(__file__).resolve().parents[3]
    raise SystemExit(
        subprocess.call(
            [
                sys.executable,
                str(root / "tools/maintainer/test-python"),
                "--scope=contracts",
                *(
                    ["--filter=test_release or test_docs or test_knowledge_ingestion"]
                    if "--with-docs-and-knowledge" in sys.argv
                    else ["--path=contracts/test_release.py"]
                ),
                *[a for a in sys.argv[1:] if a != "--with-docs-and-knowledge"],
            ],
            cwd=root,
        )
    )
