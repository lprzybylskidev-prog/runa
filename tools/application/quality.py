"""Dependency-driven local verification with durable latest-run evidence."""
import json
import hashlib
import os
from pathlib import Path
import re
import shlex
import signal
import subprocess
import time
from datetime import datetime, timezone
from tools.application.checks import yaml
from tools.application.runtime import ROOT, inside, app_command


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


def arguments(args):
    """Validate transport arguments before touching locks, logs or run evidence."""
    if not args or args[0] not in ('show', 'roadmap', 'final', 'status'):
        raise ValueError('Invalid quality action.')
    groups = []
    all_groups = background = False
    for arg in args[1:]:
        if arg == '--all' and not all_groups:
            all_groups = True
        elif arg == '--background' and not background and args[0] in ('roadmap', 'final'):
            background = True
        elif arg.startswith('--group=') and re.fullmatch(r'[a-z][a-z0-9-]*', arg[8:]):
            groups.append(arg[8:])
        else:
            raise ValueError('Invalid quality argument: ' + arg)
    if (all_groups and groups) or (args[0] == 'status' and args[1:]):
        raise ValueError('Use either --all or repeated --group=NAME; status takes no options.')
    return sorted(set(groups)) or None, all_groups, background


def resolve(definition, areas, final=False, groups=None, all_groups=False):
    if groups is None and not all_groups:
        return select(definition, areas, final), {'mode': 'legacy', 'impact_areas': areas, 'final': final}
    catalogue = definition['groups']
    if (all_groups and groups) or (groups is not None and not groups):
        raise ValueError('Provide named groups or --all.')
    requested = sorted(catalogue if all_groups else set(groups))
    if any(group not in catalogue for group in requested):
        raise ValueError('Unknown or unavailable quality group. Available: ' + ', '.join(catalogue))
    inventory = {}
    for node in definition['base'] + [n for values in definition['impact_areas'].values() for n in values] + definition['final'] + definition.get('group_steps', []):
        key = node['id']
        if key in inventory and inventory[key] != node:
            raise ValueError('Conflicting step: ' + key)
        inventory[key] = node
    reasons = {}

    def include(key, reason):
        if key not in inventory:
            raise ValueError('Missing dependency or group step: ' + key)
        if key in reasons:
            if reason not in reasons[key]:
                reasons[key].append(reason)
            return
        reasons[key] = [reason]
        for dependency in inventory[key]['depends_on']:
            include(dependency, 'prerequisite of ' + key)

    for key in definition['mandatory']:
        include(key, 'mandatory baseline')
    for group in requested:
        for key in catalogue[group]:
            include(key, 'group ' + group)
    # Preserve definition order and validate the expanded graph through the same scheduler contract.
    chosen = [dict(node) for key, node in inventory.items() if key in reasons]
    final_ids = {node['id'] for node in definition['final']}
    nodes = select({'base': [node for node in chosen if node['id'] not in final_ids],
                    'impact_areas': {}, 'final': [node for node in chosen if node['id'] in final_ids]}, [], True)
    scope = {'mode': 'all' if all_groups else 'groups', 'requested_groups': requested,
             'excluded_groups': sorted(set(catalogue) - set(requested)),
             'mandatory': definition['mandatory'], 'reasons': reasons,
             'graph_hash': graph_hash(nodes)}
    return nodes, scope


def graph_hash(nodes):
    return hashlib.sha256(json.dumps(nodes, sort_keys=True, separators=(',', ':')).encode()).hexdigest()


def show_scope(scope):
    if scope.get('mode', 'legacy') == 'legacy':
        print('Scope: legacy impact-area selection (no explicit groups).')
        return
    print('Scope: ' + scope['mode'] + '; requested groups: ' + ', '.join(scope['requested_groups']))
    print('Mandatory baseline: ' + ', '.join(scope['mandatory']))
    print('Excluded groups (not run): ' + (', '.join(scope['excluded_groups']) or 'none'))
    for key, reasons in scope['reasons'].items():
        print('Included ' + key + ': ' + '; '.join(reasons))


def validate_declared(roadmap, scope):
    declared = roadmap.get('quality_groups')
    if declared is None:
        return
    if not isinstance(declared, str) or not isinstance(roadmap.get('quality_rationale'), str) or not roadmap['quality_rationale'].strip():
        raise ValueError('Final selection requires quality_groups and quality_rationale strings.')
    if declared == 'all':
        matches = scope.get('mode') == 'all'
    else:
        groups = declared.split(',')
        matches = (all(re.fullmatch(r'[a-z][a-z0-9-]*', group) for group in groups)
                   and scope.get('mode') == 'groups' and sorted(set(groups)) == scope.get('requested_groups'))
    if not matches:
        raise ValueError('Final quality selection differs from the roadmap quality_groups agreement.')


def closure_link_update(root, name, candidate, identifier, docs):
    if not re.fullmatch(re.escape(docs) + r'/roadmaps/draft/[^/]+\.md', name):
        return False
    path = root / name
    if not path.is_file() or path.is_symlink():
        return False
    original = subprocess.run(['git', 'show', candidate + ':' + name], cwd=root, capture_output=True)
    if original.returncode:
        return False
    pattern = rb'(?<=\]\()\.\./active/(' + re.escape(identifier.encode()) + rb'-[a-z0-9-]+\.md)(?=\))'
    expected, count = re.subn(pattern, rb'../closed/\1', original.stdout)
    return count > 0 and path.read_bytes() == expected


def approved_rm010_closure_fix(root, data, candidate):
    """Owner-approved, commit-bound exception for RM-010's first closure only."""
    commit = data.get('quality_closure_fix_commit', '')
    if data['id'] != 'RM-2026-010' or not commit:
        return set()
    if not re.fullmatch('[a-f0-9]{40}', commit):
        raise ValueError('Invalid RM-010 closure-fix commit.')
    parent = subprocess.check_output(['git', 'rev-parse', commit + '^'], cwd=root, text=True).strip()
    if parent != candidate or subprocess.run(['git', 'merge-base', '--is-ancestor', commit, 'HEAD'], cwd=root, capture_output=True).returncode:
        raise ValueError('RM-010 closure fix must directly follow its verified candidate.')
    paths = {'tools/application/quality.py', 'tools/maintainer/testing/quality.py'}
    changed = set(subprocess.check_output(['git', 'diff', '--name-only', candidate, commit], cwd=root, text=True).splitlines())
    roadmap = 'docs/runa/maintainer/roadmaps/active/RM-2026-010-manual-quality-group-selection.md'
    if changed != paths | {roadmap}:
        raise ValueError('RM-010 closure-fix scope differs from the owner-approved files.')
    for name in paths:
        expected = subprocess.check_output(['git', 'show', commit + ':' + name], cwd=root)
        if (root / name).is_symlink() or (root / name).read_bytes() != expected:
            raise ValueError('RM-010 closure-fix source changed after focused verification.')
    return paths


def check_evidence(root, data, definition_path, directory, owner=False):
    """Bind selected-scope closure to actual complete results on the verified candidate."""
    if 'quality_groups' not in data:
        return  # Immutable older releases predate explicit selection evidence.
    path = directory / 'state.json'
    if not path.is_file():
        raise ValueError('Missing final quality evidence.')
    state = json.loads(path.read_text())
    scope = state.get('selection', {})
    validate_declared(data, scope)
    if (state.get('status') != 'passed' or state.get('mode') not in ('roadmap', 'final')
            or state.get('roadmap') != data['id'] or state.get('branch') != data['branch']
            or state.get('rationale') != data.get('quality_rationale')):
        raise ValueError('Final quality evidence does not match the closed roadmap.')
    definition = yaml(definition_path)
    nodes, resolved = resolve(definition, [], groups=scope.get('requested_groups') if scope.get('mode') == 'groups' else None,
                                      all_groups=scope.get('mode') == 'all')
    steps = state.get('steps', {})
    if (scope != resolved or set(steps) != set(nodes) or any(steps[key].get('status') != 'passed' or steps[key].get('exit_code') != 0 for key in nodes)):
        raise ValueError('Incomplete or stale selected quality graph/results.')
    fields = ('status', 'pid', 'started_at', 'finished_at', 'duration_seconds', 'exit_code')
    recorded = {key: {field: value for field, value in step.items() if field not in fields} for key, step in steps.items()}
    if graph_hash(recorded) != scope['graph_hash']:
        raise ValueError('Quality result commands differ from the agreed graph.')
    candidate = state.get('commit', '')
    if not re.fullmatch('[a-f0-9]{40}', candidate) or subprocess.run(['git', 'merge-base', '--is-ancestor', candidate, 'HEAD'], cwd=root, capture_output=True).returncode:
        raise ValueError('Quality candidate is not an ancestor of this release.')
    docs = 'docs/runa/maintainer' if owner else 'docs/app'
    allowed = ({'VERSION', 'src/runa/platform.json', docs + '/CHANGELOG.md', docs + '/README.md'} if owner
               else {'src/app/application.json', docs + '/CHANGELOG.md'})
    if owner:
        allowed |= approved_rm010_closure_fix(root, data, candidate)
    prefixes = [docs + '/roadmaps/' + status + '/' + data['id'] + '-' for status in ('active', 'closed')]
    changed = subprocess.check_output(['git', 'diff', '--no-renames', '--name-only', candidate], cwd=root, text=True).splitlines()
    changed += subprocess.check_output(['git', 'ls-files', '--others', '--exclude-standard'], cwd=root, text=True).splitlines()
    if any(name not in allowed and not any(name.startswith(prefix) and name.endswith('.md') for prefix in prefixes)
           and not closure_link_update(root, name, candidate, data['id'], docs) for name in changed):
        raise ValueError('Substantive changes after the final quality candidate require a complete rerun.')


def roadmap_metadata(path):
    header = path.read_text().split('---', 2)[1]
    # Use the existing Symfony YAML parser without introducing Python dependencies.
    result = subprocess.run(app_command(['php', '-r', 'require "src/vendor/autoload.php"; echo json_encode(Symfony\\Component\\Yaml\\Yaml::parse(stream_get_contents(STDIN)));']), input=header, text=True, capture_output=True, check=True, cwd=ROOT)
    return json.loads(result.stdout)


def plan(final=False, roadmap_root=None, definition_path=None, groups=None, all_groups=False):
    active = sorted((roadmap_root or ROOT / 'docs/app/roadmaps/active').glob('*.md'))
    definition = definition_path or ROOT / 'tools/application/quality.yml'
    if not active and roadmap_root is None:
        nodes, scope = resolve(yaml(definition), [], final, groups, all_groups)
        return {'id': 'application', 'impact_areas': [], 'quality_selection': scope}, nodes
    if len(active) != 1:
        raise ValueError('Exactly one active roadmap is required.')
    roadmap = roadmap_metadata(active[0])
    nodes, scope = resolve(yaml(definition), roadmap['impact_areas'], final, groups, all_groups)
    roadmap['quality_selection'] = scope
    return roadmap, nodes


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
    show_scope(state.get('selection', {}))
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
