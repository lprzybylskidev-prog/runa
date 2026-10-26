"""Focused deterministic project diagnostics. Checks never repair runtime state."""
from datetime import date, datetime, timedelta, timezone
import json
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.request
from runtime import ROOT, run, app_command


def yaml(path):
    return json.loads(run(app_command(['php', 'tools/application/read-yaml.php', str(path.relative_to(ROOT))]), capture_output=True, text=True).stdout)


def repository():
    active = list((ROOT / 'docs/runa/maintainer/roadmaps/active').glob('*.md'))
    if len(active) > 1:
        raise ValueError('Multiple active roadmaps.')
    if active:
        content = active[0].read_text()
        branch = re.search(r'^branch: (.+)$', content, re.M)
        actual = run(['git', 'branch', '--show-current'], capture_output=True, text=True).stdout.strip()
        if not branch or actual != branch[1] or '\nstatus: active\n' not in content:
            raise ValueError('Active roadmap status/branch mismatch.')
    if (ROOT / '.github/workflows').exists():
        raise ValueError('CI is prohibited by the project contract.')
    if not re.fullmatch(r'\d+\.\d+\.\d+\n?', (ROOT / 'VERSION').read_text()):
        raise ValueError('VERSION must be a semantic X.Y.Z version.')
    print('PASS: repository roadmap/branch/version/no-CI invariants (active worktree changes are allowed).')


def maintenance():
    registry = yaml(ROOT / 'docs/runa/maintainer/maintenance/registry.yml')
    ids = set()
    for entry in registry['entries']:
        required = ('id', 'type', 'name', 'last_reviewed', 'review_every_days')
        if any(not entry.get(key) for key in required) or not (entry.get('version') or entry.get('pinned_revision')):
            raise ValueError('Incomplete external registry entry.')
        if entry['id'] in ids:
            raise ValueError('Duplicate registry ID: ' + entry['id'])
        ids.add(entry['id'])
        if entry.get('source') and not (ROOT / entry['source']).is_file():
            raise ValueError('Missing registry source: ' + entry['source'])
        interval = entry['review_every_days']
        if type(interval) is not int or interval <= 0:
            raise ValueError('Invalid registry review interval.')
        due = (datetime.fromtimestamp(entry['last_reviewed'], timezone.utc).date() if isinstance(entry['last_reviewed'], int) else date.fromisoformat(entry['last_reviewed'])) + timedelta(days=interval)
        if due < date.today():
            print('REVIEW DUE: ' + entry['id'] + ' ' + due.isoformat())
    roadmap_ids = {p.stem[:11] for p in (ROOT / 'docs/runa/maintainer/roadmaps').rglob('RM-*.md')}
    known = set()
    for kind in ('backlog', 'deprecations'):
        data = yaml(ROOT / f'docs/runa/maintainer/maintenance/{kind}.yml')
        if data.get('version') != 1 or not isinstance(data.get('items'), list):
            raise ValueError('Invalid maintenance document: ' + kind)
        for item in data['items']:
            required = ('id', 'description', 'source', 'priority', 'created_at', 'status', 'closure_condition') if kind == 'backlog' else ('id', 'deprecated', 'replacement', 'introduced_at', 'removal_plan', 'roadmap')
            if any(not item.get(key) for key in required) or item['id'] in known:
                raise ValueError('Incomplete/duplicate maintenance item.')
            known.add(item['id'])
            related = item.get('roadmap')
            if related and related not in roadmap_ids:
                raise ValueError('Maintenance/deprecation references a missing roadmap.')
            if kind == 'backlog':
                if not re.fullmatch(r'MT-\d{4}-\d{3}', item['id']) or item['status'] not in ('open', 'planned', 'resolved', 'wontfix'):
                    raise ValueError('Invalid maintenance item ID/status.')
                if item['status'] == 'resolved' and not (item.get('roadmap') and item.get('released_version')):
                    raise ValueError('Resolved maintenance needs roadmap and released_version.')
                if item['status'] == 'wontfix' and not item.get('reason'):
                    raise ValueError('wontfix requires an explicit decision reason.')
    known.update(roadmap_ids)
    tracked = run(['git', 'ls-files', '-z'], capture_output=True).stdout.decode().split('\0')
    for name in tracked:
        path = ROOT / name
        if not name or not path.is_file() or path.is_symlink():
            continue
        try:
            text = path.read_text()
        except UnicodeError:
            continue
        for line in text.splitlines():
            if re.match(r'^\s*(?://|#|/\*|\*|<!--)\s*(?:TODO|FIXME|HACK)\b', line):
                if not any(identifier in line for identifier in known):
                    raise ValueError('Unlinked maintenance comment: ' + name)
    # Direct application dependencies must have a registered reviewed version.
    composer = json.loads((ROOT / 'src/composer.json').read_text())
    package = json.loads((ROOT / 'src/package.json').read_text())
    versions = {entry['package']: str(entry.get('version')) for entry in registry['entries'] if entry.get('package')}
    if len(versions) != sum('package' in entry for entry in registry['entries']):
        raise ValueError('Duplicate registry package mapping.')
    for mapping in (composer['require'], composer['require-dev'], package['dependencies'], package['devDependencies']):
        for name, version in mapping.items():
            if name != 'php' and versions.get(name) != version:
                raise ValueError('Dependency version absent from maintenance registry: ' + name)
    print('PASS: registry/backlog/deprecation structure, review dates, direct dependency versions and maintenance comment links.')


def docs():
    from tools.maintainer.docs_checks import validate
    validate(ROOT)


def tooling():
    for path in sorted((ROOT / 'bin').iterdir()):
        if path.is_file() and os.access(path, os.X_OK) and path.name != 'check':
            result = run([str(path), '--help'], capture_output=True, text=True)
            if not result.stdout.strip():
                raise ValueError('Missing help output: ' + path.name)
    scanner = ROOT / '.runtime/tools/gitleaks'
    if run([str(scanner), 'version'], capture_output=True, text=True).stdout.strip() != '8.30.1':
        raise ValueError('Pinned Gitleaks version mismatch.')
    php = run(['php', '-r', 'echo json_encode([PHP_VERSION, get_loaded_extensions()]);'], capture_output=True, text=True)
    version, extensions = json.loads(php.stdout)
    required = {'pdo_pgsql', 'mbstring', 'intl', 'bcmath', 'Zend OPcache', 'pcntl', 'zip', 'curl', 'xml', 'xdebug'}
    if version != '8.4.27' or not required.issubset(extensions):
        raise ValueError('PHP version/extensions differ from the development toolchain.')
    for command, expected in [(['node', '--version'], 'v24.22.0'), (['pnpm', '--version'], '12.14.0'),
                              (['composer', '--version'], '2.10.3'), (['codex', '--version'], '0.163.0')]:
        if expected not in run(command, capture_output=True, text=True).stdout:
            raise ValueError('Pinned tool version mismatch: ' + command[0])
    print('PASS: command help/exit contracts, PHP extensions and pinned PHP/Node/pnpm/Composer/Codex/Gitleaks.')


def development():
    run(['php', 'artisan', 'runa:config:validate'])
    run(['php', 'artisan', 'runa:config:templates'])
    if any((ROOT / 'src/app/database/migrations').glob('*.php')):
        run(['php', 'artisan', 'migrate:status', '--no-ansi'])
    for service, limit in (('queue', 120), ('scheduler', 150)):
        heartbeat = json.loads((ROOT / 'src/storage/framework/health' / (service + '.json')).read_text())
        age = time.time() - heartbeat.get('recorded_at', 0)
        if not heartbeat.get('instance') or not 0 <= age <= limit:
            raise ValueError(service + ' shared heartbeat is missing/stale.')
    for endpoint in ('http://nginx:8080/api/health', 'http://mailpit:8025/readyz'):
        with urllib.request.build_opener(urllib.request.ProxyHandler({})).open(endpoint, timeout=10) as response:
            if response.status != 200:
                raise ValueError('Development HTTP health failed.')
    run(['php', 'artisan', 'runa:dev:knowledge-status'])
    run(['php', 'artisan', 'runa:dev:profile'])
    print('PASS: config, database/migrations, queue/scheduler, Nginx, Mailpit and effective resources; optional AI availability reported separately.')


def boost():
    commands = run(['php', 'artisan', 'list', '--raw'], capture_output=True, text=True).stdout
    if 'boost:mcp' not in commands:
        raise ValueError('Boost MCP Artisan command missing.')
    result = run(['codex', 'mcp', 'get', 'laravel-boost', '--json'], capture_output=True, text=True)
    registration = json.loads(result.stdout)
    if 'boost-mcp' not in json.dumps(registration):
        raise ValueError('Boost MCP registration missing canonical launcher.')
    if any((ROOT / '.agents/skills' / name).exists() for name in ('laravel-boost', 'pest-testing', 'tailwindcss-development')):
        raise ValueError('Boost-generated skills must be removed.')
    print('PASS: Boost command/MCP registration and generated-skill cleanup; live version-aware documentation access is verified separately.')


def cleanliness():
    for folder in ('tools/scratch', '.playwright/scratch'):
        if (ROOT / folder).exists() and any(p.is_file() and p.name != '.gitkeep' for p in (ROOT / folder).rglob('*')):
            raise ValueError('Review/remove scratch files before finalization: ' + folder)
    result = run(['git', 'status', '--porcelain'], capture_output=True, text=True).stdout
    if any(line.startswith('??') or (len(line) > 1 and line[1] != ' ') for line in result.splitlines()):
        raise ValueError('Untracked/unstaged changes require review before finalization.')
    print('PASS: no unreviewed scratch/untracked/unstaged source residue.')


def release():
    from tools.maintainer.release_checks import validate
    repository()
    validate(ROOT)
