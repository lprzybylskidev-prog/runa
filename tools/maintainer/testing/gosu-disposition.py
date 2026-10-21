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
ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
from gosu_disposition import validate_report, bind_report, archive_config_id
POLICY = json.loads((ROOT / 'tools/application/security/postgres-gosu-not-affected.json').read_text())


def fixture():
    return {'Metadata': {'ImageConfig': {'architecture': 'amd64'}, 'RepoDigests': ['postgres@' + POLICY['image'].split('@')[1]]}, 'Results': [{'Target': POLICY['path'], 'Vulnerabilities': [{'VulnerabilityID': cve, 'PkgName': 'stdlib', 'InstalledVersion': 'v1.24.6'} for cve in POLICY['cves']]}]}


class DispositionContracts(unittest.TestCase):
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
