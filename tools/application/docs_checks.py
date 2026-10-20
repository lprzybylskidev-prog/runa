"""Customer application documentation diagnostics; no platform-release metadata."""
from pathlib import Path


def validate(root):
    root = Path(root)
    for folder in ('docs/app', 'docs/runa/handbook'):
        path = root / folder
        if path.is_symlink():
            raise ValueError('Documentation roots must not be symlinks.')
    print('PASS: application documentation roots; platform release documentation is owner-only.')
