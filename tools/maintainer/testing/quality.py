#!/usr/bin/env python3
"""Isolated quality scheduler failure, exclusion and interruption contracts."""
import contextlib
import io
import json
import os
from pathlib import Path
import signal
import shutil
import subprocess
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import quality


def node(key, command, deps=None, group=None):
    return {'id': key, 'command': command, 'depends_on': deps or [], 'severity': 'hard_fail', 'exclusive_group': group}


class QualityContracts(unittest.TestCase):
    def grouped_definition(self):
        return {'base': [node('baseline', 'true'), node('runtime', 'true', ['baseline'])],
                'impact_areas': {'tooling': [node('cheap', 'true'), node('expensive', 'false')]},
                'final': [node('closure', 'true')], 'mandatory': ['baseline', 'closure'],
                'groups': {'docs': ['cheap'], 'backend': ['runtime'], 'containers': ['expensive']}}

    def test_explicit_selection_expands_only_dependencies_and_never_spawns_exclusions(self):
        definition = self.grouped_definition()
        nodes, scope = quality.resolve(definition, ['tooling'], groups=['docs', 'docs', 'backend'])
        self.assertEqual(list(nodes), ['baseline', 'runtime', 'cheap', 'closure'])
        self.assertEqual(scope['requested_groups'], ['backend', 'docs'])
        self.assertEqual(scope['excluded_groups'], ['containers'])
        self.assertIn('prerequisite of runtime', scope['reasons']['baseline'])
        self.assertEqual(nodes['closure']['depends_on'], ['baseline', 'runtime', 'cheap'])
        for final in (False, True):
            self.assertEqual(quality.resolve(definition, ['tooling'], final, ['docs', 'backend']), (nodes, scope))
        with tempfile.TemporaryDirectory() as folder, patch.object(quality, 'inside', return_value=True):
            state = {'status': 'running', 'selection': scope, 'steps': {k: dict(n, status='pending') for k, n in nodes.items()}}
            self.assertEqual(quality.execute(Path(folder), state), 0)
            self.assertFalse((Path(folder) / 'expensive.log').exists())
            output = io.StringIO()
            with contextlib.redirect_stdout(output):
                quality.status(Path(folder), False)
            self.assertIn('Excluded groups (not run): containers', output.getvalue())

    def test_explicit_all_and_legacy_keep_distinct_honest_scope(self):
        definition = self.grouped_definition()
        nodes, scope = quality.resolve(definition, [], all_groups=True)
        self.assertIn('expensive', nodes)
        self.assertEqual(scope['mode'], 'all')
        self.assertEqual(scope['excluded_groups'], [])
        legacy, scope = quality.resolve(definition, ['tooling'])
        self.assertEqual(legacy, quality.select(definition, ['tooling']))
        self.assertEqual(scope['mode'], 'legacy')

    def test_group_validation_rejects_invalid_and_cyclic_selections(self):
        for args in (['show', '--group='], ['status', '--all'], ['show', '--background'],
                     ['final', '--all', '--group=docs'], ['final', '--all', '--all'],
                     ['final', '--group=../command'], ['final', 'docs'], ['final', '--group']):
            with self.subTest(args=args), self.assertRaises(ValueError):
                quality.arguments(args)
        self.assertEqual(quality.arguments(['final', '--group=docs', '--group=docs', '--background']), (['docs'], False, True))
        for groups in (['unknown'], []):
            with self.assertRaises(ValueError):
                quality.resolve(self.grouped_definition(), [], groups=groups)
        definition = self.grouped_definition()
        definition['base'][0]['depends_on'] = ['runtime']
        with self.assertRaisesRegex(ValueError, 'cycle'):
            quality.resolve(definition, [], groups=['backend'])
        definition['groups']['broken'] = ['missing']
        with self.assertRaisesRegex(ValueError, 'Missing'):
            quality.resolve(definition, [], groups=['broken'])

    def test_real_catalogues_cover_existing_steps_without_owner_customer_leaks(self):
        owner = quality.yaml(ROOT / 'docs/runa/maintainer/quality/gates.yml')
        customer = quality.yaml(ROOT / 'tools/application/quality.yml')
        owner_nodes, _ = quality.resolve(owner, [], all_groups=True)
        legacy = quality.select(owner, list(owner['impact_areas']), True)
        self.assertTrue(set(legacy) <= set(owner_nodes))
        for definition in (owner, customer):
            docs, _ = quality.resolve(definition, ['tooling', 'security'], groups=['docs', 'tooling'])
            self.assertFalse(any('installation-host.py' in n['command'] or 'security:trivy' in n['command'] for n in docs.values()))
        customer_nodes, _ = quality.resolve(customer, [], all_groups=True)
        self.assertFalse(any('tools/maintainer' in n['command'] or 'distribution/' in n['command'] for n in customer_nodes.values()))
        catalogue = json.loads((ROOT / 'src/runa/management/quality-groups.json').read_text())
        self.assertEqual(set(catalogue), set(customer['groups']))
        for group in ('installation', 'updates', 'transport', 'production'):
            with self.assertRaises(ValueError):
                quality.resolve(customer, [], groups=[group])

    def test_invalid_executor_selection_preserves_existing_evidence_and_plan_is_read_only(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            engine = root / 'tools/application/executors/quality.py'
            engine.parent.mkdir(parents=True)
            shutil.copy(ROOT / 'tools/application/executors/quality.py', engine)
            directory = root / '.runtime/application-quality'
            directory.mkdir(parents=True)
            (directory / 'state.json').write_text('{"sentinel": true}')
            harness = root / 'harness.py'
            harness.write_text('import sys, runpy\n'
                + f'sys.path.insert(0, {str(ROOT / "tools/application")!r})\nimport quality\n'
                + 'def plan(final=False, **selection):\n'
                + '    nodes, scope = quality.resolve(' + repr(self.grouped_definition()) + ', [], final, **selection)\n'
                + '    return {"id":"fixture", "impact_areas":[], "quality_selection":scope}, nodes\n'
                + 'quality.plan = plan\n'
                + f'runpy.run_path({str(engine)!r}, run_name="__main__")\n')
            for args in (['final', '--group=unknown'], ['final', '--all', '--group=docs'], ['show', '--background']):
                result = subprocess.run([sys.executable, str(harness), *args], cwd=root, capture_output=True, text=True)
                self.assertNotEqual(result.returncode, 0, result.stdout)
                self.assertEqual((directory / 'state.json').read_text(), '{"sentinel": true}')
                self.assertEqual(list(directory.iterdir()), [directory / 'state.json'])
            output = subprocess.run([sys.executable, str(harness), 'show', '--group=docs'], cwd=root, capture_output=True, text=True, check=True).stdout
            self.assertIn('Excluded groups (not run): backend, containers', output)
            self.assertIn('cheap: true', output)
            self.assertEqual((directory / 'state.json').read_text(), '{"sentinel": true}')
            self.assertFalse((directory.parent / 'quality.lock').exists())

    def test_closure_requires_agreed_scope_complete_results_and_unchanged_candidate(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            subprocess.run(['git', 'init', '-q', '-b', 'roadmap/test', str(root)], check=True)
            (root / '.git/info/exclude').write_text('.runtime/\n')
            subprocess.run(['git', '-C', str(root), '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.test', 'commit', '--allow-empty', '-qm', 'fixture'], check=True)
            directory = root / '.runtime/quality'
            directory.mkdir(parents=True)
            definition = self.grouped_definition()
            nodes, scope = quality.resolve(definition, [], groups=['docs'])
            data = {'id': 'fixture', 'branch': 'roadmap/test', 'quality_groups': 'docs', 'quality_rationale': 'docs scope'}
            state = {'status': 'passed', 'mode': 'final', 'roadmap': 'fixture', 'branch': data['branch'],
                     'rationale': data['quality_rationale'], 'selection': scope,
                     'commit': subprocess.check_output(['git', '-C', str(root), 'rev-parse', 'HEAD'], text=True).strip(),
                     'steps': {key: dict(n, status='passed', exit_code=0) for key, n in nodes.items()}}
            with patch.object(quality, 'yaml', return_value=definition):
                quality.save(directory, state)
                quality.check_evidence(root, data, root / 'definition', directory, owner=True)
                for change in ({'status': 'interrupted'}, {'branch': 'other'}, {'mode': 'show'}, {'rationale': 'other'}):
                    quality.save(directory, state | change)
                    with self.assertRaises(ValueError):
                        quality.check_evidence(root, data, root / 'definition', directory, owner=True)
                quality.save(directory, state)
                with self.assertRaises(ValueError):
                    quality.check_evidence(root, data | {'quality_groups': 'all'}, root / 'definition', directory, owner=True)
                state['steps']['cheap']['status'] = 'failed'
                quality.save(directory, state)
                with self.assertRaisesRegex(ValueError, 'Incomplete'):
                    quality.check_evidence(root, data, root / 'definition', directory, owner=True)
                state['steps']['cheap']['status'] = 'passed'
                state['steps']['cheap']['command'] = 'false'
                quality.save(directory, state)
                with self.assertRaisesRegex(ValueError, 'commands'):
                    quality.check_evidence(root, data, root / 'definition', directory, owner=True)
                state['steps']['cheap']['command'] = 'true'
                quality.save(directory, state)
                (root / 'runtime.py').write_text('changed')
                subprocess.run(['git', '-C', str(root), 'add', 'runtime.py'], check=True)
                with self.assertRaisesRegex(ValueError, 'Substantive'):
                    quality.check_evidence(root, data, root / 'definition', directory, owner=True)

    def test_records_stage_durations_and_shows_them_through_shared_status(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            state = {'status': 'running', 'steps': {'short': dict(node('short', 'true'), status='pending')}}
            self.assertEqual(quality.execute(directory, state), 0)
            stage = state['steps']['short']
            self.assertGreaterEqual(stage['duration_seconds'], 0)
            self.assertGreaterEqual(state['duration_seconds'], stage['duration_seconds'])
            self.assertLessEqual(stage['started_at'], stage['finished_at'])
            output = io.StringIO()
            with contextlib.redirect_stdout(output):
                quality.status(directory, False)
            self.assertIn('short: passed (', output.getvalue())
            self.assertIn('Total elapsed:', output.getvalue())
            self.assertIn('Overall status: passed', output.getvalue())

    def test_debug_check_allows_only_reviewed_output_statements(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'bin').mkdir()
            (root / 'tools/application/executors').mkdir(parents=True)
            shutil.copy(ROOT / 'tools/application/executors/quality_check.py', root / 'tools/application/executors/quality_check.py')
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            sources = {
                'src/runa/resources/views/errors/page.php': 'echo strtr($template, [',
                'src/runa/tests/Fixtures/browser-document.php': "echo json_encode(['status' => $response->getStatusCode(), 'headers' => $response->headers->all(),",
            }
            for name, statement in sources.items():
                target = root / name
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text('<?php\n' + statement + '\n')
            command = [sys.executable, str(root / 'tools/application/executors/quality_check.py'), 'debug-residue']
            self.assertEqual(subprocess.run(command, capture_output=True).returncode, 0)
            for name, statement in sources.items():
                for content in [statement + " dd('debug');", statement + "\necho 'debug';", "var_dump($value);"]:
                    with self.subTest(name=name, content=content):
                        (root / name).write_text('<?php\n' + content + '\n')
                        self.assertNotEqual(subprocess.run(command, capture_output=True).returncode, 0)
                (root / name).write_text('<?php\n' + statement + '\n')
            other = root / 'src/runa/Other.php'
            other.parent.mkdir(parents=True, exist_ok=True)
            other.write_text('<?php\n' + sources['src/runa/resources/views/errors/page.php'] + '\n')
            self.assertNotEqual(subprocess.run(command, capture_output=True).returncode, 0)

    def test_actual_host_production_gates_share_the_operation_group(self):
        definition = quality.yaml(ROOT / 'docs/runa/maintainer/quality/gates.yml')
        nodes = quality.select(definition, ['production', 'security'])
        for key in ('production-acceptance', 'repository-container-scan'):
            self.assertEqual(nodes[key]['exclusive_group'], 'production-simulation', key)
            self.assertIn('installation-host.py --production-only', nodes[key]['command'])
            self.assertNotIn('./bin/artisan runa:production:', nodes[key]['command'])

    def test_repository_scan_waits_for_selected_artifact_builds(self):
        definition = quality.yaml(ROOT / 'docs/runa/maintainer/quality/gates.yml')
        for areas in (['security'], ['security', 'frontend'], ['security', 'production'], ['security', 'frontend', 'production']):
            with self.subTest(areas=areas):
                nodes = quality.select(definition, areas)
                dependencies = nodes['repository-container-scan']['depends_on']
                self.assertEqual('frontend-build' in dependencies, 'frontend' in areas)
                self.assertEqual('frontend-render-diagnostics' in dependencies, 'frontend' in areas)
                self.assertEqual('production-acceptance' in dependencies, 'production' in areas)
        with tempfile.TemporaryDirectory() as folder:
            build = node('frontend-build', 'false')
            scan = node('repository-container-scan', 'true')
            scan['depends_on_if_selected'] = ['frontend-build']
            selected = quality.select({'base': [build, scan], 'impact_areas': {}, 'final': []}, [])
            state = {'status': 'running', 'steps': {key: dict(value, status='pending') for key, value in selected.items()}}
            self.assertEqual(quality.execute(Path(folder), state), 1)
            self.assertEqual(state['steps']['repository-container-scan']['status'], 'blocked')
            self.assertFalse((Path(folder) / 'repository-container-scan.log').exists())

    def test_host_coordinator_delegates_only_application_steps(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(quality, 'inside', return_value=False), patch.object(quality, 'app_command', return_value=['true']) as delegate:
            nodes = [node('app', 'application-check'), dict(node('host', 'true'), execution_context='host')]
            state = {'status': 'running', 'steps': {n['id']: dict(n, status='pending') for n in nodes}}
            self.assertEqual(quality.execute(Path(folder), state), 0)
            delegate.assert_called_once_with(['application-check'])

    def test_container_coordinator_cannot_report_pass_for_host_step(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(quality, 'inside', return_value=True):
            host = dict(node('host', 'true'), execution_context='host', status='pending')
            state = {'status': 'running', 'steps': {'host': host}}
            self.assertEqual(quality.execute(Path(folder), state), 1)
            self.assertIn('requires the host', (Path(folder) / 'host.log').read_text())

    def test_selection_deduplicates_and_validates_graph(self):
        a = node('a', 'true')
        b = node('b', 'true', ['a'])
        definition = {'base': [a], 'impact_areas': {'x': [a, b]}, 'final': [node('final', 'true')]}
        selected = quality.select(definition, ['x'], True)
        self.assertEqual(list(selected), ['a', 'b', 'final'])
        self.assertEqual(selected['final']['depends_on'], ['a', 'b'])
        definition['base'][0]['depends_on'] = ['b']
        with self.assertRaisesRegex(ValueError, 'cycle'):
            quality.select(definition, ['x'])
        with self.assertRaisesRegex(ValueError, 'Missing'):
            quality.select({'base': [b], 'impact_areas': {}, 'final': []}, [])

    def test_failure_blocks_dependents_but_runs_independent_steps(self):
        with tempfile.TemporaryDirectory() as folder:
            nodes = [node('bad', 'false'), node('blocked', 'true', ['bad']), node('good', 'true')]
            state = {'status': 'running', 'steps': {n['id']: dict(n, status='pending') for n in nodes}}
            self.assertEqual(quality.execute(Path(folder), state), 1)
            self.assertEqual([n['status'] for n in state['steps'].values()], ['failed', 'blocked', 'passed'])

    def test_shared_group_serializes_and_independent_steps_overlap(self):
        with tempfile.TemporaryDirectory() as folder:
            script = Path(folder) / 'step.py'
            script.write_text('import time\nprint(time.time(), flush=True)\ntime.sleep(0.3)\nprint(time.time())\n')
            nodes = [node('a', f'{sys.executable} {script}', group='db'), node('b', f'{sys.executable} {script}', group='db'), node('c', f'{sys.executable} {script}')]
            state = {'status': 'running', 'steps': {n['id']: dict(n, status='pending') for n in nodes}}
            self.assertEqual(quality.execute(Path(folder), state), 0)
            intervals = {k: list(map(float, (Path(folder) / (k + '.log')).read_text().split())) for k in state['steps']}
            self.assertGreaterEqual(intervals['b'][0], intervals['a'][1])
            self.assertLess(intervals['c'][0], intervals['a'][1])

    def test_background_lock_and_signal_interruption(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'bin').mkdir()
            (root / 'tools').mkdir()
            (root / 'tools/application/executors').mkdir(parents=True)
            shutil.copy(ROOT / 'tools/application/executors/quality.py', root / 'tools/application/executors/quality.py')
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            subprocess.run(['git', '-C', str(root), '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.test', 'commit', '--allow-empty', '-qm', 'fixture'], check=True)
            harness = root / 'harness.py'
            harness.write_text(
                'import sys, runpy\n'
                + f'sys.path.insert(0, {str(ROOT / "tools/application")!r})\n'
                + 'import quality\n'
                + 'quality.plan = lambda final: ' + repr(({'id': 'fixture', 'impact_areas': []}, {'wait': node('wait', "python3 -c 'import time; time.sleep(30)'")})) + '\n'
                + f'sys.argv = [{str(root / "tools/application/executors/quality.py")!r}, *sys.argv[1:]]\n'
                + f'runpy.run_path({str(root / "tools/application/executors/quality.py")!r}, run_name="__main__")\n')
            command = [sys.executable, str(harness)]
            started = subprocess.run(command + ['roadmap', '--background'], capture_output=True, text=True)
            self.assertEqual(started.returncode, 0, started.stderr)
            self.assertIn('started in background', started.stdout)
            directory = root / '.runtime/application-quality'
            try:
                # Wait only for the short fixture launch, never a project final gate.
                for _ in range(100):
                    state = json.loads((directory / 'state.json').read_text())
                    if state['steps']['wait'].get('pid'):
                        break
                    time.sleep(0.02)
                self.assertEqual(state['steps']['wait']['status'], 'running')
                refused = subprocess.run(command + ['roadmap'], capture_output=True, text=True)
                self.assertEqual(refused.returncode, 1)
                self.assertIn('Another quality run', refused.stderr)
                child = state['steps']['wait']['pid']
                # Session leader is the detached runner; its command has its own group.
                parent = int(Path(f'/proc/{child}/stat').read_text().split()[3])
                os.kill(parent, signal.SIGTERM)
                for _ in range(100):
                    state = json.loads((directory / 'state.json').read_text())
                    if state['status'] == 'interrupted':
                        break
                    time.sleep(0.02)
                self.assertEqual(state['status'], 'interrupted')
                self.assertFalse(Path(f'/proc/{child}').exists())
                result = subprocess.run(command + ['status'], capture_output=True, text=True, check=True)
                self.assertIn('Overall status: interrupted', result.stdout)
            finally:
                if 'parent' in locals():
                    try:
                        os.kill(parent, signal.SIGTERM)
                    except ProcessLookupError:
                        pass

    def test_abandoned_state_and_status_tail(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            (directory / 'state.json').write_text(json.dumps({'status': 'running', 'steps': {'a': {'status': 'running'}}}))
            (directory / 'a.log').write_text('\n'.join(f'line-{i}' for i in range(40)))
            output = io.StringIO()
            with contextlib.redirect_stdout(output):
                quality.status(directory, False)
            self.assertNotIn('line-9\n', output.getvalue())
            self.assertIn('line-10\n', output.getvalue())
            self.assertIn('Overall status: interrupted', output.getvalue())


if __name__ == '__main__':
    unittest.main()
