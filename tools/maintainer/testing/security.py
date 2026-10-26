#!/usr/bin/env python3
"""Compatibility launcher for the supported private pytest transport."""

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
                "--filter=test_security_services",
            ],
            cwd=root,
        )
    )
