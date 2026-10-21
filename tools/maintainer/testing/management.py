#!/usr/bin/env python3
"""Host-plan trust boundary, locking and bounded transport regression checks."""
import contextlib
import io
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import management
import operations


class ManagementContracts(unittest.TestCase):
    request = 'a' * 32

    def plan(self, **changes):
        return dict(schema_version=1, request_id=self.request, operation='development.status', parameters={}, **changes)

    def test_published_schema_and_executor_allowlist_agree(self):
        schema = json.loads((ROOT / 'src/runa/management/host-plan.v1.schema.json').read_text())
        self.assertEqual(set(schema['properties']['operation']['enum']), set(management.OPERATIONS))
        self.assertFalse(schema['additionalProperties'])
        self.assertFalse(schema['properties']['parameters']['additionalProperties'])
        self.assertEqual(set(schema['required']), set(self.plan()))

    def test_rejects_unversioned_unbound_and_executable_payloads(self):
        variants = [self.plan() | {'operation': 'tooling.' + retired} for retired in ('backup', 'integrity', 'project')] + [None, [], {}, self.plan() | {'schema_version': True}, self.plan() | {'schema_version': 2},
                    self.plan() | {'request_id': 'b' * 32}, self.plan() | {'operation': 'development.up; touch /tmp/injected'},
                    self.plan() | {'operation': []}, self.plan() | {'parameters': []},
                    self.plan() | {'parameters': {'argv': ['sh', '-c', 'false']}}, self.plan() | {'shell': 'false'}]
        for value in variants:
            with self.subTest(value=value), self.assertRaises(ValueError):
                management.validate_plan(json.dumps(value), self.request)
        with self.assertRaisesRegex(ValueError, 'Duplicate'):
            management.validate_plan(json.dumps(self.plan()).replace('"schema_version": 1', '"schema_version": 1, "schema_version": 1'), self.request)

    def test_tool_arguments_remain_literal_and_private_plan_fields_are_rejected(self):
        arguments = ['--filter=literal $(false); value', 'line\nbreak']
        plan = self.plan() | {'operation': 'tooling.test', 'parameters': {'arguments': arguments}}
        validated = management.validate_plan(json.dumps(plan), self.request)
        command = management.execution_command(validated)
        self.assertEqual(command[-5:], ['php', 'artisan', 'runa:test', *arguments])
        self.assertNotIn('sh', command)
        for parameters in ({'arguments': 'false'}, {'arguments': [False]}, {'arguments': ['a' * 4097]}, {'arguments': [], 'executable': 'sh'}):
            with self.subTest(parameters=parameters), self.assertRaises(ValueError):
                management.validate_plan(json.dumps(plan | {'parameters': parameters}), self.request)

    def test_reverse_transport_uses_explicit_names_and_preserves_literal_arguments(self):
        cases = [
            ('dev', ['profile'], ['runa:dev:profile']),
            ('dev', ['profile', 'custom', 'DEV_CPU_LIMIT=2'], ['runa:dev:profile:set', 'custom', 'DEV_CPU_LIMIT=2']),
            ('test', ['frontend', '--filter=literal $(false)'], ['runa:test:frontend', '--filter=literal $(false)']),
            ('test', ['--filter=literal $(false)'], ['runa:test', '--filter=literal $(false)']),
            ('deploy', ['rollback'], ['runa:deploy:rollback']),
            ('hook', ['commit-msg', 'literal;message'], ['runa:hook:commit-msg', 'literal;message']),
        ]
        for tool, arguments, expected in cases:
            with self.subTest(tool=tool, arguments=arguments):
                self.assertEqual(management.public_command(tool, arguments), expected)
        with self.assertRaises(ValueError):
            management.public_command('unregistered', ['false'])

    def test_development_plans_are_denied_in_prod_before_state_locks_or_executors(self):
        from repository_boundary import write_staging_receipt, OWNER_PATHS
        definitions = json.loads((ROOT / 'src/runa/management/development.json').read_text())
        for mode in ('prod', None, 'unknown'):
            with tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                (root / '.git').mkdir()
                write_staging_receipt(root, {'schema_version': 1, 'mode': mode, 'projection': {'version': 1, 'excluded': list(OWNER_PATHS)}})
                for entry in definitions:
                    tool = entry['tool']
                    arguments = entry['prefix']
                    operation = 'development.' + arguments[0] if tool == 'dev' else 'tooling.' + tool
                    parameters = {'arguments': arguments[1:] if tool == 'dev' else arguments}
                    plan = self.plan() | {'operation': operation, 'parameters': parameters}
                    with self.subTest(mode=mode, command=entry['name']), patch.object(management, 'ROOT', root), patch.object(management.subprocess, 'run') as run, patch.object(operations, 'lock') as lock, patch.dict(os.environ, {'APP_ENV': 'local'}):
                        with self.assertRaisesRegex(ValueError, 'Development tooling is blocked'):
                            management.execute_plan(plan, self.request)
                        run.assert_not_called()
                        lock.assert_not_called()
                        self.assertFalse((root / '.runtime').exists())

    def test_worker_reports_a_fixed_nonsecret_development_mode_denial(self):
        import shutil
        from repository_boundary import OWNER_PATHS, write_staging_receipt
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'tools/application').mkdir(parents=True)
            (root / '.git').mkdir()
            for name in ('host_executor.py', 'management.py', 'repository_boundary.py', 'runtime.py'):
                shutil.copy2(ROOT / 'tools/application' / name, root / 'tools/application' / name)
            write_staging_receipt(root, {'schema_version': 1, 'mode': 'prod', 'projection': {'version': 1, 'excluded': list(OWNER_PATHS)}})
            result = subprocess.run([sys.executable, str(root / 'tools/application/host_executor.py'), '--worker'],
                                    input=json.dumps(self.plan()) + '\n', capture_output=True, text=True,
                                    env=dict(os.environ, RUNA_HOST_WORKER='1', APP_ENV='local'))
            self.assertEqual(result.returncode, 2)
            self.assertIn('Development tooling is blocked', result.stderr)
            self.assertNotIn('Traceback', result.stderr)
            self.assertFalse((root / '.runtime').exists())

    def test_quality_queries_do_not_hold_the_production_lock_across_child_gates(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            state = root / '.runtime/production'
            plan = self.plan() | {'operation': 'tooling.quality', 'parameters': {'arguments': ['status']}}
            with patch.object(management, 'ROOT', root), patch.object(operations, 'STATE', state), patch.object(management.subprocess, 'run', return_value=subprocess.CompletedProcess([], 7)) as run:
                with operations.lock():
                    self.assertEqual(management.execute_plan(plan, self.request), 7)
                self.assertEqual(run.call_args.args[0], ['python3', str(root / 'tools/application/executors/quality.py'), 'status'])

    def test_transport_caps_output_and_duration(self):
        status, stdout, stderr = management.bounded_output([sys.executable, '-c', 'import sys; print("ok"); print("warning", file=sys.stderr)'])
        self.assertEqual((status, stdout, stderr), (0, 'ok\n', 'warning\n'))
        with patch.object(management, 'MAX_OUTPUT', 100), self.assertRaisesRegex(ValueError, 'limit'):
            management.bounded_output([sys.executable, '-c', 'print("a" * 200)'])
        with patch.object(management, 'TIMEOUT', 0.1), self.assertRaisesRegex(ValueError, 'timed out'):
            management.bounded_output([sys.executable, '-c', 'import time; time.sleep(10)'])

    def test_only_fixed_action_runs_under_the_existing_operation_lock(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            state = root / '.runtime/production'
            with patch.object(management, 'ROOT', root), patch.object(operations, 'STATE', state), patch.object(management.subprocess, 'run') as run:
                def execute(command, **kwargs):
                    self.assertEqual(command, ['python3', str(root / 'tools/application/executors/dev.py'), 'status'])
                    with self.assertRaisesRegex(ValueError, 'Another production operation'):
                        with operations.lock():
                            pass
                    plans = list((root / '.runtime/management').glob('*.json'))
                    self.assertEqual(len(plans), 1)
                    self.assertEqual(plans[0].stat().st_mode & 0o777, 0o600)
                    self.assertEqual(json.loads(plans[0].read_text()), self.plan())
                    return subprocess.CompletedProcess(command, 17)
                run.side_effect = execute
                self.assertEqual(management.execute_plan(self.plan(), self.request), 17)
                self.assertFalse(list((root / '.runtime/management').glob('*.json')))

    def test_recovery_evidence_blocks_mutation_and_lock_contention_blocks_all(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            state = root / '.runtime/production'
            state.mkdir(parents=True, mode=0o700)
            (state / 'in-progress.json').write_text('{}')
            with patch.object(management, 'ROOT', root), patch.object(operations, 'STATE', state), patch.object(management.subprocess, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'unresolved'):
                    management.execute_plan(self.plan() | {'operation': 'development.up'}, self.request)
                with operations.lock(), self.assertRaisesRegex(ValueError, 'Another production operation'):
                    management.execute_plan(self.plan(), self.request)
                run.assert_not_called()
                self.assertEqual((state / 'in-progress.json').read_text(), '{}')

    def test_rejects_state_and_lock_symlinks(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / '.runtime').symlink_to(root, target_is_directory=True)
            with patch.object(management, 'ROOT', root), self.assertRaisesRegex(ValueError, 'symlinks'):
                management.execute_plan(self.plan(), self.request)
            (root / '.runtime').unlink()
            state = root / 'state'
            state.mkdir(mode=0o700)
            (state / 'operation.lock').symlink_to(root / 'victim')
            with patch.object(operations, 'STATE', state), self.assertRaises(OSError):
                with operations.lock():
                    pass
            self.assertFalse((root / 'victim').exists())

    def test_no_network_mounts_environment_or_shell_in_management_container(self):
        with patch.object(management, 'ensure_image', return_value='sha256:' + 'a' * 64), patch.object(management, 'bounded_output', return_value=(0, 'commands\n', '')) as run, contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(management.launch(['list']), 0)
            command = run.call_args.args[0]
            self.assertEqual(command[command.index('--network') + 1], 'none')
            self.assertIn('--read-only', command)
            self.assertIn('no-new-privileges', command)
            self.assertNotIn('--volume', command)
            self.assertNotIn('--mount', command)
            self.assertNotIn('--env', command)
            self.assertNotIn('/var/run/docker.sock', ' '.join(command))

    def test_plan_only_and_parse_failure_never_execute(self):
        with patch.object(management, 'ensure_image', return_value='image'), patch.object(management, 'execute_plan') as execute, patch.object(management.uuid, 'uuid4') as identity, contextlib.redirect_stdout(io.StringIO()):
            identity.return_value.hex = self.request
            with patch.object(management, 'bounded_output', return_value=(0, json.dumps(self.plan()), '')):
                self.assertEqual(management.launch(['runa:dev:status', '--plan']), 0)
            with patch.object(management, 'bounded_output', return_value=(0, json.dumps(self.plan() | {'argv': ['false']}), '')), self.assertRaises(ValueError):
                management.launch(['runa:dev:status'])
            execute.assert_not_called()
        with self.assertRaisesRegex(ValueError, 'reserved'):
            management.launch(['runa:dev:status', '--host-request=' + self.request])

    def test_interrupted_transport_removes_only_its_own_container(self):
        with patch.object(management, 'ensure_image', return_value='image'), patch.object(management.uuid, 'uuid4') as identity, patch.object(management, 'bounded_output', side_effect=ValueError('timed out')), patch.object(management.subprocess, 'run') as cleanup:
            identity.return_value.hex = self.request
            with self.assertRaisesRegex(ValueError, 'timed out'):
                management.launch(['list'])
            self.assertEqual(cleanup.call_args.args[0], ['docker', 'rm', '--force', 'runa-management-' + self.request])


if __name__ == '__main__':
    unittest.main()
