#!/usr/bin/env python3
"""Fixed transport to the private packaged knowledge scenario."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", "tools.maintainer.testing.knowledge_retrieval", *sys.argv[1:]],
            cwd=Path(__file__).resolve().parents[3],
        )
    )
