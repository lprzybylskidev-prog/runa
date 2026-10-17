"""Exact-artifact, expiry-bound not-affected decision. Raw scanner reports stay unfiltered."""
from datetime import date
import hashlib
import json
from pathlib import Path
from runtime import ROOT, run


def validate_report(report, policy, today=None):
    today = today or date.today()
    if policy.get('status') != 'not_affected' or today > date.fromisoformat(policy['review_by']):
        raise ValueError('gosu decision expired or is not approved.')
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
    for result in results:
        if any(result.get(key) for key in ('Secrets', 'Misconfigurations', 'CustomResources')):
            raise ValueError('Non-CVE findings cannot be dispositioned.')
        for finding in result.get('Vulnerabilities', []):
            cve = finding.get('VulnerabilityID')
            if result.get('Target', '').removeprefix('/') != policy['path'] or finding.get('PkgName') != 'stdlib' or finding.get('InstalledVersion') != policy['go_version'] or cve not in policy['cves']:
                raise ValueError('New or changed finding requires assessment.')
            seen.add(cve)
    if seen != set(policy['cves']):
        raise ValueError('Finding set changed; reassess even removed findings.')



def bind_report(report, installed, policy):
    # docker image save omits repository digests. Bind its unchanged content ID
    # to the exact digest independently verified against Docker's image metadata.
    metadata = report.get('Metadata', {})
    digest = 'postgres@' + policy['image'].split('@', 1)[1]
    if installed.get('Id') != metadata.get('ImageID') or digest not in installed.get('RepoDigests', []) or installed.get('Architecture') != policy['architecture']:
        raise ValueError('Archive content ID is not bound to the approved repository digest.')
    if metadata.get('RepoDigests') and digest not in metadata['RepoDigests']:
        raise ValueError('Report contains a conflicting repository digest.')
    return dict(report, Metadata=dict(metadata, RepoDigests=[digest]))

def apply(image, report_path):
    policy_path = ROOT / 'docs/security/postgres-gosu-not-affected.json'
    policy = json.loads(policy_path.read_text())
    if image != policy['image']:
        return False
    if hashlib.sha256((ROOT / policy['evidence']).read_bytes()).hexdigest() != policy['evidence_sha256']:
        raise ValueError('gosu assessment evidence changed.')
    data = json.loads(Path(report_path).read_text())
    identity = run(['docker', 'image', 'inspect', image], capture_output=True, text=True).stdout
    installed = json.loads(identity)[0]
    if installed['Id'] != data['Metadata'].get('ImageID') or installed.get('Architecture') != policy['architecture']:
        raise ValueError('Scanned/inspected artifact identity differs.')
    validate_report(bind_report(data, installed, policy), policy)
    binary_hash = run(['docker', 'run', '--rm', '--read-only', '--network', 'none', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', 'sha256sum', installed['Id'], '/usr/local/bin/gosu'], capture_output=True, text=True).stdout.split()[0]
    version = run(['docker', 'run', '--rm', '--read-only', '--network', 'none', '--cap-drop=ALL', '--security-opt=no-new-privileges:true', '--entrypoint', '/usr/local/bin/gosu', installed['Id'], '--version'], capture_output=True, text=True).stdout.strip()
    expected = policy['gosu_version'] + ' (' + policy['go_version'].replace('v', 'go', 1) + ' on linux/amd64; gc)'
    if binary_hash != policy['binary_sha256'] or version != expected:
        raise ValueError('gosu binary/version drift.')
    evidence = {'status':'not_affected', 'image':image, 'image_id':installed['Id'], 'policy_sha256':hashlib.sha256(policy_path.read_bytes()).hexdigest(), 'report_sha256':hashlib.sha256(Path(report_path).read_bytes()).hexdigest(), 'cves':policy['cves'], 'review_by':policy['review_by']}
    path = Path(report_path).with_suffix('.disposition.json')
    path.write_text(json.dumps(evidence, indent=2) + '\n')
    path.chmod(0o600)
    print('NOT AFFECTED: exact 25 assessed gosu findings; raw report retained, review by ' + policy['review_by'], flush=True)
    return True
