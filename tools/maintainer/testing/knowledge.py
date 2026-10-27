#!/usr/bin/env python3
"""Fixed transport preserving the existing offline knowledge suite command."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    raise SystemExit(
        subprocess.call(
            [
                sys.executable,
                "-m",
                "tools.maintainer.tests.python.contracts.test_knowledge_ingestion",
                *sys.argv[1:],
            ],
            cwd=Path(__file__).resolve().parents[3],
        )
    )
