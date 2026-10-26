"""Shared argument-safe development/production command contexts."""
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[2]


def inside():
    from tools.application.development_runtime import execution_location
    from tools.core.context import ExecutionLocation
    return execution_location() is ExecutionLocation.CONTAINER


def compose():
    from tools.application.development_runtime import development_compose
    from tools.core.context import WorkspaceContext
    return list(development_compose(WorkspaceContext(ROOT)).command())


def run(args, **kwargs):
    return subprocess.run(args, cwd=ROOT, check=True, **kwargs)


def app_command(args):
    return args if inside() else compose() + ['exec', '-T', 'app', *args]


def host_stack_health():
    from tools.application.management import service
    return service().host_stack_health()
