"""Real public documentation inputs for isolated owner metadata validators."""
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[3]


def copy_public(root):
    for name in ('AGENTS.md', 'README.md', 'docs/README.md'):
        target = root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / name, target)
    for name in ('docs/app', 'docs/runa/handbook', '.agents'):
        shutil.copytree(ROOT / name, root / name, ignore=shutil.ignore_patterns('__pycache__'))
