"""First-install orchestration over established dev/production mechanics.

Host-only filesystem/Docker/network side effects. Never rewrites customer source,
rotates existing secrets, adopts unmanaged production data or reverses schemas.
"""
import argparse
import base64
import getpass
import ipaddress
import json
import os
from pathlib import Path
import re
import secrets
import ssl
import stat
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

import operations as ops
from repository_boundary import RepositoryBoundaryError
from runtime import ROOT, inside, run


class InstallationError(ValueError):
    """Fixed, safe installer diagnostics; external exceptions remain redacted."""


def host_checks():
    if sys.platform != 'linux' or sys.version_info < (3, 11):
        raise InstallationError('Installation requires native Linux and Python >=3.11.')
    for command, minimum in ((['git', '--version'], (2, 39)), (['docker', 'version', '--format', '{{.Server.Version}}'], (26, 0)), (['docker', 'compose', 'version', '--short'], (2, 35))):
        output = run(command, capture_output=True, text=True).stdout
        match = re.search(r'(\d+)\.(\d+)', output)
        if not match or tuple(map(int, match.groups())) < minimum:
            raise InstallationError('Require Git >=2.39, Docker Engine >=26 and Compose >=2.35 with daemon access.')


def private_json(path):
    descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
    with os.fdopen(descriptor) as stream:
        info = os.fstat(stream.fileno())
        if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077 or info.st_size > 65536:
            raise InstallationError('Settings must be an owned private regular JSON file, at most 64 KiB.')
        value = json.load(stream)
    if not isinstance(value, dict):
        raise InstallationError('Installer settings must be a JSON object.')
    return value


def regular_file(value, private=False):
    path = Path(value)
    if ',' in str(path) or not path.is_absolute() or path.is_symlink() or path.resolve() != path or not path.is_file():
        raise InstallationError('Certificate/key/CA inputs must be existing absolute regular paths without symlinks or commas.')
    info = path.stat()
    if info.st_size > 256 * 1024 or (private and (info.st_uid != os.getuid() or info.st_mode & 0o077)):
        raise InstallationError('Private TLS key must be owned/private; certificate inputs must be at most 256 KiB.')
    return path


def validate_settings(value, simulation):
    if set(value) - {'app_url', 'http_port', 'tls', 'smtp', 'backup', 'simulation_project'}:
        raise InstallationError('Unknown installer settings field.')
    # Reject newlines/NUL recursively before serializing any raw environment value.
    def strings(item):
        if isinstance(item, dict):
            for key, child in item.items():
                strings(key)
                strings(child)
        elif isinstance(item, list):
            raise InstallationError('Installer settings do not accept arrays.')
        elif isinstance(item, str) and any(char in item for char in '\r\n\0'):
            raise InstallationError('Installer strings must be single-line values.')
    strings(value)
    url = urllib.parse.urlsplit(value.get('app_url', ''))
    if url.scheme != 'https' or not url.hostname or url.username or url.password or url.query or url.fragment or url.path not in ('', '/'):
        raise InstallationError('Application URL must be a credential-free HTTPS origin.')
    if not re.fullmatch(r'[a-zA-Z0-9.-]+', url.hostname):
        raise InstallationError('Application hostname must be an ASCII DNS name or IPv4 address.')
    if not simulation and url.hostname in ('localhost', '127.0.0.1', '0.0.0.0'):
        raise InstallationError('Real production requires a publicly configured HTTPS hostname.')
    def port(item):
        if type(item) is not int or not 1 <= item <= 65535:
            raise InstallationError('Ports must be integers from 1 to 65535.')
        return item
    http = port(value.get('http_port', 8081))
    if http < 1024:
        raise InstallationError('Loopback HTTP port must be unprivileged.')
    tls, smtp, backup = (value.get(key) for key in ('tls', 'smtp', 'backup'))
    if not all(isinstance(item, dict) for item in (tls, smtp, backup)):
        raise InstallationError('TLS, SMTP and backup settings objects are required.')
    if set(tls) - {'mode', 'certificate', 'private_key', 'ca_file', 'https_port', 'bind'} or tls.get('mode') not in ('builtin', 'external'):
        raise InstallationError('Explicit TLS mode builtin/external and supported fields are required.')
    if tls['mode'] == 'builtin':
        regular_file(tls.get('certificate', ''))
        regular_file(tls.get('private_key', ''), private=True)
        port(tls.get('https_port', 443))
        if (url.port or 443) != tls.get('https_port', 443):
            raise InstallationError('HTTPS listener port must match the application URL.')
        address = ipaddress.ip_address(tls.get('bind', '0.0.0.0'))
        if address.version != 4 or (simulation and not address.is_loopback):
            raise InstallationError('Use an IPv4 listener; simulation HTTPS must bind to loopback.')
    elif any(key in tls for key in ('certificate', 'private_key', 'https_port', 'bind')):
        raise InstallationError('External termination does not accept built-in listener/certificate settings.')
    if tls.get('ca_file'):
        regular_file(tls['ca_file'])
    if set(smtp) - {'host', 'port', 'scheme', 'username', 'password', 'from', 'recipient'}:
        raise InstallationError('Unknown SMTP setting.')
    if not isinstance(smtp.get('host'), str) or not re.fullmatch(r'[a-zA-Z0-9.-]+', smtp['host']) or smtp['host'] in ('localhost', '127.0.0.1', 'mailpit', 'smtp.example.com'):
        raise InstallationError('A configured outbound SMTP hostname is required.')
    port(smtp.get('port'))
    if smtp.get('scheme') not in ('smtp', 'smtps'):
        raise InstallationError('SMTP scheme must be smtp or smtps; TLS is always required.')
    if not all(isinstance(smtp.get(key, ''), str) for key in ('username', 'password')) or bool(smtp.get('username')) != bool(smtp.get('password')):
        raise InstallationError('SMTP username/password must be supplied together.')
    for key in ('from', 'recipient'):
        if not isinstance(smtp.get(key), str) or not re.fullmatch(r'[^\s<>@]+@[^\s<>@]+\.[^\s<>@]+', smtp[key]):
            raise InstallationError('Explicit sender and verification recipient email addresses are required.')
    if set(backup) != {'directory', 'retention'} or type(backup['retention']) is not int or not 1 <= backup['retention'] <= 10000:
        raise InstallationError('Backup directory and positive bounded retention are required.')
    directory = Path(backup['directory'])
    if not directory.is_absolute() or directory == Path('/') or directory.resolve() != directory:
        raise InstallationError('Backup directory must be a dedicated absolute path without symlink components.')
    if directory.exists() and (directory.stat().st_uid != os.getuid() or directory.stat().st_mode & 0o077):
        raise InstallationError('Backup directory must be private and owned by the operator.')
    project = value.get('simulation_project')
    if project and (not simulation or not re.fullmatch(r'runa-prod-simulation-[a-z0-9-]{1,40}', project)):
        raise InstallationError('Custom project names are restricted to isolated runa-prod-simulation-* acceptance.')
    return value


def collect_settings():
    print('Production setup requires supplied certificates or an explicitly configured TLS terminator, SMTP and backup settings.')
    url = input('HTTPS application origin: ').strip()
    mode = input('TLS mode [builtin/external; default builtin]: ').strip() or 'builtin'
    tls = {'mode': mode}
    if mode == 'builtin':
        tls.update(certificate=input('Absolute certificate/full-chain path: ').strip(),
                   private_key=input('Absolute private key path: ').strip(),
                   https_port=int(input('Published HTTPS port [443]: ').strip() or '443'), bind='0.0.0.0')
    ca = input('Custom trusted HTTPS CA file (empty for system trust): ').strip()
    if ca:
        tls['ca_file'] = ca
    smtp = {'host': input('SMTP host: ').strip(), 'port': int(input('SMTP port [587]: ').strip() or '587'),
            'scheme': input('SMTP scheme [smtp/smtps]: ').strip() or 'smtp',
            'username': input('SMTP username (empty for authorized relay): ').strip(),
            'password': getpass.getpass('SMTP password: '), 'from': input('Sender email: ').strip(),
            'recipient': input('Installation verification recipient email: ').strip()}
    return {'app_url': url, 'tls': tls, 'smtp': smtp,
            'http_port': int(input('Loopback HTTP port [8081]: ').strip() or '8081'),
            'backup': {'directory': input('Dedicated absolute backup directory: ').strip(),
                       'retention': int(input('Backup retention [7]: ').strip() or '7')}}


def scaffold(sha, mode):
    marker = ops.STATE / ('scaffold-' + mode + '.json')
    if marker.is_symlink():
        raise InstallationError('Scaffold state must not be a symlink.')
    if marker.exists():
        return
    base = ROOT / 'src/app'
    if base.is_symlink() or any(parent.is_symlink() for parent in base.parents):
        raise InstallationError('Application scaffold must not traverse symlinks.')
    # Existing delivered application files are the initial scaffold. Never replace
    # a customer file, including a manifest with an independently changed version.
    manifest = base / 'application.json'
    if manifest.is_symlink() or not manifest.is_file():
        raise InstallationError('Delivered application scaffold/manifest is missing; restore the verified source before installation.')
    for name in ('Actions', 'modules', 'config', 'docs', 'lang', 'database/migrations', 'database/factories', 'resources/js', 'tests/Feature'):
        path = base / name
        if path.resolve() != path or path.is_symlink():
            raise InstallationError('Application extension directories must not traverse symlinks.')
        path.mkdir(parents=True, exist_ok=True)
    ops.atomic(marker, {'schema_version': 1, 'source': sha, 'created_at': ops.stamp(), 'application_version': json.loads(manifest.read_text())['version']})


def provision_tls(settings):
    if settings['tls']['mode'] != 'builtin':
        return
    tls = settings['tls']
    import management
    image = management.ensure_image()
    # Certificate parsing/key validation uses the same pinned container PHP runtime.
    code = '$c=openssl_x509_read(file_get_contents("/inputs/cert")); $k=openssl_pkey_get_private(file_get_contents("/inputs/key")); $d=$c ? openssl_x509_parse($c) : false; exit($c && $k && openssl_x509_check_private_key($c,$k) && $d["validFrom_time_t"]<=time() && $d["validTo_time_t"]>time()+86400 ? 0 : 1);'
    command = ['docker', 'run', '--rm', '--network', 'none', '--read-only', '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
               '--user', str(os.getuid()) + ':' + str(os.getgid()),
               '--mount', 'type=bind,src=' + str(regular_file(tls['certificate'])) + ',dst=/inputs/cert,readonly',
               '--mount', 'type=bind,src=' + str(regular_file(tls['private_key'], True)) + ',dst=/inputs/key,readonly',
               '--entrypoint', 'php', image, '-r', code]
    try:
        run(command, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except subprocess.CalledProcessError:
        raise InstallationError('TLS certificate/key validation failed. Supply a matching, readable, unencrypted key and a certificate valid for at least one more day.') from None
    directory = ops.STATE / 'tls'
    directory.mkdir(mode=0o700, exist_ok=True)
    if directory.is_symlink() or directory.stat().st_uid != os.getuid() or directory.stat().st_mode & 0o077:
        raise InstallationError('TLS runtime directory must be private and owned by the operator.')
    for key, name in (('certificate', 'certificate.pem'), ('private_key', 'private-key.pem')):
        # Parent remains 700; individual read-only mounts let nonroot Nginx read
        # files independent of host UID without exposing the private host directory.
        destination = directory / name
        if destination.is_symlink():
            raise InstallationError('TLS runtime inputs must not be symlinks.')
        descriptor, temporary = tempfile.mkstemp(dir=directory)
        try:
            with os.fdopen(descriptor, 'wb') as stream:
                stream.write(Path(tls[key]).read_bytes())
                os.fchmod(stream.fileno(), 0o444)
            os.replace(temporary, destination)
        finally:
            if os.path.exists(temporary):
                os.unlink(temporary)
    config = (ROOT / 'docker/nginx/production.conf').read_text().replace('listen 8080;', 'listen 8080;\n        listen 8443 ssl;\n        ssl_certificate /run/runa-tls/certificate.pem;\n        ssl_certificate_key /run/runa-tls/private-key.pem;\n        ssl_protocols TLSv1.2 TLSv1.3;')
    nginx = directory / 'nginx.conf'
    if nginx.is_symlink():
        raise InstallationError('TLS runtime configuration must not be a symlink.')
    if nginx.exists():
        if nginx.read_text() != config:
            raise InstallationError('Generated TLS configuration was customized. Preserve it and inspect before resuming.')
    else:
        nginx.write_text(config)
        nginx.chmod(0o444)
    mounts = [{'type': 'bind', 'source': str(directory / name), 'target': target, 'read_only': True, 'bind': {'create_host_path': False}}
              for name, target in (('nginx.conf', '/etc/nginx/nginx.conf'), ('certificate.pem', '/run/runa-tls/certificate.pem'), ('private-key.pem', '/run/runa-tls/private-key.pem'))]
    overlay = {'services': {'nginx': {'ports': [str(tls.get('bind', '0.0.0.0')) + ':' + str(tls.get('https_port', 443)) + ':8443'], 'volumes': mounts}}}
    # JSON is valid Compose YAML; use the standard library, no host YAML dependency.
    ops.atomic(directory / 'compose.json', overlay)


def provision(settings, sha, simulation):
    smtp = settings['smtp']
    values = ops.read_env(ROOT / '.env.prod.example')
    values.update(APP_URL=settings['app_url'], APP_KEY='base64:' + base64.b64encode(secrets.token_bytes(32)).decode(), DB_PASSWORD=secrets.token_urlsafe(36),
                  MAIL_HOST=smtp['host'], MAIL_PORT=str(smtp['port']), MAIL_SCHEME=smtp['scheme'], MAIL_USERNAME=smtp.get('username', ''),
                  MAIL_PASSWORD=smtp.get('password', ''), MAIL_FROM_ADDRESS=smtp['from'], MAIL_REQUIRE_TLS='true')
    if (ROOT / '.env.prod').exists():
        existing = ops.read_env(ROOT / '.env.prod')
        if not existing.get('APP_KEY') or not existing.get('DB_PASSWORD'):
            raise InstallationError('Existing application secrets are incomplete; inspect before resuming.')
        values = existing
    mappings = {'.env.prod': values, '.env.prod.database': {'POSTGRES_PASSWORD': secrets.token_urlsafe(36), 'APP_DB_PASSWORD': values['DB_PASSWORD']},
                '.env.prod.backup': {'BACKUP_DIRECTORY': settings['backup']['directory'], 'BACKUP_RETENTION': str(settings['backup']['retention'])},
                '.env.prod.compose': {'RUNA_IMAGE_TAG': sha, 'RUNA_HTTP_PORT': str(settings.get('http_port', 8081)), 'RUNA_PRODUCTION_MODE': 'simulation' if simulation else 'real',
                                      'RUNA_TLS_MODE': settings['tls']['mode']}}
    if settings.get('simulation_project'):
        mappings['.env.prod.compose']['RUNA_SIMULATION_PROJECT'] = settings['simulation_project']
    for name, mapping in mappings.items():
        path = ROOT / name
        # Resume writes only missing files; keep every previously generated secret.
        if not path.exists():
            descriptor, temporary = tempfile.mkstemp(prefix='.env.install-', dir=ROOT)
            try:
                with os.fdopen(descriptor, 'w') as stream:
                    stream.write(''.join(key + '=' + value + '\n' for key, value in mapping.items()))
                os.link(temporary, path)
            finally:
                os.unlink(temporary)
        elif path.is_symlink() or path.stat().st_uid != os.getuid() or path.stat().st_mode & 0o077:
            raise InstallationError('Existing production configuration must be private regular owned files.')
    # A partial write must not invent a new DB credential on resume.
    existing = ops.read_env(ROOT / '.env.prod')
    database = ops.read_env(ROOT / '.env.prod.database')
    if existing.get('DB_PASSWORD') != database.get('APP_DB_PASSWORD'):
        raise InstallationError('Partial configuration credentials differ; preserve files and reconcile before resuming.')
    ops.backup_root()


def resume_settings(settings_file, simulation):
    path = ops.STATE / 'install-settings.json'
    settings = validate_settings(private_json(path), simulation)
    if not settings_file:
        return settings
    candidate = validate_settings(private_json(settings_file), simulation)
    if {key: value for key, value in candidate.items() if key != 'smtp'} != {key: value for key, value in settings.items() if key != 'smtp'}:
        raise InstallationError('Resume preserves installation infrastructure. Only explicit SMTP corrections may change in the settings file.')
    if candidate['smtp'] != settings['smtp']:
        env_path = ROOT / '.env.prod'
        if env_path.exists():
            # Explicit operator SMTP correction never changes generated app/DB keys.
            if env_path.is_symlink() or env_path.stat().st_uid != os.getuid() or env_path.stat().st_mode & 0o077:
                raise InstallationError('Production configuration must remain private and owned.')
            values = ops.read_env(env_path)
            smtp = candidate['smtp']
            values.update(MAIL_HOST=smtp['host'], MAIL_PORT=str(smtp['port']), MAIL_SCHEME=smtp['scheme'], MAIL_USERNAME=smtp.get('username', ''), MAIL_PASSWORD=smtp.get('password', ''), MAIL_FROM_ADDRESS=smtp['from'], MAIL_REQUIRE_TLS='true')
            descriptor, temporary = tempfile.mkstemp(prefix='.env.install-', dir=ROOT)
            try:
                with os.fdopen(descriptor, 'w') as stream:
                    stream.write(''.join(key + '=' + value + '\n' for key, value in values.items()))
                os.replace(temporary, env_path)
            finally:
                if os.path.exists(temporary):
                    os.unlink(temporary)
        ops.atomic(path, candidate)
    return candidate


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        raise InstallationError('HTTPS verification must not redirect to a different origin/path.')


def verify_endpoints(settings, release):
    context = ssl.create_default_context(cafile=settings['tls'].get('ca_file'))
    opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect(), urllib.request.HTTPSHandler(context=context))
    try:
        for path in ('/', '/api/ready'):
            with opener.open(settings['app_url'].rstrip('/') + path, timeout=15) as response:
                body = response.read(65537)
                if response.status != 200 or len(body) > 65536 or (path == '/api/ready' and json.loads(body).get('status') != 'ok'):
                    raise InstallationError('HTTPS application/readiness verification failed.')
    except (OSError, urllib.error.URLError, ValueError):
        raise InstallationError('HTTPS verification failed; check DNS, listener, TLS chain/hostname and readiness. Installation is not complete.') from None
    ops.artisan(['runa:install:verify-mail', settings['smtp']['recipient']], release)


def resume_is_owned(journal):
    pending_path = ops.STATE / 'in-progress.json'
    if pending_path.exists():
        pending = json.loads(pending_path.read_text())
        if pending.get('target') != journal['sha'] or pending.get('previous') is not None:
            raise InstallationError('Pending operation is not this initial installation; use deployment recovery.')
    ledger = ops.STATE / 'install-resources.json'
    if not ledger.exists():
        return False
    recorded = json.loads(ledger.read_text())
    if recorded.get('sha') != journal['sha']:
        raise InstallationError('Initial installation resource record differs from this target.')
    config = json.loads(ops.dc(['config', '--format', 'json'], capture_output=True, text=True).stdout)
    if recorded.get('project') != config['name']:
        raise InstallationError('Initial installation project differs from its resource record.')
    allowed = {item['name'] for item in config.get('volumes', {}).values()}
    actual = set(run(['docker', 'volume', 'ls', '--filter', 'label=com.docker.compose.project=' + config['name'], '--format', '{{.Name}}'], capture_output=True, text=True).stdout.splitlines())
    if actual - allowed:
        raise InstallationError('Unexpected installation volumes; inspect ownership before recovery.')
    release = str(ops.STATE / 'releases' / journal['sha'])
    containers = ops.dc(['ps', '--all', '--quiet'], capture_output=True, text=True).stdout.splitlines()
    for container in containers:
        data = json.loads(run(['docker', 'inspect', container], capture_output=True, text=True).stdout)[0]
        if data['Config']['Labels'].get('com.docker.compose.project.working_dir') != release:
            raise InstallationError('Installation resources do not match the recorded source; inspect before recovery.')
    return True


def development_context():
    project = os.environ.get('COMPOSE_PROJECT_NAME', 'runa-dev')
    if not re.fullmatch(r'runa-dev(?:-[a-z0-9-]+)?', project):
        raise InstallationError('Development installer requires an explicitly isolated runa-dev project.')
    containers = run(['docker', 'ps', '-a', '--filter', 'label=com.docker.compose.project=' + project, '--format', '{{.ID}}'], capture_output=True, text=True).stdout.splitlines()
    for container in containers:
        info = json.loads(run(['docker', 'inspect', container], capture_output=True, text=True).stdout)[0]
        if info['Config']['Labels'].get('com.docker.compose.project.working_dir') != str(ROOT):
            raise InstallationError('Development project belongs to another checkout. Preserve it; use the owning workspace or a separate project.')


def install(mode, target, simulation=False, settings_file=None, profile=None, resume=False, non_interactive=False):
    from repository_boundary import assert_installation
    assert_installation(ROOT)
    mode = 'prod' if mode == 'production' else mode
    sha = ops.resolve(target)
    head = run(['git', 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip()
    if sha != head:
        raise InstallationError('First installation must target the exact checked-out commit; acquire the selected release first.')
    journal_path = ops.STATE / ('install-' + mode + '.json')
    journal = private_json(journal_path) if journal_path.exists() else None
    if journal and (journal.get('sha') != sha or journal.get('simulation') != simulation):
        raise InstallationError('Existing installer context differs. Use platform deployment/update for version changes.')
    if journal and journal['state'] == 'complete':
        print('Installation already complete; application source, settings, secrets and data preserved. Use dev up or deploy for normal operation.')
        return
    if journal and not resume:
        raise InstallationError('Installation is incomplete. Preserve files and use the owner-delivered installer command with --resume.')
    if not journal and resume:
        raise InstallationError('There is no recorded installation to resume.')
    if journal:
        if profile and profile != journal.get('profile'):
            raise InstallationError('Resume must preserve the originally selected resource profile.')
        profile = journal.get('profile')
    if mode == 'dev':
        if simulation or settings_file:
            raise InstallationError('Development install does not use production settings or simulation.')
        if (ops.STATE / 'in-progress.json').exists():
            raise InstallationError('Resolve the pending production operation before development mutation.')
    elif mode == 'prod':
        if profile:
            raise InstallationError('Development resource profiles do not apply to production.')
        if not journal and any((ROOT / name).exists() or (ROOT / name).is_symlink() for name in ('.env.prod', '.env.prod.database', '.env.prod.compose', '.env.prod.backup')):
            raise InstallationError('Installer refuses existing production settings without its own recovery journal. Use deploy for upgrades.')
        if not simulation and ((ROOT / '.env').exists() or (ROOT / '.env.dev.local').exists()):
            raise InstallationError('Real installation requires a dedicated production clone without development settings.')
    else:
        raise InstallationError('Install mode must be dev or prod.')
    if not journal:
        if mode == 'prod':
            if not settings_file and (non_interactive or not sys.stdin.isatty()):
                raise InstallationError('Noninteractive production install requires --settings-file with explicit TLS/SMTP/backup choices.')
            settings = validate_settings(private_json(settings_file) if settings_file else collect_settings(), simulation)
            dirty = run(['git', 'status', '--porcelain', '--untracked-files=all'], capture_output=True, text=True).stdout.strip()
            if dirty:
                raise InstallationError('First production installation requires clean verified source.')
        else:
            settings = None
        journal = {'schema_version': 1, 'mode': mode, 'sha': sha, 'simulation': simulation, 'state': 'started', 'started_at': ops.stamp(), 'profile': profile}
        ops.atomic(journal_path, journal)
        if settings:
            ops.atomic(ops.STATE / 'install-settings.json', settings)
    try:
        scaffold(sha, mode)
        if mode == 'dev':
            development_context()
            helper = ROOT / 'tools/project/executors/dev.py'
            if profile:
                run(['python3', str(helper), 'profile', profile])
            run(['python3', str(helper), 'prepare'])
            run(['python3', str(helper), 'up'])
        else:
            settings = resume_settings(settings_file if resume else None, simulation)
            if not simulation:
                run(['git', 'sparse-checkout', 'init', '--no-cone'])
                run(['git', 'sparse-checkout', 'set', '--no-cone', '--stdin'], input='\n'.join(ops.SPARSE) + '\n', text=True)
                ops.assert_sparse(ROOT)
            if settings['tls']['mode'] == 'builtin':
                provision_tls(settings)
            provision(settings, sha, simulation)
            current = ops.read_state()['current']
            if current and current['sha'] == sha and not (ops.STATE / 'in-progress.json').exists():
                ops.health(Path(current['path']), sparse=True)
                verify_endpoints(settings, Path(current['path']))
            else:
                owned_resume = resume_is_owned(journal) if resume else False
                ops.deploy(sha, simulation, initial_resume=owned_resume, verification=lambda release: verify_endpoints(settings, release))
            if os.environ.get('RUNA_HOST_WORKER') != '1':
                import host_executor
                host_executor.ensure()
        journal.update(state='complete', completed_at=ops.stamp())
        ops.atomic(journal_path, journal)
        print('PASS: ' + mode + ' installation complete; application scaffold and generated credentials are preserved on repeat.')
    except BaseException:
        journal.update(state='failed', failed_at=ops.stamp())
        ops.atomic(journal_path, journal)
        print('Installation failed; source/settings/data are preserved. Correct the cause and repeat the same command with --resume.', file=sys.stderr)
        raise


def main(arguments):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('mode', choices=('dev', 'prod', 'production'))
    parser.add_argument('target')
    parser.add_argument('--settings-file')
    parser.add_argument('--profile', choices=('low', 'standard', 'high'))
    parser.add_argument('--simulation', action='store_true')
    parser.add_argument('--resume', action='store_true')
    parser.add_argument('--non-interactive', action='store_true')
    args = parser.parse_args(arguments)
    if inside():
        print('Run installation in a host terminal; containers return a host plan and never use a Docker socket.', file=sys.stderr)
        return 1
    try:
        host_checks()
        with ops.lock():
            install(**vars(args))
        return 0
    except KeyboardInterrupt:
        print('Installation interrupted; preserve state/settings/data and use --resume for this initial installation.', file=sys.stderr)
        return 130
    except (ValueError, OSError, KeyError, TypeError, EOFError, subprocess.CalledProcessError) as error:
        # Do not echo private JSON, filenames containing credentials or provider stderr.
        print(str(error) if isinstance(error, (InstallationError, RepositoryBoundaryError)) else 'Installation did not complete. Check explicit inputs and private state; existing data and secrets have been preserved.', file=sys.stderr)
        return 1
