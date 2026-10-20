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
        self.assertEqual(calls[0].args[0], ['php', 'artisan', 'migrate', '--path=runa/database/migrations', '--pretend'])
        seeder = 'Runa\\Database\\Seeders\\DatabaseSeeder'
        code, calls = self.invoke('seed', [seeder])
        self.assertEqual(code, 0)
        self.assertEqual(calls[0].args[0], ['php', 'artisan', 'db:seed', '--class=' + seeder])

    def test_customer_quality_does_not_require_a_runa_roadmap_or_maintainer_files(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(quality, 'ROOT', Path(folder)), patch.object(quality, 'yaml', return_value={'base': [], 'impact_areas': {}, 'final': []}) as yaml:
            roadmap, nodes = quality.plan()
            self.assertEqual(roadmap['id'], 'application')
            self.assertEqual(nodes, {})
            self.assertEqual(yaml.call_args.args[0], Path(folder) / 'tools/application/quality.yml')


if __name__ == '__main__':
    unittest.main()
