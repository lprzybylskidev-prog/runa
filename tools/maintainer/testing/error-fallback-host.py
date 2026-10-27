#!/usr/bin/env python3
"""Compatibility transport to an import-safe private acceptance module."""

import os
import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    root = Path(__file__).resolve().parents[3]
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", "tools.maintainer.testing.error_fallback_host", *sys.argv[1:]],
            cwd=root,
            env=dict(os.environ, PYTHONPYCACHEPREFIX=str(root / ".runtime/python/pycache")),
        )
    )
