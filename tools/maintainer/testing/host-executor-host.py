#!/usr/bin/env python3
"""Native PHP -> private host executor -> isolated Docker lifecycle acceptance."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time
import uuid

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import management


def output(args, **kwargs):
    result = subprocess.run(args, capture_output=True, text=True, **kwargs)
    if result.returncode:
        print(result.stdout + result.stderr, flush=True)
        result.check_returncode()
    return result.stdout.strip()


def main():
    if Path('/.dockerenv').exists():
        raise SystemExit('Run native host acceptance outside containers.')
    project = 'runa-host-acceptance-' + uuid.uuid4().hex[:10]
    report = {'result': 'RUNNING', 'project': project, 'checks': []}
    image = management.ensure_image()
    report['image'] = image
    active = output(['docker', 'ps', '-q', '--filter', 'label=com.docker.compose.project=runa-dev']).splitlines()
    active = [item for item in active if output(['docker', 'inspect', '--format', '{{index .Config.Labels "com.docker.compose.project"}}', item]) == 'runa-dev']
    before = {item: output(['docker', 'inspect', '--format', '{{.State.StartedAt}}', item]) for item in active}
    def check(ok, text):
        if not ok:
            raise AssertionError(text)
        report['checks'].append(text)
        print('PASS: ' + text, flush=True)
    with tempfile.TemporaryDirectory(prefix='runa-host-') as folder:
        root = Path(folder)
        for name in ('artisan', 'src/artisan', 'bin/artisan', 'bin/command', 'src/composer.json', 'src/composer.lock', 'docker/php/Dockerfile', '.dockerignore'):
            destination = root / name
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / name, destination)
        shutil.copytree(ROOT / 'tools/application', root / 'tools/application', ignore=shutil.ignore_patterns('__pycache__'))
        shutil.copytree(ROOT / 'src/runa/management', root / 'src/runa/management')
        (root / '.devcontainer').mkdir()
        (root / '.devcontainer/compose.resources.yaml').write_text('{"services":{}}')
        (root / '.env.dev.local').write_text('DEV_PROFILE=low\nEMBEDDINGS_DEVICE=cpu\nEMBEDDINGS_CUDA=0\n')
        common = {'image': 'runa-management:' + management.image_identity()[:24], 'pull_policy': 'never', 'entrypoint': ['php', '-r'], 'command': ['sleep(1200);'],
                  'read_only': True, 'cap_drop': ['ALL'], 'healthcheck': {'test': ['CMD', 'php', '-r', 'exit(0);'], 'interval': '1s', 'timeout': '1s', 'retries': 5}}
        services = {key: dict(common) for key in ('app', 'postgres', 'nginx', 'queue', 'scheduler', 'mailpit', 'qdrant', 'embeddings')}
        for key in ('setup', 'knowledge-init'):
            services[key] = dict(common, command=['exit(0);'], healthcheck={'disable': True})
        services['app']['depends_on'] = {'postgres': {'condition': 'service_healthy'}}
        services['setup']['depends_on'] = {'app': {'condition': 'service_healthy'}}
        services['nginx']['depends_on'] = {'setup': {'condition': 'service_completed_successfully'}}
        (root / 'compose.yaml').write_text(json.dumps({'name': project, 'services': services, 'networks': {'default': {'internal': True}}}))
        launcher = str(root / 'bin/artisan')
        compose = ['docker', 'compose', '--project-directory', str(root), '--env-file', str(root / '.env.dev.local'), '-f', str(root / 'compose.yaml'), '-f', str(root / '.devcontainer/compose.resources.yaml')]
        try:
            output([launcher, '--recover-host'], cwd=root)
            services['app']['volumes'] = [{'type': 'bind', 'source': str(root / '.runtime/host'), 'target': '/run/runa-host'}]
            (root / 'compose.yaml').write_text(json.dumps({'name': project, 'services': services, 'networks': {'default': {'internal': True}}}))
            output([launcher, 'runa:dev:up'], cwd=root)
            php = compose + ['exec', '-T', '-w', '/opt/runa', 'app', 'php', 'artisan']
            result = subprocess.run(php + ['runa:dev:status'], capture_output=True, text=True)
            check(result.returncode == 0 and project + '-app-1' in result.stdout and 'Host operation:' in result.stderr, 'normal php artisan executes real host Compose status rather than printing a plan')
            check('Host operation:' in subprocess.run(php + ['runa:dev:logs', 'app'], capture_output=True, text=True, check=True).stderr, 'normal php artisan forwards lifecycle logs through the restricted executor')
            before_ids = output(compose + ['ps', '-q'])
            plan = json.loads(output(php + ['runa:dev:down', '--plan']))
            check(plan['operation'] == 'development.down' and output(compose + ['ps', '-q']) == before_ids, 'explicit --plan remains nonexecuting')
            rejected = subprocess.run(php + ['runa:dev', 'destroy'], capture_output=True, text=True)
            check(rejected.returncode != 0, 'invalid operation is rejected without arbitrary host execution')
            result = subprocess.run(php + ['runa:dev:restart', 'app'], capture_output=True, text=True)
            operation = next(line.split(': ', 1)[1] for line in result.stderr.splitlines() if line.startswith('Host operation:'))
            receipt = root / '.runtime/host/operations' / (operation + '.json')
            for _ in range(100):
                state = json.loads(receipt.read_text())
                if state.get('state') in ('completed', 'failed'):
                    break
                time.sleep(0.2)
            check(state['state'] == 'completed', 'self-restart completes on the host after its invoking PHP process disconnects')
            recovered = json.loads(output(php + ['runa:host:status', operation]))
            check(recovered['state'] == 'completed' and recovered['exit_code'] == 0, 'operation ID recovers completion without retrying a mutation')
            # An independent operator PHP process survives a stopped target.
            output(compose + ['stop', 'app'])
            operator = ['docker', 'run', '--rm', '--network', 'none', '--read-only', '--tmpfs', '/tmp', '--cap-drop=ALL',
                        '--security-opt=no-new-privileges:true', '--user', str(os.getuid()) + ':' + str(os.getgid()),
                        '--mount', 'type=bind,src=' + str(root / '.runtime/host') + ',dst=/run/runa-host', image, '--management']
            result = subprocess.run(operator + ['runa:dev:up'], capture_output=True, text=True)
            check(result.returncode == 0 and bool(output(compose + ['ps', '--status', 'running', '-q', 'app'])), 'separate operator php artisan recovers a stopped application without host PHP or Docker socket')
            result = subprocess.run(operator + ['runa:deploy', 'a' * 40], capture_output=True, text=True)
            check(result.returncode != 0 and 'Host operation:' in result.stderr, 'deployment reaches its host guard and propagates failure without touching an unmanaged database')
            # Real dev acquisition context: neither caller APP_ENV nor prod settings may enable production work.
            output(['git', 'init', '--quiet', str(root)])
            output(['git', '-C', str(root), 'remote', 'add', 'origin', 'https://github.com/fixture/runa.git'])
            output(['git', '-C', str(root), 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream'])
            from repository_boundary import write_staging_receipt, OWNER_PATHS
            acquisition = {'schema_version': 1, 'repository': 'fixture/runa', 'mode': 'dev',
                           'projection': {'version': 1, 'excluded': list(OWNER_PATHS)}}
            write_staging_receipt(root, acquisition)
            (root / '.env.prod.compose').write_text('RUNA_IMAGE_TAG=' + 'a' * 40 + '\nRUNA_PRODUCTION_MODE=simulation\nRUNA_SIMULATION_PROJECT=runa-prod-simulation-mode-guard\n')
            (root / 'compose.production.yaml').write_text(json.dumps({'services': {'probe': {'image': 'runa-management:' + management.image_identity()[:24]}}}))
            snapshot = {path: path.read_bytes() for path in (root / '.runtime/production').rglob('*') if path.is_file()}
            container_ids = output(compose + ['ps', '-q'])
            forbidden = [['runa:production:' + action] for action in ('validate', 'build', 'up', 'down', 'restart', 'status', 'logs', 'self-test', 'verify-ai-isolation')]
            forbidden += [['runa:deploy', 'a' * 40, '--simulation'], ['runa:deploy:rollback']]
            for args in forbidden:
                result = subprocess.run(php + args, capture_output=True, text=True)
                check(result.returncode != 0 and 'require a production installation' in result.stdout + result.stderr,
                      'dev installation rejects ' + args[0] + ' through normal php artisan')
            result = subprocess.run([launcher, 'runa:production:build'], cwd=root, capture_output=True, text=True)
            check(result.returncode != 0 and 'require a production installation' in result.stdout + result.stderr,
                  'recovery transport cannot bypass the development installation guard')
            check(output(compose + ['ps', '-q']) == container_ids and snapshot == {path: path.read_bytes() for path in (root / '.runtime/production').rglob('*') if path.is_file()},
                  'rejected production commands preserve target containers and operational state')
            acquisition['mode'] = 'prod'
            write_staging_receipt(root, acquisition)
            check('PASS: production validate.' in output(php + ['runa:production:validate']),
                  'production-mode simulation still executes actual quiet Compose validation')
            output(php + ['runa:host:stop'])
            for _ in range(100):
                if not (root / '.runtime/host/client.json').exists():
                    break
                time.sleep(0.1)
            result = subprocess.run(php + ['runa:dev:status'], capture_output=True, text=True)
            check(result.returncode != 0 and '--recover-host' in result.stdout + result.stderr, 'missing executor fails with recovery instructions instead of reporting success')
            output([launcher, '--recover-host'], cwd=root)
            check(json.loads(output(php + ['runa:host:status']))['state'] == 'ready', 'explicit host recovery prepares normal php artisan again')
            report['result'] = 'PASS'
        except BaseException:
            report['result'] = 'FAIL'
            raise
        finally:
            if (root / '.runtime/host/client.json').exists():
                output([launcher, '--stop-host'], cwd=root)
            resources = {'containers': output(compose + ['ps', '-aq']).splitlines(),
                         'networks': output(['docker', 'network', 'ls', '-q', '--filter', 'label=com.docker.compose.project=' + project]).splitlines(),
                         'volumes': output(['docker', 'volume', 'ls', '-q', '--filter', 'label=com.docker.compose.project=' + project]).splitlines()}
            check(not resources['volumes'], 'transport acceptance created no data volumes')
            print('Scoped host transport cleanup: ' + json.dumps(resources), flush=True)
            output(compose + ['down', '--remove-orphans'])
            report['cleanup'] = resources
            check(all(output(['docker', 'inspect', '--format', '{{.State.StartedAt}}', item]) == start for item, start in before.items()), 'active development/test containers and data were preserved')
            destination = ROOT / '.runtime/ownership/RM-2026-005-host-executor-latest.json'
            destination.write_text(json.dumps(report, indent=2) + '\n')


if __name__ == '__main__':
    main()
