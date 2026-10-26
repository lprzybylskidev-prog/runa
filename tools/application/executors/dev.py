#!/usr/bin/env python3
"""Compatibility source transport for development startup."""
import os
from pathlib import Path
import subprocess
import sys

if __name__ == '__main__':
    root = Path(__file__).resolve().parents[3]
    raise SystemExit(subprocess.call([sys.executable, '-m', 'tools.application.development', *sys.argv[1:]],
        cwd=root, env=dict(os.environ, PYTHONPYCACHEPREFIX=str(root / '.runtime/python/pycache'))))
