#!/usr/bin/env python3
"""The approved decision fails closed on artifact, finding, path and expiry changes."""
import copy
from datetime import date
import json
from pathlib import Path
import sys
import unittest
ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
from gosu_disposition import validate_report
POLICY = json.loads((ROOT / 'docs/security/postgres-gosu-not-affected.json').read_text())


def fixture():
    return {'Metadata': {'ImageConfig': {'architecture': 'amd64'}, 'RepoDigests': ['postgres@' + POLICY['image'].split('@')[1]]}, 'Results': [{'Target': POLICY['path'], 'Vulnerabilities': [{'VulnerabilityID': cve, 'PkgName': 'stdlib', 'InstalledVersion': 'v1.24.6'} for cve in POLICY['cves']]}]}


class DispositionContracts(unittest.TestCase):
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
