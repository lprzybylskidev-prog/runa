"""Bounded native Docker transport and read-only probes; no installed code is imported."""
import json
import os
from pathlib import Path
import re
import subprocess
import uuid

from update_contracts import Blocker, document, regular


def command(arguments, cwd=None, timeout=90):
    environment = {k: v for k, v in os.environ.items() if not k.startswith(('COMPOSE_', 'DOCKER_', 'RUNA_', 'GIT_'))}
    try:
        return subprocess.run(arguments, cwd=cwd, env=environment, stdin=subprocess.DEVNULL, capture_output=True,
                              text=True, timeout=timeout, check=True).stdout
    except (OSError, subprocess.SubprocessError):
        raise Blocker('probe-failed', 'Host/container operation failed; inspect the recorded phase, operator access and runtime prerequisites.') from None


def env_file(root, name):
    result = {}
    for line in regular(root, name, 65536).decode().splitlines():
        if not line or line.startswith('#'):
            continue
        key, separator, value = line.partition('=')
        if not separator or not re.fullmatch('[A-Z][A-Z0-9_]*', key) or key in result or '\x00' in value:
            raise Blocker('invalid-environment', 'Invalid private environment input: ' + name)
        result[key] = value
    if (root / name).stat().st_mode & 0o077:
        raise Blocker('environment-permissions', 'Environment inputs must be private: ' + name)
    return result


def compose(root, mode, operator):
    if mode != 'dev' or root != operator:
        raise Blocker('development-only', 'Updater runtime probes require the selected development checkout.')
    env_file(root, '.env.dev.local')
    args = ['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env.dev.local')]
    workspace = root / '.devcontainer/devcontainer.json'
    files = ['compose.yaml', '.devcontainer/compose.resources.yaml']
    if workspace.exists() and 'dockerComposeFile' in document(root, '.devcontainer/devcontainer.json'):
        value = document(root, '.devcontainer/devcontainer.json')['dockerComposeFile']
        if isinstance(value, str): value = [value]
        if not isinstance(value, list) or any(not isinstance(item, str) for item in value):
            raise Blocker('customization-review-unavailable', 'Invalid devcontainer Compose selection.')
        from update_customization import local_path
        files = [local_path(root, item, workspace.parent) for item in value]
    for name in ('compose.override.yaml', 'compose.override.yml'):
        if (root / name).exists() and name not in files: files.append(name)
    for name in files:
        regular(root, name)
        args += ['-f', str(root / name)]
    return args


def probe(root, mode, operator):
    args = compose(root, mode, operator)
    config = json.loads(command(args + ['config', '--format', 'json'], root))
    required = {'app', 'nginx', 'postgres', 'queue', 'scheduler', 'mailpit'}
    if not required <= config.get('services', {}).keys():
        raise Blocker('runtime-unavailable', 'Required development services are missing from the selected configuration.')
    for name, service in config['services'].items():
        if service.get('privileged') or service.get('network_mode') == 'host' or service.get('cap_add'):
            raise Blocker('runtime-isolation', 'Service privileges violate the development isolation contract: ' + name)
        for mount in service.get('volumes', []):
            if 'docker.sock' in mount.get('source', ''):
                raise Blocker('runtime-isolation', 'A service exposes Docker transport: ' + name)
    raw = command(args + ['ps', '--all', '--format', 'json'], root).strip()
    rows = json.loads(raw) if raw.startswith('[') else [json.loads(line) for line in raw.splitlines() if line]
    services = {row['Service']: row for row in rows}
    if not required <= services.keys():
        raise Blocker('runtime-unavailable', 'Required runtime/database services are missing.')
    for name in required:
        row = services[name]
        if row.get('State') != 'running' or row.get('Health') != 'healthy':
            raise Blocker('runtime-unavailable', 'Required runtime/database service is not healthy: ' + name)
        actual = json.loads(command(['docker', 'inspect', row['ID']]))[0]
        labels = actual['Config'].get('Labels', {})
        if labels.get('com.docker.compose.project.working_dir') != str(root):
            raise Blocker('runtime-ownership', 'Runtime service belongs to a different checkout: ' + name)
        actual_env = dict(item.split('=', 1) for item in actual['Config'].get('Env', []) if '=' in item)
        if any(str(v) != actual_env.get(k) for k, v in config['services'][name].get('environment', {}).items()):
            raise Blocker('runtime-configuration', 'Actual service environment differs from the selected customer inputs: ' + name)
        expected_image = command(['docker', 'image', 'inspect', '--format', '{{.Id}}', config['services'][name]['image']]).strip()
        if (actual['Image'] != expected_image or actual['HostConfig'].get('Privileged') or actual['HostConfig'].get('CapAdd')
                or actual['HostConfig'].get('NetworkMode') == 'host'):
            raise Blocker('runtime-identity', 'Running image/privileges differ from the selected standard runtime: ' + name)
        expected_mounts = set()
        for mount in config['services'][name].get('volumes', []):
            kind = mount['type']
            source = config['volumes'][mount['source']]['name'] if kind == 'volume' else mount['source']
            expected_mounts.add((kind, source, mount['target'], bool(mount.get('read_only'))))
        actual_mounts = {(m['Type'], m.get('Name') if m['Type'] == 'volume' else m['Source'], m['Destination'], not m['RW'])
                         for m in actual.get('Mounts', []) if m['Type'] in ('bind', 'volume')}
        if actual_mounts != expected_mounts:
            raise Blocker('runtime-mounts', 'Actual service mounts differ from the verified Compose inputs: ' + name)
        for mount in actual.get('Mounts', []):
            if 'docker.sock' in mount.get('Source', ''):
                raise Blocker('production-isolation', 'Runtime exposes Docker transport.')
    return {'project': config['name'], 'services': sorted(required), **metadata(root, args)}


def metadata(root, args=None):
    args = args or compose(root, 'dev', root)
    sql = "BEGIN READ ONLY; SELECT json_build_object('migrations',(SELECT coalesce(json_agg(migration ORDER BY id),'[]') FROM shared.migrations),'seed_runs',(SELECT coalesce(json_agg(json_build_object('release_sha',release_sha,'seeder',seeder)),'[]') FROM shared.platform_seed_runs)); ROLLBACK;"
    # Fixed shell program; password stays inside the existing PostgreSQL
    # environment, never host argv/output. SQL is a fixed positional argument.
    query = 'PGPASSWORD="$APP_DB_PASSWORD" exec psql -h 127.0.0.1 -X -qAt -U runa -d runa -v ON_ERROR_STOP=1 -c "$1"'
    database = json.loads(command(args + ['exec', '-T', 'postgres', 'sh', '-c', query, '--', sql], root))
    # Probe optional module metadata without referring to tables absent in older releases.
    exists_sql = "BEGIN READ ONLY; SELECT json_build_object('modules',to_regclass('shared.modules') IS NOT NULL,'steps',to_regclass('shared.module_update_steps') IS NOT NULL); ROLLBACK;"
    available = json.loads(command(args + ['exec', '-T', 'postgres', 'sh', '-c', query, '--', exists_sql], root))
    database.update(modules={}, module_steps=[])
    if available['modules']:
        module_sql = "BEGIN READ ONLY; SELECT coalesce(json_object_agg(module_id,json_build_object('installed',installed_version,'version',source_version,'registered',registered AND source_present,'required',manifest->'dependencies'->'required')),'{}') FROM shared.modules; ROLLBACK;"
        database['modules'] = json.loads(command(args + ['exec', '-T', 'postgres', 'sh', '-c', query, '--', module_sql], root))
    if available['steps']:
        step_sql = "BEGIN READ ONLY; SELECT coalesce(json_agg(json_build_object('module',m.module_id,'step',s.step,'fingerprint',s.fingerprint,'status',s.status,'operation',s.operation)),'[]') FROM shared.module_update_steps s JOIN shared.modules m ON m.id=s.module_id; ROLLBACK;"
        database['module_steps'] = json.loads(command(args + ['exec', '-T', 'postgres', 'sh', '-c', query, '--', step_sql], root))
    return database


def validate_locks(candidate, image):
    if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._/:@-]{0,255}', image):
        raise Blocker('toolchain-identity', 'Select one supported local owner toolchain image reference.')
    identity = command(['docker', 'image', 'inspect', '--format', '{{.Id}}', image]).strip()
    if not re.fullmatch('sha256:[a-f0-9]{64}', identity):
        raise Blocker('toolchain-unavailable', 'Owner toolchain image has no exact local identity.')
    name = 'runa-update-locks-' + uuid.uuid4().hex
    args = ['docker', 'run', '--rm', '--name', name, '--network', 'none', '--read-only', '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
            '--label', 'com.docker.compose.project=' + name, '--label', 'com.docker.compose.service=dependency-probe',
            '--user', str(os.getuid()) + ':' + str(os.getgid()), '--tmpfs', '/tmp:rw,nosuid,nodev,size=128m,mode=1777',
            '--mount', 'type=bind,src=' + str(candidate) + ',dst=/candidate', '-w', '/candidate/src',
            '-e', 'HOME=/tmp', '-e', 'COMPOSER_HOME=/tmp/composer', '-e', 'XDG_CACHE_HOME=/tmp/cache', identity]
    try:
        command([*args, 'composer', '--no-plugins', '--no-scripts', 'validate', '--no-check-publish', '--no-interaction'])
        # Structural frozen-lock check only. Registry supply-chain policy/audits
        # remain mandatory during real install/build; no packages are installed.
        command([*args, 'pnpm', 'install', '--lockfile-only', '--frozen-lockfile', '--trust-lockfile', '--ignore-scripts', '--offline', '--store-dir=/tmp/store', '--config.manage-package-manager-versions=false'])
    except Blocker:
        raise Blocker('dependency-lock-review', 'Preserved Composer/pnpm locks do not validate with the target composition, or the isolated owner toolchain is unavailable. Reconcile explicitly; locks were not replaced.') from None
    finally:
        # Only this uniquely named disposable probe; never touch runtime services.
        subprocess.run(['docker', 'rm', '--force', name], capture_output=True, timeout=30)
    return identity
