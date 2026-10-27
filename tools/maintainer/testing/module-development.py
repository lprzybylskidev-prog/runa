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
                "--path=contracts/test_module_development.py",
                *sys.argv[1:],
            ],
            cwd=root,
        )
    )
