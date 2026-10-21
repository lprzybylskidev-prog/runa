"""Explicit documentation ownership; acquisition receipts always select customer scope."""
from pathlib import Path


def maintainer_checkout(root):
    root = Path(root)
    receipt = root / '.git/runa-bootstrap.json'
    return (not receipt.exists() and not receipt.is_symlink()
            and (root / 'tools/maintainer').is_dir()
            and (root / 'docs/runa/maintainer/agent-contract.md').is_file())


def roadmap_root(root):
    return Path(root) / ('docs/runa/maintainer/roadmaps' if maintainer_checkout(root) else 'docs/app/roadmaps')
