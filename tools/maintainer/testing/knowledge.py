#!/usr/bin/env python3
"""Offline knowledge contracts; fake storage/provider never touch developer vectors."""
import contextlib
import copy
import importlib.machinery
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
import corpus
import ingest
from provider import Embeddings
from transport import RequestError

from configuration import load
CONFIG = load(ROOT)


class Provider:
    calls = []

    def __init__(self, config, device='auto'):
        pass

    def counts(self, texts):
        return [len(t.split()) + 2 for t in texts]

    def embed(self, texts):
        self.calls.extend(texts)
        return [[1.] + [0.] * 383 for t in texts]


class Store:
    points = {}
    present = False
    fail_upsert = False

    def __init__(self, config):
        pass

    def exists(self):
        return self.present

    def create(self):
        type(self).present = True

    def manifest(self):
        return copy.deepcopy(self.points.get('manifest'))

    def write_manifest(self, payload):
        self.points['manifest'] = copy.deepcopy(payload)

    def all_points(self):
        return copy.deepcopy({k: v for k, v in self.points.items() if k != 'manifest'})

    def upsert(self, points):
        if self.fail_upsert:
            raise RuntimeError('Simulated interrupted storage write')
        for p in points:
            self.points[p['id']] = copy.deepcopy(p['payload'])

    def payload(self, key, payload):
        self.points[key] = copy.deepcopy(payload)

    def delete(self, ids):
        for key in ids:
            self.points.pop(key)


class KnowledgeContracts(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        subprocess.run(['git', 'init', '-q', '-b', 'test-branch'], cwd=self.root, check=True)
        self.write('AGENTS.md', '# Policy\n\nKeep secrets out of the index.\n')
        self.track()
        subprocess.run(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.invalid', 'commit', '-qm', 'fixture'], cwd=self.root, check=True)
        Store.points, Store.present, Store.fail_upsert, Provider.calls = {}, False, False, []
        owner = patch.object(corpus, 'maintainer_checkout', return_value=True)
        owner.start()
        self.addCleanup(owner.stop)
        self.patch_store = patch.object(ingest, 'Store', Store)
        self.patch_provider = patch.object(ingest, 'Embeddings', Provider)
        self.patch_store.start()
        self.patch_provider.start()
        self.addCleanup(self.patch_store.stop)
        self.addCleanup(self.patch_provider.stop)

    def write(self, name, text):
        p = self.root / name
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(text)
        return p

    def track(self):
        subprocess.run(['git', 'add', '.'], cwd=self.root, check=True)

    def index(self):
        with contextlib.redirect_stdout(io.StringIO()):
            return ingest.index(self.root, CONFIG, 'cpu')

    def test_allowlist_excludes_untracked_secrets_and_application_code(self):
        for name in ['docs/untracked.md', '.env', 'docs/.env.local', 'app/Foo.php', 'docs/raw-chat.txt', 'tools/scratch/readme.md']:
            self.write(name, 'private fixture')
        subprocess.run(['git', 'add', '.env', 'docs/.env.local', 'app', 'docs/raw-chat.txt', 'tools'], cwd=self.root, check=True)
        self.assertEqual([p.name for p in corpus.sources(self.root, CONFIG)], ['AGENTS.md'])

    def test_symlink_cannot_import_outside_content(self):
        (self.root / 'docs/app').mkdir(parents=True)
        (self.root / 'docs/app/link.md').symlink_to('/etc/passwd')
        self.track()
        with self.assertRaisesRegex(ValueError, 'Symlink'):
            corpus.sources(self.root, CONFIG)

    def test_structural_budget_and_closed_roadmap_filter(self):
        text = '---\nid: RM-2026-099\nstatus: closed\n---\n# Roadmap\n## Overview\n' + ('Knowledge text. ' * 700) + '\n## Implementation plan\nDo not index this checklist.\n'
        p = self.write('docs/runa/maintainer/roadmaps/closed/RM-2026-099.md', text)
        meta, rows = corpus.chunks(p, self.root, text, Provider(CONFIG), CONFIG)
        self.assertEqual(meta['roadmap_id'], 'RM-2026-099')
        self.assertGreater(len(rows), 1)
        self.assertTrue(all(len(r['payload']['content'].split()) + 2 <= 420 for r in rows))
        self.assertFalse(any('Do not index' in r['payload']['content'] for r in rows))
        self.assertEqual([r['id'] for r in rows], [r['id'] for r in corpus.chunks(p, self.root, text, Provider(CONFIG), CONFIG)[1]])

    def test_heading_level_gaps_and_fences_preserve_hierarchy(self):
        text = '# Root\n#### First\nOne.\n#### Second\n````md\n# Literal heading\n```\n````\nTwo.\n## Next\nThree.\n'
        parsed = list(corpus.sections(text))
        self.assertEqual([headings for headings, _ in parsed],
                         [['Root', 'First'], ['Root', 'Second'], ['Root', 'Next']])
        self.assertIn('# Literal heading', parsed[1][1])

    def test_roadmap_examples_and_templates_are_documentation(self):
        text = '# Workflow\n```yaml\nid: RM-2026-003\nstatus: active\n```\n'
        for name in ('docs/runa/maintainer/workflows/roadmaps.md', 'docs/runa/handbook/workflows/roadmap-template.md'):
            with self.subTest(source=name):
                path = self.write(name, text)
                meta = corpus.metadata(path, self.root, text)
                self.assertEqual(meta['document_type'], 'documentation')
                self.assertIsNone(meta['roadmap_id'])
                self.assertIsNone(meta['status'])
                self.assertEqual(meta['document_id'], corpus.identity('source:' + name))

    def test_real_roadmap_identity_survives_closure_and_ignores_body_examples(self):
        text = '---\nid: RM-2026-099\nstatus: active\n---\n# Plan\n```yaml\nid: RM-2026-003\nstatus: closed\n```\n'
        active = self.write('docs/runa/maintainer/roadmaps/active/RM-2026-099-test.md', text)
        before = corpus.metadata(active, self.root, text)
        closed_text = text.replace('status: active', 'status: closed', 1)
        closed = self.write('docs/runa/maintainer/roadmaps/closed/RM-2026-099-test.md', closed_text)
        after = corpus.metadata(closed, self.root, closed_text)
        self.assertEqual(before['document_id'], after['document_id'])
        self.assertEqual(before['roadmap_id'], 'RM-2026-099')
        self.assertEqual(before['status'], 'active')
        self.assertEqual(after['status'], 'closed')
        for invalid in [text.replace('status: active', 'status: closed', 1),
                        text.replace('id: RM-2026-099', 'id: RM-2026-098', 1),
                        text.replace('id: RM-2026-099', 'id: RM-2026-099\nid: RM-2026-099', 1),
                        '# No actual frontmatter\n' + text]:
            with self.assertRaises(ValueError):
                corpus.metadata(active, self.root, invalid)

    def test_parser_refresh_removes_false_roadmap_filter_without_emptying_docs(self):
        name = 'docs/runa/maintainer/workflows/roadmaps.md'
        text = '# Workflow\n```yaml\nid: RM-2026-003\nstatus: draft\n```\n'
        self.write(name, text)
        self.track()
        original = corpus.metadata
        def legacy_metadata(path, root, text):
            meta = original(path, root, text)
            if meta['source_path'] == name:
                meta.update(document_id=corpus.identity('roadmap:RM-2026-003'),
                            document_type='roadmap', roadmap_id='RM-2026-003', status='draft')
            return meta
        with patch.object(corpus, 'metadata', legacy_metadata), patch.object(ingest, 'metadata', legacy_metadata), patch.object(ingest, 'parser_hash', return_value='legacy-parser'):
            self.index()
        self.assertTrue(any(p.get('roadmap_id') == 'RM-2026-003' for p in Store(CONFIG).all_points().values()))
        self.assertTrue(ingest.status(self.root, CONFIG)['parser_changed'])
        self.index()
        points = Store(CONFIG).all_points()
        self.assertTrue(any(p['source_path'] == name for p in points.values()))
        self.assertFalse(any(p.get('roadmap_id') == 'RM-2026-003' for p in points.values()))

    def test_idempotent_then_edit_delete_and_orphan_cleanup(self):
        self.write('docs/runa/handbook/architecture/example.md', '# Rule\n\nOriginal technical contract.\n')
        self.track()
        self.assertEqual(self.index(), 0)
        before = set(Store.points)
        Provider.calls.clear()
        self.assertEqual(self.index(), 0)
        self.assertEqual(len(Provider.calls), 1)  # Only the dimension capability probe.
        self.assertEqual(set(Store.points), before)
        self.write('docs/runa/handbook/architecture/example.md', '# Rule\n\nRevised technical contract.\n')
        self.assertTrue(ingest.status(self.root, CONFIG)['stale'])
        self.assertEqual(self.index(), 0)
        self.assertNotEqual(set(Store.points), before)
        (self.root / 'docs/runa/handbook/architecture/example.md').unlink()
        Store.points['orphan'] = {'kind': 'chunk', 'content': 'obsolete'}
        self.assertEqual(self.index(), 0)
        self.assertNotIn('orphan', Store.points)
        self.assertEqual(ingest.status(self.root, CONFIG)['documents'], 1)

    def test_failed_prepare_preserves_prior_document(self):
        self.index()
        old = copy.deepcopy(Store.points)
        self.write('AGENTS.md', '# Policy\n\n```\nUnclosed block')
        with self.assertRaisesRegex(ValueError, 'Unclosed'):
            self.index()
        self.assertEqual(Store.points, old)

    def test_interrupted_write_is_stale_and_recovers(self):
        self.index()
        self.write('AGENTS.md', '# Policy\n\nRevised policy.\n')
        Store.fail_upsert = True
        with self.assertRaisesRegex(RuntimeError, 'interrupted'):
            self.index()
        self.assertTrue(ingest.status(self.root, CONFIG)['interrupted'])
        Store.fail_upsert = False
        self.assertEqual(self.index(), 0)
        self.assertFalse(ingest.status(self.root, CONFIG)['stale'])

    def test_branch_change_refreshes_metadata_without_reembedding(self):
        self.index()
        subprocess.run(['git', 'checkout', '-qb', 'other-branch'], cwd=self.root, check=True)
        self.assertTrue(ingest.status(self.root, CONFIG)['branch_changed'])
        Provider.calls.clear()
        self.index()
        self.assertEqual(len(Provider.calls), 1)
        self.assertTrue(all(p['git_branch'] == 'other-branch' for p in Store(CONFIG).all_points().values()))

    def test_parser_change_reparses_without_reembedding_unchanged_chunks(self):
        self.index()
        Provider.calls.clear()
        with patch.object(ingest, 'parser_hash', return_value='revised-parser'):
            self.assertTrue(ingest.status(self.root, CONFIG)['parser_changed'])
            self.assertEqual(self.index(), 0)
            self.assertFalse(ingest.status(self.root, CONFIG)['stale'])
        self.assertEqual(len(Provider.calls), 1)

    def test_model_mismatch_refuses_collection_writes(self):
        self.index()
        Store.points['manifest']['space']['model_revision'] = 'different'
        old = copy.deepcopy(Store.points)
        with self.assertRaisesRegex(ValueError, 'mismatch'):
            self.index()
        self.assertEqual(Store.points, old)

    def test_gpu_transient_failure_falls_back_but_permanent_error_does_not(self):
        provider = Embeddings.__new__(Embeddings)
        provider.gpu, provider.cpu = object(), object()
        class Client:
            def request(self, *args):
                raise RequestError('GPU failure', 500)
        provider.client = Client()
        class Cpu:
            def request(self, *args):
                return [[1.]]
        provider.cpu = Cpu()
        provider.validate = lambda client: None
        with contextlib.redirect_stderr(io.StringIO()) as output:
            self.assertEqual(provider.request('/embed', {}), [[1.]])
        self.assertIn('CPU fallback', output.getvalue())
        class Permanent:
            def request(self, *args):
                raise RequestError('Bad input', 422)
        provider.client = Permanent()
        with self.assertRaises(RequestError):
            provider.request('/embed', {})


if __name__ == '__main__':
    unittest.main()
