#!/usr/bin/env python3
"""Owner entrypoint scope and customer quality graph regressions; no database mutation."""
import contextlib
import io
from pathlib import Path
import runpy
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
import context
import quality


class ToolOwnership(unittest.TestCase):
    def invoke(self, name, args):
        with patch.object(context, 'container'), patch.object(sys, 'argv', [name, *args]), patch.object(subprocess, 'run', return_value=subprocess.CompletedProcess([], 0)) as run, contextlib.redirect_stdout(io.StringIO()):
            with self.assertRaises(SystemExit) as error:
                runpy.run_path(str(ROOT / 'tools/maintainer' / name), run_name='__main__')
            return error.exception.code, run.call_args_list

    def test_owner_data_entrypoints_cannot_select_application_paths_or_seeders(self):
        for name, args in [('migrate', ['--path=app/database/migrations']), ('make-migration', ['example', '--path=app/database/migrations']), ('seed', ['Database\\Seeders\\DatabaseSeeder'])]:
            with self.subTest(name=name):
                code, calls = self.invoke(name, args)
                self.assertIsInstance(code, str)
                self.assertEqual(calls, [])

    def test_owner_migration_and_seeder_arguments_use_fixed_platform_scope(self):
        code, calls = self.invoke('migrate', ['--pretend'])
        self.assertEqual(code, 0)
        self.assertEqual(calls[0].args[0], ['php', str(ROOT / 'distribution/platform-database.php'), 'migrate', '--pretend'])
        seeder = 'Runa\\Database\\Seeders\\DatabaseSeeder'
        code, calls = self.invoke('seed', [seeder])
        self.assertEqual(code, 0)
        self.assertEqual(calls[0].args[0], ['php', str(ROOT / 'distribution/platform-database.php'), 'db:seed', '--class=' + seeder])

    def test_owner_entrypoints_reject_foreign_actions_before_running_anything(self):
        for name in ('check-repository', 'quality-plan', 'quality-status', 'quality-roadmap', 'quality-final'):
            with self.subTest(name=name):
                code, calls = self.invoke(name, ['release'])
                self.assertIsInstance(code, str)
                self.assertEqual(calls, [])
        for retired in ('doctor', 'quality', 'test'):
            self.assertFalse((ROOT / 'tools/maintainer' / retired).exists())

    def test_owner_quality_rejects_invalid_options_before_reconfiguring_the_shared_runner(self):
        original_plan = quality.plan
        cases = [('quality-plan', ['--background']), ('quality-status', ['--group=docs']),
                 ('quality-final', ['--all', '--group=docs']), ('quality-roadmap', ['--group='])]
        for name, arguments in cases:
            with self.subTest(name=name, arguments=arguments):
                code, calls = self.invoke(name, arguments)
                self.assertIsInstance(code, str)
                self.assertEqual(calls, [])
                self.assertIs(quality.plan, original_plan)

    def test_owner_test_runners_select_private_configurations(self):
        import owner_tests
        with tempfile.TemporaryDirectory() as folder, patch.object(owner_tests, 'ROOT', Path(folder)):
            for suite, option, config in (('backend', '--configuration=', 'phpunit.xml'), ('frontend', '--config=', 'vitest.config.mjs'), ('e2e', '--config=', 'playwright.config.mjs')):
                with self.subTest(suite=suite), patch.object(owner_tests, 'worker_cleanup', return_value=0), patch.object(subprocess, 'run', return_value=subprocess.CompletedProcess([], 0)), patch.object(subprocess, 'Popen') as launch:
                    launch.return_value.__enter__.return_value.wait.return_value = 0
                    self.assertEqual(owner_tests.execute(suite, []), 0)
                    self.assertIn(option + 'runa/tests/' + config, launch.call_args.args[0])
                    self.assertTrue(launch.call_args.kwargs['start_new_session'])
                    if suite == 'backend':
                        self.assertIn('--test-directory=runa/tests', launch.call_args.args[0])

    def test_customer_quality_does_not_require_a_runa_roadmap_or_maintainer_files(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(quality, 'ROOT', Path(folder)), patch.object(quality, 'yaml', return_value={'base': [], 'impact_areas': {}, 'final': []}) as yaml:
            roadmap, nodes = quality.plan()
            self.assertEqual(roadmap['id'], 'application')
            self.assertEqual(nodes, {})
            self.assertEqual(yaml.call_args.args[0], Path(folder) / 'tools/application/quality.yml')


if __name__ == '__main__':
    unittest.main()
