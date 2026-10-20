#!/usr/bin/env python3
"""Real-service retrieval acceptance, isolated from the developer collection."""
import contextlib
import copy
import io
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
import uuid

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
from ingest import index, rebuild, status
from retrieval import Retriever, doctor
from store import Store

CONFIG = json.loads((ROOT / 'tools/application/knowledge/config.json').read_text())


class RetrievalContracts(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.config = {**CONFIG, 'collection': 'runa_retrieval_test_' + uuid.uuid4().hex}
        self.store = Store(self.config)
        self.addCleanup(self.cleanup_collection)
        self.git('init', '-q', '-b', 'retrieval-test')
        for name, text in {
            'AGENTS.md': '# Rules\n\nPostgreSQL uses schemas and bigint identity keys.\n',
            'docs/ai/knowledge-system.md': '# Knowledge\n## Production prohibition\nQdrant and embeddings are development-only. Never deploy knowledge services.\n',
            'docs/roadmaps/closed/RM-2026-099-example.md': '---\nid: RM-2026-099\nstatus: closed\n---\n# History\n## Specification\nLegacy frobnicator_739 configuration.\n',
        }.items():
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(text)
        self.git('add', '.')
        self.git('-c', 'user.name=Test', '-c', 'user.email=test@example.invalid', 'commit', '-qm', 'fixture')
        self.index()

    def git(self, *args):
        subprocess.run(['git', *args], cwd=self.root, check=True, capture_output=True)

    def index(self):
        with contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(index(self.root, self.config, 'cpu'), 0)

    def cleanup_collection(self):
        self.store.http.request('DELETE', self.store.path)

    def test_hybrid_filter_exact_identifier_and_source_diversity(self):
        r = Retriever(self.root, self.config)
        hits = r.search('frobnicator_739')
        self.assertEqual(hits[0]['payload']['status'], 'closed')
        self.assertIn('lexical', hits[0]['signals'])
        self.assertIn('dense', hits[0]['signals'])
        self.assertEqual(len({h['payload']['source_path'] for h in hits}), len(hits))
        hits = r.search('knowledge production', filters={'document_type': 'roadmap', 'roadmap_id': 'RM-2026-099', 'status': 'closed'})
        self.assertEqual(len(hits), 1)
        self.assertEqual(hits[0]['payload']['roadmap_id'], 'RM-2026-099')
        self.assertEqual(r.search('knowledge', filters={'scope': 'absent'}), [])
        self.assertTrue(doctor(r)['passed'])
        with self.assertRaises(ValueError):
            r.search('')
        with self.assertRaises(ValueError):
            r.search('test', 0)
        with self.assertRaises(ValueError):
            r.search('test', filters={'unknown': 'value'})

    def test_stale_branch_missing_orphan_and_mixed_model_are_rejected(self):
        original = self.store.manifest()
        manifest = copy.deepcopy(original)
        manifest['pending'] = True
        self.store.write_manifest(manifest)
        with self.assertRaisesRegex(ValueError, 'stale'):
            Retriever(self.root, self.config)
        self.index()
        points = self.store.all_points()
        key = next(iter(points))
        self.store.payload(key, {**points[key], 'embedding_revision': 'wrong-model'})
        self.assertFalse(doctor(Retriever(self.root, self.config))['passed'])
        self.index()
        self.store.delete([key])
        self.assertEqual(status(self.root, self.config)['missing_chunks'], 1)
        with self.assertRaises(ValueError):
            Retriever(self.root, self.config)
        self.index()
        self.git('checkout', '-qb', 'new-branch')
        with self.assertRaises(ValueError):
            Retriever(self.root, self.config)
        self.index()
        self.assertTrue(doctor(Retriever(self.root, self.config))['passed'])
        path = self.root / 'AGENTS.md'
        path.write_text('# Changed\n\nChanged policy.\n')
        with self.assertRaises(ValueError):
            Retriever(self.root, self.config)

    def test_explicit_rebuild_restores_deleted_collection(self):
        before = set(self.store.all_points())
        self.store.http.request('DELETE', self.store.path)
        self.assertEqual(status(self.root, self.config)['state'], 'missing')
        with contextlib.redirect_stdout(io.StringIO()):
            self.assertEqual(rebuild(self.root, self.config), 0)
        self.assertEqual(set(self.store.all_points()), before)
        self.assertTrue(doctor(Retriever(self.root, self.config))['passed'])


if __name__ == '__main__':
    unittest.main()
