"""Explicit scanners and content-bound evidence of a real agent security review."""
import hashlib
import json
import os
from pathlib import Path
import subprocess
import shutil
import tempfile

import production
from runtime import ROOT, inside, run, app_command


def surface(reference='main'):
    files = set(run(['git', 'diff', '--name-only', reference, '--'], capture_output=True, text=True).stdout.splitlines())
    files.update(run(['git', 'ls-files', '--others', '--exclude-standard'], capture_output=True, text=True).stdout.splitlines())
    return {name: hashlib.sha256((ROOT / name).read_bytes()).hexdigest() if (ROOT / name).is_file() else 'deleted'
            for name in sorted(files) if not name.startswith(('docs/', '.agents/'))}


def review():
    path = ROOT / '.runtime/security-review.json'
    if not path.is_file():
        raise ValueError('Missing actual agent review evidence at .runtime/security-review.json. Review the changed surface; do not auto-attest.')
    data = json.loads(path.read_text())
    if not data.get('reviewer') or not data.get('reviewed_at') or not data.get('report') or data.get('blocking_findings') != [] or data.get('surface') != surface():
        raise ValueError('Security review evidence is incomplete, stale or has blocking findings.')
    report = ROOT / data['report']
    if not report.is_file() or hashlib.sha256(report.read_bytes()).hexdigest() != data.get('report_sha256'):
        raise ValueError('Security review report is missing or changed.')
    print('PASS: actual agent security review evidence matches the current changed surface.')


def trivy():
    reports = ROOT / '.runtime/security'
    reports.mkdir(parents=True, exist_ok=True, mode=0o700)
    reports.chmod(0o700)
    failures = []
    def scan_command(arguments, label):
        print('Scanning: ' + label, flush=True)
        try:
            run(arguments)
        except subprocess.CalledProcessError as error:
            failures.append(label + ' (exit ' + str(error.returncode) + ')')
            print('FAIL: ' + failures[-1] + '; continuing to collect remaining reports.', flush=True)
    def report(name):
        path = reports / name
        path.touch(mode=0o600, exist_ok=True)
        path.chmod(0o600)
        return str(path) if scanner.is_file() else '/reports/' + name
    scanner = ROOT / '.runtime/tools/trivy'
    if scanner.is_file():
        prefix = [str(scanner)]
    elif not inside() and shutil.which('docker'):
        prefix = ['docker', 'run', '--rm', '-v', str(ROOT) + ':/scan:ro',
                  '-v', 'runa-trivy-cache:/root/.cache/trivy', '-v', str(reports) + ':/reports', 'aquasec/trivy:0.76.0']
    else:
        raise ValueError('Run image scanning on the Docker host. Repository scanning also accepts pinned Trivy 0.76.0 at .runtime/tools/trivy.')
    output = run(prefix + ['--version'], capture_output=True, text=True).stdout
    if 'Version: 0.76.0' not in output:
        raise ValueError('Trivy version must be exactly 0.76.0.')
    print('Private Trivy reports: ' + str(reports), flush=True)
    scan_command(prefix + ['fs', '--include-dev-deps', '--format', 'json', '--output', report('repository.json'), '--scanners', 'vuln,secret,misconfig', '--severity', 'HIGH,CRITICAL', '--exit-code', '1',
                  '--skip-dirs', '.git,.runtime,node_modules,vendor', '--skip-files', '**/.env,**/.env.*,**/storage/**', str(ROOT) if scanner.is_file() else '/scan'], 'repository')
    if inside():
        raise ValueError('Repository scan finished; final runtime-image scanning still requires the Docker host.')
    import operations
    release = operations.active_release()
    selected = operations.dc(['config', '--images'], release, capture_output=True, text=True).stdout.splitlines()
    for image in sorted(set(selected)):
        # Feed an image archive; Docker socket never enters the scanner container.
        archive = ROOT / '.runtime' / 'trivy-image.tar'
        try:
            run(['docker', 'image', 'save', '-o', str(archive), image])
            if scanner.is_file():
                scan = prefix
                target = str(archive)
            else:
                scan = ['docker', 'run', '--rm', '-v', str(archive) + ':/image.tar:ro',
                        '-v', 'runa-trivy-cache:/root/.cache/trivy', '-v', str(reports) + ':/reports', 'aquasec/trivy:0.76.0']
                target = '/image.tar'
            scan_command(scan + ['image', '--format', 'json', '--output', report('image-' + hashlib.sha256(image.encode()).hexdigest()[:12] + '.json'), '--input', target, '--scanners', 'vuln,secret', '--severity', 'HIGH,CRITICAL', '--exit-code', '1'], image)
        finally:
            archive.unlink(missing_ok=True)
    try:
        dependencies()
    except subprocess.CalledProcessError as error:
        failures.append('dependency audit (exit ' + str(error.returncode) + ')')
    if failures:
        raise ValueError('Security checks failed: ' + '; '.join(failures) + '. Inspect the private JSON reports.')
    print('PASS: repository, exact production images and locked dependency audits.')


def dependencies():
    run(app_command(['composer', 'audit']))
    run(app_command(['pnpm', 'audit', '--audit-level=high']))


def secrets_scan():
    if not inside():
        run(app_command(['./bin/security', 'secrets']))
        return
    names = run(['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'], capture_output=True).stdout.decode().split('\0')
    with tempfile.TemporaryDirectory(prefix='runa-secret-scan-') as folder:
        config = Path(folder) / 'scanner.toml'
        config.write_text('[extend]\nuseDefault = true\n')
        flags = ['--config', str(config), '--gitleaks-ignore-path', str(Path(folder) / '.no-ignore'), '--ignore-gitleaks-allow', '--redact', '--exit-code', '1', '--no-banner']
        clean_env = {key: value for key, value in os.environ.items() if not key.startswith('GITLEAKS_')}
        run(['.runtime/tools/gitleaks', 'git', '.', *flags], env=clean_env)
        snapshot = Path(folder) / 'content'
        snapshot.mkdir()
        for name in names:
            if not name:
                continue
            source = ROOT / name
            if not source.exists() and not source.is_symlink():
                continue
            destination = snapshot / name
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_bytes(os.readlink(source).encode() if source.is_symlink() else source.read_bytes())
        run(['.runtime/tools/gitleaks', 'dir', str(snapshot), *flags], env=clean_env)
