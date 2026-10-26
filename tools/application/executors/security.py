#!/usr/bin/env python3
"""Scan the changed runtime and validate real agent security-review evidence."""
import argparse
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
if __name__ == '__main__' and not __package__ and Path(sys.argv[0]).resolve() == Path(__file__).resolve():
    raise SystemExit(subprocess.call([sys.executable, '-m', 'tools.application.executors.security', *sys.argv[1:]], cwd=ROOT))
from tools.application import security

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('action', choices=['trivy', 'review-changed-surface', 'dependencies', 'secrets'])
parser.add_argument('--dependencies-root', type=Path, help='Existing development checkout for audits from an isolated installer clone; lockfiles must match')
try:
    options = parser.parse_args()
    security.AUDIT_ROOT = options.dependencies_root.resolve() if options.dependencies_root else None
    if options.action == 'trivy':
        from tools.application import operations
        with operations.lock():
            security.trivy()
    else:
        {'trivy': security.trivy, 'review-changed-surface': security.review, 'dependencies': security.dependencies, 'secrets': security.secrets_scan}[options.action]()
except (ValueError, OSError, KeyError, subprocess.CalledProcessError) as error:
    print('Security failed: ' + (f'child command exited {error.returncode}.' if isinstance(error, subprocess.CalledProcessError) else str(error)), file=sys.stderr)
    sys.exit(1)
