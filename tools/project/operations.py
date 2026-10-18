"""Host-only production operations. No shell interpolation, global pruning or schema rollback."""
import argparse
import base64
from contextlib import contextmanager
from datetime import datetime, timezone
import fcntl
import getpass
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import subprocess
import tarfile

import production
from runtime import ROOT, inside, run

STATE = ROOT / '.runtime/production'
WRITERS = ['queue', 'scheduler', 'app']
SPARSE = ['/app/', '/bootstrap/', '/config/', '/database/', '/lang/', '/public/', '/resources/', '/routes/',
          '/docker/php/', '/docker/nginx/', '/docker/postgres/', '/docs/project/state.yml',
          '/tools/testing/production-runtime.php', '/tools/project/runtime.py', '/tools/project/production.py', '/tools/project/operations.py',
          '/bin/production', '/bin/prod', '/bin/passthrough', '/bin/deploy', '/bin/backup', '/bin/install-prod', '/bin/operations',
          '/artisan', '/composer.json', '/composer.lock', '/package.json', '/pnpm-lock.yaml',
          '/pnpm-workspace.yaml', '/tsconfig.json', '/vite.config.mjs', '/VERSION',
          '/compose.production.yaml', '/.dockerignore', '/.gitignore', '/.env.prod.example',
          '/.env.prod.database.example', '/.env.prod.compose.example', '/.env.prod.backup.example']


def stamp():
    return datetime.now(timezone.utc).isoformat()


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream, 'sha256').hexdigest()


def atomic(path, value):
    path.parent.mkdir(parents=True, exist_ok=True, mode=0o700)
    temp = path.with_suffix('.tmp')
    with open(temp, 'w', opener=lambda p, flags: os.open(p, flags, 0o600)) as stream:
        json.dump(value, stream, indent=2)
        stream.write('\n')
    temp.replace(path)


def read_state():
    path = STATE / 'deployment.json'
    return json.loads(path.read_text()) if path.exists() else {'current': None, 'previous': None}


@contextmanager
def lock():
    STATE.mkdir(parents=True, exist_ok=True, mode=0o700)
    with (STATE / 'operation.lock').open('a') as stream:
        try:
            fcntl.flock(stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise ValueError('Another production operation is running.')
        yield


def environment():
    return {key: value for key, value in os.environ.items()
            if not key.startswith(('COMPOSE_', 'DOCKER_', 'RUNA_'))}


def command(release=None):
    context = Path(release) if release else ROOT
    # Compose env-file paths are relative to the release; sync only private operator files.
    if context != ROOT:
        for name in ('.env.prod', '.env.prod.database', '.env.prod.compose'):
            source = ROOT / name
            if source.is_symlink() or not source.is_file():
                raise ValueError('Missing regular production environment file: ' + name)
            destination = context / name
            if destination.is_symlink():
                raise ValueError('Production environment destination must not be a symlink.')
            destination.touch(mode=0o600, exist_ok=True)
            destination.chmod(0o600)
            shutil.copyfile(source, destination)
            if name == '.env.prod.compose':
                destination.write_text(re.sub(r'^RUNA_IMAGE_TAG=.*$', 'RUNA_IMAGE_TAG=' + context.name, destination.read_text(), flags=re.M))
    old = production.ROOT
    try:
        production.ROOT = context
        return production.compose()
    finally:
        production.ROOT = old


def dc(args, release=None, **kwargs):
    # Noninteractive Compose clients must not consume the caller's shell script.
    if 'stdin' not in kwargs and 'input' not in kwargs:
        kwargs['stdin'] = subprocess.DEVNULL
    return run(command(release) + args, env=environment(), **kwargs)


def active_release():
    current = read_state()['current']
    return current['path'] if current else None


def artisan(args, release=None, **kwargs):
    return dc(['run', '--rm', '--no-deps', '-T', 'app', 'php', 'artisan', *args, '--no-interaction'], release, **kwargs)


def http_maintenance(enabled, release=None):
    # Dedicated shared directory: Nginx never needs session/cache/private-upload access.
    program = ('$directory = "/workspace/storage/framework/maintenance"; '
               'if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) { throw new RuntimeException("Cannot create HTTP maintenance directory."); } '
               '$path = $directory . "/enabled"; ')
    if enabled:
        program += 'if (file_put_contents($path, "maintenance\\n", LOCK_EX) === false) { throw new RuntimeException("Cannot enable HTTP maintenance."); }'
    else:
        program += 'if (is_file($path) && !unlink($path)) { throw new RuntimeException("Cannot disable HTTP maintenance."); }'
    dc(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', 'app', '-r', program], release)


def pause_http(release=None):
    http_maintenance(True, release)
    # Keep the static serving process alive even when app DNS/PHP is absent.
    dc(['up', '-d', '--no-build', '--no-deps', 'nginx'], release)
    dc(['stop', *WRITERS], release)


def resume_http(release=None, recreate=False):
    dc(['up', '-d', '--wait', '--no-build', *(['--force-recreate'] if recreate else []),
        'postgres', 'app', 'queue', 'scheduler'], release)
    # Restore may replace the public-upload directory: refresh Nginx's subpath mount.
    dc(['up', '-d', '--no-build', '--no-deps', *(['--force-recreate'] if recreate else []), 'nginx'], release)
    http_maintenance(False, release)
    try:
        dc(['up', '-d', '--wait', '--no-build', '--no-deps', 'nginx'], release)
    except BaseException:
        http_maintenance(True, release)
        raise


def validate(release=None):
    for name in ('.env.prod', '.env.prod.database', '.env.prod.compose'):
        path = ROOT / name
        if path.is_symlink() or not path.is_file() or path.stat().st_mode & 0o077:
            raise ValueError('Production env files must be regular private files (chmod 600): ' + name)
    app = read_env(ROOT / '.env.prod')
    database = read_env(ROOT / '.env.prod.database')
    if app.get('DB_PASSWORD') != database.get('APP_DB_PASSWORD') or not app.get('DB_PASSWORD'):
        raise ValueError('Application/database credentials do not agree.')
    if len(database.get('POSTGRES_PASSWORD', '')) < 24 or database.get('POSTGRES_PASSWORD') == app['DB_PASSWORD']:
        raise ValueError('Use a distinct administrative database password of at least 24 characters.')
    dc(['config', '--quiet'], release)
    artisan(['runa:config:validate'], release)


def read_env(path):
    values = {}
    for line in path.read_text().splitlines():
        if not line or line.startswith('#'):
            continue
        key, separator, value = line.partition('=')
        if not separator or not re.fullmatch('[A-Z][A-Z0-9_]*', key) or key in values or '\x00' in value:
            raise ValueError('Invalid raw environment file; do not quote values.')
        values[key] = value
    return values


def resolve(target):
    if not re.fullmatch(r'(?:v\d+\.\d+\.\d+|[a-f0-9]{40})', target):
        raise ValueError('Target must be an exact vX.Y.Z release or full lowercase Git SHA.')
    if target.startswith('v') and run(['git', 'cat-file', '-t', target], capture_output=True, text=True).stdout.strip() != 'tag':
        raise ValueError('Release versions must identify annotated tags.')
    sha = run(['git', 'rev-parse', '--verify', target + '^{commit}'], capture_output=True, text=True).stdout.strip()
    if not re.fullmatch('[a-f0-9]{40}', sha):
        raise ValueError('Invalid commit identity.')
    return sha


def tree_hash(sha):
    # Conservatively block rollback on any migration or seeder implementation change.
    return hashlib.sha256(run(['git', 'ls-tree', '-r', sha, 'database', 'app/Support/Seeding', 'app/Console/Commands/SeedProject.php'], capture_output=True).stdout).hexdigest()


def checkout(sha):
    path = STATE / 'releases' / sha
    if path.exists():
        actual = run(['git', '-C', str(path), 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip()
        dirty = run(['git', '-C', str(path), 'status', '--porcelain', '--untracked-files=all'], capture_output=True, text=True).stdout
        if actual != sha or dirty:
            raise ValueError('Existing release checkout is modified; inspect it before retrying.')
        return path
    path.parent.mkdir(parents=True, exist_ok=True)
    run(['git', 'clone', '--no-checkout', '--no-hardlinks', str(ROOT), str(path)])
    run(['git', '-C', str(path), 'sparse-checkout', 'init', '--no-cone'])
    run(['git', '-C', str(path), 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(SPARSE) + '\n', text=True)
    run(['git', '-C', str(path), 'checkout', '--detach', sha])
    return path


def set_tag(sha):
    path = ROOT / '.env.prod.compose'
    old = path.read_text()
    if not re.search(r'^RUNA_IMAGE_TAG=.*$', old, re.M):
        raise ValueError('Missing image tag configuration.')
    path.write_text(re.sub(r'^RUNA_IMAGE_TAG=.*$', 'RUNA_IMAGE_TAG=' + sha, old, flags=re.M))
    path.chmod(0o600)
    return old


def images(release):
    names = dc(['config', '--images'], release, capture_output=True, text=True).stdout.splitlines()
    return {name: run(['docker', 'image', 'inspect', '--format', '{{.Id}}', name], capture_output=True, text=True).stdout.strip()
            for name in sorted(set(names))}


def health(release=None, sparse=False):
    current = read_state()['current']
    if current and str(release) == current['path'] and images(release) != current['images']:
        raise ValueError('Selected image IDs differ from the recorded successful deployment.')
    rows = dc(['ps', '--all', '--format', 'json'], release, capture_output=True, text=True).stdout.strip()
    rows = json.loads(rows) if rows.startswith('[') else [json.loads(line) for line in rows.splitlines() if line]
    services = {row['Service']: row for row in rows}
    if set(services) != set(production.SERVICES):
        raise ValueError('Unexpected/missing service in the production project.')
    for service, row in services.items():
        if row.get('State') != 'running' or row.get('Health') != 'healthy':
            raise ValueError('Production service is not healthy: ' + service)
    dc(['exec', '-T', 'nginx', 'wget', '-qO', '/dev/null', 'http://127.0.0.1:8080/'], release)
    dc(['exec', '-T', 'nginx', 'wget', '-qO', '/dev/null', 'http://127.0.0.1:8080/api/ready'], release)
    isolation(release, sparse)
    print('PASS: production services, root page, readiness and isolation.')


def isolation(release=None, sparse=False):
    config = json.loads(dc(['config', '--format', 'json'], release, capture_output=True, text=True).stdout)
    if set(config['services']) != set(production.SERVICES) or set(config.get('volumes', {})) != {'postgres-icu-data', 'application-storage'}:
        raise ValueError('Unexpected production services or volumes.')
    for name, service in config['services'].items():
        if service.get('privileged') or any('docker.sock' in str(mount) for mount in service.get('volumes', [])):
            raise ValueError('Unsafe production privileges/mount.')
        if name != 'nginx' and service.get('ports'):
            raise ValueError('Unexpected published production port.')
        if name in ('app', 'queue', 'scheduler') and service['environment'].get('KNOWLEDGE_ENABLED') != 'false':
            raise ValueError('Knowledge must be disabled.')
    production.verify_images(command(release), environment())
    project = config['name']
    allowed_volumes = {volume['name'] for volume in config.get('volumes', {}).values()}
    volumes = set(run(['docker', 'volume', 'ls', '--filter', 'label=com.docker.compose.project=' + project, '--format', '{{.Name}}'], capture_output=True, text=True).stdout.splitlines())
    if volumes - allowed_volumes:
        raise ValueError('Unexpected project volume; production may contain development data.')
    for name in production.SERVICES:
        container = dc(['ps', '--quiet', name], release, capture_output=True, text=True).stdout.strip()
        if not container:
            raise ValueError('Missing production container: ' + name)
        actual = json.loads(run(['docker', 'inspect', container], capture_output=True, text=True).stdout)[0]
        expected_image = run(['docker', 'image', 'inspect', '--format', '{{.Id}}', config['services'][name]['image']], capture_output=True, text=True).stdout.strip()
        if actual['Image'] != expected_image:
            raise ValueError('Running image differs from the selected production identity: ' + name)
        if name != 'nginx' and actual['HostConfig'].get('PortBindings'):
            raise ValueError('Unexpected actual production port binding.')
        if name in ('app', 'queue', 'scheduler') and 'KNOWLEDGE_ENABLED=false' not in actual['Config']['Env']:
            raise ValueError('Knowledge is not disabled in the running container.')
    if sparse:
        if not release:
            raise ValueError('Sparse isolation requires a managed release checkout.')
        assert_sparse(Path(release))
    print('PASS: production Compose/environment/image' + ('/sparse-checkout' if sparse else '') + ' isolation.')


def assert_sparse(path):
    for forbidden in ('.agents', '.devcontainer', 'bin/knowledge', 'tools/knowledge', 'docker/embeddings', 'docker/qdrant', 'docs/ai', '.env', '.env.dev.local'):
        if (path / forbidden).exists():
            raise ValueError('Development material in production checkout: ' + forbidden)
    if not (path / 'docs/project/state.yml').is_file():
        raise ValueError('Missing production deployment-state policy.')


def build_images(release=None):
    selected = dc(['config', '--images'], release, capture_output=True, text=True).stdout.splitlines()
    app_images = sorted({image for image in selected if image.startswith(('runa-production:', 'runa-nginx:'))})
    present = [subprocess.run(['docker', 'image', 'inspect', image], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0 for image in app_images]
    if any(present) and not all(present):
        raise ValueError('Incomplete immutable image pair; inspect/remove the failed build explicitly before retrying.')
    if not any(present):
        dc(['build', 'app', 'nginx'], release)
    production.verify_images(command(release), environment())


def deploy(target, simulation):
    sha = resolve(target)
    previous = read_state()['current']
    mode = read_env(ROOT / '.env.prod.compose').get('RUNA_PRODUCTION_MODE', 'real')
    if simulation != (mode == 'simulation'):
        raise ValueError('The simulation flag must match RUNA_PRODUCTION_MODE in the private Compose settings.')
    if not simulation:
        assert_sparse(ROOT)
    if previous and 'postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873' not in previous.get('images', {}):
        raise ValueError('Previous deployment uses a different database baseline. Use a fresh dedicated installer simulation checkout; do not adopt the old cluster.')
    if previous and previous['simulation'] != simulation:
        raise ValueError('Cannot switch between real and simulation deployment contexts.')
    if not previous:
        existing = dc(['ps', '--all', '--quiet'], capture_output=True, text=True).stdout.strip()
        project_command = command()
        project = project_command[project_command.index('--project-name') + 1]
        volumes = run(['docker', 'volume', 'ls', '--filter', 'label=com.docker.compose.project=' + project, '--format', '{{.Name}}'], capture_output=True, text=True).stdout.strip()
        if existing or volumes:
            raise ValueError('Unmanaged production project already exists. Preserve it and choose a separate simulation context; do not adopt unknown data.')
    release = checkout(sha)
    old_config = set_tag(sha)
    mutation_started = False
    try:
        dc(['config', '--quiet'], release)
        # Never overwrite an immutable image identity with a different source tree.
        build_images(release)
        validate(release)
        if previous:
            create_backup()
        atomic(STATE / 'in-progress.json', {'target': sha, 'previous': previous, 'started_at': stamp(), 'phase': 'startup-and-migrations'})
        mutation_started = True
        dc(['up', '-d', '--wait', '--no-build', 'postgres'], release)
        pause_http(release)
        artisan(['migrate', '--force'], release)
        artisan(['runa:seed', 'production', '--force'], release)
        # Cache is per container. Entrypoint builds configuration; routes/events are safe to cache here only per-container.
        resume_http(release, recreate=True)
        health(release, sparse=True)
        identity = {'sha': sha, 'version': (release / 'VERSION').read_text().strip(), 'path': str(release),
                    'database_tree': tree_hash(sha), 'deployed_at': stamp(), 'simulation': simulation, 'images': images(release)}
        atomic(STATE / 'deployment.json', {'current': identity, 'previous': previous})
        if not simulation and not (STATE / 'first-deploy.json').exists():
            atomic(STATE / 'first-deploy.json', identity)
            print('Real first-deploy evidence written. Reconcile versioned production state before editing migrations.')
        (STATE / 'in-progress.json').unlink()
        print('PASS: deployed ' + sha + (' (simulation).' if simulation else '.'))
    except BaseException:
        if not mutation_started:
            (ROOT / '.env.prod.compose').write_text(old_config)
        elif previous and previous['database_tree'] == tree_hash(sha):
            http_maintenance(True, release)
            set_tag(previous['sha'])
            resume_http(previous['path'], recreate=True)
            health(previous['path'], sparse=True)
            (STATE / 'in-progress.json').unlink()
            print('Deployment FAILED; safely returned to previous application ' + previous['sha'] + '. Database migrations were not reversed.')
        else:
            http_maintenance(True, release)
            print('Deployment FAILED after migrations may have started. Services may be stopped or unhealthy; inspect in-progress.json. No schema reversal was attempted.')
        raise


def rollback():
    state = read_state()
    current, previous = state['current'], state['previous']
    if not current or not previous or (STATE / 'in-progress.json').exists():
        raise ValueError('Rollback needs two successful deployments and no unresolved failed deployment.')
    if current['database_tree'] != previous['database_tree']:
        raise ValueError('Migration/seeder sources changed. Automatic rollback is blocked; use the documented recovery strategy.')
    old = set_tag(previous['sha'])
    try:
        if images(previous['path']) != previous['images']:
            raise ValueError('Previous image identity changed or is missing; refusing rollback.')
        validate(previous['path'])
    except BaseException:
        (ROOT / '.env.prod.compose').write_text(old)
        raise
    atomic(STATE / 'in-progress.json', {'phase': 'rollback', 'target': previous['sha'], 'started_at': stamp()})
    pause_http(current['path'])
    try:
        resume_http(previous['path'], recreate=True)
        health(previous['path'], sparse=True)
    except BaseException:
        http_maintenance(True, previous['path'])
        raise
    atomic(STATE / 'deployment.json', {'current': previous, 'previous': current})
    (STATE / 'in-progress.json').unlink()
    print('PASS: application rollback ' + current['sha'] + ' -> ' + previous['sha'] + '; database unchanged.')


def backup_root():
    settings = read_env(ROOT / '.env.prod.backup')
    path = Path(settings.get('BACKUP_DIRECTORY', ''))
    if not path.is_absolute() or path.is_symlink() or path == Path('/') or not re.fullmatch(r'[1-9]\d*', settings.get('BACKUP_RETENTION', '')):
        raise ValueError('Backup directory must be an absolute dedicated path; retention must be positive.')
    path.mkdir(parents=True, exist_ok=True, mode=0o700)
    if path.stat().st_mode & 0o077:
        raise ValueError('Backup directory must be private (chmod 700).')
    return path, int(settings['BACKUP_RETENTION'])


def backup_path(name):
    if not re.fullmatch(r'backup-\d{8}T\d{12}Z', name):
        raise ValueError('Provide a backup ID, not an arbitrary path.')
    root, _ = backup_root()
    path = root / name
    if path.is_symlink() or not path.is_dir():
        raise ValueError('Backup is not a regular backup directory.')
    return path


def pg(args, release=None, **kwargs):
    # Database-tool failures may include restored values; never echo those diagnostics.
    kwargs.setdefault('stderr', subprocess.PIPE)
    # Fixed shell program expands container credentials; no operator text is concatenated.
    return dc(['exec', '-T', 'postgres', 'sh', '-c', 'export PGPASSWORD="$POSTGRES_PASSWORD"; exec "$@"', 'runa-pg', *args], release, **kwargs)


def create_backup(protect=None):
    current = read_state()['current']
    if not current or (STATE / 'in-progress.json').exists():
        raise ValueError('Backup requires an established deployment with no unresolved operation.')
    release = current['path']
    health(release, sparse=True)
    root, retention = backup_root()
    name = 'backup-' + datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
    partial = root / (name + '.partial')
    partial.mkdir(mode=0o700)
    atomic(STATE / 'in-progress.json', {'phase': 'backup', 'backup': name, 'started_at': stamp()})
    try:
        pause_http(release)
        with (partial / 'database.dump').open('wb') as stream:
            pg(['pg_dump', '-U', 'postgres', '-d', 'runa', '--format=custom', '--no-owner', '--no-acl'], release, stdout=stream)
        with (partial / 'storage.tar.gz').open('wb') as stream:
            dc(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'tar', 'app', '-C', '/workspace/storage', '-czf', '-', 'app'], release, stdout=stream)
        atomic(partial / 'manifest.json', {'format': 1, 'created_at': stamp(), 'deployment': current,
              'files': {name: digest(partial / name) for name in ('database.dump', 'storage.tar.gz')}})
        verify_backup_files(partial, release)
        partial.rename(root / name)
    finally:
        resume_http(release)
        (STATE / 'in-progress.json').unlink()
    # Only prune complete, validated, named backups in this dedicated directory.
    candidates = sorted(p for p in root.iterdir() if re.fullmatch(r'backup-\d{8}T\d{12}Z', p.name) and p.is_dir() and not p.is_symlink())
    for path in candidates[:-retention]:
        if path.name == protect:
            continue
        verify_backup_files(path, release)
        shutil.rmtree(path)
    print('PASS: coordinated backup ' + name)
    return name


def verify_backup_files(path, release):
    for name in ('manifest.json', 'database.dump', 'storage.tar.gz'):
        if (path / name).is_symlink() or not (path / name).is_file():
            raise ValueError('Backup contains missing/non-regular files.')
    manifest = json.loads((path / 'manifest.json').read_text())
    if manifest.get('format') != 1 or set(manifest.get('files', {})) != {'database.dump', 'storage.tar.gz'}:
        raise ValueError('Unsupported/incomplete backup metadata.')
    for name, expected in manifest['files'].items():
        if digest(path / name) != expected:
            raise ValueError('Backup integrity mismatch: ' + name)
    with tarfile.open(path / 'storage.tar.gz', 'r:gz') as archive:
        members = archive.getmembers()
        if not members or not any(m.name == 'app' and m.isdir() for m in members):
            raise ValueError('Missing persistent application storage root.')
        for member in members:
            if member.name.startswith('/') or '..' in Path(member.name).parts or Path(member.name).parts[0] != 'app' or not (member.isfile() or member.isdir()):
                raise ValueError('Unsafe storage archive entry; links/devices are forbidden.')
            if member.isfile():
                with archive.extractfile(member) as stream:
                    while stream.read(1024 * 1024):
                        pass
    with (path / 'database.dump').open('rb') as stream:
        pg(['pg_restore', '--list'], release, stdin=stream, stdout=subprocess.DEVNULL)
    return manifest


def verify_backup(name):
    path = backup_path(name)
    release = active_release()
    verify_backup_files(path, release)
    # Restore into an isolated temporary DB on the same server; never touch runa/runa_test.
    database = 'runa_backup_verify_' + secrets.token_hex(8)
    pg(['createdb', '-U', 'postgres', '-T', 'template0', database], release)
    try:
        with (path / 'database.dump').open('rb') as stream:
            pg(['pg_restore', '-U', 'postgres', '--exit-on-error', '--no-owner', '--no-acl', '-d', database], release, stdin=stream, stdout=subprocess.DEVNULL)
        pg(['psql', '-U', 'postgres', '-d', database, '-v', 'ON_ERROR_STOP=1', '-tAc', 'SELECT count(*) FROM shared.migrations'], release, stdout=subprocess.DEVNULL)
    finally:
        pg(['dropdb', '-U', 'postgres', database], release)
    print('PASS: backup hashes, storage archive and actual isolated database restore: ' + name)


def restore(name, confirmed):
    current = read_state()['current']
    if not current or (STATE / 'in-progress.json').exists():
        raise ValueError('Restore requires an established deployment and no unresolved operation.')
    path = backup_path(name)
    manifest = verify_backup_files(path, current['path'])
    if manifest['deployment']['sha'] != current['sha'] or manifest['deployment']['simulation'] != current['simulation']:
        raise ValueError('Restore requires the same application SHA and real/simulation context as the backup.')
    verify_backup(name)
    project = 'runa-prod-icu-simulation' if current['simulation'] else 'runa-prod'
    target = project + '/runa/application-storage'
    print('Destructive restore target: ' + target + '; backup: ' + name)
    if not confirmed and input('Type the full target to confirm: ') != target:
        raise ValueError('Restore not confirmed.')
    # Preserve the selected restore source even when retention would otherwise prune it.
    create_backup(protect=name)
    if not path.exists():
        raise ValueError('Restore source expired during retention; use a higher retention setting and retry.')
    release = current['path']
    atomic(STATE / 'in-progress.json', {'phase': 'restore', 'backup': name, 'started_at': stamp()})
    pause_http(release)
    # Recreate only the explicitly named application DB. No down() or global volume operation.
    pg(['dropdb', '-U', 'postgres', '--force', 'runa'], release)
    pg(['createdb', '-U', 'postgres', '-O', 'runa', '-T', 'template0', 'runa'], release)
    with (path / 'database.dump').open('rb') as stream:
        pg(['pg_restore', '-U', 'postgres', '--role=runa', '--exit-on-error', '--no-owner', '--no-acl', '-d', 'runa'], release, stdin=stream)
    pg(['psql', '-U', 'postgres', '-d', 'runa', '-v', 'ON_ERROR_STOP=1', '-c', 'REVOKE ALL ON DATABASE runa FROM PUBLIC; REVOKE ALL ON SCHEMA public FROM PUBLIC;'], release)
    dc(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'sh', 'app', '-c', 'test ! -L /workspace/storage/app && find /workspace/storage/app -mindepth 1 -delete'], release)
    with (path / 'storage.tar.gz').open('rb') as stream:
        dc(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'tar', 'app', '-C', '/workspace/storage', '--no-same-owner', '-xzf', '-'], release, stdin=stream)
    try:
        resume_http(release, recreate=True)
        health(release, sparse=True)
    except BaseException:
        http_maintenance(True, release)
        raise
    (STATE / 'in-progress.json').unlink()
    print('PASS: database/storage restored and application healthy.')


def install(target, simulation):
    if any((ROOT / name).exists() for name in ('.env.prod', '.env.prod.database', '.env.prod.compose')):
        raise ValueError('Installer refuses to overwrite existing production settings. Use deploy for upgrades.')
    if not simulation:
        dirty = run(['git', 'status', '--porcelain', '--untracked-files=all'], capture_output=True, text=True).stdout.strip()
        if dirty or (ROOT / '.env').exists() or (ROOT / '.env.dev.local').exists():
            raise ValueError('Real installer requires a clean dedicated production clone without development environment files.')
        run(['git', 'sparse-checkout', 'init', '--no-cone'])
        run(['git', 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(SPARSE) + '\n', text=True)
        assert_sparse(ROOT)
    engine = run(['docker', 'version', '--format', '{{.Server.Version}}'], capture_output=True, text=True).stdout.strip()
    compose_version = run(['docker', 'compose', 'version', '--short'], capture_output=True, text=True).stdout.strip().lstrip('v')
    def version(value):
        match = re.match(r'^(\d+)\.(\d+)', value)
        if not match:
            raise ValueError('Unable to determine Docker/Compose version.')
        return tuple(map(int, match.groups()))
    if version(engine) < (26, 0) or version(compose_version) < (2, 35):
        raise ValueError('Require Docker Engine >=26 and Compose >=2.35.')
    print('Configure host HTTPS reverse proxy separately. Secrets are generated locally; SMTP password input is hidden.')
    values = read_env(ROOT / '.env.prod.example')
    http_port = input('Loopback HTTP port [8081]: ').strip() or '8081'
    if not http_port.isascii() or not http_port.isdigit() or not 1024 <= int(http_port) <= 65535:
        raise ValueError('HTTP port must be 1024–65535.')
    values['APP_URL'] = input('HTTPS application URL: ').strip()
    values['MAIL_HOST'] = input('SMTP host: ').strip()
    values['MAIL_FROM_ADDRESS'] = input('Sender email: ').strip()
    values['MAIL_USERNAME'] = input('SMTP username: ').strip()
    values['MAIL_PASSWORD'] = getpass.getpass('SMTP password: ')
    backup = {'BACKUP_DIRECTORY': input('Dedicated absolute backup directory: ').strip(), 'BACKUP_RETENTION': input('Number of complete backups to retain [7]: ').strip() or '7'}
    values['APP_KEY'] = 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode()
    values['DB_PASSWORD'] = secrets.token_urlsafe(36)
    database = {'POSTGRES_PASSWORD': secrets.token_urlsafe(36), 'APP_DB_PASSWORD': values['DB_PASSWORD']}
    resolve(target)
    for name, mapping in (('.env.prod', values), ('.env.prod.database', database), ('.env.prod.backup', backup),
                          ('.env.prod.compose', {'RUNA_IMAGE_TAG': target, 'RUNA_HTTP_PORT': http_port, 'RUNA_PRODUCTION_MODE': 'simulation' if simulation else 'real'})):
        with open(ROOT / name, 'x', opener=lambda p, flags: os.open(p, flags, 0o600)) as stream:
            for key, value in mapping.items():
                if any(char in value for char in '\r\n\x00'):
                    raise ValueError('Environment values must be single-line raw values.')
                stream.write(key + '=' + value + '\n')
    backup_root()
    print('Private configuration created. Validation/deploy failures preserve settings for operator correction.')
    deploy(target, simulation)


def main(name, args):
    parser = argparse.ArgumentParser(prog='./bin/' + name)
    if name == 'deploy':
        parser.add_argument('target', help='Exact vX.Y.Z/full SHA, or rollback')
        parser.add_argument('--simulation', action='store_true', help='Local production simulation; never sets real-deploy marker')
    elif name == 'install-prod':
        parser.add_argument('target', help='Exact vX.Y.Z/full SHA')
        parser.add_argument('--simulation', action='store_true')
    else:
        parser.add_argument('action', choices=['create', 'verify', 'restore'])
        parser.add_argument('backup_id', nargs='?')
        parser.add_argument('--confirm-runa-prod-restore', action='store_true', help='Explicit destructive restore confirmation')
    options = parser.parse_args(args)
    if inside():
        raise ValueError('Run production operations in a host terminal outside the devcontainer.')
    with lock():
        if name == 'deploy':
            if options.target == 'rollback':
                rollback()
            else:
                if (STATE / 'in-progress.json').exists():
                    raise ValueError('Unresolved failed deployment/restore; inspect in-progress.json before retrying.')
                deploy(options.target, options.simulation)
        elif name == 'install-prod':
            install(options.target, options.simulation)
        elif options.action == 'create':
            if options.backup_id:
                raise ValueError('Create does not accept a backup ID.')
            create_backup()
        else:
            if not options.backup_id:
                raise ValueError('A backup ID is required.')
            if options.action == 'verify':
                verify_backup(options.backup_id)
            else:
                restore(options.backup_id, options.confirm_runa_prod_restore)
