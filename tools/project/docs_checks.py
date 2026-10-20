"""Bounded README state projection and documented command coverage, read-only."""
import os
from pathlib import Path
import re
import subprocess


START = '<!-- runa:current-state -->'
END = '<!-- /runa:current-state -->'
FIELDS = ('Current version', 'Active roadmap')


def validate(root):
    root = Path(root)
    text = (root / 'README.md').read_text()
    if text.count(START) != 1 or text.count(END) != 1:
        raise ValueError('README must contain exactly one runa:current-state block; see docs/workflows/releases.md.')
    before, rest = text.split(START)
    if END in before or END not in rest:
        raise ValueError('README current-state markers are out of order.')
    body = rest.split(END)[0]
    lines = [line for line in body.splitlines() if line.strip()]
    if len(lines) != len(FIELDS):
        raise ValueError('README current-state block must contain exactly two ordered fields.')
    values = {}
    for label, line in zip(FIELDS, lines, strict=True):
        match = re.fullmatch(r'- ' + re.escape(label) + r': `([^`\n]+)`', line)
        if not match:
            raise ValueError('Invalid README current-state field: ' + label)
        values[label] = match[1]
    current = (root / 'VERSION').read_text().strip()
    if not re.fullmatch(r'(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)', current):
        raise ValueError('Invalid canonical VERSION for documentation.')
    if values['Current version'] != current:
        raise ValueError('README Current version does not match VERSION.')
    active = sorted((root / 'docs/roadmaps/active').glob('*.md'))
    if len(active) > 1:
        raise ValueError('Multiple active roadmaps for README state.')
    identifier = 'none'
    if active:
        from release_checks import metadata
        data, _ = metadata(active[0])
        branch = subprocess.run(['git', 'branch', '--show-current'], cwd=root,
                                check=True, capture_output=True, text=True).stdout.strip()
        identifier = data['id']
        if data.get('status') != 'active' or data.get('branch') != branch:
            raise ValueError('Active roadmap status/branch mismatch for README state.')
    if values['Active roadmap'] != identifier:
        raise ValueError('README Active roadmap does not match roadmap directories.')
    commands = (root / 'docs/tooling/commands.md').read_text()
    for path in (root / 'bin').iterdir():
        if path.is_file() and os.access(path, os.X_OK) and path.name not in ('check', 'passthrough'):
            if './bin/' + path.name not in commands:
                raise ValueError('Undocumented command: ' + path.name)
    print('PASS: README version/active-roadmap state and public command documentation; prose review is separate.')
