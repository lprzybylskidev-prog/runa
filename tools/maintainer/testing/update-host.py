"""Exercise real read-only updater probes in the installer's disposable prod fixture."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'distribution'))
import bootstrap
import update
import update_runtime


def exercise(prod, fixture, project, check, report, call, output, commit):
    mode = json.loads((prod / '.git/runa-bootstrap.json').read_text())['mode']
    prod.chmod(0o700)
    repository = fixture / ('reference-releases-' + mode)
    call(['git', 'clone', '--quiet', '--no-hardlinks', str(prod), str(repository)])
    def git(*args):
        return output(['git', '-C', str(repository), *args])
    for tag in ('v0.2.1', 'v0.3.0'):
        call(['git', '-C', str(repository), 'tag', '-d', tag], check=False, capture=True)
    git('-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'tag', '-a', 'v0.2.1', '-m', 'original')
    (repository / 'VERSION').write_text('0.3.0\n')
    manifest = repository / 'src/runa/platform.json'
    value = json.loads(manifest.read_text()); value['version'] = '0.3.0'; manifest.write_text(json.dumps(value) + '\n')
    migration = repository / 'src/runa/database/migrations/2099_01_01_000001_update_preflight.php'
    migration.write_text("<?php throw new RuntimeException('Planning must never execute this migration');\n")
    commit(repository)
    git('-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'tag', '-a', 'v0.3.0', '-m', 'target')
    client = bootstrap.GitHub('fixture/runa')
    def local_git(source, home, *args, **kwargs):
        if args[:3] == ('remote', 'add', 'origin'):
            args = (*args[:3], str(repository))
        env = bootstrap.git_environment(home); env['GIT_ALLOW_PROTOCOL'] = 'file'
        return bootstrap.process(['git', '-c', 'core.hooksPath=/dev/null', '-C', str(source), *args], env=env, input_data=kwargs.get('input_data'))
    def resolve(tag):
        return {'release_id': 1, 'tag_object': git('rev-parse', tag), 'commit': git('rev-parse', tag+'^{commit}')}
    def sql():
        return output(['docker', 'exec', project + '-postgres-1', 'psql', '-U', 'runa', '-d', 'runa', '-XqAt', '-c',
                       "SELECT json_build_array((SELECT json_agg(t ORDER BY id) FROM shared.migrations t),(SELECT json_agg(t ORDER BY id) FROM shared.platform_seed_runs t))"])
    def files():
        result = {}
        active = prod if mode == 'dev' else Path(json.loads((prod / '.runtime/production/deployment.json').read_text())['current']['path'])
        for root, prefix in ((prod, 'operator'), (active, 'active')):
            for name in ('VERSION', 'src/runa/platform.json', 'src/app/application.json', 'src/composer.json', 'src/composer.lock', 'src/package.json', 'src/pnpm-lock.yaml', '.env', '.env.dev.local', '.env.dev.database', '.env.prod', '.env.prod.database', '.env.prod.compose', '.runtime/production/deployment.json'):
                p = root / name
                if p.exists(): result[prefix + '/' + name] = (p.stat().st_mode, hashlib.sha256(p.read_bytes()).hexdigest())
        return result
    def services():
        return {name: output(['docker', 'inspect', '--format', '{{.Id}} {{.State.StartedAt}}', project + '-' + name + '-1']) for name in ('app', 'nginx', 'postgres', 'queue', 'scheduler', *(['mailpit'] if mode == 'dev' else []))}
    before = {'files': files(), 'database': sql(), 'services': services()}
    # Only source transport/API is synthetic. All Git verification, Docker,
    # Composer/pnpm, actual runtime/image and PostgreSQL reads are exercised.
    with patch.object(bootstrap, 'git', side_effect=local_git), patch.object(client, 'resolve', side_effect=resolve), patch.object(client, 'verify_repository', return_value=1):
        result = update.preflight(prod, client, 'v0.3.0')
        (ROOT / '.runtime/ownership' / ('update-preflight-' + project + '.json')).write_text(json.dumps(result, indent=2) + '\n')
        check(result['status'] == 'ready', 'external updater verifies independently fetched original/target source and real runtime/dependency/database prerequisites')
        check(result['plan']['platform_migrations'] == [migration.stem] and result['execution_available'] is False, 'read-only plan identifies platform-only migration/seeder steps without executing target PHP')
        check(before == {'files': files(), 'database': sql(), 'services': services()}, 'dry-run preserves actual database rows, config/locks/identity/source bytes and runtime container identities/start times')
        check(not (prod / '.runtime/production/in-progress.json').exists() and result['temporary_downloads_cleaned'] and all(not Path(p).exists() for p in result['temporary_downloads']), 'planning creates no installation journal/maintenance and removes all external source downloads')
        if mode == 'prod':
            active = Path(json.loads((prod / '.runtime/production/deployment.json').read_text())['current']['path'])
            application = active / 'src/app/application.json'
            content = application.read_bytes()
            value = json.loads(content); value['runa'] = '<0.3.0'
            try:
                application.write_text(json.dumps(value))
                blocked = update.preflight(prod, client, 'v0.3.0')
                check(blocked['checks'][-1]['id'] == 'application-incompatible', 'active application compatibility is checked independently of the operator checkout')
            finally:
                application.write_bytes(content)
        original = prod / 'src/runa/platform.json'
        content = original.read_bytes(); value = json.loads(content); value['name'] = 'tampered'; original.write_text(json.dumps(value))
        blocked = update.preflight(prod, client, 'v0.3.0')
        original.write_bytes(content)
        check(blocked['status'] == 'blocked', 'modified platform manifest blocks owner planning without repair or override')
        unknown = prod / 'src/runa/unexpected.php'; unknown.write_text('<?php // unexpected')
        blocked = update.preflight(prod, client, 'v0.3.0'); unknown.unlink()
        check(blocked['checks'][-1]['id'] == 'protected-source-mismatch', 'unexpected protected source blocks before target hooks or runtime mutation')
    report.setdefault('update_preflight', {})[mode] = {'result': result['status'], 'target': result['target_release']['commit']}
