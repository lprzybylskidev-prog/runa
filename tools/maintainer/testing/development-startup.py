#!/usr/bin/env python3
"""Application startup failure boundaries and independent knowledge recovery."""
import contextlib
import importlib.machinery
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import tempfile
from types import SimpleNamespace
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import ingest
import store
import runtime
import host_executor


def load(name, path):
    loader = importlib.machinery.SourceFileLoader(name, str(ROOT / path))
    spec = importlib.util.spec_from_loader(name, loader)
    module = importlib.util.module_from_spec(spec)
    loader.exec_module(module)
    return module


dev = load('dev_startup_contract', 'tools/application/executors/dev.py')
cli = load('knowledge_startup_contract', 'tools/application/executors/knowledge.py')
startup = load('optional_startup_contract', 'tools/application/knowledge/startup.py')


class StartupContracts(unittest.TestCase):
    def setUp(self):
        executor = patch.object(host_executor, 'ensure')
        executor.start()
        self.addCleanup(executor.stop)

    def test_pre_autoload_setup_uses_the_private_executor(self):
        self.assertIn('command: [python3, tools/application/executors/dev.py, setup, --if-needed]', (ROOT / 'compose.yaml').read_text())
        self.assertFalse((ROOT / 'bin/dev').exists())
        editor = json.loads((ROOT / '.devcontainer/devcontainer.json').read_text())
        self.assertEqual(editor['initializeCommand'], ['./bin/artisan', 'runa:dev:prepare'])
        self.assertEqual(editor['postCreateCommand'], ['python3', 'tools/application/executors/dev.py', 'setup', '--if-needed'])

    def test_standalone_bootstrap_rejects_production_before_preparing_files(self):
        import shutil
        from repository_boundary import OWNER_PATHS, write_staging_receipt
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'tools/application/executors').mkdir(parents=True)
            (root / '.git').mkdir()
            for name in ('tools/application/executors/dev.py', 'tools/application/repository_boundary.py'):
                shutil.copy2(ROOT / name, root / name)
            write_staging_receipt(root, {'schema_version': 1, 'mode': 'prod', 'projection': {'version': 1, 'excluded': list(OWNER_PATHS)}})
            result = subprocess.run([sys.executable, str(root / 'tools/application/executors/dev.py'), 'prepare'], capture_output=True, text=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn('Development tooling is blocked', result.stdout + result.stderr)
            self.assertFalse((root / '.env.dev.local').exists())
            self.assertFalse((root / '.devcontainer').exists())

    def host_context(self):
        exists = Path.exists
        return patch.object(Path, 'exists', lambda path: False if str(path) == '/.dockerenv' else exists(path))

    def test_application_wait_excludes_optional_ai_and_optional_failure_is_degraded(self):
        values = dict(EMBEDDINGS_CUDA='1')
        results = [SimpleNamespace(returncode=0), SimpleNamespace(returncode=1), SimpleNamespace(returncode=0)]
        with self.host_context(), patch.object(dev, 'load', return_value=values), patch.object(dev, 'resolve_embeddings'), patch.object(dev, 'persist'), patch.object(dev.subprocess, 'run', side_effect=results) as run, patch.object(dev.sys, 'argv', ['dev', 'up']), contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(dev.main(), 0)
        wait, optional, diagnostics = [c.args[0] for c in run.call_args_list]
        self.assertIn('--wait', wait)
        self.assertNotIn('qdrant', wait)
        self.assertNotIn('embeddings-gpu', wait)
        self.assertIn('setup', wait)
        self.assertIn('--no-deps', optional)
        self.assertIn('embeddings-gpu', optional)
        self.assertIn('knowledge-init', optional)
        self.assertEqual(diagnostics[-1], 'runa:dev:knowledge-status')

    def test_selective_build_and_recreate_do_not_touch_siblings(self):
        for action, service in (('build', 'mailpit'), ('recreate', 'qdrant')):
            with self.host_context(), patch.object(dev, 'load', return_value={'EMBEDDINGS_CUDA': '0'}), patch.object(dev, 'resolve_embeddings'), patch.object(dev, 'persist'), patch.object(dev.subprocess, 'run', return_value=SimpleNamespace(returncode=0)) as run, patch.object(dev.sys, 'argv', ['dev', action, service]), contextlib.redirect_stdout(io.StringIO()):
                self.assertEqual(dev.main(), 0)
            command = run.call_args.args[0]
            self.assertEqual(command[-1], service)
            self.assertNotIn('app', command)
            if action == 'recreate':
                self.assertIn('--no-deps', command)
                self.assertIn('--force-recreate', command)
                self.assertNotIn('--wait', command)

    def test_invalid_selective_scope_never_mutates_profile_or_calls_docker(self):
        for args in (['recreate'], ['recreate', 'nginx;false'], ['build', 'postgres'], ['build', 'mailpit', 'mailpit'], ['build', 'embeddings-gpu']):
            with self.host_context(), patch.object(dev, 'load', return_value={'EMBEDDINGS_CUDA': '0'}), patch.object(dev, 'resolve_embeddings') as resolve, patch.object(dev, 'persist') as persist, patch.object(dev.subprocess, 'run') as run, patch.object(dev.sys, 'argv', ['dev', *args]):
                with self.assertRaises(ValueError):
                    dev.main()
                run.assert_not_called()
                persist.assert_not_called()
                resolve.assert_not_called()

    def test_config_validation_never_prints_expanded_environment(self):
        with self.host_context(), patch.object(dev, 'load', return_value={'EMBEDDINGS_CUDA': '0'}), patch.object(dev, 'resolve_embeddings'), patch.object(dev, 'persist'), patch.object(dev.subprocess, 'run', return_value=SimpleNamespace(returncode=0)) as run, patch.object(dev.sys, 'argv', ['dev', 'config']), contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(dev.main(), 0)
        self.assertEqual(run.call_args.args[0][-2:], ['config', '--quiet'])

    def test_application_failure_is_not_swallowed(self):
        with self.host_context(), patch.object(dev, 'load', return_value={'EMBEDDINGS_CUDA': '0'}), patch.object(dev, 'resolve_embeddings'), patch.object(dev, 'persist'), patch.object(dev.subprocess, 'run', return_value=SimpleNamespace(returncode=7)) as run, patch.object(dev.sys, 'argv', ['dev', 'up']):
            self.assertEqual(dev.main(), 7)
            self.assertEqual(run.call_count, 1)

    def test_failed_application_setup_never_writes_success_marker(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / '.env').write_text('APP_KEY=test\n')
            (root / '.env.testing').write_text('APP_KEY=test\n')
            exists = Path.exists
            def present(path):
                return str(path) == '/.dockerenv' or exists(path)
            def command(args, **kwargs):
                if args[:3] == ['php', 'artisan', 'runa:config:validate']:
                    raise subprocess.CalledProcessError(1, args)
                self.assertNotIn('knowledge', ' '.join(args))
                return SimpleNamespace(returncode=0)
            with patch.object(dev, 'ROOT', root), patch.object(dev, 'load', return_value={}), patch.object(dev, 'environment_files'), patch.object(dev, 'register_boost'), patch.object(Path, 'exists', present), patch.object(dev.subprocess, 'run', side_effect=command), patch.object(dev.sys, 'argv', ['dev', 'setup']):
                with self.assertRaises(subprocess.CalledProcessError):
                    dev.main()
            self.assertFalse((root / '.runtime/dev/setup.sha256').exists())

    def setup_fixture(self, root):
        for name in ('src/vendor/autoload.php', 'src/node_modules/.modules.yaml', 'src/public/build/index.html', 'src/public/runa-error/styles.css', 'src/public/runa-error/pages/503-pl-system.html'):
            path = root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.touch()
        (root / '.env').write_text('APP_KEY=test\n')
        (root / '.env.testing').write_text('APP_KEY=test\n')
        marker = root / '.runtime/dev/setup.sha256'
        marker.parent.mkdir(parents=True)
        marker.write_text('current\n')
        return marker

    def test_current_setup_skips_install_and_build_with_generated_error_document(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            marker = self.setup_fixture(root)
            with patch.object(dev, 'ROOT', root), patch.object(dev, 'environment_files'), patch.object(dev, 'register_boost') as register, patch.object(dev, 'setup_fingerprint', return_value='current'), patch.object(dev.subprocess, 'run', return_value=SimpleNamespace(returncode=0)) as run, contextlib.redirect_stdout(io.StringIO()) as output:
                self.assertEqual(dev.application_setup(['--if-needed']), 0)
            commands = [call.args[0] for call in run.call_args_list]
            self.assertFalse(any(command[0] in ('composer', 'pnpm') for command in commands))
            self.assertIn(['php', 'artisan', 'runa:config:validate'], commands)
            self.assertFalse(any(command[:3] in (['php', 'artisan', 'migrate'], ['php', 'artisan', 'db:seed']) for command in commands))
            register.assert_called_once()
            self.assertIn('frontend build skipped', output.getvalue())
            self.assertEqual(marker.read_text(), 'current\n')

    def test_missing_current_document_or_stale_fingerprint_runs_full_setup(self):
        for reason in ('missing_document', 'stale_fingerprint'):
            with self.subTest(reason=reason), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                marker = self.setup_fixture(root)
                if reason == 'missing_document':
                    (root / 'src/public/runa-error/pages/503-pl-system.html').unlink()
                    (root / 'src/public/runa-error/pages/503-pl.html').touch()
                else:
                    marker.write_text('stale\n')
                with patch.object(dev, 'ROOT', root), patch.object(dev, 'environment_files'), patch.object(dev, 'register_boost'), patch.object(dev, 'setup_fingerprint', return_value='current'), patch.object(dev.subprocess, 'run', return_value=SimpleNamespace(returncode=0)) as run:
                    self.assertEqual(dev.application_setup(['--if-needed']), 0)
                commands = [call.args[0] for call in run.call_args_list]
                self.assertIn(['composer', '--working-dir=src', 'install', '--no-interaction', '--prefer-dist'], commands)
                self.assertIn(['pnpm', '--dir', 'src', 'run', 'build'], commands)
                self.assertIn(['php', 'src/runa/resources/errors/build.php'], commands)
                self.assertEqual(marker.read_text(), 'current\n')

    def test_optional_probe_has_process_deadline(self):
        with patch.object(dev.subprocess, 'run', side_effect=subprocess.TimeoutExpired('probe', 5)) as run, contextlib.redirect_stdout(io.StringIO()) as output:
            dev.knowledge_availability()
        self.assertEqual(run.call_args.kwargs['timeout'], 5)
        self.assertIn('DEGRADED', output.getvalue())

    def test_initializer_retries_unavailable_ai_without_indexing(self):
        with patch.object(startup.subprocess, 'run', return_value=SimpleNamespace(returncode=1)) as run, patch.object(startup.time, 'sleep') as delay, contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(startup.initialize(), 1)
        self.assertEqual(run.call_count, 1)
        delay.assert_called_once_with(10)

    def test_initializer_recovers_and_exits_after_success(self):
        with patch.object(startup.subprocess, 'run', return_value=SimpleNamespace(returncode=0)) as run, patch.object(startup.time, 'sleep') as delay, contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(startup.initialize(), 0)
        self.assertEqual(run.call_args_list[1].args[0], [sys.executable, str(ROOT / 'tools/application/executors/knowledge.py'), 'initialize'])
        delay.assert_not_called()

    def initialize_cli(self, present, manifest, points, stale=True):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'artisan').touch()
            (root / '.env').write_text('APP_ENV=local\n')
            (root / 'tools/application/knowledge').mkdir(parents=True)
            (root / 'tools/application/knowledge/config.json').write_text('{}')
            exists = Path.exists
            def available(path):
                return str(path) == '/.dockerenv' or exists(path)
            with patch.object(cli, 'ROOT', root), patch.object(cli.sys, 'argv', ['knowledge', 'initialize']), patch.object(Path, 'exists', available), patch.object(store, 'Store') as client, patch.object(ingest, 'index', return_value=0) as index, patch.object(ingest, 'status', return_value={'stale': stale}), contextlib.redirect_stdout(io.StringIO()):
                client.return_value.exists.return_value = present
                client.return_value.manifest.return_value = manifest
                client.return_value.all_points.return_value = points
                self.assertEqual(cli.main(), 0)
                return index.called

    def test_missing_or_interrupted_empty_collection_initializes(self):
        self.assertTrue(self.initialize_cli(False, None, {}))
        self.assertTrue(self.initialize_cli(True, None, {}))

    def test_existing_stale_or_unidentified_collection_is_not_reindexed(self):
        self.assertFalse(self.initialize_cli(True, {'sources': {}}, {}, stale=True))
        self.assertFalse(self.initialize_cli(True, None, {'unidentified': {}}))

    def test_app_health_does_not_require_ai(self):
        rows = [{'Service': name, 'State': 'running', 'Health': 'healthy'} for name in ('app', 'nginx', 'postgres', 'queue', 'scheduler', 'mailpit')]
        rows.append({'Service': 'setup', 'State': 'exited', 'ExitCode': 0})
        with patch.object(runtime, 'run', return_value=SimpleNamespace(stdout=json.dumps(rows))), contextlib.redirect_stdout(io.StringIO()):
            runtime.host_stack_health()


class CustomerHookSetup(unittest.TestCase):
    def test_customer_setup_configures_hooks_once_and_preserves_custom_choice(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            (root / '.githooks').mkdir()
            for name in ('pre-commit', 'commit-msg'):
                (root / '.githooks' / name).write_text('#!/bin/sh\nexit 0\n')
            with patch.object(dev, 'ROOT', root):
                dev.register_hooks()
                result = subprocess.check_output(['git', 'config', '--local', '--get', 'core.hooksPath'], cwd=root, text=True).strip()
                self.assertEqual(result, '.githooks')
                subprocess.run(['git', 'config', '--local', 'core.hooksPath', 'custom-hooks'], cwd=root, check=True)
                dev.register_hooks()
                self.assertEqual(subprocess.check_output(['git', 'config', '--local', '--get', 'core.hooksPath'], cwd=root, text=True).strip(), 'custom-hooks')





class EnvironmentOwnership(unittest.TestCase):
    def test_repeat_preserves_secrets_and_reports_testing_mismatch_without_values(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            for name in ('.env.dev.example', '.env.test.example'):
                (root / name).write_text('DB_PASSWORD=\nAPP_KEY=\n')
            with patch.object(dev, 'ROOT', root):
                dev.environment_files()
                before = {p.name: p.read_bytes() for p in root.glob('.env*')}
                dev.environment_files()
                self.assertEqual(before, {p.name: p.read_bytes() for p in root.glob('.env*')})
                (root / '.env.testing').write_text('DB_PASSWORD=synthetic-mismatch\n')
                with self.assertRaisesRegex(ValueError, 'Testing and database initialization credentials differ') as failure:
                    dev.environment_files()
                self.assertNotIn('synthetic-mismatch', str(failure.exception))
                self.assertEqual((root / '.env.dev.database').read_bytes(), before['.env.dev.database'])
                (root / '.env.testing').chmod(0o644)
                with self.assertRaisesRegex(ValueError, 'private owned regular'):
                    dev.environment_files()

    def test_linked_secret_files_are_preserved_and_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / '.env.dev.example').write_text('DB_PASSWORD=\n')
            outside = root / 'unrelated'
            outside.write_text('DB_PASSWORD=preserved\n')
            (root / '.env').symlink_to(outside)
            with patch.object(dev, 'ROOT', root), self.assertRaisesRegex(ValueError, 'symlinks'):
                dev.environment_files()
            self.assertEqual(outside.read_text(), 'DB_PASSWORD=preserved\n')

if __name__ == '__main__':
    unittest.main()
