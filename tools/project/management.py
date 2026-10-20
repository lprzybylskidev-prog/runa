"""Bounded container transport and an allowlisted host-plan executor, never a CLI registry."""
import hashlib
import json
import os
from pathlib import Path
import re
import selectors
import stat
import subprocess
import sys
import time
import uuid

from runtime import ROOT

MAX_OUTPUT = 65536
TIMEOUT = 30
DEVELOPMENT_ACTIONS = ('prepare', 'up', 'down', 'config', 'shell', 'profile', 'embeddings', 'build', 'recreate', 'restart', 'logs', 'status', 'setup', 'doctor', 'knowledge-init', 'knowledge-status')
TOOLS = ('doctor', 'quality', 'knowledge', 'security', 'project', 'xdebug', 'check', 'hook', 'format', 'lint', 'typecheck', 'analyse', 'test', 'production', 'deploy', 'backup', 'install', 'host')
OPERATIONS = {**{'development.' + action: action for action in DEVELOPMENT_ACTIONS},
              **{'tooling.' + tool: tool for tool in TOOLS}}


def image_identity():
    digest = hashlib.sha256()
    paths = [ROOT / name for name in ('src/artisan', 'src/composer.json', 'src/composer.lock', 'docker/php/Dockerfile', '.dockerignore')]
    if not (ROOT / 'src/runa/management').is_dir() or (ROOT / 'src/runa/management').is_symlink():
        raise ValueError('Management build inputs must not traverse symlinks.')
    paths += sorted(path for path in (ROOT / 'src/runa/management').rglob('*') if not path.is_dir() or path.is_symlink())
    for path in paths:
        if path.is_symlink() or not path.is_file():
            raise ValueError('Missing regular management build input.')
        digest.update(str(path.relative_to(ROOT)).encode() + b'\0' + path.read_bytes() + b'\0')
    return digest.hexdigest()


def ensure_image():
    identity = image_identity()
    tag = 'runa-management:' + identity[:24]
    inspect = ['docker', 'image', 'inspect', '--format', '{{.Id}} {{index .Config.Labels "runa.management.inputs"}}', tag]
    result = subprocess.run(inspect, cwd=ROOT, capture_output=True, text=True, timeout=TIMEOUT)
    if result.returncode:
        print('Building the isolated management image (no app services are started).', file=sys.stderr)
        target = 'development-management' if (ROOT / 'src/runa/management/development.php').is_file() else 'management'
        subprocess.run(['docker', 'build', '--target', target, '--file', 'docker/php/Dockerfile',
                        '--label', 'runa.management.inputs=' + identity, '--tag', tag, '.'], cwd=ROOT, check=True,
                       stdout=sys.stderr, stderr=sys.stderr)
        result = subprocess.run(inspect, cwd=ROOT, capture_output=True, text=True, timeout=TIMEOUT, check=True)
    fields = result.stdout.strip().split()
    if len(fields) != 2 or not re.fullmatch(r'sha256:[a-f0-9]{64}', fields[0]) or fields[1] != identity:
        raise ValueError('Management image identity/inputs mismatch; inspect the local image before retrying.')
    return fields[0]


def bounded_output(command):
    """Drain both streams with a shared byte limit/deadline, including on command failure."""
    output = {1: bytearray(), 2: bytearray()}
    with subprocess.Popen(command, cwd=ROOT, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE, stderr=subprocess.PIPE) as child:
        try:
            with selectors.DefaultSelector() as selector:
                selector.register(child.stdout, selectors.EVENT_READ, 1)
                selector.register(child.stderr, selectors.EVENT_READ, 2)
                deadline = time.monotonic() + TIMEOUT
                while selector.get_map():
                    remaining = deadline - time.monotonic()
                    if remaining <= 0:
                        raise ValueError('Management discovery timed out.')
                    for key, _ in selector.select(min(remaining, 1)):
                        data = os.read(key.fileobj.fileno(), 4096)
                        if not data:
                            selector.unregister(key.fileobj)
                        else:
                            output[key.data].extend(data)
                            if sum(len(value) for value in output.values()) > MAX_OUTPUT:
                                raise ValueError('Management output exceeded the transport limit.')
                status = child.wait(timeout=max(0.01, deadline - time.monotonic()))
            return status, bytes(output[1]).decode('utf-8'), bytes(output[2]).decode('utf-8')
        except BaseException:
            child.kill()
            child.wait()
            raise


def validate_plan(raw, request):
    def unique_object(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError('Duplicate host-plan field.')
            result[key] = value
        return result

    try:
        plan = json.loads(raw, object_pairs_hook=unique_object)
    except (json.JSONDecodeError, UnicodeError) as error:
        raise ValueError('Management did not return a valid JSON host plan.') from error
    if (not isinstance(plan, dict) or set(plan) != {'schema_version', 'request_id', 'operation', 'parameters'}
            or type(plan['schema_version']) is not int or plan['schema_version'] != 1
            or plan['request_id'] != request or not re.fullmatch(r'[a-f0-9]{32}', request)
            or not isinstance(plan['operation'], str) or plan['operation'] not in OPERATIONS
            or type(plan['parameters']) is not dict):
        raise ValueError('Unsupported, unbound or unsafe host plan.')
    parameters = plan['parameters']
    if set(parameters) - {'arguments'} or ('arguments' not in parameters and not plan['operation'].startswith('development.')):
        raise ValueError('Unsupported host-plan parameters.')
    arguments = parameters.get('arguments', [])
    if (not isinstance(arguments, list) or len(arguments) > 128
            or any(not isinstance(value, str) or len(value.encode()) > 4096 or '\x00' in value for value in arguments)):
        raise ValueError('Invalid bounded host-plan arguments.')
    return plan


def private_directory(path):
    for parent in reversed([path, *path.parents]):
        if parent.is_symlink():
            raise ValueError('Management state must not traverse symlinks.')
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    info = path.stat()
    if not stat.S_ISDIR(info.st_mode) or info.st_uid != os.getuid() or stat.S_IMODE(info.st_mode) & 0o077:
        raise ValueError('Management state directory must be private and owned by the current operator.')


def execute_plan(plan, request):
    # Revalidate at the execution boundary; no caller can supply shell/argv/env/path fields.
    plan = validate_plan(json.dumps(plan), request)
    import operations
    directory = ROOT / '.runtime/management'
    private_directory(directory)
    for path in (operations.STATE.parent, operations.STATE, operations.STATE / 'operation.lock'):
        if path.is_symlink():
            raise ValueError('Operation state/lock must not be a symlink.')
    from contextlib import nullcontext
    with operations.lock() if plan['operation'].startswith('development.') else nullcontext():
        if plan['operation'].startswith('development.') and plan['operation'] != 'development.status' and (operations.STATE / 'in-progress.json').exists():
            raise ValueError('An unresolved production operation requires explicit recovery before host mutation.')
        path = directory / (request + '.json')
        descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
        try:
            with os.fdopen(descriptor, 'w') as stream:
                json.dump(plan, stream)
                stream.write('\n')
            # Fixed trusted executable and action, never instructions from release/retrieval text.
            result = subprocess.run(execution_command(plan), cwd=ROOT)
            return result.returncode
        finally:
            path.unlink()


def execution_command(plan):
    """Fixed executable families only; plan data never supplies an executable or shell."""
    from runtime import app_command, host_stack_health, compose
    operation = plan['operation']
    arguments = plan['parameters'].get('arguments', [])
    if operation.startswith('development.'):
        action = OPERATIONS[operation]
        if action in ('setup', 'knowledge-init', 'knowledge-status', 'doctor'):
            if action == 'doctor':
                host_stack_health()
            return app_command(['php', 'artisan', 'runa:dev', action, *arguments])
        return ['python3', str(ROOT / 'tools/project/executors/dev.py'), action, *arguments]
    tool = OPERATIONS[operation]
    if tool == 'host':
        return ['python3', str(ROOT / 'tools/project/host_executor.py'), '--control', *arguments]
    if tool in ('production', 'quality', 'security', 'project'):
        return ['python3', str(ROOT / ('tools/project/executors/' + tool + '.py')), *arguments]
    if tool == 'install':
        return ['python3', str(ROOT / 'tools/project/executors/install.py'), *arguments]
    if tool in ('deploy', 'backup'):
        return ['python3', str(ROOT / 'tools/project/executors/operations.py'),
                tool, *arguments]
    if tool == 'hook':
        return [str(ROOT / 'bin/git-hook'), *arguments]
    if tool == 'check':
        return ['python3', str(ROOT / 'tools/project/executors/quality_check.py'), *arguments]
    if tool == 'doctor':
        if arguments == ['production']:
            return ['python3', str(ROOT / 'tools/project/executors/production.py'), 'self-test']
        if not arguments or arguments == ['development']:
            host_stack_health()
    if tool == 'test' and arguments and arguments[0] == 'e2e':
        subprocess.run(compose() + ['--profile', 'testing', 'up', '-d', '--wait', 'nginx-test'], cwd=ROOT, check=True)
    return app_command(['php', 'artisan', 'runa:' + tool, *arguments])


def is_management_command(name):
    # Reuse the same bounded, named-container lifecycle as every other discovery.
    import contextlib
    import io
    with contextlib.redirect_stdout(io.StringIO()) as output:
        status = launch(['list', '--raw'])
    if status:
        raise ValueError('Management command discovery failed.')
    return name in {line.split()[0] for line in output.getvalue().splitlines() if line.strip()}


def launch(args):
    if any(arg == '--host-request' or arg.startswith('--host-request=') for arg in args):
        raise ValueError('--host-request is reserved for the host transport.')
    request = uuid.uuid4().hex
    planning = bool(args and args[0].startswith('runa:') and '--help' not in args and '-h' not in args)
    image = ensure_image()
    name = 'runa-management-' + request
    command = ['docker', 'run', '--rm', '--name', name, '--network', 'none', '--read-only',
               '--cap-drop', 'ALL', '--security-opt', 'no-new-privileges', '--pids-limit', '128',
               '--memory', '128m', '--cpus', '1', '--tmpfs', '/tmp:rw,nosuid,nodev,noexec,size=16m', image, '--management', *args]
    if planning:
        command += ['--host-request=' + request]
    try:
        status, output, _ = bounded_output(command)
    except BaseException:
        # Killing the Docker client alone does not reliably remove its daemon-side process.
        subprocess.run(['docker', 'rm', '--force', name], capture_output=True, timeout=TIMEOUT)
        raise
    if status:
        print('Management command rejected or failed; use --management help <command> for accepted arguments.', file=sys.stderr)
        return status
    if not planning:
        print(output, end='')
        return 0
    plan = validate_plan(output, request)
    if '--plan' in args:
        print(json.dumps(plan, indent=2))
        return 0
    return execute_plan(plan, request)
