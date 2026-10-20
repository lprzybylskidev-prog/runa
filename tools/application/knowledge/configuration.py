"""Shared customer knowledge configuration with explicit, undelivered owner additions."""
import json
from pathlib import Path
import sys

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from documentation import maintainer_checkout


def load(root):
    root = Path(root)
    config = json.loads((root / 'tools/application/knowledge/config.json').read_text())
    if maintainer_checkout(root):
        extra = root / 'tools/maintainer/knowledge.json'
        if extra.is_symlink():
            raise ValueError('Owner knowledge configuration must not be a symlink.')
        additions = json.loads(extra.read_text())
        if set(additions) != {'files', 'markdown_roots'}:
            raise ValueError('Owner knowledge configuration may only add source paths.')
        config = {**config, **{key: list(dict.fromkeys(config[key] + additions[key])) for key in additions}}
    return config


def golden(root):
    root = Path(root)
    queries = json.loads((root / 'tools/application/knowledge/golden.json').read_text())
    if maintainer_checkout(root):
        queries += json.loads((root / 'tools/maintainer/knowledge-golden.json').read_text())
    return queries
