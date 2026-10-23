"""External development-only source replacement, fixed forward steps and recovery."""
from contextlib import contextmanager
from datetime import datetime, timezone
import fcntl
import json
import os
from pathlib import Path
import re
import shutil
import stat
import sys
import tempfile
import uuid

import bootstrap
import update
import update_files
import update_customization
import update_runtime
from update_contracts import Blocker, SEMVER, compare, compose, document, platform, supports

SEEDER = 'Runa\\Database\\Seeders\\DatabaseSeeder'
STEPS = ('planned', 'preflight-passed', 'customization-reviewed', 'maintenance', 'source-replaced', 'staged/built',
         'platform-migrations', 'platform-seeders/upgrades', 'platform-modules', 'switch', 'health', 'completed')


def stamp():
    return datetime.now(timezone.utc).isoformat()


def private_directory(path):
    if any(item.is_symlink() for item in [path, *path.parents]):
        raise Blocker('unsafe-operation-state', 'Update state must not traverse symlinks.')
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    info = path.stat()
    if not stat.S_ISDIR(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077:
        raise Blocker('unsafe-operation-state', 'Update state must be a private operator-owned directory.')


def atomic(path, value):
    private_directory(path.parent)
    descriptor, temporary = tempfile.mkstemp(prefix='.update-', dir=path.parent)
    try:
        with os.fdopen(descriptor, 'w') as stream:
            json.dump(value, stream, indent=2); stream.write('\n'); stream.flush(); os.fsync(stream.fileno())
        os.replace(temporary, path)
        descriptor = os.open(path.parent, os.O_RDONLY | os.O_DIRECTORY)
        try: os.fsync(descriptor)
        finally: os.close(descriptor)
    finally:
        if os.path.exists(temporary): os.unlink(temporary)


@contextmanager
def lock(root):
    state = root / '.runtime/production'
    private_directory(state)
    descriptor = os.open(state / 'operation.lock', os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    with os.fdopen(descriptor, 'a') as stream:
        info = os.fstat(stream.fileno())
        if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077:
            raise Blocker('unsafe-lock', 'Operation lock must be a private operator-owned regular file.')
        try: fcntl.flock(stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError: raise Blocker('operation-running', 'Another installation/deployment/update operation is running.') from None
        yield state


def read_journal(root, client, tag):
    name = '.runtime/production/in-progress.json'
    path = root / name
    value = document(root, name, 2 * 1024 ** 2)
    if path.stat().st_uid != os.getuid() or path.stat().st_mode & 0o077:
        raise Blocker('unsafe-operation-state', 'Update journal must be private and operator-owned.')
    if (value.get('schema_version') != 1 or value.get('kind') != 'development-update'
            or value.get('root') != str(root) or value.get('repository') != client.repository.lower()
            or value.get('target', {}).get('tag') != tag or not re.fullmatch('[a-f0-9]{32}', value.get('id', ''))
            or not re.fullmatch('[a-z0-9][a-z0-9_-]{0,127}', value.get('project', ''))
            or not isinstance(value.get('baseline'), dict)
            or value.get('phase') not in (*STEPS, 'source-replacing') or not isinstance(value.get('completed'), list)
            or any(step not in STEPS for step in value['completed'])):
        raise Blocker('journal-identity', 'Pending operation is not this development update; preserve its evidence.')
    for key in ('original', 'target'):
        identity = value.get(key, {})
        if (not re.fullmatch(bootstrap.TAG, identity.get('tag', '')) or not re.fullmatch(bootstrap.SHA, identity.get('commit', ''))
                or not re.fullmatch(bootstrap.SHA, identity.get('tag_object', ''))):
            raise Blocker('journal-identity', 'Update release identities are invalid.')
    return value


def target_inputs(root, target):
    application = document(root, 'src/app/application.json')
    if (type(application.get('schema_version')) is not int or application.get('schema_version') != 1
            or not isinstance(application.get('version'), str) or not re.fullmatch(SEMVER, application['version'])
            or not supports(application.get('runa'), platform(target))):
        raise Blocker('application-incompatible', 'Application manifest does not support the selected platform release.')
    for kind in ('composer', 'package'):
        expected = compose(document(target, 'src/runa/' + kind + '.json'), document(root, 'src/app/' + kind + '.json'), kind)
        if document(root, 'src/' + kind + '.json') != expected:
            raise Blocker('manual-scaffold-reconciliation', 'Manually reconcile the target composed ' + kind + ' manifest and locks before execution; customer scaffold is never overwritten.')


class DockerRuntime:
    def __init__(self, root):
        self.root = root
        self.args = update_runtime.compose(root, 'dev', root)
        self.project = json.loads(update_runtime.command([*self.args, 'config', '--format', 'json'], root))['name']
        if not re.fullmatch('[a-z0-9][a-z0-9_-]{0,127}', self.project):
            raise Blocker('runtime-ownership', 'Unsupported Compose project identity.')
        self.args[2:2] = ['--project-name', self.project]

    def bind(self, project):
        if project != self.project:
            raise Blocker('runtime-ownership', 'The journaled Compose project changed; preserve and reconcile its environment before resuming.')

    def command(self, arguments, timeout=1800):
        if arguments[0] != 'run':
            return update_runtime.command([*self.args, *arguments], self.root, timeout=timeout)
        # Killing a Compose client must not leave a detached data/build writer.
        name = 'runa-update-step-' + uuid.uuid4().hex
        try:
            return update_runtime.command([*self.args, 'run', '--name', name, *arguments[1:]], self.root, timeout=timeout)
        finally:
            ids = update_runtime.command(['docker', 'ps', '-aq', '--filter', 'name=^/' + name + '$']).split()
            for identity in ids:
                actual = json.loads(update_runtime.command(['docker', 'inspect', identity]))[0]
                labels = actual['Config'].get('Labels', {})
                if (actual['Name'] != '/' + name or labels.get('com.docker.compose.project') != self.project
                        or labels.get('com.docker.compose.project.working_dir') != str(self.root)
                        or labels.get('com.docker.compose.oneoff', '').lower() != 'true'):
                    raise Blocker('runtime-ownership', 'Unexpected one-off identity; preserve it for scoped inspection.')
                print('Scoped updater one-off cleanup: ' + name + ' (' + identity + ')', file=sys.stderr)
                update_runtime.command(['docker', 'rm', '--force', identity])

    def probe(self, *args):
        return update_runtime.probe(self.root, 'dev', self.root)

    def metadata(self):
        container = self.command(['ps', '--quiet', 'postgres']).strip()
        actual = json.loads(update_runtime.command(['docker', 'inspect', container]))[0]
        labels = actual['Config'].get('Labels', {})
        if labels.get('com.docker.compose.project.working_dir') != str(self.root) or labels.get('com.docker.compose.project') != self.project:
            raise Blocker('runtime-ownership', 'Database container does not belong to this checkout/project.')
        mounts = sorted((item['Type'], item.get('Name') or item.get('Source'), item['Destination'])
                        for item in actual.get('Mounts', []) if item['Type'] in ('bind', 'volume'))
        return {**update_runtime.metadata(self.root, self.args), 'database_mounts': mounts}

    def marker(self, action, identity):
        # Fixed PHP program, no installed code or environment output. One-off
        # Compose mounts observe the actual selected storage even with custom volumes.
        code = r'''$directory="/workspace/src/storage/framework/maintenance";
$path=$directory."/enabled"; $down="/workspace/src/storage/framework/down";
$expected="runa-update:".$argv[2]."\n";
if (is_link($directory)||is_link($path)||is_link($down)) { exit(1); }
if ($argv[1]==="check") { echo (is_file($path)||is_file($down)) ? "manual" : "clear"; exit(0); }
if ($argv[1]==="write") {
 if (is_file($path)&&file_get_contents($path)!==$expected) { exit(1); }
 if (!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory)) { exit(1); }
 exit(file_put_contents($path,$expected,LOCK_EX)===false ? 1 : 0);
}
if ($argv[1]==="clear") {
 if (is_file($down)||!is_file($path)||file_get_contents($path)!==$expected) { exit(1); }
 exit(unlink($path) ? 0 : 1);
}
exit(1);'''
        return self.command(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', 'app', '-r', code, '--', action, identity]).strip()

    def manual(self):
        if self.marker('check', '0' * 32) != 'clear':
            raise Blocker('manual-maintenance', 'Manual HTTP/Laravel maintenance is active; preserve it before updating.')

    def fence(self, identity):
        try: self.marker('write', identity)
        finally: self.command(['stop', 'nginx', 'queue', 'scheduler', 'app'])

    def build(self):
        commands = [
            ['composer', '--working-dir=src', 'install', '--no-interaction', '--prefer-dist'],
            ['php', 'artisan', 'config:clear', '--no-interaction'],
            ['pnpm', '--dir', 'src', 'install', '--frozen-lockfile'],
            ['pnpm', '--dir', 'src', 'run', 'build'],
            ['php', 'src/runa/resources/errors/build.php'],
            ['php', 'artisan', 'runa:config:validate'],
            ['php', 'artisan', 'runa:config:templates']]
        for arguments in commands: self.command(['run', '--rm', '--no-deps', '-T', 'app', *arguments])

    def data(self, phase, release):
        self.command(['run', '--rm', '--no-deps', '-T', 'app', 'php', 'src/runa/database/prepare.php', release, phase])

    def activate(self, identity):
        self.marker('clear', identity)
        self.command(['up', '-d', '--wait', '--wait-timeout', '180', '--no-build', '--no-deps', 'app', 'queue', 'scheduler', 'nginx'], timeout=240)

    def health(self):
        self.probe()
        self.command(['exec', '-T', 'nginx', 'wget', '-qO', '/dev/null', 'http://127.0.0.1:8080/api/ready'])


def move(source, destination):
    os.rename(source, destination)
    for parent in (source.parent, destination.parent):
        descriptor = os.open(parent, os.O_RDONLY | os.O_DIRECTORY)
        try: os.fsync(descriptor)
        finally: os.close(descriptor)


def stage_shape(operation):
    for child in operation.iterdir():
        if child.name not in ('incoming', 'previous', 'project-files') or child.is_symlink() or not child.is_dir():
            raise Blocker('source-staging-changed', 'Unexpected operation staging content is preserved for inspection.')
        if child.name == 'project-files':
            continue  # Independently checked against journaled file identities.
        for part in child.iterdir():
            if part.name != 'src' or part.is_symlink() or not part.is_dir():
                raise Blocker('source-staging-changed', 'Unexpected operation staging content is preserved for inspection.')
            if any(p.name != 'runa' for p in part.iterdir()):
                raise Blocker('source-staging-changed', 'Unexpected operation staging content is preserved for inspection.')


def references(client, journal, directory, download):
    result = []
    for key in ('original', 'target'):
        root = directory / key
        expected = journal[key]
        identity = download(client, expected['tag'], root, 'dev', expected_commit=expected['commit'])
        if any(identity.get(field) != expected[field] for field in ('tag', 'commit', 'tag_object')) or platform(root) != expected['tag'][1:]:
            raise Blocker('release-changed', 'Independently acquired release identity changed; preserve the update journal.')
        result.append(root)
    return result


def source_state(root, operation, original, target, journal):
    incoming, previous = operation / 'incoming', operation / 'previous'
    for name, expected in ((incoming, target), (previous, original)):
        path = name / 'src/runa'
        if path.exists() or path.is_symlink():
            differences = compare(name, expected)
            partial = name == incoming and journal['phase'] == 'maintenance' and (root / 'src/runa').is_dir() and not compare(root, original) and all(row['kind'] == 'missing' for row in differences)
            if differences and not partial:
                raise Blocker('protected-source-mismatch', 'Journaled source staging differs from its independently downloaded release.')
    actual = root / 'src/runa'
    if actual.exists() or actual.is_symlink():
        if not compare(root, target): return 'target'
        if not compare(root, original): return 'original'
        raise Blocker('protected-source-mismatch', 'Installed platform differs from both verified operation releases; no force/repair override exists.')
    if not journal.get('source_transition') or not (previous / 'src/runa').is_dir() or not (incoming / 'src/runa').is_dir():
        raise Blocker('source-transition', 'Missing platform source is not a verified interrupted directory transition.')
    return 'gap'


def execute(value, client, tag, *, resume=False, choice=None, fingerprint=None, toolchain='runa-development:dev',
            expected_commit=None, download=bootstrap.acquire, runtime_factory=DockerRuntime, lock_probe=update_runtime.validate_locks, choose=None):
    report = {'schema_version': 1, 'mode': 'resume' if resume else 'apply', 'dry_run': False, 'status': 'blocked', 'checks': []}
    journal = None; runtime = None; root = None; locked = None; mutation_started = False
    try:
        root = update.installation(value, allow_pending=resume)
        if Path(__file__).resolve().is_relative_to(root):
            raise Blocker('owner-tool-location', 'Run the owner updater outside the customer installation.')
        runtime = runtime_factory(root)
        if not resume:
            snapshot = update.preflight(root, client, tag, toolchain=toolchain, download=download,
                                        runtime_probe=runtime.probe, lock_probe=lock_probe, expected_commit=expected_commit)
            if snapshot['status'] != 'ready': return {**snapshot, 'mode': 'apply', 'dry_run': False}
            report['customization_review'] = snapshot['customization_review']
            if snapshot['plan']['no_op']:
                return {**report, 'status': 'completed', 'no_op': True}
            review = snapshot['customization_review']
            if fingerprint is not None and fingerprint != review['fingerprint']:
                raise Blocker('customization-changed', 'The supplied review fingerprint differs from current delivered files.')
            if review['required'] and choice is None and choose is not None:
                choice = choose(review)
            if review['required'] and choice == 'continue' and choose is None and fingerprint != review['fingerprint']:
                raise Blocker('customization-decision-required', 'Unattended continue requires the exact reviewed plan fingerprint.')
            approved = update_customization.decision(review, choice)
            runtime.manual()
        locked = lock(root)
        state = locked.__enter__()
        if (root / 'src').stat().st_dev != state.stat().st_dev:
            raise Blocker('source-filesystem', 'Atomic platform replacement requires source and operation staging on the same filesystem.')
        pending = state / 'in-progress.json'
        if resume:
            journal = read_journal(root, client, tag)
        else:
            if pending.exists() or pending.is_symlink():
                raise Blocker('unresolved-operation', 'Another pending operation blocks execution.')
            # Revalidate the complete snapshot under the exclusive operation lock.
            fresh = update.preflight(root, client, tag, toolchain=toolchain, download=download, runtime_probe=runtime.probe,
                                     lock_probe=lock_probe, expected_commit=expected_commit, lock_held=True)
            if fresh['status'] != 'ready': return {**fresh, 'mode': 'apply', 'dry_run': False}
            if any(fresh[key] != snapshot[key] for key in ('installed_release', 'target_release', 'customization_review')):
                raise Blocker('preflight-changed', 'Source/review changed before the exclusive lock; inspect a fresh plan.')
            runtime.manual()
            runtime.bind(fresh['runtime']['project'])
            baseline = runtime.metadata()
            journal = {'schema_version': 1, 'kind': 'development-update', 'id': uuid.uuid4().hex, 'root': str(root),
                       'repository': client.repository.lower(), 'project': fresh['runtime']['project'], 'baseline': baseline, 'original': fresh['installed_release'], 'target': fresh['target_release'],
                       'module_plan': fresh['plan'].get('modules', []), 'phase': 'planned', 'completed': ['planned', 'preflight-passed', 'customization-reviewed'],
                       'review': review, 'decision': approved, 'file_actions': update_files.actions(review), 'file_action_history': [], 'state': 'running', 'started_at': stamp()}
            atomic(pending, journal)
        runtime.bind(journal['project'])
        operation = state / 'update-staging' / journal['id']
        private_directory(operation)
        temporary_parent = Path(os.environ.get('TMPDIR', '/tmp')).resolve(strict=True)
        if temporary_parent.is_relative_to(root):
            raise Blocker('unsafe-staging', 'Release downloads must remain outside the installation.')
        with tempfile.TemporaryDirectory(prefix='runa-update-execute-', dir=temporary_parent) as temporary:
            original, target = references(client, journal, Path(temporary), download)
            stage_shape(operation)
            position = source_state(root, operation, original, target, journal)
            overrides = update_files.validate_staging(root, operation, original, target, journal)
            reviewed = update_customization.review(root, original, target, {'original': journal['original'], 'target': journal['target']}, overrides)
            report['customization_review'] = reviewed
            if reviewed['fingerprint'] != journal['review']['fingerprint']:
                if choose is not None:
                    choice = choose(reviewed); fingerprint = reviewed['fingerprint']
                if choice != 'continue' or fingerprint != reviewed['fingerprint']:
                    raise Blocker('customization-changed', 'Delivered files changed since review; inspect the reported names and explicitly continue with the new review fingerprint.')
                if any(row['state'] == 'replacing' for row in journal.get('file_actions', [])):
                    raise Blocker('project-transition-changed', 'Finish or inspect interrupted file transitions before accepting a different plan.')
                update_files.renew_actions(reviewed, journal)
                journal['decision'] = update_customization.decision(reviewed, choice)
                journal['review'] = reviewed
                atomic(pending, journal)
            elif choice == 'abort':
                raise Blocker('customization-aborted', 'Operator aborted resume; source/data and recovery evidence preserved.')
            if expected_commit and expected_commit != journal['target']['commit']:
                raise Blocker('release-changed', 'Resume target differs from the recorded exact release.')
            target_inputs(root, target)
            lock_probe(update.candidate(root, target, Path(temporary) / 'dependencies', current_platform=original), toolchain)
            metadata = runtime.metadata()
            baseline = journal['baseline']
            pairs = lambda rows: {(row['release_sha'], row['seeder']) for row in rows}
            if json.dumps(baseline.get('database_mounts'), sort_keys=True) != json.dumps(metadata.get('database_mounts'), sort_keys=True):
                raise Blocker('database-identity', 'Journaled database mounts changed; preserve and inspect the original data before resuming.')
            if not set(baseline['migrations']) <= set(metadata['migrations']) or not pairs(baseline['seed_runs']) <= pairs(metadata['seed_runs']):
                raise Blocker('database-identity', 'Previously recorded platform data/history is missing; do not adopt a different database.')
            module_records = lambda rows: {(row['module'], row['step'], row['fingerprint'], row['status']) for row in rows}
            if not module_records(baseline.get('module_steps', [])) <= module_records(metadata.get('module_steps', [])):
                raise Blocker('module-history-missing', 'Recorded baseline module completion evidence is missing or changed.')
            if 'platform-modules' in journal['completed']:
                if not module_records(journal.get('module_steps', [])) <= module_records(metadata.get('module_steps', [])):
                    raise Blocker('module-history-missing', 'Recorded completed module steps are missing or changed.')
                for module in journal.get('module_plan', []):
                    actual = metadata.get('modules', {}).get(module['module'], {})
                    if actual.get('installed') != module['to'] or not actual.get('registered'):
                        raise Blocker('module-version-mismatch', 'Recorded completed module installed version is missing or changed.')
            migration_names = {p.stem for p in (target / 'src/runa/database/migrations').glob('*.php')}
            if set(metadata['migrations']) - migration_names:
                raise Blocker('migration-state-mismatch', 'Database contains platform migration history absent from the verified target.')
            seed_done = lambda value: any(row.get('release_sha') == journal['target']['commit'] and row.get('seeder') == SEEDER for row in value['seed_runs'])
            if 'platform-migrations' in journal['completed'] and not migration_names <= set(metadata['migrations']):
                raise Blocker('migration-state-mismatch', 'Recorded completed migrations are missing from the database.')
            if 'platform-seeders/upgrades' in journal['completed'] and not seed_done(metadata):
                raise Blocker('seed-state-mismatch', 'Recorded required seed completion is missing from the database.')
            def begin(step):
                journal.update(phase=step, state='running', updated_at=stamp()); atomic(pending, journal)
            def done(step):
                if step not in journal['completed']: journal['completed'].append(step)
                journal.update(updated_at=stamp()); atomic(pending, journal)
            # Always reestablish fencing before any resumed source/data work.
            mutation_started = True
            begin('maintenance'); runtime.fence(journal['id']); done('maintenance')
            if position == 'original':
                incoming = operation / 'incoming/src/runa'
                incoming.parent.mkdir(parents=True, exist_ok=True)
                shutil.copytree(target / 'src/runa', incoming, symlinks=True, dirs_exist_ok=True)
                if compare(operation / 'incoming', target):
                    raise Blocker('protected-source-mismatch', 'Staged target source does not match the verified release.')
            if position != 'target':
                journal['source_transition'] = True
                begin('source-replacing')
                previous = operation / 'previous/src/runa'; previous.parent.mkdir(parents=True, exist_ok=True)
                if position == 'original': move(root / 'src/runa', previous)
                move(operation / 'incoming/src/runa', root / 'src/runa')
            if compare(root, target):
                raise Blocker('protected-source-mismatch', 'Replaced source does not match the verified target release.')
            update_files.apply_actions(root, operation, target, journal, lambda: atomic(pending, journal), move)
            done('source-replaced')
            # Rebuild on resume against the current explicitly reconciled scaffold;
            # only transactional data completions are skipped from durable evidence.
            begin('staged/built'); runtime.build(); done('staged/built')
            if 'platform-migrations' not in journal['completed']:
                begin('platform-migrations')
                if not migration_names <= set(runtime.metadata()['migrations']): runtime.data('migrations', journal['target']['commit'])
                if not migration_names <= set(runtime.metadata()['migrations']):
                    raise Blocker('migration-state-mismatch', 'Forward migration completion could not be verified.')
                done('platform-migrations')
            if 'platform-seeders/upgrades' not in journal['completed']:
                begin('platform-seeders/upgrades')
                if not seed_done(runtime.metadata()): runtime.data('required-seeds', journal['target']['commit'])
                if not seed_done(runtime.metadata()):
                    raise Blocker('seed-state-mismatch', 'Required seed completion could not be verified.')
                done('platform-seeders/upgrades')
            if 'platform-modules' not in journal['completed']:
                begin('platform-modules')
                if (target / 'src/runa/Support/Modules/Updates/ModuleUpdates.php').is_file():
                    runtime.data('modules', journal['target']['commit'])
                journal['module_steps'] = runtime.metadata().get('module_steps', [])
                done('platform-modules')
            begin('switch'); runtime.activate(journal['id']); done('switch')
            begin('health'); runtime.health(); done('health')
            if compare(root, target):
                raise Blocker('protected-source-mismatch', 'Runtime/build modified protected target source.')
            source_state(root, operation, original, target, journal)
            stage_shape(operation)
            begin('completed'); done('completed')
            journal.update(state='complete', completed_at=stamp())
            atomic(state / 'updates' / (journal['id'] + '.json'), journal)
            # Only this operation's independently verified source staging.
            report['source_staging_removed'] = str(operation)
            pending.unlink()
            report.update(status='completed', operation_id=journal['id'], target_release=journal['target'],
                          manual_reconciliation=journal['review'].get('manual_reconciliation', []),
                          infrastructure_follow_up={'services': journal['review']['services'], 'devcontainer': journal['review']['devcontainer'],
                                                    'instruction': 'Review image rebuild/recreation and devcontainer reopen requirements before committing/deploying the customer project.'})
            # Completion is durable before best-effort disposal: a cleanup interruption
            # must not resurrect a pending data operation or fence a healthy release.
            try: shutil.rmtree(operation)
            except OSError: report['source_staging_requires_inspection'] = True
    except (Blocker, bootstrap.BootstrapError) as error:
        report['checks'].append({'id': getattr(error, 'code', 'release-acquisition'), 'status': 'blocked', 'message': str(error)})
    except (OSError, ValueError, KeyError, TypeError, RuntimeError):
        report['checks'].append({'id': 'execution-failed', 'status': 'blocked', 'message': 'Update step failed; inspect the private operation journal and runtime diagnostics before explicit resume.'})
    except BaseException:
        report['status'] = 'interrupted'
        raise
    finally:
        try:
            if report['status'] != 'completed' and journal is not None:
                if mutation_started:
                    journal.update(state='interrupted' if report['status'] == 'interrupted' else 'failed', updated_at=stamp())
                    atomic(root / '.runtime/production/in-progress.json', journal)
                    # Preserve foreign/manual maintenance markers on fencing failure.
                    try: runtime.fence(journal['id'])
                    except Exception: report['fencing_requires_inspection'] = True
                report.update(operation_id=journal['id'], phase=journal['phase'], journal='.runtime/production/in-progress.json')
        finally:
            if locked is not None: locked.__exit__(None, None, None)
    return report
