"""Fast staged-content checks; no full test/static-analysis suite."""
import os
from pathlib import Path
import re
import subprocess
import tempfile


def git(root, *args, data=None):
    return subprocess.run(['git', *args], cwd=root, input=data, check=True, capture_output=True).stdout


def commit_message(root, path):
    first = Path(path).read_text().splitlines()[0]
    match = re.fullmatch(r'(feat|fix|docs|style|refactor|perf|test|build|chore|revert|merge)(?:\(([A-Za-z0-9_.-]+)\))?!?: [^\s].*', first)
    if not match:
        raise ValueError('Use Conventional Commits: type(RM-YYYY-NNN): description')
    roadmaps = root / 'docs/app/roadmaps'
    if not roadmaps.exists():
        roadmaps = root / 'docs/roadmaps'
    active = list((roadmaps / 'active').glob('*.md'))
    if len(active) > 1:
        raise ValueError('At most one active roadmap is allowed.')
    if active:
        roadmap = re.search(r'^id: (RM-\d{4}-\d{3})$', active[0].read_text(), re.M)
        if not roadmap or match[2] != roadmap[1]:
            raise ValueError('Commit scope must match the active roadmap ID.')


def pre_commit(root, tools_root=None, check_knowledge=True):
    tools_root = tools_root or root
    entries = git(root, 'diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z').decode().split('\0')
    entries = [name for name in entries if name]
    transformed = []
    with tempfile.TemporaryDirectory(prefix='runa-staged-') as directory:
        snapshot = Path(directory) / 'content'
        snapshot.mkdir()
        for name in entries:
            parts = Path(name).parts
            if any(part == '.env' or (part.startswith('.env.') and not part.endswith('.example')) for part in parts):
                raise ValueError(f'Environment files cannot be committed: {name}')
            row = git(root, 'ls-files', '-s', '--', name).decode().split()[0]
            if row not in ('100644', '100755', '120000'):
                raise ValueError(f'Unsupported staged file mode: {name}')
            data = git(root, 'show', ':' + name)
            if len(data) > 10 * 1024 * 1024 or (b'\0' in data and len(data) > 1024 * 1024):
                raise ValueError(f'Unwanted large file: {name}')
            if re.search(rb'-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----', data):
                raise ValueError(f'Private key material is forbidden: {name}')
            target = snapshot / name
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(data)
            if row == '120000':
                continue
            formatted = data
            suffix = Path(name).suffix
            if suffix == '.php' and name == 'src/runa/lang/pl/validation.php':
                # Imported snapshot bytes are audited by the localization contract.
                subprocess.run(['php', '-l', str(target)], check=True, capture_output=True)
            elif suffix == '.php':
                subprocess.run([str(tools_root / 'src/vendor/bin/pint'), '--config', str(tools_root / 'src/pint.json'), str(target)],
                               cwd=tools_root, check=True, capture_output=True)
                formatted = target.read_bytes()
            elif suffix in ('.md', '.json', '.yaml', '.yml', '.vue', '.ts', '.js', '.mjs', '.css') and name != 'src/pnpm-lock.yaml':
                formatted = subprocess.run(['pnpm', '--dir', str(tools_root / 'src'), 'exec', 'prettier', '--stdin-filepath', str(root / name)], cwd=tools_root,
                                           input=data, capture_output=True, check=True).stdout
            if formatted != data:
                path = root / name
                if path.is_symlink() or not path.exists() or path.read_bytes() != data:
                    raise ValueError(f'Formatter would change partially staged file {name}. Format it and stage the intended hunks explicitly.')
                transformed.append((name, formatted, row))
            if suffix in ('.vue', '.ts', '.js', '.mjs') and (name.startswith(('src/app/resources/', 'src/runa/resources/', 'src/runa/tests/frontend/', 'src/runa/tests/e2e/')) or (name.startswith('src/') and name.count('/') == 1)):
                subprocess.run(['pnpm', '--dir', str(tools_root / 'src'), 'exec', 'eslint', '--stdin', '--stdin-filename', str(root / name), '--max-warnings=0'],
                               cwd=tools_root, input=formatted, check=True)
            elif suffix == '.py' or (name.startswith('bin/') and data.startswith(b'#!/usr/bin/env python3')):
                compile(formatted, name, 'exec')
            elif data.startswith(b'#!/bin/sh'):
                subprocess.run(['sh', '-n'], input=formatted, check=True)
        scanner = tools_root / '.runtime/tools/gitleaks'
        if not scanner.exists():
            raise ValueError('Pinned Gitleaks is missing. Run ./bin/dev setup inside the devcontainer.')
        # Explicit built-in defaults prevent staged config/allow comments from disabling scanning.
        scanner_config = Path(directory) / 'scanner.toml'
        scanner_config.write_text('[extend]\nuseDefault = true\n')
        env = {key: value for key, value in os.environ.items() if not key.startswith('GITLEAKS_')}
        subprocess.run([str(scanner), 'dir', '--config', str(scanner_config), '--gitleaks-ignore-path', str(snapshot / '.no-ignore'),
                        '--ignore-gitleaks-allow', '--redact', '--no-banner', str(snapshot)], cwd=snapshot, env=env, check=True)
    if check_knowledge:
        subprocess.run([str(root / 'bin/knowledge'), 'stale'], cwd=root, check=True, stdout=subprocess.DEVNULL)
    # Do not mutate index/worktree until all checks and partial-staging checks pass.
    for name, formatted, mode in transformed:
        path = root / name
        # Recheck in case an editor changed the worktree during the checks.
        if path.is_symlink() or path.read_bytes() != git(root, 'show', ':' + name):
            raise ValueError(f'File changed during checks: {name}; retry after staging intended content.')
        path.write_bytes(formatted)
        blob = git(root, 'hash-object', '-w', '--stdin', data=formatted).decode().strip()
        git(root, 'update-index', '--cacheinfo', f'{mode},{blob},{name}')
    if transformed and check_knowledge:
        # Formatting indexed docs can make knowledge stale; instruct explicit indexing, never reindex in a hook.
        subprocess.run([str(root / 'bin/knowledge'), 'stale'], cwd=root, check=True, stdout=subprocess.DEVNULL)
    print('PASS: staged secrets, formatting, quick lint, repository sanity and knowledge freshness.')
