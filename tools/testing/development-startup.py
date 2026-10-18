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

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/knowledge'))
sys.path.insert(0, str(ROOT / 'tools/project'))
import ingest
import store
import runtime


def load(name, path):
    loader = importlib.machinery.SourceFileLoader(name, str(ROOT / path))
    spec = importlib.util.spec_from_loader(name, loader)
    module = importlib.util.module_from_spec(spec)
    loader.exec_module(module)
    return module


dev = load('dev_startup_contract', 'bin/dev')
cli = load('knowledge_startup_contract', 'bin/knowledge')
startup = load('optional_startup_contract', 'tools/knowledge/startup.py')


class StartupContracts(unittest.TestCase):
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
        self.assertEqual(diagnostics[-1], 'knowledge-status')

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
                if args[:3] == ['php', 'artisan', 'migrate']:
                    raise subprocess.CalledProcessError(1, args)
                self.assertNotIn('knowledge', ' '.join(args))
                return SimpleNamespace(returncode=0)
            with patch.object(dev, 'ROOT', root), patch.object(dev, 'load', return_value={}), patch.object(dev, 'environment_files'), patch.object(dev, 'register_boost'), patch.object(Path, 'exists', present), patch.object(dev.subprocess, 'run', side_effect=command), patch.object(dev.sys, 'argv', ['dev', 'setup']):
                with self.assertRaises(subprocess.CalledProcessError):
                    dev.main()
            self.assertFalse((root / '.runtime/dev/setup.sha256').exists())

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
        self.assertEqual(run.call_args_list[1].args[0][-1], 'initialize')
        delay.assert_not_called()

    def initialize_cli(self, present, manifest, points, stale=True):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'artisan').touch()
            (root / '.env').write_text('APP_ENV=local\n')
            (root / 'tools/knowledge').mkdir(parents=True)
            (root / 'tools/knowledge/config.json').write_text('{}')
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


if __name__ == '__main__':
    unittest.main()
