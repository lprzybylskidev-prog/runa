#!/usr/bin/env python3
"""Internal bounded installer transport; Artisan owns the public command contract."""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from installation import main

raise SystemExit(main(sys.argv[1:]))
