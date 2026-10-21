"""Fetch customer Git into disposable staging; never alter installation Git history."""
from contextlib import contextmanager
import os
import posixpath
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
            if any(part in ('', '.', '..') or part.lower() == '.git' for part in path.split('/')) or '\\' in path or any(ord(char) < 32 or ord(char) == 127 for char in path):
                raise ValueError('Unsafe source path in customer revision.')
            mode, kind, identity = metadata.decode('ascii').split()
            if excluded(path):
                raise ValueError('Owner-only source is not part of a customer revision.')
            if mode not in ('100644', '100755', '120000') or kind != 'blob' or (application_path(path) and mode not in ('100644', '100755')):
                raise ValueError('Submodules and application symlinks are unsupported: ' + path)
            entries[path] = (mode, identity)
            if len(entries) > 50000:
                raise ValueError('Customer source exceeds 50000 files.')
    for name, (mode, _) in entries.items():
        if mode != '120000':
            continue
        visited = set()
        current = name
        while entries[current][0] == '120000':
            if current in visited or len(visited) >= 32:
                raise ValueError('Cyclic/oversized customer symlink chain.')
            visited.add(current)
            target = git(root, 'cat-file', 'blob', entries[current][1]).decode('utf-8')
            if target.startswith('/') or '\\' in target or any(ord(c) < 32 or ord(c) == 127 for c in target):
                raise ValueError('Unsafe customer symlink target.')
            current = posixpath.normpath(posixpath.join(posixpath.dirname(current), target))
            if current not in entries:
                raise ValueError('Customer symlink escapes source or has no tracked file target.')
    return entries


def validate_release(installed):
    """A recorded immutable deployment must remain its own exact commit."""
    from repository_boundary import receipt
    recorded = receipt(installed)
    if (git(installed, 'rev-parse', 'HEAD').decode().strip() != recorded.get('commit')
            or git(installed, 'status', '--porcelain=v1', '--untracked-files=all').strip()):
        raise ValueError('Successful release checkout is modified; inspect its source/evidence before deployment.')


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
        tree(source, sha)
        if installed is not None:
            validate_release(installed)
        git(source, 'update-ref', 'refs/heads/selected', sha)
        git(source, 'symbolic-ref', 'HEAD', 'refs/heads/selected')
        yield source, sha
