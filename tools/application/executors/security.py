#!/usr/bin/env python3
"""Fixed compatibility transport to an import-safe command."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    if not __package__:
        raise SystemExit(
            subprocess.call(
                [sys.executable, "-m", "tools.application.security_cli", *sys.argv[1:]],
                cwd=Path(__file__).resolve().parents[3],
            )
        )
    from tools.application.security_cli import main

    raise SystemExit(main())
