import os
import fcntl
from context import ROOT
import subprocess
def execute(suite, args):
    if suite == 'frontend':
        command = ['pnpm', 'exec', 'vitest', 'run', '--config=runa/tests/vitest.config.mjs', *args]
    elif suite == 'e2e':
        command = ['pnpm', 'exec', 'playwright', 'test', '--config=runa/tests/playwright.config.mjs', *[a for a in args if a != '--critical']]
    else:
        if args[:1] in (['frontend'], ['e2e'], ['backend']):
            raise SystemExit('Use the dedicated test-backend/frontend/e2e entrypoint.')
        if '--parallel' in args and not any(a.startswith('--processes') for a in args):
            args.append('--processes=' + os.environ.get('TEST_WORKERS', '4'))
        command = ['vendor/bin/pest', '--configuration=runa/tests/phpunit.xml', '--test-directory=runa/tests', *args]
    if (ROOT / 'src/bootstrap/cache/config.php').is_file():
        raise SystemExit('Clear cached development config before testing: php artisan config:clear')
    directory = ROOT / '.runtime/tests'
    directory.mkdir(parents=True, exist_ok=True)
    with (directory / 'suite.lock').open('a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise SystemExit('Another test suite is running in this checkout.')
        if suite == 'e2e':
            result = subprocess.run(['php', 'runa/resources/errors/build.php'], cwd=ROOT / 'src')
            if result.returncode:
                return result.returncode
        return subprocess.run(command, cwd=ROOT / 'src', env=dict(os.environ, APP_ENV='testing')).returncode
