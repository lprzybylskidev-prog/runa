#!/usr/bin/env python3
"""Security evidence must be actual, complete and bound to the reviewed content."""
import contextlib
import hashlib
import io
import json
from pathlib import Path
import sys
import tempfile
import subprocess
from types import SimpleNamespace
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import security


class ReviewContracts(unittest.TestCase):
    def test_custom_service_fixture_pulls_only_exact_digest_and_rejects_wrong_identity(self):
        import service_fixtures
        reference = service_fixtures.REDIS_IMAGE
        metadata = json.dumps([{'RepoDigests': ['redis@' + reference.split('@')[1]]}])
        missing = SimpleNamespace(returncode=1, stdout='')
        present = SimpleNamespace(returncode=0, stdout=metadata)
        with patch.object(service_fixtures.subprocess, 'run', side_effect=[missing, SimpleNamespace(returncode=0), present]) as run:
            self.assertEqual(service_fixtures.redis_image(), reference)
            self.assertEqual(run.call_args_list[1].args[0], ['docker', 'pull', reference])
        with patch.object(service_fixtures.subprocess, 'run', return_value=SimpleNamespace(returncode=0, stdout='[{"RepoDigests":["redis@sha256:other"]}]')):
            with self.assertRaisesRegex(ValueError, 'pinned Redis artifact'):
                service_fixtures.redis_image()

    def test_failed_repository_and_image_scans_collect_all_images_and_keep_failure(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            binary = root / '.runtime/tools/trivy'
            binary.parent.mkdir(parents=True)
            binary.touch()
            reports = root / '.runtime/security'
            reports.mkdir()
            (reports / 'repository.json').write_text('{"stale": true}')
            images = ['postgres:18.6-bookworm', 'runa-production:' + 'a' * 40, 'runa-nginx:' + 'a' * 40]
            fixture_image = 'redis:fixture@sha256:exact'
            def execute(args, **kwargs):
                if '--version' in args:
                    return SimpleNamespace(stdout='Version: 0.76.0')
                if args[0] == str(binary):
                    raise subprocess.CalledProcessError(1, args)
                return SimpleNamespace(stdout='')
            with patch.object(security, 'ROOT', root), patch.object(security, 'inside', return_value=False), patch.object(security, 'run', side_effect=execute) as run, patch.object(security, 'production_images', return_value=images), patch.object(security, 'dependencies') as audits, contextlib.redirect_stdout(io.StringIO()):
                with self.assertRaisesRegex(ValueError, 'Security checks failed'):
                    security.trivy(additional_images=[fixture_image])
                scans = [call.args[0] for call in run.call_args_list if call.args[0][0] == str(binary) and '--version' not in call.args[0]]
                self.assertEqual(len(scans), 5)
                self.assertIn('--include-dev-deps', scans[0])
                excluded = scans[0][scans[0].index('--skip-dirs') + 1].split(',')
                self.assertIn('src/vendor', excluded)
                self.assertIn('src/node_modules', excluded)
                self.assertNotIn('src/app', excluded)
                self.assertNotIn('src/composer.lock', excluded)
                self.assertNotIn('src/pnpm-lock.yaml', excluded)
                self.assertEqual(len({call.args[0][-1] for call in run.call_args_list if call.args[0][:3] == ['docker', 'image', 'save']}), 4)
                audits.assert_called_once()
                self.assertFalse((root / '.runtime/trivy-image.tar').exists())
                self.assertEqual((reports / 'repository.json').read_text(), '')

    def test_unknown_owner_and_dev_image_targets_are_rejected_before_scanning(self):
        import repository_boundary as boundary
        for mode in ('dev', None, 'unknown'):
            with self.subTest(mode=mode), patch.object(boundary, 'assert_installation', return_value={'mode': mode}), patch.object(security.subprocess, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'require a production installation'):
                    security.production_images(ROOT)
                run.assert_not_called()
        with tempfile.TemporaryDirectory() as folder, patch.object(security.subprocess, 'run') as run:
            with self.assertRaisesRegex(ValueError, 'receipt'):
                security.production_images(Path(folder))
            run.assert_not_called()

    def test_fixture_image_selection_uses_its_own_context_while_scanning_owner_source(self):
        import repository_boundary as boundary
        fixture = Path('/separate/customer-production')
        selected = {'images': ['runa-production:exact', 'postgres:exact'], 'locks': {
            name: hashlib.sha256((ROOT / name).read_bytes()).hexdigest() for name in ('src/composer.lock', 'src/pnpm-lock.yaml')}}
        result = SimpleNamespace(stdout=json.dumps(selected))
        with patch.object(boundary, 'assert_production_installation') as guard, patch.object(security.subprocess, 'run', return_value=result) as run:
            self.assertEqual(security.production_images(fixture), ['runa-production:exact', 'postgres:exact'])
            guard.assert_called_once_with(fixture)
            self.assertEqual(run.call_args.kwargs['cwd'], fixture)
            self.assertIn('assert_production_installation(operations.ROOT)', run.call_args.args[0][2])
            selected['locks']['src/composer.lock'] = 'different'
            result.stdout = json.dumps(selected)
            with self.assertRaisesRegex(ValueError, 'dependency locks differ'):
                security.production_images(fixture)
            selected['locks']['src/composer.lock'] = hashlib.sha256((ROOT / 'src/composer.lock').read_bytes()).hexdigest()
            selected['images'] = []
            result.stdout = json.dumps(selected)
            with self.assertRaisesRegex(ValueError, 'no runtime images'):
                security.production_images(fixture)

    def test_isolated_audits_reject_different_locks_before_docker(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / 'installer'
            dev = Path(folder) / 'dev'
            (root / 'src').mkdir(parents=True); (dev / 'src').mkdir(parents=True)
            (root / 'src/composer.lock').write_text('one')
            (dev / 'src/composer.lock').write_text('two')
            with patch.object(security, 'ROOT', root), patch.object(security, 'AUDIT_ROOT', dev), patch.object(security, 'inside', return_value=False), patch.object(security.subprocess, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'lockfiles differ'):
                    security.dependencies()
                run.assert_not_called()

    def test_missing_stale_blocking_and_changed_report_evidence_rejected(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(security, 'ROOT', Path(folder)), patch.object(security, 'surface', return_value={'app/example.php': 'current'}):
            root = Path(folder)
            (root / '.runtime').mkdir()
            report = root / 'report.md'
            report.write_text('Fixture review, not a real project attestation.')
            path = root / '.runtime/security-review.json'
            with self.assertRaisesRegex(ValueError, 'Missing actual'):
                security.review()
            data = {'reviewer': 'Fixture', 'reviewed_at': '2026-10-17', 'report': 'report.md', 'report_sha256': hashlib.sha256(report.read_bytes()).hexdigest(), 'blocking_findings': [], 'surface': {'app/example.php': 'old'}}
            path.write_text(json.dumps(data))
            with self.assertRaisesRegex(ValueError, 'stale'):
                security.review()
            data['surface'] = {'app/example.php': 'current'}
            data['blocking_findings'] = ['unresolved']
            path.write_text(json.dumps(data))
            with self.assertRaisesRegex(ValueError, 'blocking'):
                security.review()
            data['blocking_findings'] = []
            path.write_text(json.dumps(data))
            with contextlib.redirect_stdout(io.StringIO()):
                security.review()
            report.write_text('Changed after review.')
            with self.assertRaisesRegex(ValueError, 'report is missing or changed'):
                security.review()


if __name__ == '__main__':
    unittest.main()
