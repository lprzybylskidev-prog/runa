#!/usr/bin/env python3
"""Real release objects, malicious file/baseline inputs and report nonmutation."""
import contextlib
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import integrity
import operations
import repository_boundary as boundary


class IntegrityContracts(unittest.TestCase):
    def git(self, root, *args):
        return subprocess.check_output(['git', '-C', str(root), '-c', 'core.hooksPath=/dev/null', '-c', 'user.name=Integrity Fixture',
                                        '-c', 'user.email=fixture@example.invalid', *args], stderr=subprocess.DEVNULL).decode().strip()

    def fixture(self, root):
        self.git(root, 'init', '-q')
        files = {'VERSION': '1.0.0\n', 'src/runa/platform.json': '{}\n', 'src/runa/Support/Example.php': '<?php echo "trusted";\n',
                 'src/app/application.json': '{}', 'src/app/customer.txt': 'customer sentinel', 'src/public/index.php': '<?php', 'tools/project/fixture.py': 'print(1)',
                 'src/bootstrap/app.php': '<?php', 'src/bootstrap/cache/.gitignore': '*\n!.gitignore\n',
                 'docker/php/Dockerfile': 'FROM fixture', 'compose.production.yaml': 'services: {}',
                 'src/runa/composer.json': '{"name":"runa/core"}', 'src/app/composer.json': '{}',
                 'src/composer.json': '{"name":"runa/core"}', 'bin/tool': '#!/bin/sh\nexit 0\n', 'src/composer.lock': '{}', '.gitignore': 'src/runa/ignored.php\n'}
        for name, content in files.items():
            file = root / name
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text(content)
        (root / 'bin/tool').chmod(0o755)
        (root / 'bin/alias').symlink_to('tool')
        self.git(root, 'add', '.')
        self.git(root, 'commit', '-qm', 'fixture')
        sha = self.git(root, 'rev-parse', 'HEAD')
        _, digest = integrity.release_inventory(root, sha)
        data = {'schema_version': 1, 'commit': sha, 'source_sha256': digest, 'mode': 'dev',
                'projection': {'version': 1, 'excluded': list(boundary.OWNER_PATHS)}}
        boundary.write_staging_receipt(root, data)
        integrity.seal(root)
        return data

    def snapshot(self, root):
        result = {}
        for path in root.rglob('*'):
            if path.is_symlink():
                result[str(path.relative_to(root))] = ('link', os.readlink(path))
            elif path.is_file():
                result[str(path.relative_to(root))] = (path.stat().st_mode, hashlib.sha256(path.read_bytes()).hexdigest(), path.stat().st_mtime_ns)
        return result

    def test_pristine_and_customer_changes_are_read_only_and_not_platform_drift(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            (root / 'src/app/customer.txt').write_text('customer modifications')
            (root / 'src/app/new.php').write_text('<?php // customer code')
            before = self.snapshot(root)
            result = integrity.report(root)
            self.assertTrue(result['ok'])
            self.assertEqual(self.snapshot(root), before)
            self.assertEqual(integrity.report(root), result)
            self.assertEqual(self.snapshot(root), before)

    def test_modified_missing_unexpected_and_ignored_source_all_block(self):
        for mutation in ('modified', 'missing', 'unexpected', 'ignored', 'mode'):
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                self.fixture(root)
                path = root / 'src/runa/Support/Example.php'
                if mutation == 'modified':
                    path.write_text('changed')
                elif mutation == 'missing':
                    path.unlink()
                elif mutation == 'mode':
                    path.chmod(0o755)
                else:
                    path = root / ('src/runa/ignored.php' if mutation == 'ignored' else 'src/runa/Support/injected.php')
                    path.write_text('<?php // injected')
                before = self.snapshot(root)
                result = integrity.report(root)
                self.assertFalse(result['protected_ok'])
                with self.assertRaises(integrity.IntegrityError):
                    integrity.require_protected(result)
                self.assertEqual(self.snapshot(root), before)

    def test_interpreter_cache_is_narrow_and_cli_does_not_write_bytecode(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            cache = root / 'tools/project/__pycache__'
            cache.mkdir()
            (cache / 'fixture.cpython-312.pyc').write_bytes(b'generated')
            self.assertTrue(integrity.report(root)['ok'])
            (cache / 'injected.cpython-312.pyc').write_bytes(b'untracked source')
            self.assertFalse(integrity.report(root)['protected_ok'])

    def test_infrastructure_and_lock_drift_are_distinct_and_preserved(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            (root / 'docker/php/Dockerfile').write_text('FROM custom')
            (root / 'src/composer.lock').write_text('{"customer":"graph"}')
            result = integrity.report(root)
            self.assertTrue(result['protected_ok'])
            self.assertTrue(result['update_blocked'])
            self.assertEqual({row['category'] for row in result['changes']}, {'infrastructure', 'composition'})
            integrity.require_protected(result)

    def test_symlink_escape_linked_parent_fifo_and_generated_php_injection_fail(self):
        for variant in ('symlink', 'parent', 'fifo', 'cache', 'assets', 'generated_errors'):
            with self.subTest(variant=variant), tempfile.TemporaryDirectory() as folder, tempfile.TemporaryDirectory() as outside:
                root = Path(folder)
                self.fixture(root)
                target = root / 'src/runa/Support/Example.php'
                if variant in ('symlink', 'parent', 'fifo'):
                    target.unlink()
                if variant == 'symlink':
                    target.symlink_to(Path(outside) / 'secret')
                elif variant == 'parent':
                    target.parent.rmdir()
                    target.parent.symlink_to(outside)
                elif variant == 'fifo':
                    os.mkfifo(target)
                else:
                    target = root / {'cache': 'src/bootstrap/cache/unapproved.php', 'assets': 'src/public/build/injected.php',
                                     'generated_errors': 'src/runa/resources/views/errors/generated/injected.php'}[variant]
                    target.parent.mkdir(parents=True, exist_ok=True)
                    target.write_text('<?php')
                result = integrity.report(root)
                self.assertFalse(result['protected_ok'])
                self.assertTrue(result['update_blocked'])
                self.assertTrue(any(row['path'] == str(target.relative_to(root)) for row in result['changes']))
                self.assertEqual(list(Path(outside).iterdir()), [])

    def test_special_file_cannot_hide_behind_an_approved_cache_filename(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            os.mkfifo(root / 'src/bootstrap/cache/config.php')
            self.assertFalse(integrity.report(root)['protected_ok'])

    def test_exact_generated_files_do_not_mask_other_source(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            for name in ('src/bootstrap/cache/packages.php', 'src/storage/framework/health/queue.json',
                         'src/runa/resources/views/errors/generated/500-pl-light-runa.html', 'src/public/build/assets/app.js', '.env.prod'):
                file = root / name
                file.parent.mkdir(parents=True, exist_ok=True)
                file.write_text('generated')
            self.assertTrue(integrity.report(root)['ok'])

    def test_baseline_cannot_bless_modified_files_exclusions_profile_or_commit(self):
        for mutation in ('hash', 'exclusions', 'profile', 'commit', 'missing', 'symlink'):
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                self.fixture(root)
                path = root / integrity.BASELINE
                value = json.loads(path.read_text())
                if mutation == 'hash':
                    value['files'][0]['sha256'] = 'f' * 64
                elif mutation == 'exclusions':
                    value['exclusions'] = ['src/runa']
                elif mutation == 'profile':
                    value['profile'] = 'production'
                elif mutation == 'commit':
                    value['commit'] = 'f' * 40
                elif mutation == 'missing':
                    path.unlink()
                else:
                    path.unlink()
                    path.symlink_to(root / 'src/app/application.json')
                if mutation not in ('missing', 'symlink'):
                    path.write_text(json.dumps(value))
                before = self.snapshot(root)
                with contextlib.redirect_stdout(io.StringIO()) as output:
                    self.assertEqual(integrity.main(['--json'], root), 1)
                self.assertTrue(json.loads(output.getvalue())['update_blocked'])
                self.assertEqual(self.snapshot(root), before)

    def test_changed_head_and_missing_git_objects_fail_without_rebaseline(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            self.git(root, 'commit', '--allow-empty', '-qm', 'different commit')
            with self.assertRaisesRegex(integrity.IntegrityError, 'stale'):
                integrity.report(root)
            self.git(root, 'checkout', '--detach', 'HEAD~1')
            shutil.rmtree(root / '.git/objects')
            with self.assertRaises(integrity.IntegrityError):
                integrity.report(root)

    def test_customer_dependency_inputs_must_match_generated_composition(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            self.assertTrue(integrity.report(root)['composition_valid'])
            customer = root / 'src/app/composer.json'
            customer.write_text('{"require":{"customer/package":"1.0.0"}}')
            before = self.snapshot(root)
            result = integrity.report(root)
            self.assertTrue(result['protected_ok'])
            self.assertFalse(result['composition_valid'])
            self.assertTrue(result['update_blocked'])
            self.assertEqual(self.snapshot(root), before)

    def test_sealing_uses_git_objects_and_cannot_bless_live_tampering(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            original = (root / integrity.BASELINE).read_bytes()
            (root / 'src/runa/Support/Example.php').write_text('untrusted')
            integrity.seal(root)
            self.assertEqual((root / integrity.BASELINE).read_bytes(), original)
            self.assertFalse(integrity.report(root)['protected_ok'])

    def test_receipt_exclusion_tampering_and_symlinked_generated_roots_fail(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            (root / 'src/vendor').symlink_to('/etc')
            self.assertFalse(integrity.report(root)['protected_ok'])
            (root / 'src/vendor').unlink()
            data = boundary.receipt(root)
            data['projection']['excluded'].append('src/runa')
            boundary.write_staging_receipt(root, data)
            with contextlib.redirect_stdout(io.StringIO()) as output:
                self.assertEqual(integrity.main(['--json'], root), 1)
            self.assertTrue(json.loads(output.getvalue())['update_blocked'])

    def test_bootstrap_baseline_matches_independent_git_inventory(self):
        spec = importlib.util.spec_from_file_location('bootstrap_fixture', ROOT / 'tools/testing/bootstrap.py')
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        helper = module.BootstrapContracts()
        with tempfile.TemporaryDirectory() as folder, helper.local_git():
            client, identity = helper.fixture(folder)
            destination = Path(folder) / 'installed'
            module.bootstrap.acquire(client, 'v1.0.0', destination, 'dev')
            self.assertTrue(integrity.report(destination)['ok'])
            (destination / 'bin/tool').write_text('untrusted local')
            self.assertFalse(integrity.report(destination)['protected_ok'])

    def test_production_projection_is_exact_and_detects_reintroduced_source(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            data = boundary.receipt(root)
            data['mode'] = 'prod'
            boundary.write_staging_receipt(root, data)
            self.git(root, 'sparse-checkout', 'init', '--no-cone')
            subprocess.run(['git', '-C', str(root), 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(operations.SPARSE)+'\n', text=True, check=True, capture_output=True)
            integrity.seal(root, 'production')
            self.assertTrue(integrity.report(root)['ok'])
            (root / 'bin').mkdir(exist_ok=True)
            (root / 'bin/unexpected').write_text('injected')
            self.assertFalse(integrity.report(root)['protected_ok'])


if __name__ == '__main__':
    unittest.main()
