"""Dependency-driven local verification with durable latest-run evidence."""
import json
import os
from pathlib import Path
import re
import shlex
import signal
import subprocess
import time
from datetime import datetime, timezone
from checks import yaml
from runtime import ROOT, inside, app_command


def select(definition, areas, final=False):
    nodes = {}
    for node in definition['base'] + [n for area in areas for n in definition['impact_areas'][area]]:
        if node['id'] in nodes and nodes[node['id']] != node:
            raise ValueError('Conflicting step: ' + node['id'])
        nodes[node['id']] = dict(node)
    preceding = list(nodes)
    if final:
        for node in definition['final']:
            if node['id'] in nodes:
                raise ValueError('Duplicate final step: ' + node['id'])
            nodes[node['id']] = dict(node, depends_on=list(dict.fromkeys(preceding + node['depends_on'])))
    for key, node in nodes.items():
        if not re.fullmatch(r'[a-z0-9-]+', key) or node['severity'] not in ('hard_fail', 'warning'):
            raise ValueError('Invalid step: ' + key)
        node['depends_on'] = list(dict.fromkeys(node['depends_on'] + [d for d in node.get('depends_on_if_selected', []) if d in nodes]))
        if any(d not in nodes for d in node['depends_on']):
            raise ValueError('Missing dependency: ' + key)
        shlex.split(node['command'])
    done = set()
    while len(done) < len(nodes):
        ready = {k for k, n in nodes.items() if k not in done and set(n['depends_on']) <= done}
        if not ready:
            raise ValueError('Dependency cycle.')
        done.update(ready)
    return nodes


def plan(final=False, roadmap_root=None, definition_path=None):
    active = sorted((roadmap_root or ROOT / 'docs/app/roadmaps/active').glob('*.md'))
    definition = definition_path or ROOT / 'tools/application/quality.yml'
    if not active and roadmap_root is None:
        return {'id': 'application', 'impact_areas': []}, select(yaml(definition), [], final)
    if len(active) != 1:
        raise ValueError('Exactly one active roadmap is required.')
    header = active[0].read_text().split('---', 2)[1]
    # Use the existing Symfony YAML parser without introducing Python dependencies.
    result = subprocess.run(app_command(['php', '-r', 'require "src/vendor/autoload.php"; echo json_encode(Symfony\\Component\\Yaml\\Yaml::parse(stream_get_contents(STDIN)));']), input=header, text=True, capture_output=True, check=True, cwd=ROOT)
    roadmap = json.loads(result.stdout)
    return roadmap, select(yaml(definition), roadmap['impact_areas'], final)


def save(directory, state):
    temporary = directory / 'state.tmp'
    temporary.write_text(json.dumps(state, indent=2) + '\n')
    temporary.replace(directory / 'state.json')


def execute(directory, state):
    running = {}
    started = {}
    run_started = time.monotonic()
    state['started_at'] = datetime.now(timezone.utc).isoformat()
    interrupted = False

    def stop(signum, frame):
        nonlocal interrupted
        interrupted = True

    old = {sig: signal.signal(sig, stop) for sig in (signal.SIGTERM, signal.SIGINT, signal.SIGHUP)}
    try:
        while True:
            for key, (process, log) in list(running.items()):
                code = process.poll()
                if code is not None:
                    log.close()
                    state['steps'][key].update(status='passed' if code == 0 else 'failed', exit_code=code,
                                             finished_at=datetime.now(timezone.utc).isoformat(),
                                             duration_seconds=round(time.monotonic() - started.pop(key), 3))
                    del running[key]
            if interrupted:
                for key, (process, log) in running.items():
                    os.killpg(process.pid, signal.SIGTERM)
                    state['steps'][key]['status'] = 'interrupted'
                for key, (process, log) in running.items():
                    process.wait()
                    log.close()
                    state['steps'][key].update(finished_at=datetime.now(timezone.utc).isoformat(),
                                              duration_seconds=round(time.monotonic() - started.pop(key), 3))
                state['status'] = 'interrupted'
                break
            groups = {state['steps'][k].get('exclusive_group') for k in running}
            for key, node in state['steps'].items():
                if node['status'] != 'pending':
                    continue
                deps = [state['steps'][d]['status'] for d in node['depends_on']]
                if any(s in ('failed', 'blocked', 'interrupted') for s in deps):
                    node['status'] = 'blocked'
                elif all(s == 'passed' for s in deps) and len(running) < 4:
                    group = node.get('exclusive_group')
                    if group and group in groups:
                        continue
                    log = (directory / (key + '.log')).open('w')
                    started[key] = time.monotonic()
                    node['started_at'] = datetime.now(timezone.utc).isoformat()
                    try:
                        arguments = shlex.split(node['command'])
                        if node.get('execution_context') == 'host':
                            if inside():
                                raise OSError('This gate requires the host coordinator: invoke the selected explicit quality workflow through the Docker host executor.')
                        elif not inside():
                            arguments = app_command(arguments)
                        process = subprocess.Popen(arguments, cwd=ROOT, stdout=log, stderr=subprocess.STDOUT, start_new_session=True)
                    except OSError as error:
                        log.write(str(error) + '\n'); log.close()
                        node.update(status='failed', exit_code=127, finished_at=datetime.now(timezone.utc).isoformat(),
                                    duration_seconds=round(time.monotonic() - started.pop(key), 3))
                        continue
                    running[key] = (process, log)
                    node.update(status='running', pid=process.pid)
                    state['last_step'] = key
                    if group:
                        groups.add(group)
            save(directory, state)
            if not running and all(n['status'] != 'pending' for n in state['steps'].values()):
                state['status'] = 'failed' if any(n['status'] in ('failed', 'blocked') and n['severity'] == 'hard_fail' for n in state['steps'].values()) else 'passed'
                break
            time.sleep(0.1)
    finally:
        state.update(finished_at=datetime.now(timezone.utc).isoformat(), duration_seconds=round(time.monotonic() - run_started, 3))
        save(directory, state)
        for sig, handler in old.items():
            signal.signal(sig, handler)
    return 0 if state['status'] == 'passed' else 1


def status(directory, active):
    path = directory / 'state.json'
    if not path.exists():
        print('No quality run recorded.')
        return
    state = json.loads(path.read_text())
    if state['status'] == 'running' and not active:
        state['status'] = 'interrupted'
        for node in state['steps'].values():
            if node['status'] == 'running':
                node['status'] = 'interrupted'
        save(directory, state)
    for key, node in state['steps'].items():
        if active and node['status'] != 'running':
            continue
        elapsed = node.get('duration_seconds')
        if elapsed is None and node['status'] == 'running' and node.get('started_at'):
            elapsed = max(0, (datetime.now(timezone.utc) - datetime.fromisoformat(node['started_at'])).total_seconds())
        timing = f' ({elapsed:.1f}s)' if elapsed is not None else ''
        print(f"{key}: {node['status']}{timing}")
        log = directory / (key + '.log')
        if log.exists():
            print('\n'.join(log.read_text(errors='replace').splitlines()[-30:]))
            if not active:
                print('Full log: ' + str(log))
    if not active and state.get('duration_seconds') is not None:
        print(f"Total elapsed: {state['duration_seconds']:.1f}s (parallel stage durations overlap).")
    print('Process is still running.' if active else 'Overall status: ' + state['status'])
