#!/usr/bin/env python3
"""Run the canonical local quality graph."""
import fcntl
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
from runtime import app_command, inside
import quality

try:
    prefix = getattr(quality, 'COMMAND_PREFIX', 'php artisan runa:quality:')
    args = sys.argv[1:]
    if args == ['--help']:
        print('Usage: ' + prefix + 'plan|roadmap|final [--group=NAME ... | --all] [--background]; status\nManual groups include mandatory checks and prerequisites. No flags retain legacy selection. Background is supported for roadmap/final. No automatic timeouts.')
        raise SystemExit(0)
    groups, all_groups, background = quality.arguments(args)
    # Resolve and validate the complete selection before any filesystem mutation.
    if args[0] != 'status':
        roadmap, nodes = quality.plan(args[0] == 'final', **({'groups': groups, 'all_groups': all_groups} if groups or all_groups else {}))
        selection = roadmap.get('quality_selection', {'mode': 'legacy'})
        if args[0] == 'final':
            quality.validate_declared(roadmap, selection)
        if args[0] == 'show':
            quality.show_scope(selection)
            for key, node in nodes.items():
                print(key + ': ' + node['command'] + ' [after: ' + ', '.join(node['depends_on']) + ']')
            raise SystemExit(0)
    directory = getattr(quality, 'RUN_DIRECTORY', ROOT / '.runtime/application-quality')
    directory.mkdir(parents=True, exist_ok=True)
    # Stable lock inode lives outside the replaceable latest-run directory.
    with (directory.parent / 'quality.lock').open('a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            active = False
        except BlockingIOError:
            active = True
        if args[0] == 'status':
            quality.status(directory, active)
            raise SystemExit(0)
        if inside() and any(node.get('execution_context') == 'host' for node in nodes.values()):
            raise ValueError('Selected production gates need the Docker host. Run ' + prefix + args[0] + ' in the host terminal; it delegates application checks to the devcontainer.')
        if active:
            raise ValueError('Another quality run is active. Use ' + prefix + 'status.')
        # Refuse surviving children from a crashed coordinator before replacing evidence.
        prior = directory / 'state.json'
        if prior.exists():
            for node in json.loads(prior.read_text()).get('steps', {}).values():
                if node.get('status') in ('running', 'interrupted') and Path('/proc/' + str(node.get('pid'))).exists():
                    raise ValueError('Previous quality child is still alive; stop it before restarting.')
        shutil.rmtree(directory)
        directory.mkdir()
        state = {'status': 'running', 'mode': args[0], 'roadmap': roadmap['id'], 'impact_areas': roadmap['impact_areas'], 'branch': subprocess.check_output(['git', 'branch', '--show-current'], cwd=ROOT, text=True).strip(), 'commit': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip(), 'steps': {k: dict(n, status='pending') for k, n in nodes.items()}}
        state['selection'] = selection
        state['rationale'] = roadmap.get('quality_rationale')
        quality.save(directory, state)
        if background:
            pid = os.fork()
            if pid:
                print('Quality started in background. Use ' + prefix + 'status.', flush=True)
                os._exit(0)
            os.setsid()
            with open(os.devnull, 'r') as source, (directory / 'runner.log').open('a') as output:
                os.dup2(source.fileno(), 0)
                os.dup2(output.fileno(), 1)
                os.dup2(output.fileno(), 2)
        result = quality.execute(directory, state)
        print('Overall status: ' + state['status'])
        raise SystemExit(result)
except (ValueError, OSError, KeyError, subprocess.CalledProcessError) as error:
    print('Quality failed: ' + str(error), file=sys.stderr)
    sys.exit(1)
