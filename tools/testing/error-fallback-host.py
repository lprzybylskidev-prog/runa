#!/usr/bin/env python3
"""Host-only real production Nginx tests. Isolated disposable resources, no DB or deployment."""
import hashlib
import json
from pathlib import Path
import secrets
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[2]


def run(args, **kwargs):
    return subprocess.run(args, cwd=ROOT, check=True, **kwargs)


def output(args):
    return run(args, capture_output=True, text=True).stdout.strip()


def main():
    if len(sys.argv) != 1:
        raise SystemExit('Usage: python3 tools/testing/error-fallback-host.py (native host only)')
    if Path('/.dockerenv').exists():
        raise SystemExit('Run this test on the native Linux host; the devcontainer has no Docker socket.')
    report = ROOT / 'artifacts/playwright/ui-foundations/error-fallback-host.json'
    report.parent.mkdir(parents=True, exist_ok=True)
    report.write_text(json.dumps({'result': 'RUNNING'}, indent=2) + '\n')
    compose = ['docker', 'compose', '--env-file', '.env.dev.local', '-f', 'compose.yaml', '-f', '.devcontainer/compose.resources.yaml']
    app = output(compose + ['ps', '-q', 'app'])
    if not app:
        raise SystemExit('The existing development app must be running to supply its Python-enabled fixture image.')
    fixture_image = output(['docker', 'inspect', '--format', '{{.Image}}', app])
    token = secrets.token_hex(5)
    name = 'runa-rm004-errors-' + token
    image = 'runa-rm004-errors-nginx:' + token
    if subprocess.run(['docker', 'image', 'inspect', image], capture_output=True).returncode == 0:
        raise SystemExit('Disposable image tag already exists; refusing to overwrite it.')
    network = name + '-network'
    frontend, backend = name + '-nginx', name + '-fastcgi'
    checks = []
    created = []
    build_started = False
    verification_passed = False
    def check(condition, label):
        if not condition:
            raise AssertionError(label)
        checks.append(label)
        print('PASS: ' + label, flush=True)
    with tempfile.TemporaryDirectory(prefix=name + '-') as folder:
        marker = Path(folder)
        marker.chmod(0o755)
        try:
            # A disposable tag, never an immutable release tag. Build uses locked production stages.
            build_started = True
            run(['docker', 'build', '--target', 'production-nginx', '--label', 'runa.task=RM-2026-004-point5', '--label', 'runa.test.run=' + token, '-t', image, '.'])
            run(['docker', 'run', '--rm', '--read-only', '--tmpfs', '/tmp', '--entrypoint', 'nginx', image, '-t'])
            check(True, 'production Nginx configuration is valid')
            run(['docker', 'network', 'create', '--label', 'runa.task=RM-2026-004-point5', '--label', 'runa.test.run=' + token, network])
            created.append(('network', network))
            run(['docker', 'run', '-d', '--name', frontend, '--network', network,
                 '--label', 'runa.task=RM-2026-004-point5', '--label', 'runa.test.run=' + token, '--read-only', '--tmpfs', '/tmp',
                 '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
                 '--mount', 'type=bind,src=' + str(marker) + ',dst=/run/runa-maintenance,readonly',
                 '-p', '127.0.0.1::8080', image])
            created.append(('container', frontend))
            port = output(['docker', 'port', frontend, '8080/tcp']).rsplit(':', 1)[1]
            base = 'http://127.0.0.1:' + port
            def fetch(path, headers=None, method='GET'):
                request = urllib.request.Request(base + path, headers=headers or {}, method=method)
                try:
                    response = urllib.request.urlopen(request, timeout=10)
                except urllib.error.HTTPError as error:
                    response = error
                with response:
                    return response.status, response.headers, response.read()
            for attempt in range(40):
                try:
                    status, headers, body = fetch('/')
                    break
                except (urllib.error.URLError, TimeoutError):
                    time.sleep(.25)
            else:
                raise AssertionError('Nginx did not start without PHP.')
            check(status == 503 and b'Przerwa techniczna' in body, 'Nginx starts and serves branded PL 503 without PHP or database')
            check(b'minimal-page' in body and b'runa-art' in body and b'<script' not in body, 'outage serves the actual MinimalLayout without JavaScript')
            check(headers['Retry-After'] == '120' and 'no-store' in headers['Cache-Control'], 'fallback preserves retry and no-store headers')
            check("style-src 'self'" in headers['Content-Security-Policy'] and "'unsafe-inline'" not in headers['Content-Security-Policy'], 'fallback retains restrictive CSP')
            status, headers, body = fetch('/?lang=en&theme=dark')
            check(status == 503 and b'Service unavailable' in body, 'EN emergency localization')
            check(b'data-theme="dark"' in body, 'emergency theme selection')
            status, headers, body = fetch('/api/ready')
            check(status == 503 and headers.get_content_type() == 'application/json' and json.loads(body)['code'] == 'service_unavailable', 'API outage is JSON with status 503')
            status, headers, body = fetch('/missing', {'Accept': 'application/problem+json'})
            check(status == 503 and headers.get_content_type() == 'application/json', 'negotiated JSON outage')
            check(fetch('/runa-error/pages/503-pl-system.html')[0] == 404, 'emergency HTML is internal')
            check(fetch('/runa-error/pages/503.json')[0] == 404, 'emergency JSON is internal')
            for asset in ('styles.css', 'runa-logo.png', 'IBMPlexMono-Regular.woff2', 'IBMPlexMono-SemiBold.woff2'):
                check(fetch('/runa-error/' + asset)[0] == 200, 'independent static asset ' + asset)
            check(fetch('/', method='HEAD')[0] == 503, 'HEAD preserves 503')
            run(['docker', 'run', '-d', '--name', backend, '--network', network, '--network-alias', 'app',
                 '--label', 'runa.task=RM-2026-004-point5', '--label', 'runa.test.run=' + token, '--entrypoint', 'python3',
                 '--read-only', '--tmpfs', '/tmp', '--cap-drop=ALL', '--security-opt=no-new-privileges:true',
                 '--mount', 'type=bind,src=' + str(ROOT / 'tools/testing/error-fastcgi-fixture.py') + ',dst=/fixture.py,readonly',
                 fixture_image, '/fixture.py'])
            created.append(('container', backend))
            # Nginx's negative DNS cache may live for the configured ten seconds.
            for _ in range(60):
                if fetch('/')[0] == 200:
                    break
                time.sleep(.25)
            check(fetch('/')[0] == 200, 'PHP recovery works without Nginx restart')
            for path, expected in (('/api/forbidden', 403), ('/api/missing', 404), ('/api/conflict', 409), ('/api/ready', 503), ('/api/upstream-502', 502), ('/unavailable', 503)):
                status, headers, body = fetch(path)
                check(status == expected and b'fixture response' in body.lower() and headers['Retry-After'] == '17', 'preserve PHP status/body/retry contract ' + path)
            (marker / 'enabled').write_text('maintenance\n')
            check(fetch('/')[0] == 503 and b'Przerwa techniczna' in fetch('/')[2], 'explicit maintenance overrides a healthy PHP peer')
            check(json.loads(fetch('/api/forbidden')[2])['code'] == 'service_unavailable', 'explicit maintenance API response remains JSON')
            (marker / 'enabled').unlink()
            check(fetch('/')[0] == 200, 'clearing maintenance restores the PHP response')
            run(['docker', 'stop', backend])
            check(fetch('/')[0] == 503, 'stopping PHP leaves Nginx serving 503')
            report.write_text(json.dumps({'result': 'PASS', 'checks': checks,
                'source_sha': output(['git', 'rev-parse', 'HEAD']), 'nginx_config_sha256': hashlib.sha256((ROOT / 'docker/nginx/production.conf').read_bytes()).hexdigest()}, indent=2) + '\n')
            verification_passed = True
        except BaseException:
            report.write_text(json.dumps({'result': 'FAIL', 'passed_checks': checks}, indent=2) + '\n')
            if ('container', frontend) in created:
                subprocess.run(['docker', 'logs', frontend], check=False)
            raise
        finally:
            # Only exact identities created by this invocation; no volumes, prune or builder-cache removal.
            scoped = []
            cleanup_errors = []
            for kind, identity in ([('image', image)] if build_started else []) + created:
                inspected = subprocess.run(['docker', kind, 'inspect', identity], capture_output=True, text=True)
                if inspected.returncode:
                    continue
                metadata = json.loads(inspected.stdout)[0]
                labels = metadata.get('Labels') if kind == 'network' else metadata.get('Config', {}).get('Labels')
                if not labels or labels.get('runa.test.run') != token:
                    cleanup_errors.append('Uncertain ownership; preserved ' + identity)
                    continue
                scoped.append((kind, identity, metadata['Id']))
            print('Scoped cleanup after identity/ownership inspection: ' + json.dumps(scoped), flush=True)
            removed = []
            for kind, identity, resource_id in reversed(scoped):
                args = ['docker', 'rm', '-f', resource_id] if kind == 'container' else ['docker', kind, 'rm', identity]
                result = subprocess.run(args, check=False)
                if result.returncode:
                    cleanup_errors.append('Removal failed: ' + identity)
                else:
                    removed.append(identity)
            evidence = json.loads(report.read_text())
            evidence['removed_resources'] = removed
            evidence['cleanup_errors'] = cleanup_errors
            if cleanup_errors:
                evidence['result'] = 'FAIL'
            report.write_text(json.dumps(evidence, indent=2) + '\n')
            if cleanup_errors and verification_passed:
                raise RuntimeError('; '.join(cleanup_errors))
            print('Preserved: development/test services and databases, all durable volumes, model/Qdrant caches, backups, reports and shared build caches.', flush=True)
    print('PASS: real production Nginx emergency fallback; report: ' + str(report))


if __name__ == '__main__':
    main()
