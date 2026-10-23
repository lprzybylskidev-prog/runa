"""Secret-safe three-way inventory of independently acquired customer projections."""
import hashlib
import json
import os
from pathlib import PurePosixPath
import stat
import subprocess

from update_contracts import Blocker, OWNER_PATHS

PRESERVED_FILES = {'src/composer.json', 'src/composer.lock', 'src/package.json', 'src/pnpm-lock.yaml'}
GENERATED = {'.devcontainer/compose.resources.yaml'}
PRIVATE_PARTS = {'vendor', 'node_modules', '.git', '.runtime', 'storage', 'artifacts', 'coverage', 'backups', 'cache'}


def safe_name(name):
    path = PurePosixPath(name)
    if (not name or path.is_absolute() or path.as_posix() != name or '..' in path.parts
            or '\\' in name or any(ord(c) < 32 or ord(c) == 127 for c in name)):
        raise Blocker('unsafe-project-path', 'Delivered inventory contains an unsafe path.')
    return name


def ownership(name):
    safe_name(name)
    parts = PurePosixPath(name).parts
    if any(name == p or name.startswith(p + '/') for p in OWNER_PATHS):
        return 'owner-excluded'
    if name.startswith('src/runa/'):
        return 'protected-platform'
    if name.startswith(('src/app/', 'docs/app/')) or name in PRESERVED_FILES:
        return 'application'
    if (name in GENERATED or any(p in PRIVATE_PARTS for p in parts)
            or any((p == '.env' or p.startswith('.env.')) and not p.endswith('.example') for p in parts)):
        return 'private-or-generated'
    if name in ('AGENTS.md', 'CLAUDE.md') or name.startswith('.agents/'):
        return 'instructions'
    return 'delivered'


def tracked(root):
    result = subprocess.run(['git', '-C', str(root), 'ls-files', '-z'], check=True, capture_output=True)
    names = {safe_name(n) for n in result.stdout.decode().split('\0') if n}
    if len(names) > 50000:
        raise Blocker('project-inventory-size', 'Delivered inventory exceeds supported file count.')
    return names


def identity(root, name):
    safe_name(name)
    path = root / name
    if any(parent.is_symlink() for parent in path.parents):
        raise Blocker('unsafe-project-path', 'Delivered file parent traverses a symlink: ' + name)
    try:
        info = path.lstat()
    except FileNotFoundError:
        return None
    if stat.S_ISLNK(info.st_mode):
        data = os.readlink(path).encode()
        mode = '120000'
    elif stat.S_ISREG(info.st_mode):
        if info.st_size > 32 * 1024 ** 2:
            raise Blocker('project-inventory-size', 'Delivered file exceeds supported size: ' + name)
        # O_NOFOLLOW also rejects a link installed between lstat and open.
        descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
        with os.fdopen(descriptor, 'rb') as stream:
            opened = os.fstat(stream.fileno())
            if not stat.S_ISREG(opened.st_mode) or opened.st_size > 32 * 1024 ** 2:
                raise Blocker('unsafe-project-path', 'Delivered file changed type during review.')
            data = stream.read(32 * 1024 ** 2 + 1)
            if len(data) > 32 * 1024 ** 2:
                raise Blocker('project-inventory-size', 'Delivered file grew during review.')
        mode = '100755' if opened.st_mode & 0o111 else '100644'
    else:
        return {'mode': 'unsupported', 'sha256': None}
    return {'mode': mode, 'sha256': hashlib.sha256(data).hexdigest()}


def difference(before, after):
    return 'unchanged' if before == after else 'added' if before is None else 'removed' if after is None else 'modified'


def review(root, original, target, releases=None, local_overrides=None):
    original_names, target_names, local_names = tracked(original), tracked(target), tracked(root)
    rows = []
    for name in sorted(original_names | target_names | local_names):
        owner = ownership(name)
        if owner in ('owner-excluded', 'protected-platform', 'private-or-generated'):
            continue
        if owner == 'application':
            # Do not inspect customer domain contents. Report ownership and target changes only.
            if name in original_names or name in target_names:
                rows.append({'path': name, 'ownership': owner, 'action': 'preserve',
                             'local': 'customer-owned', 'upstream': 'customer-owned', 'conflict': None})
            continue
        before = identity(original, name) if name in original_names else None
        after = identity(target, name) if name in target_names else None
        local = identity(root, name) if before is not None or after is not None else None
        if before is None and after is None:
            if name in local_names:
                rows.append({'path': name, 'ownership': owner, 'action': 'preserve',
                             'local': 'added', 'upstream': 'unchanged', 'conflict': None})
            continue
        # Journaled known transitions may be normalized only after their real identities
        # have been independently validated by the execution layer.
        if local_overrides and name in local_overrides:
            local = local_overrides[name]
        local_change, upstream = difference(before, local), difference(before, after)
        if local_change == upstream == 'unchanged':
            continue
        action, conflict = 'preserve', None
        if local != after:
            action = 'remove' if after is None else 'add' if local is None else 'update'
            if local_change != 'unchanged':
                conflict = 'overlapping-change'
            if (before is None and local is not None or owner == 'instructions' and local_change != 'unchanged'
                    or any(i is not None and i['mode'] == 'unsupported' for i in (local, after))):
                action, conflict = 'manual-reconciliation', 'customer-content-preserved'
        rows.append({'path': name, 'ownership': owner, 'local': local_change, 'upstream': upstream,
                     'action': action, 'conflict': conflict, 'original': before, 'current': local, 'target': after})
    fingerprint = hashlib.sha256(json.dumps({'releases': releases, 'files': rows}, sort_keys=True, separators=(',', ':')).encode()).hexdigest()
    return {'files': rows, 'fingerprint': fingerprint,
            'required': any(r['local'] not in ('unchanged', 'customer-owned') or r['action'] in ('add', 'update', 'remove', 'manual-reconciliation') for r in rows),
            'manual_reconciliation': [r['path'] for r in rows if r['action'] == 'manual-reconciliation']}
