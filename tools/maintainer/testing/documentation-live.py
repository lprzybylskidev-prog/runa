#!/usr/bin/env python3
"""Real embeddings/Qdrant ownership transition in a uniquely named disposable collection."""
import contextlib
import io
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import uuid

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application/knowledge'))
from configuration import load
from corpus import sources
from ingest import index, status
from retrieval import Retriever, doctor
from store import Store


def main():
    collection = 'runa_documentation_test_' + uuid.uuid4().hex
    with tempfile.TemporaryDirectory(prefix='runa-documentation-live-') as folder:
        root = Path(folder)
        def write(name, value):
            p = root / name
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text(value)
        def copy(name):
            p = root / name
            p.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / name, p)
        def git(*args):
            subprocess.run(['git', *args], cwd=root, check=True, capture_output=True)
        git('init', '-q', '-b', 'documentation-acceptance')
        for name in ('tools/application/knowledge/config.json', 'tools/maintainer/knowledge.json',
                     'docs/app/README.md', 'docs/runa/handbook/ai/knowledge-system.md'):
            copy(name)
        write('docs/runa/maintainer/agent-contract.md', '# Owner policy\n\nPrivate platform release guidance.\n')
        write('docs/runa/maintainer/private.md', '# Private decision\n\nOwner-only sentinel private_739.\n')
        write('docs/runa/maintainer/customer-agent/AGENTS.md', '# Customer template\n\nTemplate sentinel template_739, never owner operating context.\n')
        git('add', '.')
        git('-c', 'user.name=Documentation Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture baseline')
        def config():
            return {**load(root), 'collection': collection}
        store = Store(config())
        if store.exists():
            raise RuntimeError('Disposable collection unexpectedly exists; refusing to reuse it.')
        try:
            with contextlib.redirect_stdout(io.StringIO()):
                assert index(root, config(), 'cpu') == 0
            assert any('/maintainer/' in p['source_path'] for p in store.all_points().values())
            assert not any('/customer-agent/' in p['source_path'] for p in store.all_points().values())
            (root / '.git/runa-bootstrap.json').write_text('{}\n')
            customer = config()
            assert status(root, customer)['stale']
            with contextlib.redirect_stdout(io.StringIO()):
                assert index(root, customer, 'cpu') == 0
            points = store.all_points()
            assert not any('/maintainer/' in p['source_path'] for p in points.values())
            assert not any('/maintainer/' in name for name in store.manifest()['sources'])
            assert not status(root, customer)['stale']
            retrieval = Retriever(root, customer, 'cpu')
            assert retrieval.search('application documentation roadmap lifecycle', filters={'source_path': 'docs/app/README.md'})
            assert retrieval.search('production knowledge prohibition', filters={'source_path': 'docs/runa/handbook/ai/knowledge-system.md'})
            assert not retrieval.search('private_739', filters={'source_path': 'docs/runa/maintainer/private.md'})
            assert doctor(retrieval)['passed']
            broad = {**customer, 'markdown_roots': ['docs'], 'files': customer['files'] + ['docs/runa/maintainer/private.md']}
            assert not any('/maintainer/' in p.relative_to(root).as_posix() for p in sources(root, broad))
            print('PASS: actual embeddings/index/retrieval/doctor remove owner vectors and retain app/public knowledge in customer receipt scope.')
        finally:
            print('Scoped disposable Qdrant cleanup: ' + collection, flush=True)
            store.http.request('DELETE', store.path)
            assert not store.exists()
    print('Preserved the live project collection, Qdrant volumes/model caches and all application data.')


if __name__ == '__main__':
    main()
