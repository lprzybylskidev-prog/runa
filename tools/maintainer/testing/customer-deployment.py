#!/usr/bin/env python3
"""Real Git customer fetch/staging and protected-source contracts; no production data."""
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
import customer_source as source
import operations
import repository_boundary as boundary


class CustomerDeployment(unittest.TestCase):
    def git(self, root, *args):
        return subprocess.check_output(['git', '-C', str(root), *args], stderr=subprocess.DEVNULL).decode().strip()

    def commit(self, root):
        self.git(root, 'add', '--all')
        self.git(root, '-c', 'core.hooksPath=/dev/null', '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture')
        return self.git(root, 'rev-parse', 'HEAD')

    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        base = Path(self.directory.name)
        self.root, self.remote, self.state = base / 'installed', base / 'customer', base / 'state'
        self.root.mkdir()
        self.state.mkdir()
        self.git(self.root, 'init', '-q', '-b', 'main')
        for name, text in {'VERSION': '0.2.1', 'src/runa/platform.json': '{}', 'src/app/application.json': '{"version":"customer-choice"}', 'src/app/composer.json': '{}', 'src/composer.json': '{}', 'src/composer.lock': '{}', 'compose.production.yaml': 'protected', 'docs/app/README.md': 'customer'}.items():
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(text)
        self.installed = self.commit(self.root)
        self.git(self.root, 'clone', '-q', str(self.root), str(self.remote))
        self.git(self.root, 'remote', 'add', 'origin', 'https://github.com/fixture/runa.git')
        self.git(self.root, 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream')
        self.git(self.root, 'remote', 'add', 'customer', str(self.remote))
        self.git(self.root, 'config', 'runa.applicationRemote', 'customer')
        boundary.write_staging_receipt(self.root, {'schema_version': 1, 'repository': 'fixture/runa', 'mode': 'prod', 'commit': self.installed,
                                                 'projection': {'version': 1, 'excluded': list(boundary.OWNER_PATHS)}})
        (self.remote / 'src/app/new.php').write_text('<?php // customer transition')
        self.target = self.commit(self.remote)

    def test_default_branch_lightweight_tag_arbitrary_version_and_exact_commit(self):
        self.git(self.remote, 'tag', 'customer-release-42')
        self.git(self.remote, 'branch', 'release/customer-v2')
        before = (self.root / '.git/config').read_bytes()
        for target in ('main', 'customer-release-42', 'release/customer-v2', 'refs/heads/main', self.target):
            with self.subTest(target=target), source.fetch(self.root, self.state, target) as (download, sha):
                self.assertEqual(sha, self.target)
                self.assertTrue(download.exists())
            self.assertFalse(download.exists())
        self.assertEqual(list(self.state.iterdir()), [])
        self.assertEqual(self.git(self.root, 'rev-parse', 'HEAD'), self.installed)
        self.assertEqual((self.root / '.git/config').read_bytes(), before)

    def test_customer_origin_can_be_selected_without_rewriting_remotes_or_history(self):
        self.git(self.root, 'remote', 'set-url', 'origin', str(self.remote))
        self.git(self.root, 'config', 'runa.applicationRemote', 'origin')
        before = (self.root / '.git/config').read_bytes()
        self.assertEqual(boundary.assert_production_installation(self.root)['mode'], 'prod')
        with source.fetch(self.root, self.state) as (_, sha):
            self.assertEqual(sha, self.target)
        self.assertEqual(before, (self.root / '.git/config').read_bytes())

    def test_committing_protected_drift_in_operator_and_customer_repos_cannot_upgrade_platform(self):
        installed = Path(self.directory.name) / 'previous-release'
        self.git(self.root, 'clone', '-q', str(self.root), str(installed))
        boundary.write_staging_receipt(installed, boundary.receipt(self.root))
        for repository in (self.root, self.remote):
            (repository / 'VERSION').write_text('committed-platform-drift')
            self.commit(repository)
        with self.assertRaisesRegex(ValueError, 'successfully installed protected source'):
            with source.fetch(self.root, self.state, installed=installed):
                self.fail('committed platform drift accepted')

    def test_modified_successful_release_is_rejected_before_compose_can_use_it(self):
        installed = Path(self.directory.name) / 'previous-release'
        self.git(self.root, 'clone', '-q', str(self.root), str(installed))
        boundary.write_staging_receipt(installed, boundary.receipt(self.root))
        (installed / 'compose.production.yaml').write_text('unsafe drift')
        with self.assertRaisesRegex(ValueError, 'Successful release checkout is modified'):
            with source.fetch(self.root, self.state, installed=installed):
                pass
        self.commit(installed)
        with self.assertRaisesRegex(ValueError, 'Successful release checkout is modified'):
            with source.fetch(self.root, self.state, installed=installed):
                pass

    def test_staging_uses_customer_source_and_retains_original_acquisition_identity(self):
        import runtime
        with source.fetch(self.root, self.state) as (download, sha), patch.object(operations, 'ROOT', self.root), patch.object(runtime, 'ROOT', self.root), patch.object(operations, 'STATE', self.state):
            release = operations.checkout(sha, download)
            self.assertEqual((release / 'src/app/new.php').read_text(), '<?php // customer transition')
            self.assertEqual((release / 'src/runa/platform.json').read_bytes(), (self.root / 'src/runa/platform.json').read_bytes())
            self.assertEqual(boundary.assert_production_installation(release)['repository'], 'fixture/runa')
            self.assertEqual(operations.checkout(sha, download), release)
        self.assertTrue(release.exists())
        self.assertFalse(download.exists())

    def test_missing_ambiguous_and_invalid_refs_leave_running_checkout_unchanged(self):
        self.git(self.remote, 'tag', 'main')
        for target in ('main', 'missing', '--upload-pack=bad', 'main~1', 'a..b', 'refs/remotes/origin/main', 'bad.lock', 'bad\n', 'feature//bad'):
            with self.subTest(target=target), self.assertRaises(ValueError):
                with source.fetch(self.root, self.state, target):
                    self.fail('invalid target accepted')
        with source.fetch(self.root, self.state, 'refs/heads/main') as (_, sha):
            self.assertEqual(sha, self.target)
        self.assertEqual(list(self.state.iterdir()), [])

    def test_protected_modification_addition_deletion_and_mode_changes_block(self):
        for mutation in ('change', 'add', 'delete', 'mode'):
            self.git(self.remote, 'reset', '--hard', self.target)
            path = self.remote / 'src/runa/platform.json'
            if mutation == 'change':
                path.write_text('changed')
            elif mutation == 'add':
                (self.remote / 'src/runa/unknown.php').write_text('<?php')
            elif mutation == 'delete':
                path.unlink()
            else:
                path.chmod(0o755)
            self.commit(self.remote)
            with self.subTest(mutation=mutation), self.assertRaisesRegex(ValueError, 'protected platform source'):
                with source.fetch(self.root, self.state):
                    self.fail('protected source changed')
        self.assertEqual(list(self.state.iterdir()), [])

    def test_application_symlinks_dirty_protected_source_and_failed_fetch_block(self):
        (self.remote / 'src/app/escape').symlink_to('/etc/passwd')
        self.commit(self.remote)
        with self.assertRaisesRegex(ValueError, 'symlinks'):
            with source.fetch(self.root, self.state):
                pass
        self.git(self.remote, 'reset', '--hard', self.target)
        (self.root / 'VERSION').write_text('dirty')
        with self.assertRaisesRegex(ValueError, 'modified/untracked protected'):
            with source.fetch(self.root, self.state):
                pass
        self.git(self.root, 'remote', 'set-url', 'customer', str(self.remote / 'missing'))
        with self.assertRaisesRegex(ValueError, 'Git operation failed'):
            with source.fetch(self.root, self.state):
                pass
        self.assertEqual(list(self.state.iterdir()), [])

    def test_owner_remote_credentials_helpers_and_ambiguous_remote_config_are_denied(self):
        for url in ('https://github.com/fixture/runa.git', 'git@github.com:fixture/runa.git', 'ssh://git@github.com/fixture/runa.git',
                    'https://www.github.com/FIXTURE//runa.GIT', 'https://github.com/fixture%2Fruna.git', 'https://github.com/fixture/./runa.git',
                    'https://user:synthetic@github.com/customer/app.git', 'ext::bad', '--help', 'https://github.com/customer/app.git?token=synthetic'):
            self.git(self.root, 'config', 'remote.customer.url', url)
            with self.subTest(url=url), self.assertRaises(ValueError):
                boundary.application_remote(self.root)
        self.git(self.root, 'remote', 'set-url', 'customer', str(self.remote))
        self.git(self.root, 'config', '--add', 'runa.applicationRemote', 'origin')
        with self.assertRaises(ValueError):
            boundary.application_remote(self.root)


if __name__ == '__main__':
    unittest.main()
