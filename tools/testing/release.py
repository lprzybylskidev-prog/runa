#!/usr/bin/env python3
"""Isolated release metadata acceptance/rejection contracts."""
import contextlib
import io
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'project'))
import release_checks as release


class ReleaseContracts(unittest.TestCase):
    def setUp(self):
        self.folder = tempfile.TemporaryDirectory()
        self.addCleanup(self.folder.cleanup)
        self.root = Path(self.folder.name)
        self.write('VERSION', '0.0.0\n')
        self.write('CHANGELOG.md', '# Changelog\n\n## Unreleased\n')
        self.git('init', '--initial-branch=main')
        self.git('config', 'user.name', 'Release fixture')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('config', 'core.hooksPath', str(self.root / 'fixture-hooks'))
        self.git('add', '.')
        self.git('commit', '-m', 'fixture baseline')
        self.branch = 'roadmap/RM-2026-001-test'
        self.git('switch', '-c', self.branch)
        self.active = 'docs/roadmaps/active/RM-2026-001-test.md'
        self.closed = self.active.replace('/active/', '/closed/')
        self.write(self.active, self.roadmap())

    def git(self, *args):
        return subprocess.run(['git', *args], cwd=self.root, check=True, capture_output=True, text=True)

    def write(self, name, text):
        p = self.root / name
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(text)

    def roadmap(self):
        return f'---\nid: RM-2026-001\nstatus: active\nbranch: {self.branch}\nrelease_type: feature\nclosed_at: null\n---\n\n- [x] Implementation\n- [ ] Full final verification and release\n'

    def validate(self):
        with contextlib.redirect_stdout(io.StringIO()):
            return release.validate(self.root)

    def close(self):
        (self.root / self.active).unlink()
        text = self.roadmap().replace('status: active', 'status: closed').replace('closed_at: null', 'closed_at: 2026-10-18').replace('[ ]', '[x]')
        text += '\n## Final report\nRM-2026-001 delivers version 0.1.0.\n'
        self.write(self.closed, text)
        self.write('VERSION', '0.1.0\n')
        self.write('CHANGELOG.md', '# Changelog\n\n## 0.1.0 — 2026-10-18\nFeature: RM-2026-001 foundation.\n')

    def test_pre_gate_allows_only_final_open_and_computes_version(self):
        self.assertEqual(self.validate(), '0.1.0')
        self.write(self.active, self.roadmap() + '- [ ] Unfinished fix\n')
        with self.assertRaisesRegex(ValueError, 'Only the final'):
            self.validate()

    def test_version_rules_and_major_target_are_explicit(self):
        self.assertEqual(release.expected('1.12.2', 'feature'), '1.13.0')
        self.assertEqual(release.expected('1.12.2', 'fix'), '1.12.3')
        self.assertEqual(release.expected('1.12.2', 'maintenance'), '1.12.3')
        self.assertEqual(release.expected('0.9.0', 'release', '1.0.0'), '1.0.0')
        for kind, target in [('release', None), ('release', '0.0.0'), ('unknown', None)]:
            with self.assertRaises(ValueError):
                release.expected('0.0.0', kind, target)
        with self.assertRaises(ValueError):
            release.version('01.0.0')

    def test_active_version_and_branch_drift_fail(self):
        self.write('VERSION', '0.1.0\n')
        with self.assertRaisesRegex(ValueError, 'Active roadmap VERSION'):
            self.validate()
        self.write('VERSION', '0.0.0\n')
        self.write(self.active, self.roadmap().replace(self.branch, 'roadmap/other'))
        with self.assertRaisesRegex(ValueError, 'branch mismatch'):
            self.validate()

    def test_already_claimed_target_tag_is_rejected(self):
        self.git('tag', 'v0.1.0')
        with self.assertRaisesRegex(ValueError, 'already exists'):
            self.validate()

    def test_closed_metadata_accepts_release_before_publication(self):
        self.close()
        self.assertEqual(self.validate(), '0.1.0')
        self.git('add', '.')
        self.git('commit', '-m', 'fixture closure')
        self.git('tag', '-a', 'v0.1.0', '-m', 'fixture release')
        self.assertEqual(self.validate(), '0.1.0')

    def test_closed_checklist_report_and_release_type_drift_fail(self):
        self.close()
        original = (self.root / self.closed).read_text()
        for changed, message in [(original.replace('[x]', '[ ]', 1), 'open checklist'), (original.replace('## Final report', '## Notes'), 'final report'), (original.replace('release_type: feature', 'release_type: fix'), 'release_type')]:
            self.write(self.closed, changed)
            with self.assertRaisesRegex(ValueError, message):
                self.validate()
        self.write(self.closed, original)

    def test_changelog_mismatch_duplicates_and_missing_roadmap_fail(self):
        self.close()
        original = (self.root / 'CHANGELOG.md').read_text()
        for content in [original.replace('0.1.0', '0.2.0'), original + '\n## 0.1.0\nDuplicate\n', original.replace('RM-2026-001', 'RM-2026-999')]:
            self.write('CHANGELOG.md', content)
            with self.assertRaises(ValueError):
                self.validate()

    def test_lightweight_and_wrong_commit_tags_are_rejected(self):
        self.close()
        self.git('tag', 'v0.1.0')
        with self.assertRaisesRegex(ValueError, 'annotated'):
            self.validate()
        self.git('tag', '-d', 'v0.1.0')
        self.git('tag', '-a', 'v0.1.0', '-m', 'fixture stale tag')
        self.git('add', '.')
        self.git('commit', '-m', 'fixture closure')
        with self.assertRaisesRegex(ValueError, 'identify HEAD'):
            self.validate()


if __name__ == '__main__':
    unittest.main()
