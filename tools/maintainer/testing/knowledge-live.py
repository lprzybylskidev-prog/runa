#!/usr/bin/env python3
"""Exercise real TEI/Qdrant contracts using a disposable collection and checkout."""
import contextlib
import io
import json
import math
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import uuid

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
from corpus import identity
from ingest import index, status
from provider import Embeddings
from store import Store


def main():
    if os.environ.get('APP_ENV') == 'production':
        raise ValueError('Knowledge acceptance is development-only.')
    config = json.loads((ROOT / 'tools/application/knowledge/config.json').read_text())
    cpu = Embeddings(config, 'cpu')
    gpu = Embeddings(config)
    if gpu.client is not gpu.gpu:
        raise ValueError('CUDA acceptance requires the GPU service; fallback is tested separately.')
    texts = ['PostgreSQL uses explicit schemas.', 'Knowledge tooling must remain development-only.']
    a, b = gpu.embed(texts), cpu.embed(texts)
    similarities = [sum(x*y for x, y in zip(u, v)) / math.sqrt(sum(x*x for x in u) * sum(y*y for y in v)) for u, v in zip(a, b)]
    assert min(similarities) > .999, similarities
    fallback_config = {**config, 'cuda_url': 'http://127.0.0.1:1'}
    with contextlib.redirect_stderr(io.StringIO()) as diagnostic:
        fallback = Embeddings(fallback_config)
        assert len(fallback.embed([texts[0]])[0]) == config['dimension']
    assert fallback.client is fallback.cpu and 'CPU fallback' in diagnostic.getvalue()
    try:
        cpu.embed(['token ' * 600])
    except ValueError as error:
        assert 'token budget' in str(error)
    else:
        raise AssertionError('Over-budget input was accepted.')
    config = {**config, 'collection': 'runa_knowledge_acceptance_' + uuid.uuid4().hex}
    store = Store(config)
    with tempfile.TemporaryDirectory(prefix='runa-knowledge-') as directory:
        root = Path(directory)
        def git(*args):
            subprocess.run(['git', *args], cwd=root, check=True, capture_output=True)
        def run_index(device='auto'):
            with contextlib.redirect_stdout(io.StringIO()) as output:
                assert index(root, config, device) == 0
            return json.loads(output.getvalue())
        git('init', '-q', '-b', 'knowledge-acceptance')
        (root / 'AGENTS.md').write_text('# Rules\n\nUse explicit PostgreSQL schemas.\n')
        git('add', 'AGENTS.md')
        git('-c', 'user.name=Knowledge test', '-c', 'user.email=test@example.invalid', 'commit', '-qm', 'fixture')
        try:
            assert run_index()['embedded_chunks'] == 1
            assert run_index()['embedded_chunks'] == 0
            assert run_index('cpu')['embedded_chunks'] == 0
            (root / 'docs').mkdir()
            document = root / 'docs/architecture.md'
            document.write_text('# Runtime\n\nNever deploy knowledge services into production.\n')
            git('add', 'docs/architecture.md')
            assert status(root, config)['stale']
            assert run_index()['embedded_chunks'] == 1
            document.write_text('# Runtime\n\nKeep Qdrant and embedding services development-only.\n')
            changed = run_index()
            assert changed['embedded_chunks'] == 1 and changed['removed_chunks'] == 1
            store.upsert([{'id': identity('acceptance-orphan'), 'vector': a[0], 'payload': {'kind': 'chunk'}}])
            assert status(root, config)['orphan_chunks'] == 1
            assert run_index()['removed_chunks'] == 1
            manifest = store.manifest()
            store.write_manifest({**manifest, 'pending': True})
            assert status(root, config)['interrupted']
            assert run_index()['embedded_chunks'] == 0
            document.unlink()
            assert run_index()['removed_chunks'] == 1
            git('checkout', '-qb', 'changed-branch')
            assert status(root, config)['branch_changed']
            assert run_index()['embedded_chunks'] == 0
            assert not status(root, config)['stale']
        finally:
            # Only this random test collection is removed; never the developer corpus.
            if store.exists():
                store.http.request('DELETE', store.path)
    print(json.dumps({'result': 'PASS', 'cpu_cuda_cosine': similarities,
                      'checks': ['dimension', 'token_budget', 'cpu_fallback', 'initial_index', 'no_op',
                                 'add_edit_delete', 'orphan_cleanup', 'interrupted_recovery', 'branch_refresh'],
                      'disposable_collection_removed': True}, indent=2))


if __name__ == '__main__':
    main()
