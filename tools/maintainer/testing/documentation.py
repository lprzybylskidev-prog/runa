#!/usr/bin/env python3
"""Actual customer source/knowledge/documentation boundaries using disposable Git fixtures."""
import contextlib
import hashlib
import io
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
import corpus
import docs_checks
from configuration import load, golden
from documentation import maintainer_checkout, roadmap_root
from hooks import commit_message


class DocumentationOwnership(unittest.TestCase):
    def setUp(self):
        self.folder = tempfile.TemporaryDirectory(prefix='runa-doc-ownership-')
        self.addCleanup(self.folder.cleanup)
        self.root = Path(self.folder.name)
        subprocess.run(['git', 'init', '-q', '-b', 'documentation-test', str(self.root)], check=True)
        for name in ('AGENTS.md', 'README.md', 'docs/README.md'):
            self.copy(name)
        for name in ('docs/app', 'docs/runa/handbook', '.agents', 'tools/application/knowledge'):
            shutil.copytree(ROOT / name, self.root / name, ignore=shutil.ignore_patterns('__pycache__'))
        self.copy('tools/application/quality.yml')
        self.receipt = self.root / '.git/runa-bootstrap.json'
        self.receipt.write_text('{}\n')
        self.track()

    def copy(self, name):
        target = self.root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / name, target)

    def write(self, name, text):
        target = self.root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(text)
        return target

    def track(self):
        subprocess.run(['git', 'add', '.'], cwd=self.root, check=True)

    def validate(self):
        with contextlib.redirect_stdout(io.StringIO()):
            docs_checks.validate(self.root)

    def copy_owner_inputs(self):
        for name in ('docs/runa/maintainer/agent-contract.md', 'tools/maintainer/knowledge.json', 'tools/maintainer/knowledge-golden.json'):
            self.copy(name)
        self.write('docs/runa/maintainer/internal.md', '# Private owner roadmap\nInternal sentinel, never customer knowledge.\n')
        self.track()

    def test_actual_public_projection_has_complete_docs_skills_empty_roadmaps_and_no_owner_history(self):
        self.validate()
        self.assertFalse((self.root / 'docs/runa/maintainer').exists())
        self.assertNotIn('runa:current-state', (self.root / 'README.md').read_text())
        for state in ('draft', 'active', 'paused', 'closed'):
            self.assertEqual(list((self.root / 'docs/app/roadmaps' / state).glob('*.md')), [])
        self.assertEqual(len(list((self.root / '.agents/skills').glob('*/SKILL.md'))), 9)
        paths = {p.relative_to(self.root).as_posix() for p in corpus.sources(self.root, load(self.root))}
        self.assertIn('docs/app/README.md', paths)
        self.assertIn('docs/runa/handbook/development/editor-and-agents.md', paths)
        self.assertIn('docs/runa/handbook/security/baseline.md', paths)
        self.assertFalse(any('/maintainer/' in p for p in paths))
        self.assertTrue(all('/maintainer/' not in q['source_path'] for q in golden(self.root)))

    def test_copied_owner_sources_and_broad_allowlist_cannot_cross_customer_receipt(self):
        self.copy_owner_inputs()
        self.assertFalse(maintainer_checkout(self.root))
        config = load(self.root)
        self.assertNotIn('docs/runa/maintainer', config['markdown_roots'])
        config['markdown_roots'] = ['docs']
        config['files'] += ['docs/runa/maintainer/internal.md']
        paths = {p.relative_to(self.root).as_posix() for p in corpus.sources(self.root, config)}
        self.assertFalse(any('/maintainer/' in p for p in paths))
        self.receipt.unlink()
        self.assertTrue(maintainer_checkout(self.root))
        self.assertIn('docs/runa/maintainer', load(self.root)['markdown_roots'])
        self.assertTrue(any('/maintainer/' in q['source_path'] for q in golden(self.root)))

    def test_public_link_cannot_require_owner_source_even_when_it_exists(self):
        self.copy_owner_inputs()
        self.receipt.unlink()
        self.write('docs/app/notes.md', '[Internal](../runa/maintainer/internal.md)\n')
        with self.assertRaisesRegex(ValueError, 'owner-only'):
            self.validate()

    def test_legacy_roots_and_materialized_owner_docs_are_rejected(self):
        legacy = self.root / 'docs/development'
        legacy.mkdir()
        with self.assertRaisesRegex(ValueError, 'placement'):
            self.validate()
        legacy.rmdir()
        self.copy_owner_inputs()
        with self.assertRaisesRegex(ValueError, 'owner-only'):
            self.validate()

    def test_missing_directories_duplicate_root_and_symlink_sources_fail(self):
        (self.root / 'docs/app/roadmaps/active/.gitkeep').unlink()
        (self.root / 'docs/app/roadmaps/active').rmdir()
        with self.assertRaisesRegex(ValueError, 'roadmap directory'):
            self.validate()
        (self.root / 'docs/app/roadmaps/active').mkdir()
        duplicate = self.root / 'src/app/docs'
        duplicate.mkdir(parents=True)
        with self.assertRaisesRegex(ValueError, 'docs/app'):
            self.validate()
        duplicate.rmdir()
        (self.root / 'docs/app/linked.md').symlink_to(ROOT / 'AGENTS.md')
        with self.assertRaisesRegex(ValueError, 'symlink'):
            self.validate()

    def test_module_local_documentation_is_rejected(self):
        for owner in ('app', 'runa'):
            local = self.root / ('src/' + owner + '/modules/example/docs')
            local.mkdir(parents=True)
            with self.assertRaisesRegex(ValueError, 'central docs'):
                self.validate()
            local.rmdir()

    def test_owner_and_application_roadmap_ids_are_distinct_and_lifecycle_identity_is_stable(self):
        text = '---\nid: RM-2026-099\nstatus: active\n---\n# Plan\n'
        app = self.write('docs/app/roadmaps/active/RM-2026-099-test.md', text)
        owner = self.write('docs/runa/maintainer/roadmaps/active/RM-2026-099-test.md', text)
        a = corpus.metadata(app, self.root, text)
        b = corpus.metadata(owner, self.root, text)
        self.assertNotEqual(a['document_id'], b['document_id'])
        self.assertEqual(b['document_id'], corpus.identity('roadmap:RM-2026-099'))
        closed_text = text.replace('status: active', 'status: closed')
        closed = self.write('docs/app/roadmaps/closed/RM-2026-099-test.md', closed_text)
        self.assertEqual(a['document_id'], corpus.metadata(closed, self.root, closed_text)['document_id'])

    def test_commit_scope_uses_application_roadmap_despite_copied_owner_history(self):
        self.copy_owner_inputs()
        self.write('docs/app/roadmaps/active/RM-2026-099-test.md', '---\nid: RM-2026-099\nstatus: active\n---\n')
        self.write('docs/runa/maintainer/roadmaps/active/RM-2026-098-test.md', '---\nid: RM-2026-098\nstatus: active\n---\n')
        message = self.write('message.txt', 'docs(RM-2026-099): application notes\n')
        commit_message(self.root, message)
        self.assertEqual(roadmap_root(self.root), self.root / 'docs/app/roadmaps')
        self.receipt.unlink()
        with self.assertRaisesRegex(ValueError, 'scope'):
            commit_message(self.root, message)

    def test_shared_operational_scan_policy_preserves_approved_scope_and_binds_public_evidence(self):
        archived = json.loads((ROOT / 'docs/runa/maintainer/security/postgres-gosu-not-affected.json').read_text())
        shared = json.loads((ROOT / 'tools/application/security/postgres-gosu-not-affected.json').read_text())
        self.assertEqual({k: v for k, v in archived.items() if k not in ('evidence', 'evidence_sha256')},
                         {k: v for k, v in shared.items() if k not in ('evidence', 'evidence_sha256')})
        self.assertTrue(shared['evidence'].startswith('docs/runa/handbook/security/'))
        self.assertEqual(hashlib.sha256((ROOT / shared['evidence']).read_bytes()).hexdigest(), shared['evidence_sha256'])

    def test_relocated_closed_roadmaps_and_dated_reports_remain_byte_identical(self):
        mapping = json.loads((ROOT / 'docs/runa/maintainer/documentation-map.json').read_text())
        for name, expected in mapping['immutable_sha256'].items():
            with self.subTest(source=name):
                self.assertEqual(hashlib.sha256((ROOT / name).read_bytes()).hexdigest(), expected)
        for name in mapping['paths'].values():
            path = ROOT / name
            if '/roadmaps/' in name and path.name.startswith('RM-'):
                self.assertEqual(len(list((ROOT / 'docs/runa/maintainer/roadmaps').glob('*/' + path.name))), 1, name)
            else:
                self.assertTrue(path.is_file(), name)


if __name__ == '__main__':
    unittest.main()
