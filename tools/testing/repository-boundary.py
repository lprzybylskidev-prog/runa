#!/usr/bin/env python3
"""Real Git role fencing: no maintenance/customer source or remote repair."""
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import repository_boundary as boundary
import installation
import operations
import production
from unittest.mock import patch


class RepositoryBoundary(unittest.TestCase):
    def git(self, root, *arguments):
        return subprocess.check_output(['git', '-C', str(root), *arguments], stderr=subprocess.DEVNULL).decode().strip()

    def fixture(self, root):
        self.git(root, 'init', '--quiet')
        self.git(root, 'remote', 'add', 'origin', 'https://github.com/fixture/runa.git')
        self.git(root, 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream')
        boundary.write_staging_receipt(root, {'schema_version': 1, 'repository': 'fixture/runa',
                                            'projection': {'version': 1, 'excluded': list(boundary.OWNER_PATHS)}})

    def test_unknown_and_maintenance_repositories_are_not_mutated(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.git(root, 'init', '--quiet')
            self.git(root, 'remote', 'add', 'origin', 'https://github.com/lprzybylskidev-prog/runa.git')
            original = (root / '.git/config').read_bytes()
            with self.assertRaises(ValueError):
                boundary.assert_installation(root)
            with patch.object(installation, 'ROOT', root), self.assertRaises(ValueError):
                installation.install('dev', 'a' * 40)
            with patch.object(operations, 'ROOT', root), self.assertRaises(ValueError):
                operations.deploy('a' * 40, False)
            with patch.object(operations, 'ROOT', root), self.assertRaises(ValueError):
                operations.main('deploy', ['a' * 40])
            self.assertEqual((root / '.git/config').read_bytes(), original)
            self.assertEqual(self.git(root, 'status', '--porcelain'), '')

    def test_customer_read_only_role_and_existing_customer_remote_preservation(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            self.assertEqual(boundary.assert_installation(root)['repository'], 'fixture/runa')
            with patch.dict(os.environ, {'GIT_DIR': str(ROOT / '.git')}), self.assertRaises(ValueError):
                boundary.assert_installation(root)
            self.git(root, 'remote', 'set-url', 'origin', 'https://github.com/customer/application.git')
            config = (root / '.git/config').read_bytes()
            with self.assertRaises(ValueError):
                boundary.assert_installation(root)
            self.assertEqual((root / '.git/config').read_bytes(), config)

    def test_ambient_push_origin_alias_escape_and_restored_owner_source_are_denied(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / 'installation'
            root.mkdir()
            self.fixture(root)
            alias = Path(folder) / 'alias'
            alias.symlink_to(root)
            with self.assertRaises(ValueError):
                boundary.assert_installation(alias)
            self.git(root, 'config', '--add', 'remote.origin.pushurl', 'https://github.com/lprzybylskidev-prog/runa.git')
            with self.assertRaises(ValueError):
                boundary.assert_installation(root)
            self.git(root, 'config', '--replace-all', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream')
            (root / 'distribution').mkdir()
            with self.assertRaises(ValueError):
                boundary.assert_installation(root)

    def test_customer_command_registry_excludes_maintenance_even_if_restored(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            source = root / 'src/runa/management'
            shutil.copytree(ROOT / 'src/runa/management', source)
            program = 'require $argv[1]; $classes = require $argv[2]; foreach ($classes as $class) { echo (new $class)->getName(), "\\n"; }'
            def names():
                return subprocess.check_output(['php', '-r', program, str(ROOT / 'src/vendor/autoload.php'), str(source / 'commands.php')]).decode().splitlines()
            self.fixture(root)
            for restored in (False, True):
                if restored:
                    shutil.copytree(ROOT / 'distribution/maintenance', root / 'distribution/maintenance')
                registered = names()
                self.assertNotIn('runa:backup', registered)
                self.assertNotIn('runa:integrity', registered)
                self.assertNotIn('runa:project', registered)
                self.assertIn('runa:dev', registered)
                for name in ('runa:install', 'runa:project', 'runa:hook'):
                    self.assertNotIn(name, registered)
            (root / '.git/runa-bootstrap.json').unlink()
            self.assertTrue({'runa:install', 'runa:hook'}.issubset(names()))

    def test_distribution_projection_is_fixed_and_shared(self):
        spec = importlib.util.spec_from_file_location('standalone', ROOT / 'distribution/bootstrap.py')
        standalone = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(standalone)
        self.assertEqual(standalone.OWNER_PATHS, boundary.OWNER_PATHS)
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            path = root / '.git/runa-bootstrap.json'
            data = json.loads(path.read_text())
            data['projection']['excluded'].append('src/app')
            path.write_text(json.dumps(data))
            with self.assertRaises(ValueError):
                boundary.assert_installation(root)


if __name__ == '__main__':
    unittest.main()
