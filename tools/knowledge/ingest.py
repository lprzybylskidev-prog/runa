"""Incremental ingestion with prepare-before-write and recoverable remote state."""
from datetime import datetime, timezone
import json
from pathlib import Path
import subprocess
from corpus import chunks, digest, metadata, read_source, sources
from provider import Embeddings
from store import Store


def space(config):
    keys = ('schema_version', 'model_id', 'model_revision', 'runtime_version', 'dimension', 'max_input_tokens', 'chunk_tokens', 'query_prefix', 'pooling', 'dtype', 'normalize')
    return {key: config[key] for key in keys}


def parser_hash():
    return digest(Path(__file__).with_name('corpus.py').read_text())


def source_state(root, config):
    return {p.relative_to(root).as_posix(): (p, read_source(p)) for p in sources(root, config)}


def git_value(root, *args):
    return subprocess.run(['git', *args], cwd=root, check=True, capture_output=True, text=True).stdout.strip()


def status(root, config, store=None):
    store = store or Store(config)
    if not store.exists():
        return {'state': 'missing', 'stale': True, 'documents': 0, 'chunks': 0}
    manifest = store.manifest()
    if not manifest or manifest.get('space') != space(config):
        return {'state': 'incompatible', 'stale': True}
    current = source_state(root, config)
    indexed = manifest.get('sources', {})
    changed = sorted(name for name, (_, text) in current.items() if indexed.get(name, {}).get('source_hash') != digest(text))
    deleted = sorted(set(indexed) - set(current))
    points = store.all_points()
    expected = {chunk for document in indexed.values() for chunk in document['chunks']}
    missing = sorted(expected - set(points))
    orphans = sorted(set(points) - expected)
    branch = git_value(root, 'branch', '--show-current')
    branch_changed = manifest.get('git_branch') != branch
    parser_changed = manifest.get('parser_hash') != parser_hash()
    stale = bool(parser_changed or changed or deleted or missing or orphans or manifest.get('pending') or branch_changed)
    return {'state': 'stale' if stale else 'current', 'stale': stale,
            'documents': len(indexed), 'chunks': len(points), 'changed': changed, 'deleted': deleted,
            'missing_chunks': len(missing), 'orphan_chunks': len(orphans),
            'interrupted': bool(manifest.get('pending')), 'branch_changed': branch_changed,
            'parser_changed': parser_changed}


def index(root, config, device='auto'):
    provider = Embeddings(config, device)
    # Prove dimensions before creating/writing a collection.
    provider.embed(['Runa development knowledge indexing capability check.'])
    store = Store(config)
    if not store.exists():
        store.create()
    previous = store.manifest()
    if previous and previous.get('space') != space(config):
        raise ValueError('Knowledge model/schema mismatch; use an explicit rebuild after reviewing the change.')
    if not previous and store.all_points():
        raise ValueError('Unidentified non-empty knowledge collection; refusing to adopt it.')
    previous = previous or {'space': space(config), 'sources': {}}
    current = store.all_points()
    branch = git_value(root, 'branch', '--show-current')
    commit = git_value(root, 'rev-parse', 'HEAD')
    timestamp = datetime.now(timezone.utc).isoformat()
    documents = source_state(root, config)
    prepared = []
    state = {}
    new_vectors = 0
    for name, (path, text) in documents.items():
        old = previous['sources'].get(name, {})
        if previous.get('parser_hash') == parser_hash() and old.get('source_hash') == digest(text) and all(i in current for i in old['chunks']):
            meta = metadata(path, root, text)
            rows = [{'id': i, 'payload': dict(current[i])} for i in old['chunks']]
        else:
            meta, rows = chunks(path, root, text, provider, config)
        changed = [row for row in rows if row['id'] not in current or current[row['id']].get('content_hash') != row['payload']['content_hash']]
        vectors = provider.embed([r['payload']['content'] for r in changed]) if changed else []
        for row, vector in zip(changed, vectors, strict=True):
            row['vector'] = vector
        new_vectors += len(changed)
        for row in rows:
            # Refresh Git/source metadata without re-embedding unchanged semantic chunks.
            row['payload'].update(meta, git_branch=branch, git_commit=commit,
                                  embedding_provider='tei', embedding_model=config['model_id'],
                                  embedding_revision=config['model_revision'], embedding_dimension=config['dimension'])
            if 'vector' in row:
                row['payload']['indexed_at'] = timestamp
        prepared.extend(rows)
        state[name] = {'source_hash': meta['source_hash'], 'document_id': meta['document_id'], 'chunks': [r['id'] for r in rows]}
    # Reject sources modified while preparing vectors; no content writes have happened yet.
    if {n: digest(t) for n, (_, t) in source_state(root, config).items()} != {n: s['source_hash'] for n, s in state.items()}:
        raise ValueError('Knowledge sources changed during preparation; rerun index.')
    if git_value(root, 'branch', '--show-current') != branch or git_value(root, 'rev-parse', 'HEAD') != commit:
        raise ValueError('Git context changed during preparation; rerun index.')
    # Remote pending survives loss of local state. An interrupted write never appears current.
    store.write_manifest({**previous, 'pending': True})
    for start in range(0, len(prepared), 64):
        batch = [row for row in prepared[start:start + 64] if 'vector' in row]
        if batch:
            store.upsert(batch)
    for row in prepared:
        if 'vector' not in row and current[row['id']] != row['payload']:
            store.payload(row['id'], row['payload'])
    expected = {row['id'] for row in prepared}
    removed = sorted(set(current) - expected)
    store.delete(removed)
    store.write_manifest({'space': space(config), 'sources': state, 'pending': False, 'parser_hash': parser_hash(),
                          'git_branch': branch, 'git_commit': commit, 'indexed_at': timestamp})
    result = status(root, config, store)
    result.update(embedded_chunks=new_vectors, removed_chunks=len(removed))
    print(json.dumps(result, indent=2))
    return 1 if result['stale'] else 0
