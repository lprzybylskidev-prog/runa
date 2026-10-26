#!/usr/bin/env python3
"""Run the complete native database guard acceptance in a disposable suite worker."""
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
raise SystemExit(subprocess.run([
    sys.executable, str(ROOT / 'tools/maintainer/test-backend'),
    '--parallel', '--processes=1', '--filter=DatabaseTest',
], cwd=ROOT).returncode)
