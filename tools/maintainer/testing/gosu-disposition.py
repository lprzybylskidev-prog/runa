#!/usr/bin/env python3
"""The approved decision fails closed on artifact, finding, path and expiry changes."""
import copy
import hashlib
import io
import tarfile
import tempfile
from datetime import date
import json
from pathlib import Path
import sys
import unittest
from types import SimpleNamespace
ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
from gosu_disposition import validate_report, bind_report, archive_config_id
from unittest.mock import patch
import owner_gosu_disposition
POLICY = json.loads((ROOT / 'tools/application/security/postgres-gosu-not-affected.json').read_text())
OWNER_POLICY = json.loads((ROOT / 'tools/maintainer/security/postgres-gosu-pending-upstream.json').read_text())


def fixture():
    return {'Metadata': {'ImageConfig': {'architecture': 'amd64'}, 'RepoDigests': ['postgres@' + POLICY['image'].split('@')[1]]}, 'Results': [{'Target': POLICY['path'], 'Vulnerabilities': [{'VulnerabilityID': cve, 'PkgName': 'stdlib', 'InstalledVersion': 'v1.24.6'} for cve in POLICY['cves']]}]}


class DispositionContracts(unittest.TestCase):
    def owner_fixture(self):
        report = fixture()
        report['Results'][0]['Vulnerabilities'] = [
            {'VulnerabilityID': row['id'], 'PkgName': row['package'], 'InstalledVersion': row['version'],
             'Severity': row['severity'], 'FixedVersion': row['fixed_version']}
            for row in OWNER_POLICY['findings']]
        return report

    def test_owner_acceptance_requires_explicit_selection_and_current_exact_details(self):
        report = self.owner_fixture()
        with self.assertRaises(ValueError):
            validate_report(report, POLICY, date(2026, 10, 21))
        with self.assertRaises(ValueError):
            validate_report(report, OWNER_POLICY, date(2026, 10, 21))
        options = {'allowed_statuses': ('accepted_risk_pending_upstream',)}
        validate_report(report, OWNER_POLICY, date(2026, 10, 21), **options)
        with self.assertRaises(ValueError):
            validate_report(report, OWNER_POLICY, date(2026, 11, 21), **options)
        for field, value in [('Severity', 'CRITICAL'), ('FixedVersion', 'new-fix'), ('PkgName', 'other'), ('VulnerabilityID', 'CVE-NEW')]:
            changed = copy.deepcopy(report)
            # Select a HIGH row so changing to CRITICAL always alters severity.
            finding = next(row for row in changed['Results'][0]['Vulnerabilities'] if row['Severity'] == 'HIGH')
            finding[field] = value
            with self.subTest(field=field), self.assertRaises(ValueError):
                validate_report(changed, OWNER_POLICY, date(2026, 10, 21), **options)
        report['Results'][0]['Vulnerabilities'].pop()
        with self.assertRaises(ValueError):
            validate_report(report, OWNER_POLICY, date(2026, 10, 21), **options)

    def test_owner_acceptance_is_denied_with_customer_receipt_even_if_owner_files_copied(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'tools/maintainer').mkdir(parents=True)
            (root / '.git').mkdir()
            marker = root / '.git/runa-bootstrap.json'
            with patch.object(owner_gosu_disposition, 'ROOT', root), patch.object(owner_gosu_disposition.gosu_disposition, 'apply', return_value=True) as apply:
                self.assertTrue(owner_gosu_disposition.apply('image', 'report', 'archive'))
                self.assertEqual(apply.call_args.kwargs['allowed_statuses'], ('accepted_risk_pending_upstream',))
                apply.reset_mock()
                marker.write_text('{}')
                with self.assertRaises(ValueError):
                    owner_gosu_disposition.apply('image', 'report', 'archive')
                marker.unlink()
                marker.symlink_to(root / 'missing')
                with self.assertRaises(ValueError):
                    owner_gosu_disposition.apply('image', 'report', 'archive')
                apply.assert_not_called()

    def test_evidence_binary_and_version_drift_rejected_before_acceptance(self):
        import gosu_disposition
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            policy = copy.deepcopy(OWNER_POLICY)
            policy['evidence'] = 'evidence.md'
            (root / 'evidence.md').write_text('assessed evidence')
            policy_path = root / 'policy.json'
            report_path = root / 'report.json'
            report = self.owner_fixture()
            report['Metadata']['ImageID'] = 'sha256:config'
            report_path.write_text(json.dumps(report))
            policy_path.write_text(json.dumps(policy))
            options = {'policy_path': policy_path, 'allowed_statuses': ('accepted_risk_pending_upstream',)}
            with patch.object(gosu_disposition, 'ROOT', root), patch.object(gosu_disposition, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'evidence changed'):
                    gosu_disposition.apply(policy['image'], report_path, root / 'archive', **options)
                run.assert_not_called()
            policy['evidence_sha256'] = hashlib.sha256((root / 'evidence.md').read_bytes()).hexdigest()
            policy_path.write_text(json.dumps(policy))
            installed = {'Id': 'sha256:manifest', 'Architecture': policy['architecture'], 'RepoDigests': report['Metadata']['RepoDigests']}
            expected_version = '1.19 (go1.24.6 on linux/amd64; gc)'
            for binary, version in [('wrong-hash', expected_version), (policy['binary_sha256'], 'changed-version')]:
                outputs = [SimpleNamespace(stdout=json.dumps([installed])), SimpleNamespace(stdout=binary + ' /usr/local/bin/gosu'), SimpleNamespace(stdout=version)]
                with self.subTest(binary=binary, version=version), patch.object(gosu_disposition, 'ROOT', root), patch.object(gosu_disposition, 'archive_config_id', return_value='sha256:config'), patch.object(gosu_disposition, 'run', side_effect=outputs):
                    with self.assertRaisesRegex(ValueError, 'binary/version drift'):
                        gosu_disposition.apply(policy['image'], report_path, root / 'archive', **options)
                self.assertFalse(report_path.with_suffix('.disposition.json').exists())

    def test_saved_config_bytes_bind_report_independently_of_manifest_id(self):
        config = b'{"architecture":"amd64"}'
        with tempfile.TemporaryDirectory() as folder:
            archive = Path(folder) / 'image.tar'
            with tarfile.open(archive, 'w') as saved:
                for name, content in [('manifest.json', b'[{"Config":"blobs/sha256/config"}]'), ('blobs/sha256/config', config)]:
                    member = tarfile.TarInfo(name)
                    member.size = len(content)
                    saved.addfile(member, io.BytesIO(content))
            self.assertEqual(archive_config_id(archive), 'sha256:' + hashlib.sha256(config).hexdigest())

    def test_archive_without_digest_requires_same_content_id_and_verified_docker_digest(self):
        report = fixture()
        report['Metadata'].pop('RepoDigests')
        report['Metadata']['ImageID'] = 'sha256:content'
        installed = {'Id': 'sha256:content', 'Architecture': 'amd64', 'RepoDigests': ['postgres@' + POLICY['image'].split('@')[1]]}
        bound = bind_report(report, installed, POLICY, 'sha256:content')
        validate_report(bound, POLICY, date(2026,10,17))
        self.assertNotIn('RepoDigests', report['Metadata'])
        with self.assertRaises(ValueError):
            bind_report(report, installed, POLICY, 'sha256:other')
        installed['Id'] = 'sha256:manifest'
        validate_report(bind_report(report, installed, POLICY, 'sha256:content'), POLICY, date(2026,10,17))
        for change in ({'RepoDigests':[]}, {'Architecture':'arm64'}):
            altered = dict(installed, **change)
            with self.subTest(change=change), self.assertRaises(ValueError):
                bind_report(report, altered, POLICY, 'sha256:content')

    def test_exact_set_accepted_and_expired_rejected(self):
        validate_report(fixture(), POLICY, date(2026,10,17))
        with self.assertRaises(ValueError):validate_report(fixture(), POLICY, date(2026,11,17))

    def test_drift_new_missing_path_version_and_secret_rejected(self):
        cases=[]
        r=fixture();r['Metadata']['RepoDigests']=[];cases.append(r)
        r=fixture();r['Metadata']['ImageConfig']['architecture']='arm64';cases.append(r)
        r=fixture();r['Results'][0]['Target']='other/gosu';cases.append(r)
        r=fixture();r['Results'][0]['Vulnerabilities'][0]['InstalledVersion']='v1.24.7';cases.append(r)
        r=fixture();r['Results'][0]['Vulnerabilities'][0]['VulnerabilityID']='CVE-NEW';cases.append(r)
        r=fixture();r['Results'][0]['Vulnerabilities'].pop();cases.append(r)
        r=fixture();r['Results'].append({'Target':'secret','Secrets':[{'RuleID':'private-key'}]});cases.append(r)
        for r in cases:
            with self.subTest(report=r), self.assertRaises(ValueError):validate_report(r,POLICY,date(2026,10,17))

if __name__ == '__main__':unittest.main()
