"""Read-only, bounded native Docker probes; no installed Python/PHP is imported."""
import ast
import json
import os
from pathlib import Path
import re
import subprocess
import uuid

from update_contracts import Blocker, document, regular


def gpu_descriptor(root, values):
    """Read the verified generator's data expression without importing it."""
    config = document(root, 'tools/application/knowledge/config.json')
    def data(node):
        if isinstance(node, ast.Constant): return node.value
        if isinstance(node, ast.List): return [data(item) for item in node.elts]
        if isinstance(node, ast.Dict): return {data(k): data(v) for k, v in zip(node.keys, node.values)}
        if isinstance(node, ast.Subscript) and isinstance(node.value, ast.Name) and node.value.id in ('config', 'values'):
            return {'config': config, 'values': values}[node.value.id][data(node.slice)]
        raise Blocker('unsupported-profile', 'GPU profile descriptor contains unsupported non-data expressions.')
    tree = ast.parse(regular(root, 'tools/application/executors/dev.py').decode())
    for node in ast.walk(tree):
        if isinstance(node, ast.Assign) and any(isinstance(t, ast.Subscript) and isinstance(t.value, ast.Name)
                and t.value.id == 'siblings' and isinstance(t.slice, ast.Constant) and t.slice.value == 'embeddings-gpu' for t in node.targets):
            return data(node.value)
    raise Blocker('unsupported-profile', 'Verified release has no supported GPU profile descriptor.')


def command(arguments, cwd=None):
    environment = {k: v for k, v in os.environ.items() if not k.startswith(('COMPOSE_', 'DOCKER_', 'RUNA_', 'GIT_'))}
    try:
        return subprocess.run(arguments, cwd=cwd, env=environment, stdin=subprocess.DEVNULL, capture_output=True,
                              text=True, timeout=90, check=True).stdout
    except (OSError, subprocess.SubprocessError):
        raise Blocker('probe-failed', 'Read-only host/container check failed; inspect operator access and required runtime prerequisites.') from None


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
    if mode == 'dev':
        env_file(operator, '.env.dev.local')
        return ['docker', 'compose', '--project-directory', str(root), '--env-file', str(operator / '.env.dev.local'),
                '-f', str(root / 'compose.yaml'), '-f', str(operator / '.devcontainer/compose.resources.yaml')]
    values = env_file(root, '.env.prod.compose')
    if root != operator and regular(root, '.env.prod.compose') != regular(operator, '.env.prod.compose'):
        raise Blocker('customer-input-review-required', 'Operator and active production Compose inputs differ; review the concrete input change.')
    if set(values) - {'RUNA_IMAGE_TAG', 'RUNA_HTTP_PORT', 'RUNA_PRODUCTION_MODE', 'RUNA_TLS_MODE', 'RUNA_SIMULATION_PROJECT'}:
        raise Blocker('custom-infrastructure', 'Unsupported production Compose settings.')
    port = values.get('RUNA_HTTP_PORT', '8081')
    if not re.fullmatch(r'(?:v\d+\.\d+\.\d+|[a-f0-9]{40})', values.get('RUNA_IMAGE_TAG', '')) or not port.isascii() or not port.isdigit() or not 1024 <= int(port) <= 65535:
        raise Blocker('custom-infrastructure', 'Production image tag or HTTP port is unsupported.')
    mode_value = values.get('RUNA_PRODUCTION_MODE', 'real')
    if mode_value not in ('real', 'simulation'):
        raise Blocker('custom-infrastructure', 'Unsupported production mode.')
    project = 'runa-prod' if mode_value == 'real' else values.get('RUNA_SIMULATION_PROJECT', 'runa-prod-icu-simulation')
    if mode_value == 'simulation' and project != 'runa-prod-icu-simulation' and not re.fullmatch('runa-prod-simulation-[a-z0-9-]{1,40}', project):
        raise Blocker('custom-infrastructure', 'Unsupported simulation project identity.')
    if mode_value == 'real' and 'RUNA_SIMULATION_PROJECT' in values:
        raise Blocker('custom-infrastructure', 'Real production cannot select a simulation project.')
    for name in ('.env.prod', '.env.prod.database'):
        env_file(root, name)
        if root != operator and regular(root, name) != regular(operator, name):
            raise Blocker('customer-input-review-required', 'Operator and active production settings differ; review the concrete customer input change before updating.')
    app = env_file(root, '.env.prod')
    database = env_file(root, '.env.prod.database')
    if (app.get('DB_CONNECTION') != 'pgsql' or app.get('DB_USERNAME') != 'runa' or app.get('DB_DATABASE') != 'runa'
            or app.get('DB_HOST') != 'postgres' or app.get('DB_PASSWORD') != database.get('APP_DB_PASSWORD')
            or not app.get('DB_PASSWORD') or len(database.get('POSTGRES_PASSWORD', '')) < 24
            or database.get('POSTGRES_PASSWORD') == app.get('DB_PASSWORD')):
        raise Blocker('database-configuration', 'Runtime database configuration/credentials differ from the supported isolated production contract.')
    args = ['docker', 'compose', '--project-name', project, '--project-directory', str(root), '--env-file', str(root / '.env.prod.compose'), '-f', str(root / 'compose.production.yaml')]
    if values.get('RUNA_TLS_MODE', 'external') == 'builtin':
        overlay = document(operator, '.runtime/production/tls/compose.json')
        nginx = overlay.get('services', {}).get('nginx', {})
        directory = operator / '.runtime/production/tls'
        expected_mounts = [{'type': 'bind', 'source': str(directory / name), 'target': target, 'read_only': True, 'bind': {'create_host_path': False}}
                           for name, target in (('nginx.conf', '/etc/nginx/nginx.conf'), ('certificate.pem', '/run/runa-tls/certificate.pem'), ('private-key.pem', '/run/runa-tls/private-key.pem'))]
        if (set(overlay) != {'services'} or set(overlay['services']) != {'nginx'} or set(nginx) != {'ports', 'volumes'}
                or nginx['volumes'] != expected_mounts or len(nginx['ports']) != 1
                or not re.fullmatch(r'(?:127\.0\.0\.1|0\.0\.0\.0):[0-9]{1,5}:8443', nginx['ports'][0])):
            raise Blocker('custom-infrastructure', 'TLS overlay differs from the supported installer-generated structure.')
        expected = regular(root, 'docker/nginx/production.conf').decode().replace('listen 8080;', 'listen 8080;\n        listen 8443 ssl;\n        ssl_certificate /run/runa-tls/certificate.pem;\n        ssl_certificate_key /run/runa-tls/private-key.pem;\n        ssl_protocols TLSv1.2 TLSv1.3;')
        if regular(operator, '.runtime/production/tls/nginx.conf').decode() != expected:
            raise Blocker('custom-infrastructure', 'Generated TLS Nginx source has been customized.')
        args += ['-f', str(operator / '.runtime/production/tls/compose.json')]
    elif values.get('RUNA_TLS_MODE', 'external') != 'external':
        raise Blocker('custom-infrastructure', 'Unsupported TLS mode.')
    return args


def probe(root, mode, operator):
    args = compose(root, mode, operator)
    config = json.loads(command(args + ['config', '--format', 'json'], root))
    base_args = args[:-2] if mode == 'dev' or Path(args[-1]) == operator / '.runtime/production/tls/compose.json' else args
    # Every non-TLS base file was already compared to original release source.
    base = json.loads(command(base_args + ['config', '--format', 'json'], root))
    if mode == 'dev':
        overlay = document(operator, '.devcontainer/compose.resources.yaml')
        if 'embeddings-gpu' in overlay.get('services', {}):
            if overlay['services']['embeddings-gpu'] != gpu_descriptor(root, env_file(operator, '.env.dev.local')):
                raise Blocker('custom-infrastructure', 'GPU overlay differs from the verified standard profile.')
        def topology(value):
            value = json.loads(json.dumps(value))
            if 'embeddings-gpu' not in base['services']:
                value['services'].pop('embeddings-gpu', None)
            for service in value['services'].values():
                for name in ('cpus', 'mem_limit'):
                    service.pop(name, None)
                for name in ('TEST_WORKERS', 'PLAYWRIGHT_WORKERS'):
                    service.get('environment', {}).pop(name, None)
                for name in ('RUNA_UID', 'RUNA_GID'):
                    service.get('build', {}).get('args', {}).pop(name, None)
                if service.get('build', {}).get('args') == {}:
                    service['build'].pop('args')
            return value
        if topology(base) != topology(config):
            raise Blocker('custom-infrastructure', 'Development resource inputs change service topology beyond supported resource/worker/UID settings.')
    required = {'app', 'nginx', 'postgres', 'queue', 'scheduler'} | ({'mailpit'} if mode == 'dev' else set())
    if mode == 'prod' and (set(config['services']) != required or set(config.get('volumes', {})) != {'postgres-icu-data', 'application-storage'}):
        raise Blocker('custom-infrastructure', 'Unexpected production services or volumes.')
    for name, service in config['services'].items():
        if service.get('privileged') or service.get('network_mode') == 'host' or service.get('cap_add'):
            raise Blocker('custom-infrastructure', 'Unsafe service privilege or network configuration: ' + name)
        if mode == 'prod' and name in ('app', 'queue', 'scheduler') and str(service.get('environment', {}).get('KNOWLEDGE_ENABLED')).lower() != 'false':
            raise Blocker('production-isolation', 'Production knowledge tooling is not disabled.')
        if mode == 'prod' and name != 'nginx' and service.get('ports'):
            raise Blocker('custom-infrastructure', 'Unexpected production published service: ' + name)
        for mount in service.get('volumes', []):
            if mount.get('type') == 'bind':
                path = Path(mount['source'])
                declared = {item['source'] for item in base['services'][name].get('volumes', []) if item.get('type') == 'bind'}
                if 'docker.sock' in str(path) or not (str(path) in declared or path.is_relative_to(operator / '.runtime/production/tls')):
                    raise Blocker('custom-infrastructure', 'Unsupported external service mount: ' + name)
    raw = command(args + ['ps', '--all', '--format', 'json'], root).strip()
    rows = json.loads(raw) if raw.startswith('[') else [json.loads(line) for line in raw.splitlines() if line]
    services = {row['Service']: row for row in rows}
    if mode == 'prod' and set(services) != required:
        raise Blocker('custom-infrastructure', 'Unexpected actual service exists in the production project.')
    if not required <= services.keys():
        raise Blocker('runtime-unavailable', 'Required runtime/database services are missing.')
    recorded_images = document(operator, '.runtime/production/deployment.json')['current']['images'] if mode == 'prod' else None
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
        if recorded_images is not None and recorded_images.get(config['services'][name]['image']) != expected_image:
            raise Blocker('runtime-identity', 'Selected image identity differs from the recorded successful deployment: ' + name)
        if (actual['Image'] != expected_image or actual['HostConfig'].get('Privileged') or actual['HostConfig'].get('CapAdd')
                or actual['HostConfig'].get('NetworkMode') == 'host'):
            raise Blocker('runtime-identity', 'Running image/privileges differ from the selected standard runtime: ' + name)
        if mode == 'prod' and name in ('app', 'queue', 'scheduler'):
            if 'KNOWLEDGE_ENABLED=false' not in actual['Config'].get('Env', []) or actual['Config'].get('User') in ('', 'root', '0'):
                raise Blocker('production-isolation', 'Running application user/environment violates production isolation.')
        if mode == 'prod' and name != 'nginx' and actual['HostConfig'].get('PortBindings'):
            raise Blocker('custom-infrastructure', 'Running service has unexpected published ports: ' + name)
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
    sql = "BEGIN READ ONLY; SELECT json_build_object('migrations',(SELECT coalesce(json_agg(migration ORDER BY id),'[]') FROM shared.migrations),'seed_runs',(SELECT coalesce(json_agg(json_build_object('release_sha',release_sha,'seeder',seeder)),'[]') FROM shared.platform_seed_runs)); ROLLBACK;"
    # Fixed shell program; password stays inside the existing PostgreSQL
    # environment, never host argv/output. SQL is a fixed positional argument.
    query = 'PGPASSWORD="$APP_DB_PASSWORD" exec psql -h 127.0.0.1 -X -qAt -U runa -d runa -v ON_ERROR_STOP=1 -c "$1"'
    database = json.loads(command(args + ['exec', '-T', 'postgres', 'sh', '-c', query, '--', sql], root))
    return {'project': config['name'], 'services': sorted(required), **database}


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
