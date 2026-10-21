#!/usr/bin/env python3
"""Production image/Compose entrypoint; run from the host."""
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
from production import main

try:
    main(sys.argv[1:])
except (ValueError, OSError, subprocess.CalledProcessError) as error:
    message = f'Child command failed (exit {error.returncode}).' if isinstance(error, subprocess.CalledProcessError) else str(error)
    print('Production failed: ' + message, file=sys.stderr)
    sys.exit(getattr(error, 'returncode', 1))
