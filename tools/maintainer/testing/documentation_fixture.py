"""Real public documentation inputs for isolated owner metadata validators."""
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[3]


def copy_public(root):
    for name in ('README.md', 'docs/README.md'):
        target = root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / name, target)
    for name in ('docs/app', 'docs/runa/handbook', '.agents'):
        shutil.copytree(ROOT / name, root / name, ignore=shutil.ignore_patterns('__pycache__'))

    product = ROOT / 'docs/runa/maintainer/customer-agent'
    shutil.copy2(product / 'AGENTS.md', root / 'AGENTS.md')
    shutil.copy2(product / 'CLAUDE.md', root / 'CLAUDE.md')
    shutil.rmtree(root / '.agents/skills')
    shutil.copytree(product / 'skills', root / '.agents/skills')
