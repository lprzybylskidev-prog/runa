"""Shared argument-safe development/production command contexts."""
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]


def inside():
    return Path('/.dockerenv').exists()


def compose():
    return ['docker', 'compose', '--project-directory', str(ROOT), '--env-file', str(ROOT / '.env.dev.local'),
            '-f', str(ROOT / 'compose.yaml'), '-f', str(ROOT / '.devcontainer/compose.resources.yaml')]


def run(args, **kwargs):
    return subprocess.run(args, cwd=ROOT, check=True, **kwargs)


def app_command(args):
    return args if inside() else compose() + ['exec', '-T', 'app', *args]


def host_stack_health():
    import json
    result = run(compose() + ['ps', '--all', '--format', 'json'], capture_output=True, text=True).stdout.strip()
    rows = json.loads(result) if result.startswith('[') else [json.loads(line) for line in result.splitlines() if line]
    services = {row['Service']: row for row in rows}
    required = ['app', 'nginx', 'postgres', 'queue', 'scheduler', 'mailpit', 'qdrant', 'embeddings']
    local = ROOT / '.env.dev.local'
    if local.exists() and 'EMBEDDINGS_CUDA=1' in local.read_text().splitlines():
        required.append('embeddings-gpu')
    for service in required:
        row = services.get(service, {})
        if row.get('State') != 'running' or row.get('Health') != 'healthy':
            raise ValueError('Development container is missing/not healthy: ' + service)
    setup = services.get('setup', {})
    if setup.get('State') != 'exited' or setup.get('ExitCode') != 0:
        raise ValueError('Development setup did not complete successfully.')
    print('PASS: host Compose service/container health and setup exit status.')
