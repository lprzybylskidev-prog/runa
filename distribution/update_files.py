"""Secret-safe three-way inventory of independently acquired customer projections."""
import hashlib
import json
import os
from pathlib import PurePosixPath
import stat
import subprocess

from update_contracts import Blocker, OWNER_PATHS

PRESERVED_FILES = {'src/composer.json', 'src/composer.lock', 'src/package.json', 'src/pnpm-lock.yaml', 'src/pnpm-workspace.yaml'}
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


def review(root, original, target, releases=None, local_overrides=None, infrastructure_inputs=()):
    original_names, target_names, local_names = tracked(original), tracked(target), tracked(root)
    rows = []
    infrastructure_inputs = set(infrastructure_inputs)
    for name in sorted(original_names | target_names | local_names | infrastructure_inputs):
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
            if name in local_names or name in infrastructure_inputs:
                rows.append({'path': name, 'ownership': owner, 'action': 'preserve',
                             'local': 'added', 'upstream': 'unchanged', 'conflict': None,
                             'current': identity(root, name) if name in infrastructure_inputs else None})
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


def actions(reviewed):
    import uuid
    return [{**row, 'id': uuid.uuid4().hex, 'state': 'planned'} for row in reviewed['files']
            if row['action'] in ('add', 'update', 'remove')]


def renew_actions(reviewed, journal):
    """Keep normalized completed actions; renew only actual new local differences."""
    prior = {row['path']: row for row in journal.get('file_actions', [])}
    current = []
    for row in actions(reviewed):
        old = prior.get(row['path'])
        if old and all(old[key] == row[key] for key in ('action', 'original', 'current', 'target')):
            current.append(old)
            prior.pop(row['path'])
        else:
            current.append(row)
    journal.setdefault('file_action_history', []).extend(prior.values())
    journal['file_actions'] = current


def validate_staging(root, operation, original, target, journal):
    """Verify retained bytes before normalizing known interrupted file transitions."""
    base = operation / 'project-files'
    all_actions = [*journal.get('file_action_history', []), *journal.get('file_actions', [])]
    ids = set()
    overrides = {}
    for row in all_actions:
        name = safe_name(row['path'])
        token = row.get('id', '')
        if (ownership(name) not in ('delivered', 'instructions') or len(token) != 32
                or any(c not in '0123456789abcdef' for c in token) or token in ids
                or row.get('action') not in ('add', 'update', 'remove')
                or row.get('state') not in ('planned', 'replacing', 'done')):
            raise Blocker('project-journal-changed', 'Invalid delivered-file action journal.')
        ids.add(token)
        if row['original'] != identity(original, name) or row['target'] != identity(target, name):
            raise Blocker('project-journal-changed', 'File action differs from independent release projections.')
        folder = base / token
        if folder.is_symlink() or folder.exists() and not folder.is_dir():
            raise Blocker('project-staging-changed', 'Unsafe file action staging directory.')
        if folder.exists():
            if any(p.name not in ('incoming', 'previous') for p in folder.iterdir()):
                raise Blocker('project-staging-changed', 'Unexpected file action staging content.')
            for part, expected in (('incoming', row['target']), ('previous', row['current'])):
                actual = identity(folder, part)
                if actual is not None and actual != expected:
                    raise Blocker('project-staging-changed', 'Retained delivered-file bytes changed.')
        if row in journal.get('file_action_history', []):
            continue
        actual = identity(root, name)
        if row['state'] == 'replacing':
            previous, incoming = identity(folder, 'previous'), identity(folder, 'incoming')
            if (actual not in (row['current'], row['target'], None)
                    or row['current'] is not None and previous is None and actual != row['current']
                    or row['target'] is not None and incoming is None and actual != row['target']):
                raise Blocker('project-transition-changed', 'Interrupted file transition no longer matches its recorded identities.')
            overrides[name] = row['current']
        elif row['state'] == 'done' and actual == row['target']:
            overrides[name] = row['current']
    if base.is_symlink() or base.exists() and not base.is_dir():
        raise Blocker('project-staging-changed', 'Unsafe delivered-file staging root.')
    if base.exists() and any(p.name not in ids for p in base.iterdir()):
        raise Blocker('project-staging-changed', 'Unrecognized delivered-file staging is preserved for inspection.')
    return overrides


def apply_actions(root, operation, target, journal, save, move):
    """Fenced per-file forward replacement with before/after durable identities."""
    import shutil
    base = operation / 'project-files'
    base.mkdir(mode=0o700, exist_ok=True)
    for row in journal.get('file_actions', []):
        if row['state'] == 'done':
            if identity(root, row['path']) != row['target']:
                raise Blocker('project-file-changed', 'Completed file changed; obtain a fresh reviewed reconciliation.')
            continue
        folder = base / row['id']
        folder.mkdir(mode=0o700, exist_ok=True)
        incoming, previous = folder / 'incoming', folder / 'previous'
        destination = root / row['path']
        if row['state'] == 'planned':
            if identity(root, row['path']) != row['current']:
                raise Blocker('project-file-changed', 'Reviewed local file changed before replacement.')
            if row['target'] is not None:
                if identity(folder, 'incoming') is None:
                    source = target / row['path']
                    if source.is_symlink():
                        incoming.symlink_to(os.readlink(source))
                    else:
                        shutil.copyfile(source, incoming, follow_symlinks=False)
                        incoming.chmod(0o755 if row['target']['mode'] == '100755' else 0o644)
                        with incoming.open('rb') as stream:
                            os.fsync(stream.fileno())
                if identity(folder, 'incoming') != row['target']:
                    raise Blocker('project-staging-changed', 'Prepared file does not match the verified target.')
            destination.parent.mkdir(parents=True, exist_ok=True)
            if destination.parent.stat().st_dev != folder.stat().st_dev:
                raise Blocker('project-filesystem', 'Atomic file replacement requires the operation filesystem.')
            row['state'] = 'replacing'
            save()
        # Resume each rename from independently verified before/after positions.
        actual = identity(root, row['path'])
        if row['current'] is not None and identity(folder, 'previous') is None:
            if actual != row['current']:
                raise Blocker('project-file-changed', 'Local file no longer matches reviewed before-state.')
            move(destination, previous)
            actual = None
        if row['target'] is not None:
            if actual is None:
                if identity(folder, 'incoming') != row['target']:
                    raise Blocker('project-staging-changed', 'Missing verified incoming delivered file.')
                move(incoming, destination)
            elif actual != row['target']:
                raise Blocker('project-file-changed', 'Delivered file changed during replacement.')
        elif actual is not None:
            raise Blocker('project-file-changed', 'Reviewed file removal found unexpected content.')
        row['state'] = 'done'
        save()
