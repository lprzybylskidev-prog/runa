#!/usr/bin/env python3
"""External owner preflight/plan. Downloads outside the installation; never applies updates."""
import argparse
from contextlib import contextmanager
import fcntl
import getpass
import json
import os
from pathlib import Path
import re
import shutil
import signal
import stat
import sys
import tempfile

import bootstrap
from update_contracts import (Blocker, OWNER_PATHS, SEMVER, compare, changes, compose, document,
                              platform, regular, stable, supports)
import update_runtime
import update_customization


@contextmanager
def operation_snapshot(root):
    """Observe existing locks without creating a lock/state directory in dry-run."""
    path = root / '.runtime/production/operation.lock'
    stream = None
    try:
        if path.exists() or path.is_symlink():
            if any(p.is_symlink() for p in [path, *path.parents]):
                raise Blocker('unsafe-lock', 'Operation lock must not traverse symlinks.')
            descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
            stream = os.fdopen(descriptor, 'rb')
            info = os.fstat(stream.fileno())
            if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077:
                raise Blocker('unsafe-lock', 'Operation lock must be a private operator-owned regular file.')
            try:
                fcntl.flock(stream, fcntl.LOCK_SH | fcntl.LOCK_NB)
            except BlockingIOError:
                raise Blocker('operation-running', 'Another installation/deployment/update operation is running.') from None
        yield
    finally:
        if stream:
            stream.close()


def installation(value):
    root = Path(value).absolute()
    if root.resolve() != root or not root.is_dir() or root.stat().st_uid != os.getuid() or root.stat().st_mode & 0o022:
        raise Blocker('installation-ownership', 'Select an existing operator-owned installation without symlinks or group/other write access.')
    if not (root / '.git').is_dir() or (root / '.git').is_symlink():
        raise Blocker('installation-identity', 'Select a customer Git checkout with real local metadata.')
    for name in OWNER_PATHS:
        if (root / name).exists() or (root / name).is_symlink():
            raise Blocker('owner-source-present', 'Owner-only source is materialized in the installation: ' + name)
    marker = root / '.git/runa-bootstrap.json'
    if marker.exists() or marker.is_symlink():
        receipt = document(root, '.git/runa-bootstrap.json', 32768)
        if receipt.get('mode') != 'dev':
            raise Blocker('development-only', 'The Runa updater operates exclusively on development checkouts; deploy customer commits to production.')
    if (root / '.env.prod.compose').exists() or (root / '.env.prod.compose').is_symlink():
        raise Blocker('development-only', 'Production configuration cannot be an updater target.')
    values = update_runtime.env_file(root, '.env')
    if values.get('APP_ENV', '').strip("\"'") != 'local':
        raise Blocker('development-only', 'Configure the customer development environment before updating.')
    if (root / '.runtime/production/in-progress.json').exists() or (root / '.runtime/production/in-progress.json').is_symlink():
        raise Blocker('unresolved-operation', 'Unresolved operation state blocks planning; preserve recovery evidence.')
    return root


def candidate(root, target, directory):
    destination = directory / 'candidate'
    (destination / 'src').mkdir(parents=True)
    for kind in ('composer', 'package'):
        app = document(root, 'src/app/' + kind + '.json')
        result = compose(document(target, 'src/runa/' + kind + '.json'), app, kind)
        current = compose(document(root, 'src/runa/' + kind + '.json'), app, kind)
        if document(root, 'src/' + kind + '.json') != current:
            raise Blocker('dependency-composition', 'Installed composed dependency manifest is stale: ' + kind)
        (destination / 'src' / (kind + '.json')).write_text(json.dumps(result, indent=2) + '\n')
    for name in ('composer.lock', 'pnpm-lock.yaml'):
        (destination / 'src' / name).write_bytes(regular(root, 'src/' + name, 32 * 1024 ** 2))
    (destination / 'src/pnpm-workspace.yaml').write_bytes(regular(root, 'src/pnpm-workspace.yaml'))
    return destination


def plan(root, target, original, target_identity, installed_identity, runtime):
    delta = changes(original, target)
    migrations = sorted(p.stem for p in (target / 'src/runa/database/migrations').glob('*.php'))
    applied = set(runtime['migrations'])
    changed_applied = [row['path'] for row in delta if row['path'].startswith('src/runa/database/migrations/') and Path(row['path']).stem in applied]
    if changed_applied:
        raise Blocker('applied-migration-changed', 'Target changes/removes applied platform migrations: ' + ', '.join(changed_applied[:10]))
    seeder = 'Runa\\Database\\Seeders\\DatabaseSeeder'
    seed_done = any(row.get('release_sha') == target_identity['commit'] and row.get('seeder') == seeder for row in runtime['seed_runs'])
    return {'no_op': target_identity['commit'] == installed_identity['commit'] and not (set(migrations) - applied) and seed_done, 'source_changes': delta,
            'platform_migrations': [name for name in migrations if name not in applied],
            'platform_seeder': {'class': seeder, 'release': target_identity['commit'], 'already_completed': seed_done},
            'customer_inputs': 'preserved; reconcile scaffold/infrastructure manually from release instructions',
            'steps': ['preflight-passed', 'maintenance', 'staged/built', 'platform-migrations', 'platform-seeders/upgrades', 'switch', 'health/isolation', 'completed'],
            'preserve': ['src/app', 'docs/app', 'application identity/version', 'application dependency declarations/locks',
                         'private environment inputs', 'both durable storage trees', 'customer Git remotes/history'],
            'uncertainties': ['Migration/seed bodies are not executed to predict effects.', 'Image build disk use is an estimate.', 'Offline lock consistency is not a supply-chain audit; real build/install policies must still pass.',
                              'Preflight is a snapshot, not a reservation; execution must revalidate.', 'Code rollback cannot reverse schema/data.']}


def preflight(value, client, tag, *, toolchain='runa-development:dev', download=bootstrap.acquire,
              runtime_probe=update_runtime.probe, lock_probe=update_runtime.validate_locks, expected_commit=None):
    report = {'schema_version': 1, 'mode': 'preflight', 'dry_run': True, 'status': 'blocked', 'checks': [],
              'execution_available': False, 'mismatches': [], 'temporary_downloads': [], 'temporary_downloads_cleaned': False}
    def passed(name):
        report['checks'].append({'id': name, 'status': 'passed'})
    try:
        root = installation(value)
        if Path(__file__).resolve().is_relative_to(root):
            raise Blocker('owner-tool-location', 'Run the separately supplied owner updater outside the customer installation.')
        version = platform(root)
        stable(tag.removeprefix('v'))
        if not re.fullmatch(bootstrap.TAG, tag):
            raise Blocker('target-release', 'Select an exact stable published target release.')
        report['installed_version'], report['target_version'] = version, tag[1:]
        if stable(tag[1:]) < stable(version):
            raise Blocker('downgrade-unsupported', 'A downgrade has unproven schema/data compatibility; use reviewed recovery rather than an update plan.')
        passed('installation-identity')
        with operation_snapshot(root):
            temporary_parent = Path(os.environ.get('TMPDIR', '/tmp')).resolve(strict=True)
            if temporary_parent.is_relative_to(root):
                raise Blocker('unsafe-staging', 'Temporary release downloads must stay outside the installation.')
            with tempfile.TemporaryDirectory(prefix='runa-update-plan-', dir=temporary_parent) as temporary:
                staging = Path(temporary).resolve()
                if staging.is_relative_to(root):
                    raise Blocker('unsafe-staging', 'Temporary release downloads must stay outside the installation.')
                report['temporary_downloads'] = [str(staging / 'original'), str(staging / 'target')]
                original, target = staging / 'original', staging / 'target'
                installed_identity = download(client, 'v' + version, original, 'dev')
                if platform(original) != version:
                    raise Blocker('original-release', 'Original release platform identity does not match the installed version.')
                report['installed_release'] = {key: installed_identity[key] for key in ('tag', 'commit', 'tag_object')}
                passed('original-release')
                report['mismatches'] = compare(root, original)
                if report['mismatches']:
                    raise Blocker('protected-source-mismatch', 'Protected source differs from the independently downloaded installed release; no repair or consent override exists.')
                passed('protected-source')
                target_identity = download(client, tag, target, 'dev', expected_commit=expected_commit)
                if platform(target) != tag[1:]:
                    raise Blocker('target-release', 'Target release platform identity does not match its tag.')
                report['target_release'] = {key: target_identity[key] for key in ('tag', 'commit', 'tag_object')}
                passed('target-release')
                applications = [('development', root)]
                for location, source in applications:
                    application = document(source, 'src/app/application.json', 16384)
                    if (type(application.get('schema_version')) is not int or application.get('schema_version') != 1
                            or not isinstance(application.get('version'), str) or not re.fullmatch(SEMVER, application['version'])
                            or not supports(application.get('runa'), tag[1:])):
                        raise Blocker('application-incompatible', 'Application manifest schema/range does not support the selected platform release: ' + location)
                passed('application-compatibility')
                free = shutil.disk_usage(root).free
                if free < bootstrap.MIN_FREE or not os.access(root, os.W_OK | os.X_OK):
                    raise Blocker('disk-permissions', 'Installation needs an owned writable directory and at least 5 GiB free space; builds may require more.')
                report['free_bytes'] = free
                passed('disk-permissions')
                report['dependency_checks'] = []
                for location, source in applications:
                    report['toolchain_image'] = lock_probe(candidate(source, target, staging / location), toolchain)
                    report['dependency_checks'].append(location)
                passed('preserved-dependency-locks')
                runtime = runtime_probe(root, 'dev', root)
                report['runtime'] = {'project': runtime['project'], 'services': runtime['services']}
                passed('runtime-and-database')
                report['plan'] = plan(root, target, original, target_identity, installed_identity, runtime)
                report['customization_review'] = update_customization.review(root, original)
                passed('infrastructure-review')
                report['status'] = 'ready'
    except Blocker as error:
        report['checks'].append({'id': error.code, 'status': 'blocked', 'message': str(error)})
    except bootstrap.BootstrapError as error:
        report['checks'].append({'id': 'release-acquisition', 'status': 'blocked', 'message': str(error)})
    except (OSError, ValueError, KeyError, IndexError, AttributeError, TypeError, SyntaxError, RuntimeError):
        report['checks'].append({'id': 'invalid-preflight-input', 'status': 'blocked', 'message': 'Preflight input/probe is unavailable or unsupported; no update was executed.'})
    finally:
        report['temporary_downloads_cleaned'] = all(not Path(p).parent.exists() for p in report['temporary_downloads'])
    return report


def main(arguments=None):
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument('--preflight', action='store_true')
    mode.add_argument('--plan', action='store_true')
    parser.add_argument('--installation', required=True)
    parser.add_argument('--target', required=True, help='Exact published stable vX.Y.Z release')
    parser.add_argument('--repository', default=bootstrap.REPOSITORY, help='Explicitly authorized GitHub owner/name')
    parser.add_argument('--toolchain-image', default='runa-development:dev', help='Existing trusted owner toolchain image; no automatic pull/build')
    parser.add_argument('--expected-commit', help='Independently supplied target release commit')
    parser.add_argument('--json', action='store_true')
    credentials = parser.add_mutually_exclusive_group()
    credentials.add_argument('--token-file')
    credentials.add_argument('--ask-token', action='store_true')
    args = parser.parse_args(arguments)
    token = ''
    try:
        if args.ask_token and not sys.stdin.isatty():
            raise bootstrap.BootstrapError('Hidden credentials require an interactive terminal; use --token-file for automation.')
        token = bootstrap.token_from_file(args.token_file) if args.token_file else bootstrap.validate_token(getpass.getpass('GitHub read token (not saved): ')) if args.ask_token else ''
        if args.expected_commit and not re.fullmatch(bootstrap.SHA, args.expected_commit):
            raise bootstrap.BootstrapError('Expected target commit requires a full lowercase SHA.')
        bootstrap.host_checks()
        result = preflight(args.installation, bootstrap.GitHub(args.repository, token), args.target,
                           toolchain=args.toolchain_image, expected_commit=args.expected_commit)
    except bootstrap.BootstrapError as error:
        result = {'schema_version': 1, 'dry_run': True, 'status': 'blocked', 'execution_available': False,
                  'mismatches': [], 'temporary_downloads': [], 'temporary_downloads_cleaned': True,
                  'checks': [{'id': 'host-or-credentials', 'status': 'blocked', 'message': str(error)}]}
    result['mode'] = 'plan' if args.plan else 'preflight'
    if args.json:
        print(json.dumps(result, indent=2))
    else:
        print('Runa update ' + result['mode'] + ': ' + result['status'].upper() + ' (read-only; execution is not available).')
        for check in result['checks']:
            print(check['status'].upper() + ': ' + check['id'] + (' — ' + check['message'] if 'message' in check else ''))
        for row in result.get('mismatches', []):
            print(row['kind'] + ': ' + row['path'])
        if 'plan' in result:
            print(json.dumps(result['plan'], indent=2))
        print('Temporary release downloads outside installation: ' + ('cleaned.' if result['temporary_downloads_cleaned'] else 'cleanup requires operator inspection.'))
    return 0 if result['status'] == 'ready' else 1


if __name__ == '__main__':
    def interrupt(signum, frame):
        raise KeyboardInterrupt
    signal.signal(signal.SIGTERM, interrupt)
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print('Preflight interrupted; temporary staging cleaned, installation unchanged.', file=sys.stderr)
        raise SystemExit(130)
