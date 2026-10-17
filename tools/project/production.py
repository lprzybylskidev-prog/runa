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
        if not separator or key not in ('RUNA_IMAGE_TAG', 'RUNA_HTTP_PORT') or key in values:
            raise ValueError('Invalid production Compose setting; use the example without quoting or interpolation.')
        values[key] = value
    if not re.fullmatch(r'(?:v\d+\.\d+\.\d+|[a-f0-9]{40})', values.get('RUNA_IMAGE_TAG', '')):
        raise ValueError('RUNA_IMAGE_TAG requires an exact vX.Y.Z release or full lowercase Git commit hash.')
    port = values.get('RUNA_HTTP_PORT', '8081')
    if not port.isascii() or not port.isdigit() or not 1024 <= int(port) <= 65535:
        raise ValueError('RUNA_HTTP_PORT must be an unprivileged TCP port (1024–65535).')
    return ['docker', 'compose', '--project-name', 'runa-prod', '--project-directory', str(ROOT), '--env-file', str(path), '-f', str(ROOT / 'compose.production.yaml')]


def main(args):
    if args == ['--help'] or not args:
        print('Usage: ./bin/production validate|build|up|down|restart|status|logs [service]\nHost terminal only; requires .env.prod.compose, .env.prod and .env.prod.database.\nBuild creates exact-tag PHP/Nginx images. Up expects migrations/production seeding already complete. Down preserves volumes. Deployment/self-test/isolation operations follow in RM-2026-001 point 14.')
        return
    if inside():
        raise ValueError('Run production Docker operations in a host terminal outside the devcontainer; no Docker socket is mounted here.')
    action, *services = args
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
    if commands[action]:
        run(command + commands[action], env=environment)
    if action == 'build':
        verify_images(command, environment)
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
