#!/usr/bin/env python3
"""Security evidence must be actual, complete and bound to the reviewed content."""
import contextlib
import hashlib
import io
import json
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import security


class ReviewContracts(unittest.TestCase):
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
