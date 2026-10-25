"""Temporary owner-approved PHP/PostgreSQL risk; raw scanner output stays intact."""
from datetime import date
import hashlib
import json
from pathlib import Path
import re
import tarfile

import gosu_disposition
from runtime import ROOT, run


def validate_findings(report, policy):
    results = report.get('Results')
    if not isinstance(results, list) or not results:
        raise ValueError('Missing complete runtime scanner results.')
    accepted = []
    for result in results:
        if any(result.get(key) for key in ('Secrets', 'Misconfigurations', 'CustomResources')):
            raise ValueError('Non-vulnerability findings remain blocking.')
        for finding in result.get('Vulnerabilities', []):
            signature = gosu_disposition.finding_signature(result, finding)
            core_php = (signature['id'] in policy['php_cves']
                        and signature['package'] in ('php', 'php-src', 'php/php-src')
                        and signature['version'] == policy['php_version']
                        and signature['severity'] in ('HIGH', 'CRITICAL'))
            if signature not in policy['os_findings'] and not core_php:
                raise ValueError('Unapproved runtime finding remains blocking.')
            accepted.append(signature)
    if not accepted:
        raise ValueError('No assessed findings in failed runtime scan.')
    return accepted


def bind_php_report(report, installed, archive_path, policy):
    # Local production tags change with each fixture commit. Bind the complete
    # image config/rootfs to Docker inspection and the raw report to saved bytes.
    with tarfile.open(archive_path) as saved:
        entries = json.load(saved.extractfile('manifest.json'))
        if len(entries) != 1:
            raise ValueError('Expected one production image configuration.')
        raw = saved.extractfile(entries[0]['Config']).read()
    config = json.loads(raw)
    config_id = 'sha256:' + hashlib.sha256(raw).hexdigest()
    if (report.get('Metadata', {}).get('ImageID') != config_id
            or report.get('Metadata', {}).get('ImageConfig', {}).get('architecture') != policy['architecture']
            or config.get('architecture') != policy['architecture']
            or installed.get('Architecture') != policy['architecture']
            or config.get('os') != 'linux'
            or config.get('config') != installed.get('Config')
            or config.get('rootfs', {}).get('diff_ids') != installed.get('RootFS', {}).get('Layers')):
        raise ValueError('Runtime scan is not bound to the selected image.')
    return config_id


def apply(image, report_path, archive_path):
    marker = ROOT / '.git/runa-bootstrap.json'
    if marker.exists() or marker.is_symlink() or not (ROOT / 'tools/maintainer').is_dir():
        raise ValueError('Owner runtime acceptance is unavailable in customer installations.')
    path = ROOT / 'tools/maintainer/security/runtime-pending-upstream.json'
    policy = json.loads(path.read_text())
    if policy['status'] != 'accepted_risk_pending_upstream' or date.today() > date.fromisoformat(policy['review_by']):
        raise ValueError('Runtime risk acceptance expired or is not approved.')
    for source, expected in policy['source_hashes'].items():
        if hashlib.sha256((ROOT / source).read_bytes()).hexdigest() != expected:
            raise ValueError('Runtime risk evidence or Dockerfile changed.')
    gosu_path = ROOT / 'tools/maintainer/security/postgres-gosu-pending-upstream.json'
    gosu_policy = json.loads(gosu_path.read_text())
    if image == gosu_policy['image']:
        accepted = gosu_disposition.apply(image, report_path, archive_path, policy_path=gosu_path,
                                          allowed_statuses=('accepted_risk_pending_upstream',),
                                          additional_findings=policy['os_findings'])
        print('ACCEPTED_RISK_PENDING_UPSTREAM: exact PostgreSQL Brotli finding; raw report retained.', flush=True)
        disposition = Path(report_path).with_suffix('.disposition.json')
        evidence = json.loads(disposition.read_text())
        evidence['runtime_policy_sha256'] = hashlib.sha256(path.read_bytes()).hexdigest()
        disposition.write_text(json.dumps(evidence, indent=2) + '\n')
        return accepted
    if not re.fullmatch(r'runa-production:[0-9a-f]{40}', image):
        return False
    report = json.loads(Path(report_path).read_text())
    installed = json.loads(run(['docker', 'image', 'inspect', image], capture_output=True, text=True).stdout)[0]
    config_id = bind_php_report(report, installed, archive_path, policy)
    findings = validate_findings(report, policy)
    identity = json.loads(run(['docker', 'run', '--rm', '--read-only', '--network', 'none', '--cap-drop=ALL',
                               '--security-opt=no-new-privileges:true', '--entrypoint', 'php', installed['Id'],
                               '-r', 'echo json_encode([PHP_VERSION,hash_file("sha256",PHP_BINARY)]);'],
                              capture_output=True, text=True).stdout)
    if identity != [policy['php_version'], policy['php_binary_sha256']]:
        raise ValueError('PHP binary/version differs from the approved artifact.')
    evidence = {'status': policy['status'], 'image': image, 'image_id': installed['Id'], 'config_id': config_id,
                'policy_sha256': hashlib.sha256(path.read_bytes()).hexdigest(), 'php_identity': identity,
                'report_sha256': hashlib.sha256(Path(report_path).read_bytes()).hexdigest(),
                'accepted_findings': findings, 'review_by': policy['review_by']}
    disposition = Path(report_path).with_suffix('.disposition.json')
    disposition.write_text(json.dumps(evidence, indent=2) + '\n')
    disposition.chmod(0o600)
    print('ACCEPTED_RISK_PENDING_UPSTREAM: exact PHP 8.4.26 runtime findings; raw report retained, review by '
          + policy['review_by'], flush=True)
    return True
