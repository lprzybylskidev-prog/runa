#!/usr/bin/env python3
"""Versioned Git hook entrypoint."""
from pathlib import Path
import subprocess
import os
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
from runtime import compose, inside
from hooks import commit_message, pre_commit

try:
    args = sys.argv[1:]
    if args == ['--help']:
        print('Usage: ./distribution/maintenance/bin/git-hook pre-commit|commit-msg <message-file>\nFast staged-content and Conventional/RM-aware checks.')
    elif not inside():
        extra = []
        if os.environ.get('GIT_INDEX_FILE'):
            index = Path(os.environ['GIT_INDEX_FILE']).resolve()
            relative = index.relative_to(ROOT)
            extra = ['-e', 'GIT_INDEX_FILE=/workspace/' + relative.as_posix()]
        if len(args) == 2 and Path(args[1]).is_absolute():
            args[1] = '/workspace/' + Path(args[1]).relative_to(ROOT).as_posix()
        raise SystemExit(subprocess.run(compose() + ['exec', '-T', *extra, 'app', './distribution/maintenance/bin/git-hook', *args], cwd=ROOT).returncode)
    elif args == ['pre-commit']:
        repository = Path(subprocess.run(['git', 'rev-parse', '--show-toplevel'], check=True, capture_output=True, text=True).stdout.strip())
        pre_commit(repository, ROOT)
    elif len(args) == 2 and args[0] == 'commit-msg':
        repository = Path(subprocess.run(['git', 'rev-parse', '--show-toplevel'], check=True, capture_output=True, text=True).stdout.strip())
        commit_message(repository, args[1])
    else:
        raise ValueError('See ./distribution/maintenance/bin/git-hook --help.')
except (ValueError, OSError, subprocess.CalledProcessError, SyntaxError) as error:
    print(f'Hook rejected: {error}', file=sys.stderr)
    sys.exit(1)
