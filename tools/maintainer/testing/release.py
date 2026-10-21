#!/usr/bin/env python3
"""Isolated release metadata acceptance/rejection contracts."""
import contextlib
import io
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from documentation_fixture import copy_public

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'application'))
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import release_checks as release


class ReleaseContracts(unittest.TestCase):
    def setUp(self):
        self.folder = tempfile.TemporaryDirectory()
        self.addCleanup(self.folder.cleanup)
        self.root = Path(self.folder.name)
        copy_public(self.root)
        self.write('VERSION', '0.0.0\n')
        self.write('docs/runa/maintainer/CHANGELOG.md', '# Changelog\n\n## Unreleased\n')
        self.write('docs/runa/handbook/tooling/commands.md', '# Fixture commands\n')
        (self.root / 'bin').mkdir()
        self.readme('0.0.0', 'none')
        self.git('init', '--initial-branch=main')
        self.git('config', 'user.name', 'Release fixture')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('config', 'core.hooksPath', str(self.root / 'fixture-hooks'))
        self.git('add', '.')
        self.git('commit', '-m', 'fixture baseline')
        self.branch = 'roadmap/RM-2026-001-test'
        self.git('switch', '-c', self.branch)
        self.active = 'docs/runa/maintainer/roadmaps/active/RM-2026-001-test.md'
        self.closed = self.active.replace('/active/', '/closed/')
        self.write(self.active, self.roadmap())
        self.readme('0.0.0', 'RM-2026-001')

    def git(self, *args):
        return subprocess.run(['git', *args], cwd=self.root, check=True, capture_output=True, text=True)

    def write(self, name, text):
        p = self.root / name
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(text)

    def readme(self, version, roadmap):
        self.write('docs/runa/maintainer/README.md', f'# Fixture\n\n<!-- runa:current-state -->\n\n- Current version: `{version}`\n- Active roadmap: `{roadmap}`\n\n<!-- /runa:current-state -->\n')

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
        self.readme('0.1.0', 'none')
        self.write('docs/runa/maintainer/CHANGELOG.md', '# Changelog\n\n## 0.1.0 — 2026-10-18\nFeature: RM-2026-001 foundation.\n')

    def test_application_compatibility_does_not_change_release_arithmetic_or_customer_version(self):
        self.write('src/composer.json', '{}')
        self.write('src/runa/platform.json', '{"schema_version":1,"id":"runa","name":"Runa","version":"0.0.0"}')
        self.write('src/app/application.json', '{"schema_version":1,"id":"customer","name":"Customer","version":"9.4.2","runa":">=0.1.0 <0.2.0"}')
        before = (self.root / 'src/app/application.json').read_bytes()
        release.check_application(self.root, '0.1.0')
        self.assertEqual(release.expected('0.0.0', 'feature'), '0.1.0')
        self.assertEqual((self.root / 'src/app/application.json').read_bytes(), before)
        with self.assertRaisesRegex(ValueError, 'compatible'):
            release.check_application(self.root, '0.2.0')
        self.write('src/runa/platform.json', '{"schema_version":1,"id":"runa","name":"Runa","version":"0.1.0"}')
        with self.assertRaisesRegex(ValueError, 'platform manifest'):
            release.check_application(self.root, '0.1.0')
        (self.root / 'src/app/application.json').unlink()
        with self.assertRaisesRegex(ValueError, 'manifest'):
            release.check_application(self.root, '0.1.0')

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
        original = (self.root / 'docs/runa/maintainer/CHANGELOG.md').read_text()
        for content in [original.replace('0.1.0', '0.2.0'), original + '\n## 0.1.0\nDuplicate\n', original.replace('RM-2026-001', 'RM-2026-999')]:
            self.write('docs/runa/maintainer/CHANGELOG.md', content)
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

    def merge_release(self):
        self.close()
        self.git('add', '.')
        self.git('commit', '-m', 'fixture closure')
        self.git('switch', 'main')
        self.git('merge', '--no-ff', self.branch, '-m', 'fixture release merge')
        self.git('tag', '-a', 'v0.1.0', '-m', 'fixture published release')

    def test_main_allows_only_draft_planning_after_valid_release_tag(self):
        self.merge_release()
        self.assertEqual(self.validate(), '0.1.0')
        self.write('docs/runa/maintainer/roadmaps/draft/RM-2026-002-test.md', '# Draft planning\n')
        self.git('add', '.')
        self.git('commit', '-m', 'fixture planning')
        self.assertEqual(self.validate(), '0.1.0')
        # Even a valid-looking release metadata set cannot excuse runtime drift.
        self.write('app/Unexpected.php', '<?php // fixture runtime change\n')
        self.git('add', '.')
        self.git('commit', '-m', 'fixture unauthorized runtime drift')
        with self.assertRaisesRegex(ValueError, 'draft-planning baseline'):
            self.validate()

    def test_main_rejects_nonancestor_and_metadata_drift_tags(self):
        self.merge_release()
        self.write(self.closed, (self.root / self.closed).read_text() + '\nUnexpected report edit.\n')
        self.git('add', '.')
        self.git('commit', '-m', 'fixture metadata drift')
        with self.assertRaisesRegex(ValueError, 'draft-planning baseline'):
            self.validate()
        # Build a separate descendant, then move the fixture tag there; main is not its descendant.
        self.git('switch', '-c', 'unrelated')
        self.git('commit', '--allow-empty', '-m', 'fixture different history')
        self.git('tag', '-d', 'v0.1.0')
        self.git('tag', '-a', 'v0.1.0', '-m', 'fixture invalid published baseline')
        self.git('switch', 'main')
        with self.assertRaisesRegex(ValueError, 'draft-planning baseline'):
            self.validate()

    def test_readme_is_checked_before_and_after_closure(self):
        self.readme('0.0.0', 'none')
        with self.assertRaisesRegex(ValueError, 'README Active roadmap'):
            self.validate()
        self.close()
        self.readme('0.0.0', 'RM-2026-001')
        with self.assertRaisesRegex(ValueError, 'README Current version'):
            self.validate()


if __name__ == '__main__':
    if '--with-docs-and-knowledge' in sys.argv:
        sys.argv.remove('--with-docs-and-knowledge')
        for filename in ('docs.py', 'knowledge.py'):
            subprocess.run([sys.executable, str(Path(__file__).with_name(filename))], check=True)
    unittest.main()
