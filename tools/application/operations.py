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
from repository_boundary import OWNER_PATHS
DEVELOPMENT_MODULES = ('src/app/modules/module_probe', 'src/app/modules/reference')
DELIVERY = ['/*', *['!/' + name + '/' for name in DEVELOPMENT_MODULES], *['!/' + name + ('/' if name in ('distribution', 'tools/maintainer') else '') for name in OWNER_PATHS]]


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
        *[name for name, value in resolved(release)['services'].items() if name != 'nginx' and not value.get('profiles')]], release)
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


def tree_hash(sha, repository=None):
    # Conservatively include module migrations/upgrades and their owned helper implementations.
    return hashlib.sha256(run(['git', '-C', str(repository or ROOT), 'ls-tree', '-r', sha, 'src/app/database', 'src/app/Support/Seeding', 'src/runa/database', 'src/runa/Support/Seeding', 'src/runa/modules', 'src/app/modules', 'src/runa/Support/Modules/Updates'], capture_output=True).stdout).hexdigest()


def checkout(sha, source=None):
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
    run(['git', '-c', 'core.hooksPath=/dev/null', 'clone', '--no-checkout', '--no-hardlinks', str(source or ROOT), str(path)])
    run(['git', '-C', str(path), 'sparse-checkout', 'init', '--no-cone'])
    run(['git', '-C', str(path), 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(DELIVERY) + '\n', text=True)
    run(['git', '-C', str(path), 'checkout', '--detach', sha])
    staged = {**data, 'staging': staging, 'commit': sha, 'mode': 'prod'}
    # Acquisition digests identify the original download, not a different staged commit.
    staged.pop('source_sha256', None)
    write_staging_receipt(path, staged)
    from repository_boundary import application_remote
    _, url = application_remote(ROOT, data)
    run(['git', '-C', str(path), 'remote', 'set-url', 'origin', url])
    run(['git', '-C', str(path), 'config', '--local', 'runa.applicationRemote', 'origin'])
    return path


def set_tag(sha):
    path = ROOT / '.env.prod.compose'
    old = path.read_text()
    if not re.search(r'^RUNA_IMAGE_TAG=.*$', old, re.M):
        raise ValueError('Missing image tag configuration.')
    path.write_text(re.sub(r'^RUNA_IMAGE_TAG=.*$', 'RUNA_IMAGE_TAG=' + sha, old, flags=re.M))
    path.chmod(0o600)
    return old


def resolved(release=None):
    return json.loads(dc(['config', '--format', 'json'], release, capture_output=True, text=True).stdout)


def platform_data(sha, phase, release=None):
    dc(['run', '--rm', '--no-deps', '-T', 'app', 'php', 'src/runa/database/prepare.php', sha, phase], release)


def images(release):
    config = resolved(release)
    names = {service['image'] for service in config['services'].values() if not service.get('profiles')}
    return {name: run(['docker', 'image', 'inspect', '--format', '{{.Id}}', name], capture_output=True, text=True).stdout.strip()
            for name in sorted(names)}


def health(release=None, verify_source=False):
    current = read_state()['current']
    if current and str(release) == current['path'] and images(release) != current['images']:
        raise ValueError('Selected image IDs differ from the recorded successful deployment.')
    config = resolved(release)
    rows = dc(['ps', '--all', '--format', 'json'], release, capture_output=True, text=True).stdout.strip()
    rows = json.loads(rows) if rows.startswith('[') else [json.loads(line) for line in rows.splitlines() if line]
    services = {row['Service']: row for row in rows}
    required = {name for name, value in config['services'].items() if not value.get('profiles')}
    if not required <= services.keys() or services.keys() - config['services'].keys():
        raise ValueError('Missing selected services or undeclared running/stopped services; reconcile the production project explicitly.')
    for service, row in services.items():
        definition = config['services'][service]
        needs_health = service in production.SERVICES or bool(definition.get('healthcheck'))
        if row.get('State') != 'running' or (needs_health and row.get('Health') != 'healthy'):
            raise ValueError('Production service is not healthy/running: ' + service)
    dc(['exec', '-T', 'nginx', 'wget', '-qO', '/dev/null', 'http://127.0.0.1:8080/'], release)
    dc(['exec', '-T', 'nginx', 'wget', '-qO', '/dev/null', 'http://127.0.0.1:8080/api/ready'], release)
    isolation(release, verify_source)
    print('PASS: production services, root page, readiness and isolation.')


def isolation(release=None, verify_source=False):
    config = resolved(release)
    if not set(production.SERVICES) <= config['services'].keys():
        raise ValueError('Required production runtime services are missing.')
    forbidden = {'qdrant', 'embeddings', 'embeddings-gpu', 'knowledge-init', 'setup', 'mailpit', 'app-test', 'nginx-test'}
    for name, service in config['services'].items():
        if name in forbidden or any(part in service.get('image', '').lower() for part in ('qdrant', 'text-embeddings-inference', 'runa-embeddings')):
            raise ValueError('AI/development services cannot run in production.')
        if (service.get('privileged') or service.get('network_mode') == 'host' or service.get('cap_add')
                or any('docker.sock' in str(mount) for mount in service.get('volumes', []))):
            raise ValueError('Unsafe production privileges/mount/network.')
        if name in ('app', 'queue', 'scheduler') and service.get('environment', {}).get('KNOWLEDGE_ENABLED') != 'false':
            raise ValueError('Knowledge must be disabled.')
    production.verify_images(command(release), environment(), source=Path(release) if release else ROOT)
    for name, service in config['services'].items():
        container = dc(['ps', '--quiet', name], release, capture_output=True, text=True).stdout.strip()
        if not container and service.get('profiles'):
            continue
        if not container:
            raise ValueError('Missing production container: ' + name)
        actual = json.loads(run(['docker', 'inspect', container], capture_output=True, text=True).stdout)[0]
        expected_image = run(['docker', 'image', 'inspect', '--format', '{{.Id}}', service['image']], capture_output=True, text=True).stdout.strip()
        if actual['Image'] != expected_image:
            raise ValueError('Running image differs from the selected production identity: ' + name)
        if actual['HostConfig'].get('Privileged') or actual['HostConfig'].get('CapAdd') or actual['HostConfig'].get('NetworkMode') == 'host' or any('docker.sock' in mount.get('Source', '') for mount in actual.get('Mounts', [])):
            raise ValueError('Running production privileges/mount/network violate isolation.')
        if name in ('app', 'queue', 'scheduler') and 'KNOWLEDGE_ENABLED=false' not in actual['Config']['Env']:
            raise ValueError('Knowledge is not disabled in the running container.')
    if verify_source:
        if not release:
            raise ValueError('Source isolation requires a managed release checkout.')
        assert_delivery(Path(release))
    print('PASS: production Compose/environment/image' + ('/customer-source' if verify_source else '') + ' isolation.')


def assert_delivery(path):
    if any((path / name).exists() or (path / name).is_symlink() for name in (*OWNER_PATHS, *DEVELOPMENT_MODULES)):
        raise ValueError('Owner-only or development example source is present in the production project.')


def build_images(release=None):
    from dependencies import materialize
    materialize(Path(release) if release else ROOT, check=True)
    selected = dc(['config', '--images'], release, capture_output=True, text=True).stdout.splitlines()
    app_images = sorted({image for image in selected if image.startswith(('runa-production:', 'runa-nginx:', 'runa-postgres:'))})
    present = [subprocess.run(['docker', 'image', 'inspect', image], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0 for image in app_images]
    if any(present) and not all(present):
        raise ValueError('Incomplete immutable image set; inspect/remove the failed build explicitly before retrying.')
    if not any(present):
        dc(['build', 'app', 'nginx', 'postgres'], release)
    production.verify_images(command(release), environment(), source=Path(release) if release else ROOT)


def deploy(target='main', simulation=False, initial_resume=False, verification=None, database_setup=None):
    from repository_boundary import assert_production_installation
    assert_production_installation(ROOT)
    if database_setup is not None:
        # Private external installer callback; never selected by customer commands.
        return deploy_revision(resolve(target), simulation, initial_resume, verification, database_setup)
    installed = read_state()['current']
    if not installed:
        raise ValueError('Customer deployment requires a completed platform installation; use the external owner installer first.')
    from customer_source import fetch
    with fetch(ROOT, STATE, target, installed=Path(installed['path'])) as (source, sha):
        return deploy_revision(sha, simulation, source=source)


def maintenance_active(release):
    result = dc(['run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', 'app', '-r',
                 'echo (is_file("/workspace/src/storage/framework/maintenance/enabled") || is_file("/workspace/src/storage/framework/down")) ? "active" : "clear";'],
                release, capture_output=True, text=True)
    return result.stdout.strip() != 'clear'


def deploy_revision(sha, simulation, initial_resume=False, verification=None, database_setup=None, source=None):
    previous = read_state()['current']
    mode = read_env(ROOT / '.env.prod.compose').get('RUNA_PRODUCTION_MODE', 'real')
    if simulation != (mode == 'simulation'):
        raise ValueError('The simulation flag must match RUNA_PRODUCTION_MODE in the private Compose settings.')
    if not simulation:
        assert_delivery(ROOT)
    if previous and previous['simulation'] != simulation:
        raise ValueError('Cannot switch between real and simulation deployment contexts.')
    if previous and maintenance_active(previous['path']):
        raise ValueError('Maintenance is already active. Preserve operator ownership and resolve it before deployment.')
    if previous and previous['sha'] == sha:
        health(previous['path'], verify_source=True)
        print('PASS: requested customer revision is already deployed; no data steps repeated.')
        return
    if not previous:
        existing = dc(['ps', '--all', '--quiet'], capture_output=True, text=True).stdout.strip()
        project_command = command()
        project = project_command[project_command.index('--project-name') + 1]
        volumes = run(['docker', 'volume', 'ls', '--filter', 'label=com.docker.compose.project=' + project, '--format', '{{.Name}}'], capture_output=True, text=True).stdout.strip()
        if (existing or volumes) and not initial_resume:
            raise ValueError('Unmanaged production project already exists. Preserve it and choose a separate simulation context; do not adopt unknown data.')
        if verification is not None and not (STATE / 'install-resources.json').exists():
            atomic(STATE / 'install-resources.json', {'sha': sha, 'project': project, 'started_at': stamp()})
    release = checkout(sha, source) if source is not None else checkout(sha)
    old_config = set_tag(sha)
    mutation_started = False
    pending = {'target': sha, 'previous': previous, 'started_at': stamp(), 'phase': 'prepared', 'data_steps_started': False}
    def phase(value):
        pending['phase'] = value
        atomic(STATE / 'in-progress.json', pending)
    try:
        dc(['config', '--quiet'], release)
        # Never overwrite an immutable image identity with a different source tree.
        build_images(release)
        validate(release)
        phase('startup-and-maintenance')
        mutation_started = True
        dc(['up', '-d', '--wait', '--no-build', 'postgres'], release)
        pause_http(release)
        pending['data_steps_started'] = True
        if database_setup is not None:
            phase('platform-installation')
            database_setup(release)
        else:
            phase('platform-migrations')
            platform_data(sha, 'migrations', release)
            phase('platform-seeding')
            platform_data(sha, 'required-seeds', release)
            if (release / 'src/runa/Support/Modules/Updates/ModuleUpdates.php').is_file():
                phase('platform-modules')
                platform_data(sha, 'modules', release)
            phase('application-migrations')
            artisan(['migrate', '--force'], release)
            phase('application-seeding')
            artisan(['db:seed', '--force'], release)
        # Cache is per container. Entrypoint builds configuration; routes/events are safe to cache here only per-container.
        phase('restart-and-health')
        resume_http(release, recreate=True)
        health(release, verify_source=True)
        if verification is not None:
            verification(release)
        identity = {'sha': sha, 'version': json.loads((release / 'src/runa/platform.json').read_text())['version'], 'path': str(release),
                    'application_version': json.loads((release / 'src/app/application.json').read_text())['version'],
                    'database_tree': tree_hash(sha, release), 'rollback_safe': False,
                    'deployed_at': stamp(), 'simulation': simulation, 'images': images(release)}
        atomic(STATE / 'deployment.json', {'current': identity, 'previous': previous})
        (STATE / 'in-progress.json').unlink()
        print('PASS: deployed ' + sha + (' (simulation).' if simulation else '.'))
    except BaseException:
        if not mutation_started:
            (ROOT / '.env.prod.compose').write_text(old_config)
        else:
            try:
                pause_http(release)
            except BaseException:
                print('Recovery fencing failed; immediately stop production HTTP/writers through the operator environment.')
            print('Deployment FAILED at ' + pending['phase'] + '. Maintenance/recovery state retained; inspect .runtime/production/in-progress.json before any retry. No schema/data reversal or automatic seeder replay was attempted.')
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
    if not current.get('rollback_safe'):
        raise ValueError('Schema/data compatibility is unproven after deployment data steps. Code rollback cannot reverse data; recover under operator review.')
    if maintenance_active(current['path']):
        raise ValueError('Maintenance is already active; preserve operator ownership before rollback.')
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
        health(previous['path'], verify_source=True)
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
    parser.add_argument('target', nargs='?', default='main', help='Customer branch/tag/full SHA (default: main), or internal rollback')
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
