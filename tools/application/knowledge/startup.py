#!/usr/bin/env python3
"""Fixed source transport for optional development knowledge startup."""

import os
import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    root = Path(__file__).resolve().parents[3]
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", "tools.application.knowledge.startup_cli", *sys.argv[1:]],
            cwd=root,
            env=dict(os.environ, PYTHONPYCACHEPREFIX=str(root / ".runtime/python/pycache")),
        )
    )
