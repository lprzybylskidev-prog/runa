"""Fresh customer Qdrant acceptance inside the existing isolated installation fixture."""
import json
from pathlib import Path


def exercise(root, project, data, env, check, report, call, output, yaml_source):
    compose = ['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env.dev.local'),
               '-f', str(root / 'compose.yaml'), '-f', str(root / '.devcontainer/compose.resources.yaml')]
    def command(*args):
        return call([*compose, *args], cwd=root, env=env)
    def app(*args):
        return output(['docker', 'exec', '-w', '/workspace', project + '-app-1', *args])
    # Reuse model files only; never reuse owner Qdrant storage or collection.
    owner = json.loads(output(['docker', 'inspect', 'runa-dev-embeddings-1']))[0]
    caches = [m['Name'] for m in owner['Mounts'] if m['Type'] == 'volume' and m['Destination'] == '/data']
    check(len(caches) == 1, 'one inspected model cache is available without copying owner knowledge')
    source = yaml_source('compose.yaml')
    for key in ('qdrant', 'embeddings', 'knowledge-init'):
        data['services'][key] = source['services'][key]
    data['volumes']['qdrant-data'] = {}
    data['volumes']['embedding-cache'] = {'external': True, 'name': caches[0]}
    (root / 'compose.yaml').write_text(json.dumps(data, indent=2) + '\n')
    command('up', '-d', '--no-build', '--wait', '--wait-timeout', '240', 'qdrant', 'embeddings')
    actual = json.loads(output(['docker', 'inspect', project + '-qdrant-1']))[0]
    volumes = [m['Name'] for m in actual['Mounts'] if m['Type'] == 'volume' and m['Destination'] == '/qdrant/storage']
    check(volumes == [project + '_qdrant-data'], 'fresh customer Qdrant uses the actual isolated project volume')
    empty = json.loads(app('python3', '-c', 'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections").read().decode())'))
    check(empty['result']['collections'] == [], 'customer Qdrant contains no collections before first initialization')
    command('up', '-d', '--no-build', '--no-deps', 'knowledge-init')
    command('wait', 'knowledge-init')
    status = json.loads(app('php', 'artisan', 'runa:knowledge:status'))
    check(not status['stale'] and status['documents'] > 0, 'real first-start initialization indexes delivered customer sources')
    app('php', 'artisan', 'runa:knowledge:doctor')
    app('php', 'artisan', 'runa:knowledge:eval')
    probe = '''import json,sys
from pathlib import Path
sys.path.insert(0,"tools/application/knowledge")
from configuration import load
from store import Store
config=load(Path.cwd()); points=Store(config).all_points()
paths={p['payload'].get('source_path') for p in points.values() if p['payload'].get('kind')=='chunk'}
assert 'AGENTS.md' in paths
assert '.agents/skills/frontend/references/application.md' in paths
assert not any('/maintainer/' in p for p in paths)
assert not any('Owner Agent Contract' in p['payload'].get('content','') for p in points.values())
print(json.dumps(sorted(paths)))'''
    paths = json.loads(app('python3', '-c', probe))
    before = app('python3', '-c', 'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections/runa_knowledge").read().decode())')
    app('php', 'artisan', 'runa:knowledge:initialize')
    after = app('python3', '-c', 'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections/runa_knowledge").read().decode())')
    check(json.loads(before)['result']['points_count'] == json.loads(after)['result']['points_count'], 'repeat initialization preserves the existing collection and points')
    document = root / 'docs/app/knowledge-acceptance.md'
    document.write_text('# Application sentinel\n\nCustomer knowledge incremental acceptance.\n')
    call(['git', '-C', str(root), 'add', 'docs/app/knowledge-acceptance.md'])
    app('php', 'artisan', 'runa:knowledge:index')
    check('docs/app/knowledge-acceptance.md' in json.loads(app('python3', '-c', probe)), 'incremental indexing adds application knowledge')
    call(['git', '-C', str(root), 'rm', '-f', 'docs/app/knowledge-acceptance.md'])
    app('php', 'artisan', 'runa:knowledge:index')
    check('docs/app/knowledge-acceptance.md' not in json.loads(app('python3', '-c', probe)), 'incremental indexing removes obsolete source identities')
    hits = app('php', 'artisan', 'runa:knowledge:search', 'application protected platform source', '--top-k=5')
    check('maintainer/' not in hits and bool(hits.strip()), 'fresh customer retrieval returns delivered sources only')
    report['customer_knowledge'] = {'qdrant_volume': volumes[0], 'preserved_model_cache': caches[0], 'sources': paths}
    # The outer fixture owns scoped cleanup. External model cache is never removed.
