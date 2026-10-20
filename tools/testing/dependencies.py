#!/usr/bin/env python3
"""Dependency ownership conflicts and lock preservation contracts."""
import json
from pathlib import Path
import sys
import tempfile
import unittest
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'project'))
from dependencies import compose, materialize


class DependencyContracts(unittest.TestCase):
    def test_combines_customer_dependencies_without_mutating_inputs(self):
        platform = {'require': {'framework': '1.0'}, 'scripts': {'build': 'protected'}}
        application = {'require': {'customer': '2.0'}}
        result = compose(platform, application, 'composer')
        self.assertEqual(result['require'], {'framework': '1.0', 'customer': '2.0'})
        self.assertEqual(platform['require'], {'framework': '1.0'})
        self.assertEqual(result['scripts'], platform['scripts'])

    def test_rejects_conflicting_and_unsupported_inputs(self):
        for app in ({'require': {'framework': '2.0'}}, {'require-dev': {'framework': '1.0'}}, {'scripts': {'build': 'replace'}}, {'require': []}, {'require': {'new': ''}}):
            with self.subTest(app=app), self.assertRaises(ValueError):
                compose({'require': {'framework': '1.0'}}, app, 'composer')

    def test_checks_both_graphs_before_writes_and_preserves_locks(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            for owner in ('runa', 'app'):
                (root / 'src' / owner).mkdir(parents=True)
                for kind in ('composer', 'package'):
                    (root / 'src' / owner / (kind + '.json')).write_text('{}')
            lock = root / 'src/composer.lock'
            lock.write_bytes(b'customer lock sentinel')
            materialize(root)
            materialize(root, True)
            before = (root / 'src/composer.json').read_bytes()
            (root / 'src/app/composer.json').write_text('{"require":{"customer":"1.0"}}')
            (root / 'src/app/package.json').write_text('{"scripts":{}}')
            with self.assertRaises(ValueError):
                materialize(root)
            self.assertEqual((root / 'src/composer.json').read_bytes(), before)
            self.assertEqual(lock.read_bytes(), b'customer lock sentinel')
            with self.assertRaises(ValueError):
                materialize(root, True)


if __name__ == '__main__':
    unittest.main()
