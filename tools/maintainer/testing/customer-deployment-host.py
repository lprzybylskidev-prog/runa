"""Real customer deployment acceptance inside the installer's disposable prod project."""
import fcntl
import json
from pathlib import Path
import subprocess
import sys


def exercise(prod, fixture, project, check, report, call, output, commit):
    customer = fixture / 'customer-repository'
    call(['git', 'clone', '--quiet', '--no-hardlinks', str(prod), str(customer)])
    call(['git', '-C', str(customer), 'checkout', '-B', 'main'])
    call(['git', '-C', str(prod), 'remote', 'add', 'customer', str(customer)])
    call(['git', '-C', str(prod), 'config', '--local', 'runa.applicationRemote', 'customer'])
    git_config = (prod / '.git/config').read_bytes()
    head = output(['git', '-C', str(prod), 'rev-parse', 'HEAD'])
    state = prod / '.runtime/production'
    def deployment():
        return json.loads((state / 'deployment.json').read_text())
    def sql(statement):
        return output(['docker', 'exec', project + '-postgres-1', 'psql', '-U', 'runa', '-d', 'runa', '-At', '-v', 'ON_ERROR_STOP=1', '-c', statement])
    def platform():
        return sql("SELECT json_build_array((SELECT json_agg(t ORDER BY id) FROM shared.migrations t), (SELECT json_agg(t ORDER BY id) FROM shared.platform_seed_runs t), (SELECT payload FROM shared.jobs WHERE queue='deployment-sentinel'))")
    def execute(*args, success=True):
        # First deploy also traverses real cold PHP
        # parsing and the fixed host recovery executor, rather than a mocked plan.
        if not args and not report.get('customer_command_transport'):
            command = [str(prod / 'bin/artisan'), 'runa:deploy', '--simulation']
            report['customer_command_transport'] = True
        else:
            command = [sys.executable, str(prod / 'tools/application/executors/operations.py'), 'deploy', *args, '--simulation']
        result = call(command, cwd=prod, check=False)
        check((result.returncode == 0) == success, 'customer deploy ' + ('succeeds' if success else 'rejects/fails') + ': ' + (' '.join(args) or 'default main'))
    def maintenance():
        return output(['docker', 'exec', project + '-nginx-1', 'sh', '-c', 'test -f /run/runa-maintenance/enabled && echo active || echo clear'])
    def assert_fenced(phase):
        pending = json.loads((state / 'in-progress.json').read_text())
        stopped = [output(['docker', 'inspect', '--format', '{{.State.Running}}', project + '-' + service + '-1']) for service in ('app', 'queue', 'scheduler')]
        check(pending['phase'] == phase and maintenance() == 'active' and stopped == ['false'] * 3,
              'failed ' + phase + ' retains recorded phase, HTTP maintenance and stopped writers')
    def recover_fixture():
        # Fixture-only reviewed recovery: migrations failed before changes or seed
        # effects were inspected. Restore known previous code; never undo data.
        code = 'import sys; sys.path.insert(0,"tools/application"); import operations as o; old=o.read_state()["current"]; o.set_tag(old["sha"]); o.resume_http(old["path"],recreate=True); o.health(old["path"],verify_source=True); (o.STATE/"in-progress.json").unlink()'
        call([sys.executable, '-c', code], cwd=prod)
    sql("INSERT INTO shared.jobs(queue,payload,attempts,available_at,created_at) VALUES ('deployment-sentinel','preserved-platform-data',0,0,0)")
    before_platform = platform()
    before_source = {name: (prod / name).read_bytes() for name in ('VERSION', 'src/runa/platform.json', 'compose.production.yaml')}
    private_settings = {name: (prod / name).read_bytes() for name in ('.env.prod', '.env.prod.database')}
    storage = 'foreach (["application", "runa"] as $owner) { file_put_contents("/workspace/src/storage/app/".$owner."/private/deployment-sentinel", $owner); }'
    call(['docker', 'exec', project + '-app-1', 'php', '-r', storage])

    migration = customer / 'src/app/database/migrations/2099_01_01_000001_deploy_probe.php'
    migration.write_text('''<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement('CREATE SCHEMA deploy_probe');
        DB::statement('CREATE TABLE deploy_probe.records (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, label text NOT NULL)');
    }
    public function down(): void { throw new RuntimeException('Fixture rollback must never run'); }
};
''')
    seed = customer / 'src/app/database/seeders/DatabaseSeeder.php'
    seed_template = '''<?php
namespace Database\\Seeders;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
final class DatabaseSeeder extends Seeder {
    public function run(): void { DB::table('deploy_probe.records')->insert(['label' => 'LABEL']); EXTRA }
}
'''
    seed.write_text(seed_template.replace('LABEL', 'first').replace('EXTRA', ''))
    loose = customer / 'src/app/database/seeders/LooseSeeder.php'
    loose.write_text("<?php namespace Database\\Seeders; final class LooseSeeder extends \\Illuminate\\Database\\Seeder { public function run(): void { throw new \\RuntimeException('Loose seeder must never run'); } }\n")
    manifest = customer / 'src/app/application.json'
    identity = json.loads(manifest.read_text())
    identity['version'] = '7.4.2'
    manifest.write_text(json.dumps(identity) + '\n')
    route = customer / 'src/app/routes/web.php'
    route.write_text(route.read_text() + "\nRoute::get('/api/deploy-probe', fn () => ['count' => \\Illuminate\\Support\\Facades\\DB::table('deploy_probe.records')->count()])->name('deploy.probe');\n")
    first = commit(customer)
    execute()
    check(deployment()['current']['sha'] == first and sql('SELECT string_agg(label,\',\' ORDER BY id) FROM deploy_probe.records') == 'first', 'default main builds customer code, runs application migration and only its explicit DatabaseSeeder')
    check(deployment()['current']['application_version'] == '7.4.2'
          and deployment()['current']['version'] == before_source['VERSION'].decode().strip(),
          'deployment records independent customer and platform versions without rewriting either manifest')
    check(output(['docker', 'exec', project + '-nginx-1', 'wget', '-qO-', 'http://127.0.0.1:8080/api/deploy-probe']) == '{"count":1}', 'running production HTTP serves the customer code/data transition')
    execute()
    check(sql('SELECT count(*) FROM deploy_probe.records') == '1', 'same deployed revision does not replay application seeds')

    seed.write_text(seed_template.replace('LABEL', 'second').replace('EXTRA', ''))
    second = commit(customer)
    call(['git', '-C', str(customer), 'tag', 'customer-release-42'])
    execute('customer-release-42')
    check(deployment()['current']['sha'] == second and sql('SELECT string_agg(label,\',\' ORDER BY id) FROM deploy_probe.records') == 'first,second', 'arbitrary explicit customer tag preserves existing application data and migration history')
    execute(second)
    check(platform() == before_platform and all((Path(deployment()['current']['path']) / name).read_bytes() == content for name, content in before_source.items()), 'platform migration/seed histories, sentinel data and protected source remain unchanged')
    durable = 'foreach (["application", "runa"] as $owner) { if (file_get_contents("/workspace/src/storage/app/".$owner."/private/deployment-sentinel") !== $owner) { exit(1); } }'
    call(['docker', 'exec', project + '-app-1', 'php', '-r', durable])
    check(all((prod / name).read_bytes() == content for name, content in private_settings.items()), 'both durable storage owners and production credentials survive deployment')
    stable = (state / 'deployment.json').read_bytes()
    execute('missing-customer-branch', success=False)
    with (state / 'operation.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        execute(success=False)
    call(['docker', 'exec', project + '-app-1', 'php', '-r', 'file_put_contents("/workspace/src/storage/framework/maintenance/enabled", "manual");'])
    execute(success=False)
    check(maintenance() == 'active', 'preexisting manual maintenance is preserved without being claimed or cleared')
    call(['docker', 'exec', project + '-app-1', 'php', '-r', 'unlink("/workspace/src/storage/framework/maintenance/enabled");'])
    call(['git', '-C', str(customer), 'checkout', '-B', 'bad-protected'])
    (customer / 'VERSION').write_text('unauthorized-platform-change\n')
    commit(customer)
    execute('bad-protected', success=False)
    call(['git', '-C', str(customer), 'checkout', '-B', 'bad-build', second])
    (customer / 'src/composer.lock').write_text('broken lock\n')
    commit(customer)
    execute('bad-build', success=False)
    check((state / 'deployment.json').read_bytes() == stable and not (state / 'in-progress.json').exists() and maintenance() == 'clear', 'fetch, lock, protected-source and build failures leave the running deployment/state untouched')

    call(['git', '-C', str(customer), 'checkout', '-B', 'bad-migration', second])
    (migration.parent / '2099_01_01_000002_failure.php').write_text("<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { throw new \\RuntimeException('Synthetic migration failure before mutation'); } };\n")
    commit(customer)
    execute('bad-migration', success=False)
    assert_fenced('application-migrations')
    check(sql('SELECT count(*) FROM deploy_probe.records') == '2', 'failed migration does not run application seeding or undo prior data')
    recover_fixture()
    call(['git', '-C', str(customer), 'checkout', '-B', 'bad-seed', second])
    seed.write_text(seed_template.replace('LABEL', 'partial-seed').replace('EXTRA', "throw new \\RuntimeException('Synthetic seed failure after mutation');"))
    commit(customer)
    execute('bad-seed', success=False)
    assert_fenced('application-seeding')
    check(sql('SELECT count(*) FROM deploy_probe.records') == '3', 'partial seed effects remain visible to recovery; no claimed data rollback')
    execute('main', success=False)
    check(sql('SELECT count(*) FROM deploy_probe.records') == '3', 'unresolved operation blocks blind retries and duplicate seed execution')
    recover_fixture()
    call(['git', '-C', str(customer), 'checkout', '-B', 'bad-health', second])
    seed.write_text(seed_template.replace('LABEL', 'health-failure').replace('EXTRA', ''))
    route.write_text(route.read_text().replace("Route::get('/', SpaController::class)", "Route::get('/', fn () => abort(503))"))
    commit(customer)
    execute('bad-health', success=False)
    assert_fenced('restart-and-health')
    check(platform() == before_platform and sql('SELECT count(*) FROM deploy_probe.records') == '4', 'health failure fences HTTP/writers while preserving completed app changes and platform data')
    check((prod / '.git/config').read_bytes() == git_config and output(['git', '-C', str(prod), 'rev-parse', 'HEAD']) == head, 'customer remotes/config/history are unchanged by all deployments and failures')
    check(not list(state.glob('customer-source-*')), 'all disposable customer fetch staging is removed after success and failure')
    report['customer_deployment'] = {'first': first, 'second': second, 'successful_images': deployment()['current']['images'],
                                     'pending_phase': json.loads((state / 'in-progress.json').read_text())['phase']}
