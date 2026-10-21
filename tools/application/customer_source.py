"""Fetch customer Git into disposable staging; never alter installation Git history."""
from contextlib import contextmanager
import os
from pathlib import Path
import re
import subprocess
import tempfile

from repository_boundary import OWNER_PATHS, application_remote

COMPOSED = {'src/composer.json', 'src/composer.lock', 'src/package.json', 'src/pnpm-lock.yaml'}


def application_path(path):
    return path.startswith(('src/app/', 'docs/app/')) or path in COMPOSED


def excluded(path):
    return any(path == item or path.startswith(item + '/') for item in OWNER_PATHS)


def git(root, *arguments):
    environment = {key: value for key, value in os.environ.items() if not key.startswith('GIT_')}
    environment['GIT_TERMINAL_PROMPT'] = '0'
    try:
        return subprocess.run(['git', '-c', 'core.hooksPath=/dev/null', '-c', 'protocol.ext.allow=never',
                               '-C', str(root), *arguments], env=environment, stdin=subprocess.DEVNULL,
                              capture_output=True, check=True, timeout=180).stdout
    except (subprocess.CalledProcessError, subprocess.TimeoutExpired):
        raise ValueError('Customer Git operation failed. Check the selected remote, target and operator credentials; no running release was changed.') from None


def validate_target(target):
    if (not isinstance(target, str) or len(target) > 255
            or not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._/-]*', target)
            or any(value in target for value in ('..', '//', '@{'))
            or target.endswith(('/', '.', '.lock'))):
        raise ValueError('Target must be a branch, tag, refs/heads/... or refs/tags/... name, or full lowercase SHA.')
    if target.startswith('refs/') and not target.startswith(('refs/heads/', 'refs/tags/')):
        raise ValueError('Only branch/tag refs are supported.')
    return target


def tree(root, ref):
    entries = {}
    for row in git(root, 'ls-tree', '-rz', ref).split(b'\0'):
        if row:
            metadata, name = row.split(b'\t', 1)
            path = name.decode('utf-8')
            if any(part in ('', '.', '..') for part in path.split('/')) or any(ord(char) < 32 for char in path):
                raise ValueError('Unsafe source path in customer revision.')
            mode, kind, identity = metadata.decode('ascii').split()
            if kind != 'blob' or (application_path(path) and mode not in ('100644', '100755')):
                raise ValueError('Submodules and application symlinks are unsupported: ' + path)
            entries[path] = (mode, identity)
    return entries


def verify_platform(root, source, sha, installed=None):
    """Compare against the installed source; deployment never upgrades platform files."""
    expected, selected = tree(root, 'HEAD'), tree(source, sha)
    protected = lambda entries: {name: identity for name, identity in entries.items()
                                 if not application_path(name) and not excluded(name)}
    baseline, candidate = protected(expected), protected(selected)
    if installed is not None:
        from repository_boundary import receipt
        recorded = receipt(installed)
        if (git(installed, 'rev-parse', 'HEAD').decode().strip() != recorded.get('commit')
                or git(installed, 'status', '--porcelain=v1', '--untracked-files=all').strip()):
            raise ValueError('Successful release checkout is modified; inspect its source/evidence before deployment.')
        if protected(tree(installed, 'HEAD')) != baseline:
            raise ValueError('Operator checkout differs from the successfully installed protected source; application deployment cannot upgrade the platform.')
    differences = sorted(name for name in baseline.keys() | candidate.keys() if baseline.get(name) != candidate.get(name))
    if differences:
        raise ValueError('Customer revision changes protected platform source: ' + ', '.join(differences[:10]))
    # A dirty installation must not supply different runtime/build helpers from Git.
    changed = git(root, 'status', '--porcelain=v1', '-z', '--untracked-files=all').split(b'\0')
    for row in changed:
        if row:
            name = row[3:].decode('utf-8')
            if not application_path(name) and not excluded(name):
                raise ValueError('Installation has modified/untracked protected source; inspect before deployment.')


@contextmanager
def fetch(root, state, target='main', installed=None):
    target = validate_target(target)
    _, url = application_remote(root)
    with tempfile.TemporaryDirectory(prefix='customer-source-', dir=state) as directory:
        source = Path(directory)
        git(source, 'init', '--bare', '--quiet')
        if re.fullmatch('[a-f0-9]{40}', target):
            ref = target
        else:
            candidates = [target] if target.startswith('refs/') else ['refs/heads/' + target, 'refs/tags/' + target]
            rows = git(source, 'ls-remote', '--refs', '--', url, *candidates).decode().splitlines()
            refs = {row.split('\t', 1)[1] for row in rows}
            if len(refs) != 1:
                raise ValueError('Customer target is missing or ambiguous; select an explicit refs/heads/... or refs/tags/... name.')
            ref = refs.pop()
        git(source, 'fetch', '--quiet', '--no-tags', '--no-recurse-submodules', '--', url, ref)
        sha = git(source, 'rev-parse', '--verify', 'FETCH_HEAD^{commit}').decode().strip()
        if not re.fullmatch('[a-f0-9]{40}', sha) or (re.fullmatch('[a-f0-9]{40}', target) and sha != target):
            raise ValueError('Fetched customer commit identity differs from the requested target.')
        verify_platform(root, source, sha, installed)
        yield source, sha
