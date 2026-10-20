#!/usr/bin/env python3
"""Native isolated dev/prod installer acceptance. Creates disposable Docker resources.

Fresh PostgreSQL/storage volumes, synthetic TLS/SMTP, real migrations/builds/HTTP.
Never reuses development data or production projects. Prints scoped cleanup first.
"""
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.request
import uuid

ROOT = Path(__file__).resolve().parents[3]
REPORTS = ROOT / '.runtime/ownership'
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
from repository_boundary import OWNER_PATHS, write_staging_receipt


def call(arguments, cwd=ROOT, env=None, capture=False, check=True):
    return subprocess.run(arguments, cwd=cwd, env=env, check=check, text=True,
                          stdout=subprocess.PIPE if capture else None,
                          stderr=subprocess.PIPE if capture else None)


def output(arguments, **kwargs):
    return call(arguments, capture=True, **kwargs).stdout.strip()


def port():
    with socket.socket() as stream:
        stream.bind(('127.0.0.1', 0))
        return stream.getsockname()[1]


def yaml_source(name):
    code = 'require "src/vendor/autoload.php"; echo json_encode(Symfony\\Component\\Yaml\\Yaml::parseFile($argv[1]), JSON_THROW_ON_ERROR);'
    return json.loads(output(['docker', 'exec', '-w', '/workspace', 'runa-dev-app-1', 'php', '-r', code, name]))


def snapshot(root):
    call(['git', 'clone', '--quiet', '--shared', '--no-checkout', str(ROOT), str(root)])
    paths = output(['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z']).split('\0')
    for name in paths:
        if not name:
            continue
        source, destination = ROOT / name, root / name
        if source.is_file() or source.is_symlink():
            destination.parent.mkdir(parents=True, exist_ok=True)
            if source.is_symlink():
                destination.symlink_to(os.readlink(source))
            else:
                shutil.copy2(source, destination)


def commit(root):
    call(['git', '-C', str(root), 'add', '--all'])
    call(['git', '-C', str(root), '-c', 'core.hooksPath=/dev/null', '-c', 'user.name=Installer Fixture', '-c', 'user.email=fixture@example.invalid',
          'commit', '--quiet', '-m', 'test(RM-2026-005): snapshot isolated installer acceptance'])
    return output(['git', '-C', str(root), 'rev-parse', 'HEAD'])


def project_customer(root, sha):
    patterns = '/*\n' + ''.join('!/' + name + ('/' if name in ('distribution', 'tools/maintainer') else '') + '\n' for name in OWNER_PATHS)
    call(['git', '-C', str(root), 'sparse-checkout', 'init', '--no-cone'])
    subprocess.run(['git', '-C', str(root), 'sparse-checkout', 'set', '--no-cone', '--stdin'], input=patterns, text=True, check=True)
    call(['git', '-C', str(root), 'remote', 'set-url', 'origin', 'https://github.com/fixture/runa.git'])
    call(['git', '-C', str(root), 'config', 'remote.origin.pushurl', 'disabled://runa-read-only-upstream'])
    write_staging_receipt(root, {'mode': 'prod' if root.name == 'prod' else 'dev', 'schema_version': 1, 'repository': 'fixture/runa', 'commit': sha,
                               'projection': {'version': 1, 'excluded': list(OWNER_PATHS)}})
    assert all(not (root / name).exists() for name in OWNER_PATHS)
    assert not output(['git', '-C', str(root), 'status', '--porcelain'])


def installer(root, mode, sha):
    # Owner delivery invokes a fixed internal library, never an installed launcher.
    return [sys.executable, '-c', 'import runpy,sys; sys.path.insert(0,"tools/application"); path=sys.argv.pop(1); runpy.run_path(path,run_name="__main__")', str(ROOT / 'distribution/installation.py'), mode, sha]


def remove_project(project, report):
    def ids(kind):
        return output(['docker', kind, 'ls', '-q', '--filter', 'label=com.docker.compose.project=' + project]).splitlines()
    containers = output(['docker', 'ps', '-a', '-q', '--filter', 'label=com.docker.compose.project=' + project]).splitlines()
    resources = {'project': project, 'containers': containers, 'networks': ids('network'), 'volumes': ids('volume')}
    print('Scoped disposable installation cleanup: ' + json.dumps(resources), flush=True)
    if containers:
        call(['docker', 'rm', '--force', *containers])
    if resources['networks']:
        call(['docker', 'network', 'rm', *resources['networks']])
    if resources['volumes']:
        call(['docker', 'volume', 'rm', *resources['volumes']])
    report.setdefault('cleanup', []).append(resources)


def main():
    if Path('/.dockerenv').exists():
        raise SystemExit('Run this acceptance from the native Docker host.')
    REPORTS.mkdir(parents=True, exist_ok=True)
    token = uuid.uuid4().hex[:10]
    report = {'result': 'RUNNING', 'checks': [], 'preserved': ['active development/test services and data', 'rollback resources', 'Qdrant/model/shared dependency/build caches', 'backups', 'verification reports']}
    report_path = REPORTS / ('installation-host-' + token + '.json')
    def check(condition, text):
        if not condition:
            raise RuntimeError(text)
        report['checks'].append(text)
        print('PASS: ' + text, flush=True)
    active = output(['docker', 'ps', '-q', '--filter', 'label=com.docker.compose.project=runa-dev']).splitlines()
    active = [item for item in active if output(['docker', 'inspect', '--format', '{{index .Config.Labels "com.docker.compose.project"}}', item]) == 'runa-dev']
    before = {item: output(['docker', 'inspect', '--format', '{{.State.StartedAt}}', item]) for item in active}
    dev_project = 'runa-dev-install-' + token
    prod_project = 'runa-prod-simulation-' + token
    network = 'runa-install-smtp-' + token
    smtp = network + '-server'
    fixture_directory = Path(tempfile.mkdtemp(prefix='runa-install-'))
    fixture_directory.chmod(0o700)
    try:
        dev, prod = fixture_directory / 'dev', fixture_directory / 'prod'
        snapshot(dev)
        # Real development services, isolated ports/project; deliberately unavailable
        # optional AI proves the existing degradation boundary without model downloads.
        data = yaml_source('compose.yaml')
        data['name'] = dev_project
        services = ('app', 'app-test', 'nginx-test', 'postgres', 'setup', 'nginx', 'queue', 'scheduler', 'mailpit')
        data['services'] = {key: value for key, value in data['services'].items() if key in services}
        for key in ('qdrant', 'embeddings', 'knowledge-init'):
            data['services'][key] = {'image': 'runa-development:dev', 'pull_policy': 'never',
                                     'build': {'context': '.', 'dockerfile': 'docker/php/Dockerfile', 'target': 'development'},
                                     'entrypoint': ['python3', '-c', 'raise SystemExit(1)'], 'restart': 'no', 'healthcheck': {'disable': True}}
        data['volumes'] = {key: value for key, value in data['volumes'].items() if key in ('postgres-icu-data', 'composer-cache', 'mailpit-data')}
        web_port, mail_port = port(), port()
        data['services']['nginx']['ports'] = ['127.0.0.1:' + str(web_port) + ':8080']
        data['services']['mailpit']['ports'] = ['127.0.0.1:' + str(mail_port) + ':8025']
        (dev / 'compose.yaml').write_text(json.dumps(data, indent=2) + '\n')
        dev_sha = commit(dev)
        project_customer(dev, dev_sha)
        env = dict(os.environ, COMPOSE_PROJECT_NAME=dev_project)
        # Avoid GPU/model resources even on a CUDA-capable acceptance host.
        (dev / '.env.dev.local').write_text('EMBEDDINGS_DEVICE=cpu\n')
        (dev / '.env.dev.local').chmod(0o600)
        command = installer(dev, 'dev', dev_sha) + ['--profile=low', '--non-interactive']
        check(not (dev / 'src/vendor').exists() and not (dev / '.env').exists(), 'development starts with no vendor, application key or database settings')
        call(command, cwd=dev, env=env)
        with urllib.request.urlopen('http://127.0.0.1:' + str(web_port) + '/api/ready', timeout=10) as response:
            check(json.load(response)['status'] == 'ok', 'fresh development installation serves real Laravel readiness')
        check(not (dev / '.git/runa-ownership.json').exists(), 'development installation leaves no source baseline')
        bootstrap_providers = (dev / 'src/bootstrap/providers.php').read_bytes()
        for generator_command in (['make:config', 'CustomerGeneratorProbe'], ['make:test', 'CustomerGeneratorProbe', '--pest'], ['make:provider', 'CustomerGeneratorProbeProvider']):
            call(['docker', 'exec', '-w', '/workspace', dev_project + '-app-1', 'php', 'artisan', *generator_command, '--no-interaction'])
        check((dev / 'src/app/config/CustomerGeneratorProbe.php').is_file() and (dev / 'src/app/tests/Feature/CustomerGeneratorProbe.php').is_file(), 'standard config and test generators write only application roots')
        check((dev / 'src/app/Providers/CustomerGeneratorProbeProvider.php').is_file() and 'CustomerGeneratorProbeProvider' in (dev / 'src/app/providers.php').read_text() and (dev / 'src/bootstrap/providers.php').read_bytes() == bootstrap_providers, 'provider generation registers the application provider without editing platform bootstrap')

        check(not (dev / 'src/runa/tests').exists() and not (dev / 'src/runa/tools').exists(), 'customer source excludes all platform tests and browser harnesses')
        native_tests = output(['docker', 'exec', '-w', '/workspace', dev_project + '-app-1', 'php', 'artisan', 'test', '--list-tests'])
        check('WelcomeTest' in native_tests and 'Runa\\tests' not in native_tests and 'ManagementTest' not in native_tests, 'native customer Artisan discovers application tests without owner autoloading')
        for test_command in ('test', 'runa:test', 'runa:test:frontend'):
            call(['docker', 'exec', '-w', '/workspace', dev_project + '-app-1', 'php', 'artisan', test_command])
        call(['docker', 'compose', '--project-directory', str(dev), '--env-file', str(dev / '.env.dev.local'), '-f', str(dev / 'compose.yaml'), '-f', str(dev / '.devcontainer/compose.resources.yaml'), '--profile', 'testing', 'up', '-d', '--wait', 'nginx-test'], cwd=dev, env=env)
        call(['docker', 'exec', '-w', '/workspace', dev_project + '-app-1', 'php', 'artisan', 'runa:test:e2e'])
        browser_evidence = ROOT / 'artifacts/playwright' / ('installation-' + token)
        shutil.copytree(dev / 'artifacts/playwright/application', browser_evidence)
        report['application_browser_artifacts'] = str(browser_evidence.relative_to(ROOT))
        check((dev / 'artifacts/playwright/application/report/index.html').is_file(), 'customer backend, frontend and real Nginx browser suites pass without maintainer source; HTML report is available')

        secrets = {name: (dev / name).read_bytes() for name in ('.env', '.env.testing', '.env.dev.database')}
        customer = dev / 'src/app/customer-sentinel.txt'
        customer.write_text('preserve customer source\n')
        app = json.loads((dev / 'src/app/application.json').read_text())
        app['version'] = '2.4.1'
        (dev / 'src/app/application.json').write_text(json.dumps(app))
        call(command, cwd=dev, env=env)
        check(all((dev / name).read_bytes() == value for name, value in secrets.items()) and customer.read_text() == 'preserve customer source\n', 'repeated development installation preserves all credentials and customer source')
        check(json.loads((dev / 'src/app/application.json').read_text())['version'] == '2.4.1', 'application version remains customer-owned on repeat')
        check(not (dev / '.runtime/production/first-deploy.json').exists(), 'development never writes a real production marker')
        remove_project(dev_project, report)

        snapshot(prod)
        certificates = fixture_directory / 'certificates'
        certificates.mkdir(mode=0o700)
        call(['docker', 'run', '--rm', '--network', 'none', '--user', str(os.getuid()) + ':' + str(os.getgid()),
              '--mount', 'type=bind,src=' + str(certificates) + ',dst=/fixture', '--entrypoint', 'openssl', 'runa-development:dev',
              'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-keyout', '/fixture/private-key.pem', '-out', '/fixture/certificate.pem', '-days', '2',
              '-subj', '/CN=localhost', '-addext', 'subjectAltName=DNS:localhost,DNS:smtp', '-addext', 'basicConstraints=critical,CA:TRUE'])
        (certificates / 'private-key.pem').chmod(0o600)
        shutil.copyfile(ROOT / 'tools/maintainer/testing/fixtures/installation-smtp.py', certificates / 'smtp.py')
        mail_reports = REPORTS / ('RM-2026-005-point-8-smtp-' + token)
        mail_reports.mkdir(mode=0o700)
        call(['docker', 'network', 'create', '--label', 'runa.task=RM-2026-005-point-8', network])
        call(['docker', 'run', '-d', '--name', smtp, '--network', network, '--network-alias', 'smtp', '--user', str(os.getuid()) + ':' + str(os.getgid()),
              '--read-only', '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
              '--mount', 'type=bind,src=' + str(certificates) + ',dst=/fixture,readonly', '--mount', 'type=bind,src=' + str(mail_reports) + ',dst=/reports',
              '--entrypoint', 'python3', 'runa-development:dev', '/fixture/smtp.py'])
        data = yaml_source('compose.production.yaml')
        data['networks'] = {'default': {'external': True, 'name': network}}
        for service in ('app', 'queue', 'scheduler'):
            data['services'][service]['environment']['SSL_CERT_FILE'] = '/run/fixture-ca.pem'
            data['services'][service]['volumes'].append({'type': 'bind', 'source': str(certificates / 'certificate.pem'), 'target': '/run/fixture-ca.pem', 'read_only': True})
        (prod / 'compose.production.yaml').write_text(json.dumps(data, indent=2) + '\n')
        sha = commit(prod)
        project_customer(prod, sha)
        https_port, http_port = port(), port()
        settings = {'app_url': 'https://localhost:' + str(https_port), 'http_port': http_port, 'simulation_project': prod_project,
                    'tls': {'mode': 'builtin', 'certificate': str(certificates / 'certificate.pem'), 'private_key': str(certificates / 'private-key.pem'), 'ca_file': str(certificates / 'certificate.pem'), 'https_port': https_port, 'bind': '127.0.0.1'},
                    'smtp': {'host': 'smtp', 'port': 2465, 'scheme': 'smtps', 'username': '', 'password': '', 'from': 'fixture@example.invalid', 'recipient': 'verification@example.invalid'}}
        settings_file = fixture_directory / 'settings.json'
        settings_file.write_text(json.dumps(settings))
        settings_file.chmod(0o600)
        command = installer(prod, 'prod', sha) + ['--simulation', '--settings-file=' + str(settings_file), '--non-interactive']
        failed = call(command, cwd=prod, check=False)
        check(failed.returncode != 0 and (prod / '.runtime/production/in-progress.json').exists(), 'SMTP rejection fails initial installation and retains recovery state after real migrations/startup')
        check((mail_reports / 'rejected.eml').is_file(), 'failed installation actually reached SMTP DATA and was rejected by the synthetic provider')
        check(not (prod / '.runtime/production/first-deploy.json').exists(), 'failed simulation never writes real first-deploy evidence')
        secrets = {name: (prod / name).read_bytes() for name in ('.env.prod', '.env.prod.database', '.env.prod.compose')}
        (mail_reports / 'allow-mail').touch()
        call(command + ['--resume'], cwd=prod)
        journal = json.loads((prod / '.runtime/production/install-prod.json').read_text())
        deployment = json.loads((prod / '.runtime/production/deployment.json').read_text())
        check(journal['state'] == 'complete' and deployment['current']['simulation'], 'explicit resume completes real isolated production topology through the same installer')
        check(all((prod / name).read_bytes() == value for name, value in secrets.items()), 'production recovery preserves all originally generated secrets/settings')
        check((mail_reports / 'accepted.eml').is_file() and 'Runa installation SMTP verification' in (mail_reports / 'accepted.eml').read_text(), 'SMTP fixture actually received the verification message over certificate-verified TLS')
        check(not (prod / '.runtime/production/in-progress.json').exists() and not (prod / '.runtime/production/first-deploy.json').exists(), 'successful simulation clears its pending marker without writing real production evidence')
        call(command, cwd=prod)
        check(all((prod / name).read_bytes() == value for name, value in secrets.items()), 'completed production install is repeatable without secret rotation or redeployment')
        check(not (prod / '.git/runa-ownership.json').exists(), 'production installation leaves no source baseline')
        report['dev_sha'], report['prod_sha'] = dev_sha, sha
        report['images'] = deployment['current']['images']
        report['result'] = 'PASS'
    except BaseException:
        report['result'] = 'FAIL'
        raise
    finally:
        for mode in ('dev', 'prod'):
            source = fixture_directory / mode
            if (source / '.runtime/host/client.json').exists():
                print('Scoped fixture executor shutdown: ' + str(source), flush=True)
                call([sys.executable, '-c', 'import sys; sys.path.insert(0,"tools/application"); from host_executor import stop; stop()'], cwd=source, check=False)
        remove_project(dev_project, report)
        remove_project(prod_project, report)
        if output(['docker', 'ps', '-a', '-q', '--filter', 'name=^/' + smtp + '$']):
            print('Scoped fixture container cleanup: ' + smtp, flush=True)
            call(['docker', 'rm', '--force', smtp])
        if output(['docker', 'network', 'ls', '-q', '--filter', 'name=^' + network + '$']):
            print('Scoped fixture network cleanup: ' + network, flush=True)
            call(['docker', 'network', 'rm', network])
        check(all(output(['docker', 'inspect', '--format', '{{.State.StartedAt}}', item]) == value for item, value in before.items()), 'active development container identities/start times remain unchanged')
        report['fixture_root'] = str(fixture_directory)
        report_path.write_text(json.dumps(report, indent=2) + '\n')
        # Preserve failed source context for diagnosis; successful source is disposable.
        if report['result'] == 'PASS':
            shutil.rmtree(fixture_directory)
    print('Report: ' + str(report_path))


if __name__ == '__main__':
    main()
