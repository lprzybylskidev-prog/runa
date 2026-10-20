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
    active = list((ROOT / 'docs/app/roadmaps/active').glob('*.md'))
    if len(active) > 1:
        raise ValueError('Multiple active application roadmaps.')
    if active:
        content = active[0].read_text()
        branch = re.search(r'^branch: (.+)$', content, re.M)
        actual = run(['git', 'branch', '--show-current'], capture_output=True, text=True).stdout.strip()
        if not branch or actual != branch[1] or '\nstatus: active\n' not in content:
            raise ValueError('Active application roadmap status/branch mismatch.')
    print('PASS: application repository/roadmap state.')


def maintenance():
    repository()
    print('PASS: application maintenance context; platform release maintenance is owner-only.')


def docs():
    from docs_checks import validate
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
    if version != '8.4.26' or not required.issubset(extensions):
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
    print('PASS: Boost command/MCP registration and generated-skill cleanup; actual session docs acceptance is recorded in point 8.')
