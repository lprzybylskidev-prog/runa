#!/usr/bin/env python3
"""Real customer clones: complete source equality, local roles and no upstream acquisition."""
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'distribution'))
sys.path.insert(0, str(ROOT / 'tools/application'))
import initialize
import bootstrap
import repository_boundary as boundary
import operations
import runtime


class CustomerClones(unittest.TestCase):
    def git(self, root, *args):
        return subprocess.check_output(['git', '-C', str(root), *args], stderr=subprocess.DEVNULL).decode().strip()

    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.repository = self.base / 'customer'; self.repository.mkdir()
        self.git(self.repository, 'init', '-qb', 'main')
        for name, content in {'src/app/application.json': '{"version":"2.1.0"}', 'src/runa/platform.json':'{"version":"0.3.0"}',
                              'src/app/custom.php':'<?php // customer', 'compose.yaml':'services: {}', 'compose.production.yaml':'services: {}',
                              '.devcontainer/devcontainer.json':'{}', 'docker/custom/Dockerfile':'FROM scratch',
                              'docs/app/README.md':'Customer', '.agents/skills/example.md':'public guidance'}.items():
            path = self.repository / name; path.parent.mkdir(parents=True, exist_ok=True); path.write_text(content)
        self.commit()

    def commit(self):
        self.git(self.repository, 'add', '--all')
        self.git(self.repository, '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture')
        return self.git(self.repository, 'rev-parse', 'HEAD')

    def test_production_clone_versions_the_same_complete_customer_source_and_preserves_origin(self):
        destination = self.base / 'production'
        sha = initialize.acquire(str(self.repository), 'main', destination)
        self.assertEqual(sha, self.git(self.repository, 'rev-parse', 'HEAD'))
        for path in self.git(self.repository, 'ls-files').splitlines():
            self.assertEqual((destination / path).read_bytes(), (self.repository / path).read_bytes())
        self.assertEqual(self.git(destination, 'remote', 'get-url', 'origin'), str(self.repository))
        self.assertEqual(boundary.assert_production_installation(destination)['mode'], 'prod')
        with self.assertRaises(ValueError): boundary.assert_development_context(destination)
        with self.assertRaises(bootstrap.BootstrapError): initialize.acquire(str(self.repository), 'main', destination)
        self.assertEqual(self.git(destination, 'rev-parse', 'HEAD'), sha)

    def test_teammate_clone_prepares_local_role_without_first_workstation_metadata(self):
        destination = self.base / 'teammate'
        self.git(self.base, 'clone', '--quiet', str(self.repository), str(destination))
        self.assertFalse((destination / '.git/runa-bootstrap.json').exists())
        before = self.git(destination, 'remote', '-v')
        boundary.prepare_customer_context(destination, 'dev')
        self.assertEqual(boundary.assert_installation(destination)['mode'], 'dev')
        marker = (destination / '.git/runa-bootstrap.json').read_bytes()
        boundary.prepare_customer_context(destination, 'dev')
        self.assertEqual((destination / '.git/runa-bootstrap.json').read_bytes(), marker)
        self.assertEqual(self.git(destination, 'remote', '-v'), before)
        self.assertFalse((destination / '.runtime').exists())
        with self.assertRaises(ValueError): boundary.prepare_customer_context(destination, 'prod')

    def test_ordinary_production_clone_prepares_its_own_role_without_acquisition_fields(self):
        destination = self.base / 'operator'
        self.git(self.base, 'clone', '--quiet', str(self.repository), str(destination))
        before = self.git(destination, 'remote', '-v')
        self.assertFalse((destination / '.git/runa-bootstrap.json').exists())
        boundary.prepare_customer_context(destination, 'prod')
        role = boundary.assert_production_installation(destination)
        self.assertEqual(role['source'], 'customer-repository')
        self.assertEqual(role['commit'], self.git(destination, 'rev-parse', 'HEAD'))
        self.assertNotIn('tag_object', role)
        self.assertEqual(self.git(destination, 'remote', '-v'), before)
        self.assertEqual(self.git(destination, 'status', '--porcelain'), '')

    def test_owner_source_unsafe_refs_and_owner_remote_are_rejected_before_publication(self):
        for value in ('https://github.com/lprzybylskidev-prog/runa.git', 'https://user:secret@example.invalid/repo', '--upload-pack=evil'):
            with self.assertRaises(bootstrap.BootstrapError): initialize.customer_url(value)
        for target in ('--help', '../main', 'refs/notes/private'):
            with self.assertRaises(bootstrap.BootstrapError): initialize.acquire(str(self.repository), target, self.base / 'invalid')
        private = self.repository / 'tools/maintainer/private.py'; private.parent.mkdir(parents=True); private.write_text('private')
        self.commit()
        with self.assertRaisesRegex(bootstrap.BootstrapError, 'owner-only'): initialize.acquire(str(self.repository), 'main', self.base / 'invalid')
        self.assertFalse((self.base / 'invalid').exists())
        self.assertFalse(list(self.base.glob('.runa-customer-*')))

    def test_production_staging_keeps_full_versioned_development_configuration(self):
        destination = self.base / 'production'
        sha = initialize.acquire(str(self.repository), 'main', destination)
        with patch.object(operations, 'ROOT', destination), patch.object(runtime, 'ROOT', destination), patch.object(operations, 'STATE', destination / '.runtime/production'):
            staged = operations.checkout(sha)
            operations.assert_delivery(staged)
            self.assertEqual(set(self.git(staged, 'ls-files').splitlines()), set(self.git(self.repository, 'ls-files').splitlines()))
            for name in self.git(self.repository, 'ls-files').splitlines(): self.assertTrue((staged / name).is_file(), name)


if __name__ == '__main__':
    unittest.main()
