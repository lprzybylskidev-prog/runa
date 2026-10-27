#!/usr/bin/env python3
"""Fixed transport to the private packaged knowledge scenario."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    raise SystemExit(
        subprocess.call(
            [
                sys.executable,
                str(Path(__file__).resolve().parents[1] / "test-python"),
                "--scope=integration",
                "--path=integration/test_knowledge_retrieval.py",
                "--marker=knowledge",
                *sys.argv[1:],
            ],
            cwd=Path(__file__).resolve().parents[3],
        )
    )
