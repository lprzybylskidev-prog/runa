#!/usr/bin/env python3
"""Fixed compatibility transport to the import-safe quality CLI."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    if not __package__:
        raise SystemExit(
            subprocess.call(
                [sys.executable, "-m", "tools.application.quality_cli", *sys.argv[1:]],
                cwd=Path(__file__).resolve().parents[3],
            )
        )
    from tools.application.quality_cli import main

    raise SystemExit(main())
