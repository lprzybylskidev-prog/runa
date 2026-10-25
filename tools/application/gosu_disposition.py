"""Exact-artifact, expiry-bound dispositions; customer default remains not affected. Raw reports stay unfiltered."""
from datetime import date
import hashlib
import json
import tarfile
from pathlib import Path
from runtime import ROOT, run


def finding_signature(result, finding):
    target = 'os:' + result.get('Type', '') if result.get('Class') == 'os-pkgs' else result.get('Target', '').removeprefix('/')
    return {'target': target,
            'id': finding.get('VulnerabilityID'), 'package': finding.get('PkgName'),
            'version': finding.get('InstalledVersion'), 'severity': finding.get('Severity'),
            'fixed_version': finding.get('FixedVersion')}


def validate_report(report, policy, today=None, *, allowed_statuses=('not_affected',), additional_findings=()):
    today = today or date.today()
    if policy.get('status') not in allowed_statuses or today > date.fromisoformat(policy['review_by']):
        raise ValueError('gosu decision expired or is not approved.')
    if additional_findings and policy.get('status') != 'accepted_risk_pending_upstream':
        raise ValueError('Additional risk requires explicit pending-upstream acceptance.')
    results = report.get('Results')
    if not isinstance(results, list):
        raise ValueError('Missing complete scanner results.')
    metadata = report.get('Metadata', {})
    config = metadata.get('ImageConfig', {})
    if config.get('architecture') != policy['architecture']:
        raise ValueError('Image architecture changed.')
    digest = policy['image'].split('@', 1)[1]
    if 'postgres@' + digest not in metadata.get('RepoDigests', []):
        raise ValueError('Scanner image digest changed.')
    seen = set()
    signatures = []
    additional_seen = []
    for result in results:
        if any(result.get(key) for key in ('Secrets', 'Misconfigurations', 'CustomResources')):
            raise ValueError('Non-CVE findings cannot be dispositioned.')
        for finding in result.get('Vulnerabilities', []):
            signature = finding_signature(result, finding)
            if signature in additional_findings:
                additional_seen.append(signature)
                continue
            cve = finding.get('VulnerabilityID')
            if result.get('Target', '').removeprefix('/') != policy['path'] or finding.get('PkgName') != 'stdlib' or finding.get('InstalledVersion') != policy['go_version'] or cve not in policy['cves']:
                raise ValueError('New or changed finding requires assessment.')
            seen.add(cve)
            signatures.append(finding_signature(result, finding))
    if seen != set(policy['cves']):
        raise ValueError('Finding set changed; reassess even removed findings.')
    if sorted(json.dumps(row, sort_keys=True) for row in additional_seen) != sorted(json.dumps(row, sort_keys=True) for row in additional_findings):
        raise ValueError('Additional assessed finding set changed.')
    if 'findings' in policy and sorted(map(lambda row: json.dumps(row, sort_keys=True), signatures)) != sorted(map(lambda row: json.dumps(row, sort_keys=True), policy['findings'])):
        raise ValueError('Finding details changed; reassess severity, package and fix drift.')



def archive_config_id(archive):
    # Read members only; never extract an image archive into the filesystem.
    with tarfile.open(archive) as saved:
        entries = json.load(saved.extractfile('manifest.json'))
        if len(entries) != 1:
            raise ValueError('Expected exactly one saved image configuration.')
        config = saved.extractfile(entries[0]['Config']).read()
    return 'sha256:' + hashlib.sha256(config).hexdigest()


def bind_report(report, installed, policy, config_id):
    # Bind the scanner's configuration ID to the exact saved archive; Docker's
    # inspected ID may instead identify a manifest or index.
    metadata = report.get('Metadata', {})
    digest = 'postgres@' + policy['image'].split('@', 1)[1]
    if config_id != metadata.get('ImageID') or digest not in installed.get('RepoDigests', []) or installed.get('Architecture') != policy['architecture']:
        raise ValueError('Archive content ID is not bound to the approved repository digest.')
    if metadata.get('RepoDigests') and digest not in metadata['RepoDigests']:
        raise ValueError('Report contains a conflicting repository digest.')
    return dict(report, Metadata=dict(metadata, RepoDigests=[digest]))

def apply(image, report_path, archive_path, *, policy_path=None, allowed_statuses=('not_affected',), additional_findings=()):
    policy_path = policy_path or ROOT / 'tools/application/security/postgres-gosu-not-affected.json'
    policy = json.loads(policy_path.read_text())
    if image != policy['image']:
        return False
    if hashlib.sha256((ROOT / policy['evidence']).read_bytes()).hexdigest() != policy['evidence_sha256']:
        raise ValueError('gosu assessment evidence changed.')
    data = json.loads(Path(report_path).read_text())
    identity = run(['docker', 'image', 'inspect', image], capture_output=True, text=True).stdout
    installed = json.loads(identity)[0]
    config_id = archive_config_id(archive_path)
    validate_report(bind_report(data, installed, policy, config_id), policy, allowed_statuses=allowed_statuses, additional_findings=additional_findings)
    binary_hash = run(['docker', 'run', '--rm', '--read-only', '--network', 'none', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', 'sha256sum', installed['Id'], '/usr/local/bin/gosu'], capture_output=True, text=True).stdout.split()[0]
    version = run(['docker', 'run', '--rm', '--read-only', '--network', 'none', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', '/usr/local/bin/gosu', installed['Id'], '--version'], capture_output=True, text=True).stdout.strip()
    expected = policy['gosu_version'] + ' (' + policy['go_version'].replace('v', 'go', 1) + ' on linux/amd64; gc)'
    if binary_hash != policy['binary_sha256'] or version != expected:
        raise ValueError('gosu binary/version drift.')
    evidence = {'status':policy['status'], 'image':image, 'image_id':installed['Id'], 'config_id':config_id, 'policy_sha256':hashlib.sha256(policy_path.read_bytes()).hexdigest(), 'report_sha256':hashlib.sha256(Path(report_path).read_bytes()).hexdigest(), 'cves':policy['cves'], 'review_by':policy['review_by'], 'additional_findings':list(additional_findings)}
    path = Path(report_path).with_suffix('.disposition.json')
    path.write_text(json.dumps(evidence, indent=2) + '\n')
    path.chmod(0o600)
    print(policy['status'].upper() + ': exact ' + str(len(policy['cves'])) + ' assessed gosu findings; raw report retained, review by ' + policy['review_by'], flush=True)
    return True
