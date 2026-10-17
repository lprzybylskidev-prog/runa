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

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import security


class ReviewContracts(unittest.TestCase):
    def test_failed_repository_and_image_scans_collect_all_images_and_keep_failure(self):
        import operations
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            binary = root / '.runtime/tools/trivy'
            binary.parent.mkdir(parents=True)
            binary.touch()
            reports = root / '.runtime/security'
            reports.mkdir()
            (reports / 'repository.json').write_text('{"stale": true}')
            images = ['postgres:18.6-bookworm', 'runa-production:' + 'a' * 40, 'runa-nginx:' + 'a' * 40]
            def execute(args, **kwargs):
                if '--version' in args:
                    return SimpleNamespace(stdout='Version: 0.76.0')
                if args[0] == str(binary):
                    raise subprocess.CalledProcessError(1, args)
                return SimpleNamespace(stdout='')
            with patch.object(security, 'ROOT', root), patch.object(security, 'inside', return_value=False), patch.object(security, 'run', side_effect=execute) as run, patch.object(operations, 'active_release', return_value=None), patch.object(operations, 'dc', return_value=SimpleNamespace(stdout='\n'.join(images))), patch.object(security, 'dependencies') as audits, contextlib.redirect_stdout(io.StringIO()):
                with self.assertRaisesRegex(ValueError, 'Security checks failed'):
                    security.trivy()
                scans = [call.args[0] for call in run.call_args_list if call.args[0][0] == str(binary) and '--version' not in call.args[0]]
                self.assertEqual(len(scans), 4)
                self.assertIn('--include-dev-deps', scans[0])
                audits.assert_called_once()
                self.assertFalse((root / '.runtime/trivy-image.tar').exists())
                self.assertEqual((reports / 'repository.json').read_text(), '')

    def test_isolated_audits_reject_different_locks_before_docker(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / 'installer'
            dev = Path(folder) / 'dev'
            root.mkdir(); dev.mkdir()
            (root / 'composer.lock').write_text('one')
            (dev / 'composer.lock').write_text('two')
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
