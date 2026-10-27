#!/usr/bin/env python3
"""Fixed source transport selecting the receipt-scoped knowledge composition root."""

import os
import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    root = Path(__file__).resolve().parents[3]
    receipt = root / ".git/runa-bootstrap.json"
    owner = (
        not receipt.exists()
        and not receipt.is_symlink()
        and (root / "tools/maintainer").is_dir()
        and (root / "docs/runa/maintainer/agent-contract.md").is_file()
    )
    module = "tools.maintainer.knowledge_cli" if owner else "tools.application.knowledge.cli"
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", module, *sys.argv[1:]],
            cwd=root,
            env=dict(os.environ, PYTHONPYCACHEPREFIX=str(root / ".runtime/python/pycache")),
        )
    )
