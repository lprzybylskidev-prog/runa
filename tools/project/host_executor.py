"""Private per-workspace Unix transport for trusted developer/operator commands.

Host-only: no TCP/Docker proxy, caller cwd/env/executable or remote metadata code.
Disconnected operations finish with durable receipts; never automatically retry.
"""
import base64
import fcntl
import hashlib
import hmac
import json
import os
from pathlib import Path
import re
import secrets
import selectors
import signal
import socket
import socketserver
import stat
import struct
import subprocess
import sys
import threading
import time
import contextlib

import management
from runtime import ROOT

LIMIT = 65536
OUTPUT_LIMIT = 64 * 1024 * 1024


def directory():
    return ROOT / '.runtime/host'


def connect(client):
    # /proc/self/fd avoids the Unix pathname limit for long host checkout paths;
    # the client container uses its short mounted /workspace or /run path.
    descriptor = os.open(directory(), os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
    try:
        client.connect('/proc/self/fd/' + str(descriptor) + '/executor.sock')
    finally:
        os.close(descriptor)


def encode(value):
    return json.dumps(value, separators=(',', ':')).encode() + b'\n'


def read_json(stream):
    raw = stream.readline(LIMIT + 1)
    if not raw or len(raw) > LIMIT or not raw.endswith(b'\n'):
        raise ValueError('Missing or oversized transport frame.')
    def unique(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError('Duplicate transport field.')
            result[key] = value
        return result
    return json.loads(raw, object_pairs_hook=unique)


def atomic(path, value):
    from operations import atomic as write
    write(path, value)


def validate_request(value, credentials, challenge):
    if (not isinstance(value, dict) or set(value) != {'version', 'workspace', 'token', 'challenge', 'id', 'command', 'arguments', 'interactive'}
            or type(value['version']) is not int or value['version'] != 1
            or not isinstance(value['token'], str) or not hmac.compare_digest(value['token'], credentials['token'])
            or value['workspace'] != credentials['workspace'] or value['challenge'] != challenge
            or not isinstance(value['id'], str) or not re.fullmatch('[a-f0-9]{32}', value['id'])
            or not isinstance(value['command'], str) or not re.fullmatch('runa:[a-z-]+', value['command'])
            or type(value['interactive']) is not bool
            or not isinstance(value['arguments'], list) or len(value['arguments']) > 128
            or any(not isinstance(arg, str) or len(arg.encode()) > 4096 or '\0' in arg for arg in value['arguments'])):
        raise ValueError('Unauthorized or invalid workspace request.')
    if any(arg == '--host-request' or arg.startswith('--host-request=') or arg in ('--plan', '--help', '-h', '--') for arg in value['arguments']):
        raise ValueError('Transport-only options are forbidden in execution requests.')
    return value


def validated_plan(value, image):
    # Reparse caller arguments in the trusted isolated Artisan registry. Do not
    # execute a caller-supplied plan or trust container-side argument validation.
    request = value['id']
    command = ['docker', 'run', '--rm', '--name', 'runa-host-validate-' + request, '--network', 'none', '--read-only',
               '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--memory=128m', '--cpus=1', '--pids-limit=128',
               '--tmpfs', '/tmp:rw,nosuid,nodev,noexec,size=16m', image, '--management', value['command'],
               *value['arguments'], '--plan', '--host-request=' + request]
    try:
        code, output, _ = management.bounded_output(command)
    finally:
        subprocess.run(['docker', 'rm', '--force', 'runa-host-validate-' + request], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=30)
    if code:
        raise ValueError('Artisan rejected this operation or its arguments.')
    return management.validate_plan(output, request)


class Server(socketserver.ThreadingUnixStreamServer):
    daemon_threads = False
    block_on_close = True
    max_children = threading.BoundedSemaphore(8)


class Handler(socketserver.StreamRequestHandler):
    def handle(self):
        if not self.server.max_children.acquire(blocking=False):
            return
        try:
            self.execute()
        except (ValueError, OSError, KeyError, TypeError, subprocess.SubprocessError):
            try:
                self.wfile.write(encode({'type': 'error', 'message': 'Host request rejected or unavailable. Inspect private host receipts; use ./bin/artisan --recover-host if required.'}))
            except OSError:
                pass
        finally:
            self.server.max_children.release()

    def execute(self):
        self.connection.settimeout(15)
        _, uid, _ = struct.unpack('3i', self.connection.getsockopt(socket.SOL_SOCKET, socket.SO_PEERCRED, 12))
        if uid != os.getuid():
            raise ValueError('Foreign operating-system user.')
        challenge = secrets.token_hex(32)
        self.wfile.write(encode({'type': 'challenge', 'value': challenge}))
        value = validate_request(read_json(self.rfile), self.server.credentials, challenge)
        if management.image_identity() != self.server.identity:
            raise ValueError('Management source changed. Recover the host executor before execution.')
        self.connection.settimeout(None)
        request = value['id']
        receipt = directory() / 'operations' / (request + '.json')
        descriptor = os.open(receipt, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
        state = {'id': request, 'command': value['command'], 'state': 'validating', 'started_at': time.time(), 'connected': True}
        with os.fdopen(descriptor, 'w') as stream:
            json.dump(state, stream)
        try:
            plan = validated_plan(value, self.server.image)
            if value['command'] == 'runa:host':
                args = value['arguments']
                if args[:1] == ['status']:
                    result = self.server.public_status(args[1] if len(args) == 2 else None)
                    self.wfile.write(encode({'type': 'stdout', 'data': base64.b64encode((json.dumps(result, indent=2) + '\n').encode()).decode()}))
                elif args == ['stop']:
                    threading.Thread(target=self.server.shutdown, daemon=True).start()
                else:
                    raise ValueError('Invalid executor control.')
                self.wfile.write(encode({'type': 'exit', 'code': 0}))
                state.update(state='completed', exit_code=0)
            else:
                state.update(state='running')
                atomic(receipt, state)
                self.wfile.write(encode({'type': 'accepted', 'id': request}))
                self.run_operation(plan, value['interactive'], state, receipt)
        except BaseException:
            if state['state'] in ('validating', 'running'):
                state.update(state='failed', exit_code=1)
            raise
        finally:
            state['finished_at'] = time.time()
            atomic(receipt, state)

    def run_operation(self, plan, interactive, state, receipt):
        # Fresh process owns existing locking/executors. Its stdin contains one
        # trusted plan frame followed by explicit operator input, never shell code.
        command = [sys.executable, str(ROOT / 'tools/project/host_executor.py'), '--worker']
        child = subprocess.Popen(command, cwd=ROOT, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                                 start_new_session=True, env=self.server.environment)
        child.stdin.write(encode(plan))
        child.stdin.flush()
        if not interactive:
            child.stdin.close()
        connected = True
        total = 0
        log = directory() / 'operations' / (plan['request_id'] + '.log')
        with open(log, 'xb', opener=lambda p, flags: os.open(p, flags, 0o600)) as journal, selectors.DefaultSelector() as selector:
            selector.register(child.stdout, selectors.EVENT_READ, 'stdout')
            selector.register(child.stderr, selectors.EVENT_READ, 'stderr')
            selector.register(self.connection, selectors.EVENT_READ, 'client')
            client_buffer = bytearray()
            try:
                while any(key.data != 'client' for key in selector.get_map().values()):
                    for key, _ in selector.select(timeout=1):
                        if key.data == 'client':
                            raw = self.connection.recv(4096)
                            if not raw:
                                selector.unregister(self.connection)
                                connected = False
                                state['connected'] = False
                                atomic(receipt, state)
                                if not child.stdin.closed:
                                    child.stdin.close()
                                continue
                            client_buffer.extend(raw)
                            if len(client_buffer) > LIMIT:
                                raise ValueError('Input frame exceeds bound.')
                            while b'\n' in client_buffer:
                                line, _, rest = client_buffer.partition(b'\n')
                                client_buffer = bytearray(rest)
                                message = json.loads(line)
                                if message == {'type': 'cancel'}:
                                    os.killpg(child.pid, signal.SIGINT)
                                    state['cancel_requested'] = True
                                elif set(message) == {'type', 'data'} and message['type'] == 'stdin' and interactive:
                                    content = base64.b64decode(message['data'], validate=True)
                                    if len(content) > 4096 or child.stdin.closed:
                                        raise ValueError('Invalid interactive input.')
                                    child.stdin.write(content)
                                    child.stdin.flush()
                                elif message == {'type': 'eof'}:
                                    if not child.stdin.closed:
                                        child.stdin.close()
                                else:
                                    raise ValueError('Invalid input frame.')
                        else:
                            raw = os.read(key.fileobj.fileno(), 4096)
                            if not raw:
                                selector.unregister(key.fileobj)
                                continue
                            total += len(raw)
                            if total > OUTPUT_LIMIT:
                                raise ValueError('Operation output exceeds the private log bound.')
                            journal.write(encode({'stream': key.data, 'data': base64.b64encode(raw).decode()}))
                            if connected:
                                try:
                                    self.wfile.write(encode({'type': key.data, 'data': base64.b64encode(raw).decode()}))
                                except OSError:
                                    connected = False
                                    state['connected'] = False
                code = child.wait()
                state.update(state='completed' if code == 0 else 'failed', exit_code=code)
                if connected:
                    try:
                        self.wfile.write(encode({'type': 'exit', 'code': code if code >= 0 else 128 - code}))
                    except OSError:
                        state['connected'] = False
            except BaseException:
                if child.poll() is None:
                    os.killpg(child.pid, signal.SIGTERM)
                    try:
                        child.wait(timeout=10)
                    except subprocess.TimeoutExpired:
                        os.killpg(child.pid, signal.SIGKILL)
                        child.wait()
                state.update(state='failed', exit_code=1, inspect_recovery=True)
                raise


def serve():
    management.private_directory(directory())
    management.private_directory(directory() / 'operations')
    descriptor = os.open(directory() / 'daemon.lock', os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    with os.fdopen(descriptor, 'w') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        image = management.ensure_image()
        endpoint = directory() / 'executor.sock'
        if endpoint.is_symlink():
            raise ValueError('Host socket must not be a symlink.')
        if endpoint.exists():
            endpoint.unlink()
        credentials = {'version': 1, 'workspace': secrets.token_hex(32), 'token': secrets.token_hex(32), 'pid': os.getpid(), 'identity': management.image_identity()}
        # Capture only administrator-chosen startup context; no client env/cwd.
        environment = dict(os.environ)
        for name in list(environment):
            if name.startswith(('GIT_', 'COMPOSE_', 'DOCKER_')):
                environment.pop(name)
        environment['RUNA_HOST_WORKER'] = '1'
        os.chdir(directory())
        with Server('executor.sock', Handler) as server:
            os.chmod(endpoint, 0o600)
            server.credentials, server.image, server.identity, server.environment = credentials, image, management.image_identity(), environment
            def status(request=None):
                if request:
                    if not re.fullmatch('[a-f0-9]{32}', request):
                        raise ValueError('Invalid operation ID.')
                    return json.loads((directory() / 'operations' / (request + '.json')).read_text())
                return {'state': 'ready', 'workspace': credentials['workspace'], 'pid': os.getpid(),
                        'operations': [json.loads(path.read_text()) for path in sorted((directory() / 'operations').glob('*.json'))[-50:]]}
            server.public_status = status
            atomic(directory() / 'client.json', credentials)
            def stop(signum, frame):
                threading.Thread(target=server.shutdown, daemon=True).start()
            signal.signal(signal.SIGTERM, stop)
            signal.signal(signal.SIGINT, stop)
            try:
                server.serve_forever(poll_interval=0.2)
            finally:
                # Wait for in-flight mutation workers; no implicit retries/cancellation.
                server.server_close()
                endpoint.unlink(missing_ok=True)
                (directory() / 'client.json').unlink(missing_ok=True)


def ensure():
    if Path('/.dockerenv').exists():
        raise ValueError('Host executor recovery must run on the native host.')
    management.private_directory(directory())
    try:
        with socket.socket(socket.AF_UNIX) as client:
            client.settimeout(1)
            connect(client)
            response = read_json(client.makefile('rb'))
            if response.get('type') == 'challenge':
                info = json.loads((directory() / 'client.json').read_text())
                # Pin source identity too: an old daemon must be deliberately stopped
                # before a changed command registry can acquire host privileges.
                if info.get('identity') and info['identity'] != management.image_identity():
                    raise ValueError('Executor registry changed; stop it before recovery.')
                return
    except (OSError, ValueError, KeyError):
        # A live lock disambiguates stale filesystem state from a running daemon.
        descriptor = os.open(directory() / 'daemon.lock', os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
        with os.fdopen(descriptor, 'w') as lock:
            try:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                raise ValueError('Host executor is running but unavailable/stale; stop it deliberately before recovery.') from None
    with open(directory() / 'daemon.log', 'ab', opener=lambda p, flags: os.open(p, flags, 0o600)) as log:
        subprocess.Popen([sys.executable, str(ROOT / 'tools/project/host_executor.py'), '--serve'], cwd=ROOT,
                         stdin=subprocess.DEVNULL, stdout=log, stderr=log, start_new_session=True)
    for _ in range(120):
        try:
            with socket.socket(socket.AF_UNIX) as client:
                client.settimeout(1)
                connect(client)
                if read_json(client.makefile('rb')).get('type') == 'challenge' and (directory() / 'client.json').is_file():
                    return
        except (OSError, ValueError):
            pass
        time.sleep(0.5)
    raise ValueError('Host executor did not become ready. Inspect its private daemon.log and recover explicitly.')


def control(arguments):
    """Recovery-only native client, using the same authenticated protocol."""
    credentials = json.loads((directory() / 'client.json').read_text())
    with socket.socket(socket.AF_UNIX) as client:
        connect(client)
        stream = client.makefile('rwb', buffering=0)
        challenge = read_json(stream)
        stream.write(encode({'version': 1, 'workspace': credentials['workspace'], 'token': credentials['token'],
                             'challenge': challenge['value'], 'id': secrets.token_hex(16), 'command': 'runa:host',
                             'arguments': arguments, 'interactive': False}))
        while True:
            frame = read_json(stream)
            if frame['type'] == 'exit':
                return frame['code']
            if frame['type'] == 'error':
                raise ValueError('Host executor rejected control; inspect private receipts.')
            if frame['type'] in ('stdout', 'stderr'):
                target = sys.stdout.buffer if frame['type'] == 'stdout' else sys.stderr.buffer
                target.write(base64.b64decode(frame['data'], validate=True))
                target.flush()


def stop():
    """Graceful recovery even when the old registry cannot validate new commands."""
    descriptor = os.open(directory() / 'daemon.lock', os.O_RDWR | os.O_NOFOLLOW)
    with os.fdopen(descriptor, 'w') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            return
        except BlockingIOError:
            credentials = json.loads((directory() / 'client.json').read_text())
            pid = credentials['pid']
            cmdline = Path('/proc', str(pid), 'cmdline').read_bytes().split(b'\0')
            if os.fsencode(ROOT / 'tools/project/host_executor.py') not in cmdline or b'--serve' not in cmdline:
                raise ValueError('Executor PID ownership is uncertain; inspect it before recovery.')
            os.kill(pid, signal.SIGTERM)
    for _ in range(100):
        if not (directory() / 'client.json').exists():
            return
        time.sleep(0.1)
    raise ValueError('Host executor is draining active operations; inspect receipts before restarting.')


if __name__ == '__main__':
    try:
        if sys.argv[1:] == ['--serve']:
            serve()
        elif sys.argv[1:] == ['--worker'] and os.environ.get('RUNA_HOST_WORKER') == '1':
            plan = read_json(sys.stdin.buffer)
            raise SystemExit(management.execute_plan(plan, plan['request_id']))
        elif sys.argv[1:2] == ['--control']:
            raise SystemExit(control(sys.argv[2:]))
        else:
            raise ValueError('Internal executor entrypoint; use Artisan or host recovery transport.')
    except KeyboardInterrupt:
        print('Host operation interrupted. Inspect its receipt and existing recovery markers before retrying.', file=sys.stderr)
        raise SystemExit(130)
    except (ValueError, OSError, KeyError, TypeError, subprocess.SubprocessError):
        print('Host execution failed. Inspect the private operation receipt; do not retry a mutation blindly.', file=sys.stderr)
        raise SystemExit(1)
