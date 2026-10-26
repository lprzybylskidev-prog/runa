"""Compatibility transport for the workspace-scoped host executor."""
from pathlib import Path
import os
import subprocess
import sys

def directory():
    return Path(__file__).resolve().parents[2] / '.runtime/host'

def ensure():
    from tools.application.host_transport import default_service
    return default_service().ensure()

def stop():
    from tools.application.host_transport import default_service
    return default_service().stop()

if __name__ == '__main__':
    root = Path(__file__).resolve().parents[2]
    raise SystemExit(subprocess.call([sys.executable, '-m', 'tools.application.host_transport', *sys.argv[1:]],
        cwd=root, env=dict(os.environ, PYTHONPYCACHEPREFIX=str(root / '.runtime/python/pycache'))))
