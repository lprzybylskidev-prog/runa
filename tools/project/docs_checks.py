"""Bounded README state projection and documented command coverage, read-only."""
from datetime import datetime
import json
import os
from pathlib import Path
import re
import subprocess

from runtime import ROOT, app_command

START = '<!-- runa:current-state -->'
END = '<!-- /runa:current-state -->'
FIELDS = ('Current version', 'Active roadmap', 'Production ever deployed')


def read_yaml(path):
    # Preserve YAML timestamps as ISO strings; the default parser turns them into integers.
    script = ('require "src/vendor/autoload.php"; '
              '$data = Symfony\\Component\\Yaml\\Yaml::parseFile($argv[1], Symfony\\Component\\Yaml\\Yaml::PARSE_DATETIME); '
              'if (is_array($data)) { array_walk_recursive($data, static function (&$value) { '
              'if ($value instanceof DateTimeInterface) { $value = $value->format(DATE_ATOM); } }); } '
              'echo json_encode($data, JSON_THROW_ON_ERROR);')
    result = subprocess.run(app_command(['php', '-r', script, str(path)]),
                            cwd=ROOT, check=True, capture_output=True, text=True)
    return json.loads(result.stdout)


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
        raise ValueError('README current-state block must contain exactly three ordered fields.')
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
    state = read_yaml(root / 'docs/project/state.yml')
    if not isinstance(state, dict) or type(state.get('version')) is not int or state['version'] != 1:
        raise ValueError('Invalid project state schema version.')
    production = state.get('production')
    if not isinstance(production, dict) or type(production.get('ever_deployed')) is not bool or 'first_deployed_at' not in production:
        raise ValueError('Invalid production deployment metadata.')
    deployed = production['ever_deployed']
    stamp = production['first_deployed_at']
    if deployed:
        try:
            parsed = datetime.fromisoformat(stamp) if isinstance(stamp, str) else None
            if parsed is None or parsed.tzinfo is None:
                raise ValueError('Missing timezone.')
        except ValueError as error:
            raise ValueError('Production first_deployed_at must be an ISO timestamp with timezone.') from error
    elif stamp is not None:
        raise ValueError('Production first_deployed_at must be null before first deployment.')
    if values['Production ever deployed'] != str(deployed).lower():
        raise ValueError('README Production ever deployed does not match project state.')
    commands = (root / 'docs/tooling/commands.md').read_text()
    for path in (root / 'bin').iterdir():
        if path.is_file() and os.access(path, os.X_OK) and path.name not in ('check', 'passthrough'):
            if './bin/' + path.name not in commands:
                raise ValueError('Undocumented command: ' + path.name)
    print('PASS: README version/active-roadmap/production state and public command documentation; prose review is separate.')
