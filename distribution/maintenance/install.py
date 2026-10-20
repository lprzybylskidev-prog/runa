#!/usr/bin/env python3
"""Owner-only bounded installer transport; never installed in a customer checkout."""
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'tools/project'))
from installation import main

raise SystemExit(main(sys.argv[1:]))
