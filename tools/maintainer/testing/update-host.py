"""Exercise real updater planning, execution and recovery in disposable customer development/production fixtures."""
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
import update_execution


def exercise(prod, fixture, project, check, report, call, output, commit):
    mode = json.loads((prod / '.git/runa-bootstrap.json').read_text())['mode']
    prod.chmod(0o700)
    if mode == 'prod':
        result = update.preflight(prod, bootstrap.GitHub('fixture/runa'), 'v0.3.0')
        check(result['checks'][-1]['id'] == 'development-only' and not result['temporary_downloads'], 'production updater rejects before release acquisition or runtime mutation')
        return
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
    migration.write_text("<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('CREATE TABLE shared.update_reference (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, code text NOT NULL UNIQUE, label text NOT NULL)'); } };\n")
    scope = repository / 'src/runa/Database/DatabaseScope.php'
    scope.write_text(scope.read_text().replace("'shared.platform_seed_runs'", "'shared.platform_seed_runs', 'shared.update_reference'", 1))
    seed = repository / 'src/runa/database/seeders/DatabaseSeeder.php'
    seed.write_text("<?php namespace Runa\\Database\\Seeders; final class DatabaseSeeder extends \\Illuminate\\Database\\Seeder { public function run(): void { \\Illuminate\\Support\\Facades\\DB::table('shared.update_reference')->upsert([['code'=>'baseline','label'=>'updated-reference']], ['code'], ['label']); } }\n")
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
        check(result['plan']['platform_migrations'] == [migration.stem] and result['dry_run'] is True, 'read-only plan identifies platform-only migration/seeder steps without executing target PHP')
        check(before == {'files': files(), 'database': sql(), 'services': services()}, 'dry-run preserves actual database rows, config/locks/identity/source bytes and runtime container identities/start times')
        check(not (prod / '.runtime/production/in-progress.json').exists() and result['temporary_downloads_cleaned'] and all(not Path(p).exists() for p in result['temporary_downloads']), 'planning creates no installation journal/maintenance and removes all external source downloads')
        original = prod / 'src/runa/platform.json'
        content = original.read_bytes(); value = json.loads(content); value['name'] = 'tampered'; original.write_text(json.dumps(value))
        blocked = update.preflight(prod, client, 'v0.3.0')
        original.write_bytes(content)
        check(blocked['status'] == 'blocked', 'modified platform manifest blocks owner planning without repair or override')
        unknown = prod / 'src/runa/unexpected.php'; unknown.write_text('<?php // unexpected')
        blocked = update.preflight(prod, client, 'v0.3.0'); unknown.unlink()
        check(blocked['checks'][-1]['id'] == 'protected-source-mismatch', 'unexpected protected source blocks before target hooks or runtime mutation')
        # Application data work is deliberately pending: the owner update must never execute it.
        app_pending = prod / 'src/app/database/migrations/2099_01_01_000099_never_from_updater.php'
        app_pending.write_text("<?php throw new RuntimeException('Updater must not execute application migrations');\n")
        private = prod / 'src/storage/app/application/private/update-sentinel'
        private.parent.mkdir(parents=True, exist_ok=True); private.write_text('preserved')
        preserved = files()
        git_head = output(['git', '-C', str(prod), 'rev-parse', 'HEAD'])
        class InterruptedHealth(update_execution.DockerRuntime):
            def health(self):
                super().health()
                raise RuntimeError('Synthetic lost response after actual healthy switch')
        review = result['customization_review']
        failed = update_execution.execute(prod, client, 'v0.3.0', choice='continue', fingerprint=review['fingerprint'], runtime_factory=InterruptedHealth)
        (ROOT / '.runtime/ownership' / ('update-execution-failure-' + project + '.json')).write_text(json.dumps(failed, indent=2) + '\n')
        check(failed['status'] == 'blocked' and failed.get('phase') == 'health', 'real update reaches schema/seed/source switch and fences a lost health response')
        journal = json.loads((prod / '.runtime/production/in-progress.json').read_text())
        check(all(output(['docker', 'inspect', '--format', '{{.State.Running}}', project + '-' + service + '-1']) == 'false' for service in ('app', 'nginx', 'queue', 'scheduler')), 'failed development update stops HTTP and application writers while retaining PostgreSQL')
        def query(statement):
            return output(['docker', 'exec', project + '-postgres-1', 'psql', '-U', 'runa', '-d', 'runa', '-XqAt', '-v', 'ON_ERROR_STOP=1', '-c', statement])
        check(query('SELECT count(*) FROM shared.update_reference') == '1', 'development updater applies actual platform migration and required reference seed')
        seeds_before = query("SELECT count(*) FROM shared.platform_seed_runs WHERE release_sha='" + journal['target']['commit'] + "'")
        resumed = update_execution.execute(prod, client, 'v0.3.0', resume=True)
        (ROOT / '.runtime/ownership' / ('update-execution-' + project + '.json')).write_text(json.dumps(resumed, indent=2) + '\n')
        check(resumed['status'] == 'completed', 'independently verified resume recovers the actual development runtime')
        check(query("SELECT count(*) FROM shared.platform_seed_runs WHERE release_sha='" + journal['target']['commit'] + "'") == seeds_before == '1', 'resume never blindly replays already committed required seeds')
        check(private.read_text() == 'preserved' and app_pending.is_file() and output(['git', '-C', str(prod), 'rev-parse', 'HEAD']) == git_head, 'updater preserves application pending data/source, private storage and customer Git HEAD')
        after = files()
        check(all(after[name] == content for name, content in preserved.items() if not name.endswith('/src/runa/platform.json')), 'customer identity, dependency locks, settings and scaffold survive real update and recovery')
        check(not (prod / '.runtime/production/in-progress.json').exists() and not list((prod / '.runtime/production/update-staging').iterdir()), 'successful resume archives journal and removes only its verified temporary platform source')
        app_pending.unlink()
    report.setdefault('update_preflight', {})[mode] = {'result': result['status'], 'target': result['target_release']['commit']}
