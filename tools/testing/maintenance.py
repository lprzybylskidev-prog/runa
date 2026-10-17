#!/usr/bin/env python3
"""Maintenance validator rejection contracts; no repository fixtures are written."""
import contextlib
import copy
import io
from pathlib import Path
import sys
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import checks


class MaintenanceContracts(unittest.TestCase):
    def setUp(self):
        self.data = {kind: checks.yaml(ROOT / f'docs/maintenance/{kind}.yml') for kind in ('registry', 'backlog', 'deprecations')}

    def validate(self):
        with patch.object(checks, 'yaml', side_effect=lambda path: self.data[path.stem]), contextlib.redirect_stdout(io.StringIO()):
            checks.maintenance()

    def test_current_registry_passes(self):
        self.validate()

    def test_duplicate_id_and_dependency_version_drift_are_rejected(self):
        original = copy.deepcopy(self.data)
        self.data['registry']['entries'].append(copy.deepcopy(self.data['registry']['entries'][0]))
        with self.assertRaisesRegex(ValueError, 'Duplicate'):
            self.validate()
        self.data = original
        next(e for e in self.data['registry']['entries'] if e.get('package') == 'vue')['version'] = '0.0.0'
        with self.assertRaisesRegex(ValueError, 'Dependency version'):
            self.validate()

    def test_incomplete_maintenance_and_deprecation_are_rejected(self):
        for kind in ('backlog', 'deprecations'):
            self.data[kind]['items'] = [{'id': 'MT-2026-999'}]
            with self.assertRaisesRegex(ValueError, 'Incomplete'):
                self.validate()
            self.data[kind]['items'] = []


if __name__ == '__main__':
    unittest.main()
