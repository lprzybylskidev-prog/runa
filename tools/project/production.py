"""Explicit host-only production image and Compose operations; never use dev context."""
import re
import os
from runtime import ROOT, inside, run

SERVICES = ('app', 'nginx', 'postgres', 'queue', 'scheduler')


def compose():
    path = ROOT / '.env.prod.compose'
    values = {}
    for line in path.read_text().splitlines():
        if not line.strip() or line.lstrip().startswith('#'):
            continue
        key, separator, value = line.partition('=')
        if not separator or key not in ('RUNA_IMAGE_TAG', 'RUNA_HTTP_PORT', 'RUNA_PRODUCTION_MODE') or key in values:
            raise ValueError('Invalid production Compose setting; use the example without quoting or interpolation.')
        values[key] = value
    if not re.fullmatch(r'(?:v\d+\.\d+\.\d+|[a-f0-9]{40})', values.get('RUNA_IMAGE_TAG', '')):
        raise ValueError('RUNA_IMAGE_TAG requires an exact vX.Y.Z release or full lowercase Git commit hash.')
    port = values.get('RUNA_HTTP_PORT', '8081')
    if not port.isascii() or not port.isdigit() or not 1024 <= int(port) <= 65535:
        raise ValueError('RUNA_HTTP_PORT must be an unprivileged TCP port (1024–65535).')
    mode = values.get('RUNA_PRODUCTION_MODE', 'real')
    if mode not in ('real', 'simulation'):
        raise ValueError('RUNA_PRODUCTION_MODE must be real or simulation.')
    project = 'runa-prod-simulation' if mode == 'simulation' else 'runa-prod'
    return ['docker', 'compose', '--project-name', project, '--project-directory', str(ROOT), '--env-file', str(path), '-f', str(ROOT / 'compose.production.yaml')]


def dispatch(args):
    if args == ['--help'] or not args:
        print('Usage: ./bin/production validate|build|up|down|restart|status|logs [service]|self-test|verify-ai-isolation\nHost terminal only; requires .env.prod.compose, .env.prod and .env.prod.database.\nBuild creates exact-tag PHP/Nginx images. Up expects migrations/production seeding already complete. Down preserves volumes. Use install-prod/deploy for migrations and managed releases.')
        return
    if inside():
        raise ValueError('Run production Docker operations in a host terminal outside the devcontainer; no Docker socket is mounted here.')
    action, *services = args
    if action in ('self-test', 'verify-ai-isolation'):
        if services:
            raise ValueError('This operation takes no service arguments.')
        import operations
        release = operations.active_release()
        if action == 'self-test':
            operations.health(release, sparse=True)
        else:
            operations.isolation(release, sparse=True)
        return
    if action not in ('validate', 'build', 'up', 'down', 'restart', 'status', 'logs'):
        raise ValueError('Unknown or not yet implemented production operation. See ./bin/production --help.')
    if services and (action not in ('restart', 'logs') or len(services) != 1 or services[0] not in SERVICES):
        raise ValueError('Only restart/logs accept one known production service.')
    command = compose()
    environment = dict(os.environ)
    for key in ('RUNA_IMAGE_TAG', 'RUNA_HTTP_PORT'):
        environment.pop(key, None)
    # Quiet validation never prints expanded application or database secrets.
    run(command + ['config', '--quiet'], env=environment)
    commands = {
        'validate': None,
        'build': ['build', 'app', 'nginx'],
        'up': ['up', '-d', '--wait', '--no-build'],
        'down': ['down'],
        'restart': ['restart', *services],
        'status': ['ps', '--all'],
        'logs': ['logs', '--tail', '100', *services],
    }
    if action == 'build':
        import operations
        target = operations.read_env(ROOT / '.env.prod.compose')['RUNA_IMAGE_TAG']
        sha = operations.resolve(target)
        head = run(['git', 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip()
        dirty = run(['git', 'status', '--porcelain', '--untracked-files=all'], capture_output=True, text=True).stdout.strip()
        if sha != head or dirty:
            raise ValueError('Production build requires a clean checkout whose HEAD matches the exact image tag.')
        operations.build_images()
        print('PASS: production build.')
        return
    if commands[action]:
        run(command + commands[action], env=environment)
    print('PASS: production ' + action + '.')


def verify_images(command, environment):
    """Inspect the exact images selected by Compose, without starting a deployment."""
    images = run(command + ['config', '--images'], env=environment, capture_output=True, text=True).stdout.splitlines()
    php = next(image for image in images if image.startswith('runa-production:'))
    nginx = next(image for image in images if image.startswith('runa-nginx:'))
    for image, user in ((php, 'runa'), (nginx, 'nginx')):
        actual = run(['docker', 'image', 'inspect', '--format', '{{.Config.User}}', image], capture_output=True, text=True).stdout.strip()
        if actual != user:
            raise ValueError('Unexpected runtime image user.')
    code = r'''require '/workspace/vendor/autoload.php';
foreach (['pdo_pgsql', 'mbstring', 'intl', 'bcmath', 'Zend OPcache', 'pcntl', 'zip', 'curl', 'xml'] as $extension) {
    if (!extension_loaded($extension)) { throw new RuntimeException('Missing runtime extension: '.$extension); }
}
if (extension_loaded('xdebug')) { throw new RuntimeException('Development extension in runtime.'); }
foreach (['.git', '.env', '.agents', '.devcontainer', 'docs', 'tools', 'bin', 'tests', 'node_modules', 'vendor/laravel/boost', 'vendor/pestphp', 'vendor/phpstan'] as $path) {
    if (file_exists('/workspace/'.$path)) { throw new RuntimeException('Development material in runtime: '.$path); }
}
$installed = json_decode(file_get_contents('/workspace/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
if ($installed['dev'] !== false) { throw new RuntimeException('Development dependencies included.'); }
if (!is_file('/workspace/public/build/index.html')) { throw new RuntimeException('Missing SPA build.'); }
fwrite(STDOUT, "PASS: runtime extensions, production dependencies, SPA assets and development isolation.\n");'''
    run(['docker', 'run', '--rm', '--read-only', '--tmpfs', '/tmp', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', 'php', php, '-r', code])
    run(['docker', 'run', '--rm', '--read-only', '--tmpfs', '/tmp', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', 'sh', php, '-c', 'for tool in composer node npm pnpm codex python3 git gcc g++; do if command -v "$tool" >/dev/null 2>&1; then echo "Development tool in runtime: $tool" >&2; exit 1; fi; done'])
    run(['docker', 'run', '--rm', '--read-only', '--tmpfs', '/tmp', '--add-host', 'app:127.0.0.1', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', nginx, '-t'])


def main(args):
    if not args or args == ['--help'] or inside():
        return dispatch(args)
    import operations
    with operations.lock():
        return dispatch(args)
