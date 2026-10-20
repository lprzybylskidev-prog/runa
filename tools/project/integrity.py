"""Trusted Git release inventory and read-only installed source integrity.

No network, repair, baseline refresh, Git mutation or application bootstrap in report.
The operator controls the local acquisition receipt/Git store; no signature is implied.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import sys

sys.dont_write_bytecode = True

from repository_boundary import OWNER_PATHS, receipt, write_staging_receipt

MAX_FILE = 512 * 1024 ** 2
MAX_ENTRIES = 100000
BASELINE = '.git/runa-ownership.json'
COMPOSITION = ('src/composer.json', 'src/package.json', 'src/composer.lock', 'src/pnpm-lock.yaml')
PRIVATE = ('.env', '.env.testing', '.env.dev.local', '.env.dev.database', '.env.prod', '.env.prod.database', '.env.prod.compose', '.env.prod.backup')
PRUNED = ('.git', '.runtime', '.pnpm-store', 'src/.runtime', 'src/vendor', 'src/node_modules', 'artifacts/playwright', 'coverage',
          'src/storage/app/application', 'src/storage/app/runa', 'src/storage/framework/testing')


class IntegrityError(ValueError):
    """Fixed diagnostics; file content, external errors and secrets stay private."""


def under(name, root):
    return name == root or name.startswith(root + '/')


def category(name):
    if under(name, 'src/app'):
        return 'customer'
    if any(under(name, path) for path in OWNER_PATHS):
        return 'owner_distribution'
    if name in COMPOSITION:
        return 'composition'
    if name in PRIVATE or any(under(name, path) for path in PRUNED):
        return 'generated'
    if name == '.devcontainer/compose.resources.yaml':
        return 'generated'
    if re.fullmatch(r'src/bootstrap/cache/(?:config|events|packages|services|routes-v7)\.php', name):
        return 'generated'
    if re.fullmatch(r'src/runa/resources/views/errors/generated/(?:403|404|419|422|429|500|503)-(?:pl|en)-(?:light|dark|system)-(?:runa|custom)\.html', name):
        return 'generated'
    if any(under(name, path) for path in ('src/public/build', 'src/public/runa-error', 'src/public/ui-review')):
        if Path(name).suffix.lower() in ('.php', '.phtml', '.phar'):
            return 'protected'
        return 'generated'
    if re.fullmatch(r'src/storage/(?:logs/[A-Za-z0-9_.-]+\.log|framework/(?:health/[a-z-]+\.json|sessions/[A-Za-z0-9]+|views/[a-f0-9]{40}\.php|cache/data/(?:[a-f0-9]{2}/){2}[a-f0-9]{40}))', name):
        return 'generated'
    if any(under(name, path) for path in ('docker', '.devcontainer')) or name in ('.dockerignore', 'compose.yaml', 'compose.production.yaml') or (name.startswith('.env.') and name.endswith('.example')):
        return 'infrastructure'
    return 'protected'


def git(root, *args, input_data=None):
    env = {key: value for key, value in os.environ.items() if not key.startswith('GIT_')}
    env.update(GIT_OPTIONAL_LOCKS='0', GIT_NO_REPLACE_OBJECTS='1', GIT_NO_LAZY_FETCH='1')
    try:
        return subprocess.run(['git', '--no-optional-locks', '-C', str(root), '-c', 'core.fsmonitor=false',
                               '-c', 'core.hooksPath=/dev/null', '-c', 'protocol.allow=never', *args], input=input_data,
                              capture_output=True, check=True, env=env, timeout=60).stdout
    except (OSError, subprocess.SubprocessError):
        raise IntegrityError('Trusted release Git objects are unavailable or invalid; no baseline was inferred from installed files.') from None


def release_inventory(root, commit):
    if not isinstance(commit, str) or re.fullmatch('[a-f0-9]{40}', commit) is None:
        raise IntegrityError('A full acquired release commit is required.')
    records = git(root, 'ls-tree', '-rlz', '--full-tree', commit).split(b'\0')
    entries, total, names = [], 0, set()
    for row in records:
        if not row:
            continue
        metadata, raw_name = row.split(b'\t', 1)
        mode, kind, oid, raw_size = metadata.decode().split()
        name = raw_name.decode('utf-8')
        if mode not in ('100644', '100755', '120000') or kind != 'blob' or not raw_size.isdigit():
            raise IntegrityError('Unsupported release object type.')
        if name.startswith('/') or '\\' in name or any(part in ('', '.', '..', '.git') for part in name.split('/')) or any(ord(char) < 32 for char in name):
            raise IntegrityError('Unsafe release inventory path.')
        if name in names:
            raise IntegrityError('Duplicate release inventory path.')
        names.add(name)
        total += int(raw_size)
        if total > MAX_FILE or len(entries) >= MAX_ENTRIES:
            raise IntegrityError('Release inventory exceeds supported bounds.')
        entries.append((name, mode, oid, int(raw_size)))
    # Batch immutable objects rather than starting a process for each file.
    raw = git(root, 'cat-file', '--batch', input_data=''.join(entry[2] + '\n' for entry in entries).encode())
    offset, files, digest = 0, [], hashlib.sha256()
    for name, mode, oid, size in entries:
        end = raw.index(b'\n', offset)
        if raw[offset:end] != (oid + ' blob ' + str(size)).encode():
            raise IntegrityError('Release object batch differs from trusted tree.')
        offset = end + 1
        content = raw[offset:offset + size]
        offset += size + 1
        if len(content) != size or hashlib.sha1(b'blob ' + str(size).encode() + b'\0' + content).hexdigest() != oid:
            raise IntegrityError('Release object hash mismatch.')
        value = hashlib.sha256(content).hexdigest()
        files.append({'path': name, 'mode': mode, 'sha256': value})
        digest.update(json.dumps([name, mode, value], separators=(',', ':')).encode() + b'\n')
    return files, digest.hexdigest()


def selected(name, profile):
    if category(name) == 'owner_distribution':
        return False
    if profile == 'source':
        return True
    if profile != 'production':
        raise IntegrityError('Unsupported installed projection profile.')
    from operations import SPARSE
    return any(under(name, rule.strip('/')) if rule.endswith('/') else name == rule.lstrip('/') for rule in SPARSE)


def manifest(data, files, digest, profile):
    return {'schema_version': 1, 'policy_version': 1, 'commit': data['commit'], 'source_sha256': digest,
            'projection': data['projection'], 'profile': profile, 'files': files}


def seal(root, profile='source'):
    """Acquisition/staging/install only: derive from exact trusted Git, never live bytes."""
    data = receipt(root)
    files, digest = release_inventory(root, data['commit'])
    if digest != data['source_sha256']:
        raise IntegrityError('Acquired full-release digest differs from trusted objects.')
    if profile not in ('source', 'production') or (profile == 'production' and data['mode'] != 'prod'):
        raise IntegrityError('Projection profile does not belong to this acquisition.')
    data['installed_profile'] = profile
    value = manifest(data, files, digest, profile)
    path = Path(root) / BASELINE
    if path.is_symlink():
        raise IntegrityError('Ownership baseline must not be a symlink.')
    temporary = path.with_suffix('.tmp')
    descriptor = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
    try:
        with os.fdopen(descriptor, 'w') as stream:
            json.dump(value, stream, separators=(',', ':'))
            stream.write('\n')
        temporary.replace(path)
        write_staging_receipt(root, data)
    finally:
        temporary.unlink(missing_ok=True)


def read_at(root_fd, name, limit=MAX_FILE):
    """Open every component relative to an anchored descriptor, never follow links."""
    descriptor = os.dup(root_fd)
    try:
        parts = name.split('/')
        for part in parts[:-1]:
            child = os.open(part, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=descriptor)
            os.close(descriptor)
            descriptor = child
        leaf = os.open(parts[-1], os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=descriptor)
        with os.fdopen(leaf, 'rb') as stream:
            info = os.fstat(stream.fileno())
            if not stat.S_ISREG(info.st_mode) or info.st_size > limit:
                raise IntegrityError('Integrity input is not a bounded regular file.')
            content = stream.read(limit + 1)
            if len(content) > limit:
                raise IntegrityError('Integrity input exceeds supported bounds.')
            return content, '100755' if info.st_mode & 0o111 else '100644'
    finally:
        os.close(descriptor)


def walk(descriptor, prefix='', budget=None):
    budget = [0] if budget is None else budget
    if prefix.count('/') > 64:
        raise IntegrityError('Installed inventory depth exceeds supported bounds.')
    for entry in sorted(os.scandir(descriptor), key=lambda value: value.name):
        budget[0] += 1
        if budget[0] > MAX_ENTRIES:
            raise IntegrityError('Installed inventory exceeds supported bounds.')
        name = prefix + entry.name
        if entry.is_symlink():
            yield name, 'symlink', os.readlink(entry.name, dir_fd=descriptor)
        elif entry.is_dir(follow_symlinks=False):
            if category(name) == 'customer' or name in PRUNED:
                continue
            child = os.open(entry.name, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=descriptor)
            try:
                yield from walk(child, name + '/', budget)
            finally:
                os.close(child)
        elif entry.is_file(follow_symlinks=False):
            yield name, 'file', None
        else:
            yield name, 'special', None


def require_protected(report):
    if not report['protected_ok']:
        raise IntegrityError('Protected-source integrity failed; update is blocked. No files were repaired.')


def report(root):
    root = Path(root)
    data = receipt(root)
    if git(root, 'rev-parse', '--verify', 'HEAD').decode().strip() != data.get('commit'):
        raise IntegrityError('Installed checkout differs from acquisition commit; ownership baseline is stale.')
    descriptor = os.open(root, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
    try:
        baseline = json.loads(read_at(descriptor, BASELINE, 32 * 1024 ** 2)[0])
        files, digest = release_inventory(root, data.get('commit'))
        profile = data.get('installed_profile', 'source')
        if profile not in ('source', 'production') or (profile == 'production' and data.get('mode') != 'prod'):
            raise IntegrityError('Ownership profile is inconsistent with acquired installation.')
        if digest != data.get('source_sha256') or baseline != manifest(data, files, digest, profile):
            raise IntegrityError('Ownership baseline is stale, modified or inconsistent with trusted release objects/projection.')
        expected = {entry['path']: entry for entry in files if selected(entry['path'], profile) and category(entry['path']) not in ('customer', 'generated')}
        changes, seen = [], set()
        for count, (name, kind, target) in enumerate(walk(descriptor)):
            if count >= MAX_ENTRIES:
                raise IntegrityError('Installed inventory exceeds supported bounds.')
            owner = category(name)
            if kind == 'special':
                seen.add(name)
                changes.append({'path': name, 'category': 'protected', 'change': 'unsafe_or_unreadable'})
                continue
            cache = re.fullmatch(r'(tools/(?:project|knowledge|testing)(?:/[^/]+)*)/__pycache__/([A-Za-z_][A-Za-z_0-9]*)\.cpython-[0-9]{2,3}(?:\.opt-[12])?\.pyc', name)
            if cache and cache[1] + '/' + cache[2] + '.py' in expected and kind != 'symlink':
                continue
            entry = expected.get(name)
            if owner in ('customer', 'generated') and kind != 'symlink':
                continue
            seen.add(name)
            if not entry:
                changes.append({'path': name, 'category': owner if owner in ('infrastructure', 'composition') else 'protected', 'change': 'unsafe_symlink' if kind == 'symlink' else 'unexpected'})
                continue
            try:
                if kind == 'symlink':
                    content, mode = target.encode(), '120000'
                    # Full release acquisition already checks targets. Never follow one here.
                    if target.startswith('/') or '\\' in target:
                        raise IntegrityError('Unsafe installed symlink.')
                else:
                    content, mode = read_at(descriptor, name)
                if mode != entry['mode'] or hashlib.sha256(content).hexdigest() != entry['sha256']:
                    changes.append({'path': name, 'category': owner, 'change': 'modified'})
            except (OSError, IntegrityError):
                changes.append({'path': name, 'category': owner, 'change': 'unsafe_or_unreadable'})
        changes += [{'path': name, 'category': category(name), 'change': 'missing'} for name in expected.keys() - seen]
        from dependencies import compose
        composition_valid = True
        for kind in ('composer', 'package'):
            name = 'src/' + kind + '.json'
            if name not in expected:
                continue
            try:
                platform = json.loads(read_at(descriptor, 'src/runa/' + kind + '.json', 1024 ** 2)[0])
                application = json.loads(read_at(descriptor, 'src/app/' + kind + '.json', 1024 ** 2)[0])
                actual = json.loads(read_at(descriptor, name, 1024 ** 2)[0])
                if actual != compose(platform, application, kind):
                    raise IntegrityError('Composed manifest does not match owner inputs.')
            except (OSError, ValueError, TypeError, KeyError):
                composition_valid = False
                changes.append({'path': name, 'category': 'composition', 'change': 'invalid_composition'})
        changes.sort(key=lambda row: (row['path'], row['change']))
        protected_ok = not any(row['category'] == 'protected' for row in changes)
        return {'schema_version': 1, 'commit': data['commit'], 'source_sha256': digest, 'profile': profile,
                'checked_files': len(expected), 'protected_ok': protected_ok, 'ok': not changes,
                'update_blocked': bool(changes), 'composition_valid': composition_valid, 'changes': changes,
                'ownership_counts': {kind: sum(category(entry['path']) == kind for entry in files) for kind in ('protected', 'infrastructure', 'composition', 'customer', 'generated', 'owner_distribution')}}
    finally:
        os.close(descriptor)


def main(arguments, root=None):
    parser = argparse.ArgumentParser(description='Read-only trusted release integrity; never repairs or accepts new exclusions/baselines.')
    parser.add_argument('--json', action='store_true', help='Emit structured report (also on failure)')
    options = parser.parse_args(arguments)
    try:
        result = report(root or Path(__file__).resolve().parents[2])
    except (ValueError, OSError, KeyError, TypeError, AttributeError) as error:
        result = {'schema_version': 1, 'ok': False, 'protected_ok': False, 'update_blocked': True,
                  'error': str(error) if isinstance(error, IntegrityError) else 'Trusted ownership baseline unavailable/invalid; no files were repaired.'}
    if options.json:
        print(json.dumps(result, sort_keys=True))
    elif 'error' in result:
        print('FAIL: ' + result['error'])
    else:
        print(('PASS' if result['ok'] else 'FAIL') + ': integrity at ' + result['commit'] + '; no files changed.')
        for row in result['changes']:
            print(row['category'] + ': ' + row['change'] + ' ' + json.dumps(row['path'], ensure_ascii=True))
    return 0 if result['ok'] else 1


if __name__ == '__main__':
    raise SystemExit(main(sys.argv[1:]))
