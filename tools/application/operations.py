"""Host-only production operations. No shell interpolation, global pruning or schema rollback."""
import argparse
from contextlib import contextmanager
from datetime import datetime, timezone
import fcntl
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import stat
import subprocess

import production
from runtime import ROOT, inside, run

STATE = ROOT / '.runtime/production'
WRITERS = ['queue', 'scheduler', 'app']
SPARSE = ['/src/app/', '/src/bootstrap/', '/src/runa/Console/', '/src/runa/Database/', '/src/runa/Foundation/', '/src/runa/Http/', '/src/runa/Providers/', '/src/runa/Support/', '/src/runa/config/', '/src/runa/database/', '/src/runa/routes/',
          '/src/runa/resources/', '/src/runa/lang/', '/src/runa/platform.json', '/src/runa/composer.json', '/src/runa/package.json', '/src/public/',
          '/src/artisan', '/src/composer.json', '/src/composer.lock', '/src/package.json', '/src/pnpm-lock.yaml',
          '/src/pnpm-workspace.yaml', '/src/tsconfig.json', '/src/vite.config.mjs', '/src/vite.errors.config.mjs', '/VERSION',
          '/artisan', '/docker/php/', '/docker/nginx/', '/docker/postgres/', '/compose.production.yaml', '/.dockerignore',
          '/tools/application/runtime.py', '/tools/application/production.py', '/tools/application/operations.py', '/tools/application/dependencies.py', '/tools/application/repository_boundary.py',
          '/bin/artisan', '/bin/command', '/tools/application/management.py', '/tools/application/executors/operations.py', '/tools/application/executors/production.py',
          '/src/runa/management/autoload.php',
          '/src/runa/management/bootstrap.php',
          '/src/runa/management/commands.php',
          '/src/runa/management/ToolingCommand.php',
          '/src/runa/management/NamedToolCommand.php',
          '/src/runa/management/operations.json', '/src/runa/management/HostBridge.php', '/tools/application/host_executor.py',
          '/tools/application/testing/production-runtime.php',
          '/bin/passthrough', '/bin/deploy', '/bin/operations',
          '/compose.production.yaml', '/.dockerignore', '/.gitignore', '/.env.prod.example',
          '/.env.prod.database.example', '/.env.prod.compose.example']


def stamp():
    return datetime.now(timezone.utc).isoformat()


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
    if any(path.is_symlink() for path in [STATE, *STATE.parents]):
        raise ValueError('Operation state must not traverse symlinks.')
    STATE.mkdir(parents=True, exist_ok=True, mode=0o700)
    info = STATE.stat()
    if info.st_uid != os.getuid() or info.st_mode & 0o022:
        raise ValueError('Operation state must be operator-owned and not writable by other users.')
    descriptor = os.open(STATE / 'operation.lock', os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    with os.fdopen(descriptor, 'a') as stream:
        info = os.fstat(stream.fileno())
        if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid():
            raise ValueError('Operation lock must be a regular operator-owned file.')
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
        return production.compose(tls_root=ROOT)
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
    program = ('$directory = "/workspace/src/storage/framework/maintenance"; '
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
    # Refresh Nginx's public-storage subpath mount when recreating the stack.
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
    return hashlib.sha256(run(['git', 'ls-tree', '-r', sha, 'database', 'app/Support/Seeding', 'app/Console/Commands/SeedProject.php', 'src/app/database', 'src/runa/database', 'src/runa/Support/Seeding', 'src/runa/Console/Commands/SeedProject.php'], capture_output=True).stdout).hexdigest()


def checkout(sha):
    from repository_boundary import receipt, write_staging_receipt
    data = receipt(ROOT)
    if not re.fullmatch('[a-f0-9]{40}', sha):
        raise ValueError('Staging requires an exact commit identity.')
    path = STATE / 'releases' / sha
    if path.resolve() != path:
        raise ValueError('Release staging must not traverse symlinks.')
    staging = {'source_checkout': str(ROOT), 'commit': sha}
    if path.exists():
        actual = run(['git', '-C', str(path), 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip()
        dirty = run(['git', '-C', str(path), 'status', '--porcelain', '--untracked-files=all'], capture_output=True, text=True).stdout
        if actual != sha or dirty:
            raise ValueError('Existing release checkout is modified; inspect it before retrying.')
        if receipt(path).get('staging') != staging:
            raise ValueError('Existing release staging ownership differs; no source is adopted or repaired.')
        return path
    path.parent.mkdir(parents=True, exist_ok=True)
    run(['git', 'clone', '--no-checkout', '--no-hardlinks', str(ROOT), str(path)])
    run(['git', '-C', str(path), 'sparse-checkout', 'init', '--no-cone'])
    run(['git', '-C', str(path), 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(SPARSE) + '\n', text=True)
    run(['git', '-C', str(path), 'checkout', '--detach', sha])
    staged = {**data, 'staging': staging, 'commit': sha, 'mode': 'prod'}
    # Acquisition digests identify the original download, not a different staged commit.
    staged.pop('source_sha256', None)
    write_staging_receipt(path, staged)
    run(['git', '-C', str(path), 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream'])
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
    from repository_boundary import OWNER_PATHS
    if any((path / name).exists() or (path / name).is_symlink() for name in OWNER_PATHS):
        raise ValueError('Owner distribution/maintenance tools are present in production source.')
    for forbidden in ('.agents', '.devcontainer', 'bin/knowledge', 'tools/application/knowledge', 'docker/embeddings', 'docker/qdrant', 'docs/ai', 'src/runa/management/development.json', 'src/runa/management/development.php', 'tools/application/executors/knowledge.py', '.env', '.env.dev.local'):
        if (path / forbidden).exists():
            raise ValueError('Development material in production checkout: ' + forbidden)


def build_images(release=None):
    from dependencies import materialize
    materialize(Path(release) if release else ROOT, check=True)
    selected = dc(['config', '--images'], release, capture_output=True, text=True).stdout.splitlines()
    app_images = sorted({image for image in selected if image.startswith(('runa-production:', 'runa-nginx:'))})
    present = [subprocess.run(['docker', 'image', 'inspect', image], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0 for image in app_images]
    if any(present) and not all(present):
        raise ValueError('Incomplete immutable image pair; inspect/remove the failed build explicitly before retrying.')
    if not any(present):
        dc(['build', 'app', 'nginx'], release)
    production.verify_images(command(release), environment())


def deploy(target, simulation, initial_resume=False, verification=None, database_setup=None):
    from repository_boundary import assert_production_installation
    assert_production_installation(ROOT)
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
        if (existing or volumes) and not initial_resume:
            raise ValueError('Unmanaged production project already exists. Preserve it and choose a separate simulation context; do not adopt unknown data.')
        if verification is not None and not (STATE / 'install-resources.json').exists():
            atomic(STATE / 'install-resources.json', {'sha': sha, 'project': project, 'started_at': stamp()})
    release = checkout(sha)
    old_config = set_tag(sha)
    mutation_started = False
    try:
        dc(['config', '--quiet'], release)
        # Never overwrite an immutable image identity with a different source tree.
        build_images(release)
        validate(release)
        atomic(STATE / 'in-progress.json', {'target': sha, 'previous': previous, 'started_at': stamp(), 'phase': 'startup-and-migrations'})
        mutation_started = True
        dc(['up', '-d', '--wait', '--no-build', 'postgres'], release)
        pause_http(release)
        if database_setup is not None:
            database_setup(release)
        else:
            artisan(['migrate', '--force'], release)
            artisan(['db:seed', '--force'], release)
        # Cache is per container. Entrypoint builds configuration; routes/events are safe to cache here only per-container.
        resume_http(release, recreate=True)
        health(release, sparse=True)
        if verification is not None:
            verification(release)
        identity = {'sha': sha, 'version': (release / 'VERSION').read_text().strip(), 'path': str(release),
                    'database_tree': tree_hash(sha), 'deployed_at': stamp(), 'simulation': simulation, 'images': images(release)}
        atomic(STATE / 'deployment.json', {'current': identity, 'previous': previous})
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
    from repository_boundary import assert_production_installation
    assert_production_installation(ROOT)
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


def main(name, args):
    if name != 'deploy':
        raise ValueError('Unsupported internal operation family.')
    parser = argparse.ArgumentParser(prog='./bin/' + name)
    parser.add_argument('target', help='Exact vX.Y.Z/full SHA, or rollback')
    parser.add_argument('--simulation', action='store_true', help='Isolated local production simulation')
    options = parser.parse_args(args)
    if inside():
        raise ValueError('Run production operations in a host terminal outside the devcontainer.')
    from repository_boundary import assert_production_installation
    assert_production_installation(ROOT)
    with lock():
        if options.target == 'rollback':
            rollback()
        else:
            if (STATE / 'in-progress.json').exists():
                raise ValueError('Unresolved failed deployment; inspect in-progress.json before retrying.')
            deploy(options.target, options.simulation)
