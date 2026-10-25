import os
import fcntl
from context import ROOT
import subprocess
import signal


def worker_cleanup():
    return subprocess.run(['php', 'runa/Testing/worker-databases.php'], cwd=ROOT / 'src',
                          env=dict(os.environ, APP_ENV='testing', TEST_TOKEN='')).returncode


def interrupted(signum, frame):
    raise KeyboardInterrupt


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
        if '--parallel' in args and not any(a.startswith(('--max-batch-size', '-m')) for a in args):
            # Owner integration files rebuild large application/module graphs. Do
            # not retain native PHP state from another file in the same worker.
            args.append('--max-batch-size=1')
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
        previous = signal.signal(signal.SIGTERM, interrupted)
        try:
            if suite == 'backend':
                status = worker_cleanup()
                if status:
                    return status
            try:
                if suite == 'e2e':
                    result = subprocess.run(['php', 'runa/resources/errors/build.php'], cwd=ROOT / 'src')
                    if result.returncode:
                        return result.returncode
                # Kill/reap the runner and its workers before releasing the lock
                # or dropping databases when interrupted.
                with subprocess.Popen(command, cwd=ROOT / 'src', env=dict(os.environ, APP_ENV='testing'), start_new_session=True) as child:
                    try:
                        status = child.wait()
                    except BaseException:
                        os.killpg(child.pid, signal.SIGTERM)
                        try:
                            child.wait(timeout=10)
                        except subprocess.TimeoutExpired:
                            os.killpg(child.pid, signal.SIGKILL)
                            child.wait()
                        raise
            finally:
                cleanup = worker_cleanup() if suite == 'backend' else 0
            return status if status else cleanup
        finally:
            signal.signal(signal.SIGTERM, previous)
