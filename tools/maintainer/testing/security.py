#!/usr/bin/env python3
"""Security evidence must be actual, complete and bound to the reviewed content."""
import contextlib
import hashlib
import io
import json
from pathlib import Path
import sys
import tempfile
import subprocess
from types import SimpleNamespace
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import security
import owner_runtime_disposition as runtime_risk
import copy
import tarfile


class ReviewContracts(unittest.TestCase):
    def test_custom_service_fixture_pulls_only_exact_digest_and_rejects_wrong_identity(self):
        import service_fixtures
        reference = service_fixtures.REDIS_IMAGE
        metadata = json.dumps([{'RepoDigests': ['redis@' + reference.split('@')[1]]}])
        missing = SimpleNamespace(returncode=1, stdout='')
        present = SimpleNamespace(returncode=0, stdout=metadata)
        with patch.object(service_fixtures.subprocess, 'run', side_effect=[missing, SimpleNamespace(returncode=0), present]) as run:
            self.assertEqual(service_fixtures.redis_image(), reference)
            self.assertEqual(run.call_args_list[1].args[0], ['docker', 'pull', reference])
        with patch.object(service_fixtures.subprocess, 'run', return_value=SimpleNamespace(returncode=0, stdout='[{"RepoDigests":["redis@sha256:other"]}]')):
            with self.assertRaisesRegex(ValueError, 'pinned Redis artifact'):
                service_fixtures.redis_image()

    def test_failed_repository_and_image_scans_collect_all_images_and_keep_failure(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            binary = root / '.runtime/tools/trivy'
            binary.parent.mkdir(parents=True)
            binary.touch()
            reports = root / '.runtime/security'
            reports.mkdir()
            (reports / 'repository.json').write_text('{"stale": true}')
            images = ['postgres:18.6-bookworm', 'runa-production:' + 'a' * 40, 'runa-nginx:' + 'a' * 40]
            fixture_image = 'redis:fixture@sha256:exact'
            def execute(args, **kwargs):
                if '--version' in args:
                    return SimpleNamespace(stdout='Version: 0.76.0')
                if args[0] == str(binary):
                    raise subprocess.CalledProcessError(1, args)
                return SimpleNamespace(stdout='')
            with patch.object(security, 'ROOT', root), patch.object(security, 'inside', return_value=False), patch.object(security, 'run', side_effect=execute) as run, patch.object(security, 'production_images', return_value=images), patch.object(security, 'dependencies') as audits, contextlib.redirect_stdout(io.StringIO()):
                with self.assertRaisesRegex(ValueError, 'Security checks failed'):
                    security.trivy(additional_images=[fixture_image])
                scans = [call.args[0] for call in run.call_args_list if call.args[0][0] == str(binary) and '--version' not in call.args[0]]
                self.assertEqual(len(scans), 5)
                self.assertIn('--include-dev-deps', scans[0])
                excluded = scans[0][scans[0].index('--skip-dirs') + 1].split(',')
                self.assertIn('src/vendor', excluded)
                self.assertIn('src/node_modules', excluded)
                self.assertNotIn('src/app', excluded)
                self.assertNotIn('src/composer.lock', excluded)
                self.assertNotIn('src/pnpm-lock.yaml', excluded)
                self.assertEqual(len({call.args[0][-1] for call in run.call_args_list if call.args[0][:3] == ['docker', 'image', 'save']}), 4)
                audits.assert_called_once()
                self.assertFalse((root / '.runtime/trivy-image.tar').exists())
                self.assertEqual((reports / 'repository.json').read_text(), '')

    def test_unknown_owner_and_dev_image_targets_are_rejected_before_scanning(self):
        import repository_boundary as boundary
        for mode in ('dev', None, 'unknown'):
            with self.subTest(mode=mode), patch.object(boundary, 'assert_installation', return_value={'mode': mode}), patch.object(security.subprocess, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'require a production installation'):
                    security.production_images(ROOT)
                run.assert_not_called()
        with tempfile.TemporaryDirectory() as folder, patch.object(security.subprocess, 'run') as run:
            with self.assertRaisesRegex(ValueError, 'receipt'):
                security.production_images(Path(folder))
            run.assert_not_called()

    def test_fixture_image_selection_uses_its_own_context_while_scanning_owner_source(self):
        import repository_boundary as boundary
        fixture = Path('/separate/customer-production')
        selected = {'images': ['runa-production:exact', 'postgres:exact'], 'locks': {
            name: hashlib.sha256((ROOT / name).read_bytes()).hexdigest() for name in ('src/composer.lock', 'src/pnpm-lock.yaml')}}
        result = SimpleNamespace(stdout=json.dumps(selected))
        with patch.object(boundary, 'assert_production_installation') as guard, patch.object(security.subprocess, 'run', return_value=result) as run:
            self.assertEqual(security.production_images(fixture), ['runa-production:exact', 'postgres:exact'])
            guard.assert_called_once_with(fixture)
            self.assertEqual(run.call_args.kwargs['cwd'], fixture)
            self.assertIn('assert_production_installation(operations.ROOT)', run.call_args.args[0][2])
            selected['locks']['src/composer.lock'] = 'different'
            result.stdout = json.dumps(selected)
            with self.assertRaisesRegex(ValueError, 'dependency locks differ'):
                security.production_images(fixture)
            selected['locks']['src/composer.lock'] = hashlib.sha256((ROOT / 'src/composer.lock').read_bytes()).hexdigest()
            selected['images'] = []
            result.stdout = json.dumps(selected)
            with self.assertRaisesRegex(ValueError, 'no runtime images'):
                security.production_images(fixture)

    def test_isolated_audits_reject_different_locks_before_docker(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / 'installer'
            dev = Path(folder) / 'dev'
            (root / 'src').mkdir(parents=True); (dev / 'src').mkdir(parents=True)
            (root / 'src/composer.lock').write_text('one')
            (dev / 'src/composer.lock').write_text('two')
            with patch.object(security, 'ROOT', root), patch.object(security, 'AUDIT_ROOT', dev), patch.object(security, 'inside', return_value=False), patch.object(security.subprocess, 'run') as run:
                with self.assertRaisesRegex(ValueError, 'lockfiles differ'):
                    security.dependencies()
                run.assert_not_called()

    def test_missing_stale_blocking_and_changed_report_evidence_rejected(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(security, 'ROOT', Path(folder)), patch.object(security, 'surface', return_value={'app/example.php': 'current'}):
            root = Path(folder)
            (root / '.runtime').mkdir()
            report = root / 'report.md'
            report.write_text('Fixture review, not a real project attestation.')
            path = root / '.runtime/security-review.json'
            with self.assertRaisesRegex(ValueError, 'Missing actual'):
                security.review()
            data = {'reviewer': 'Fixture', 'reviewed_at': '2026-10-17', 'report': 'report.md', 'report_sha256': hashlib.sha256(report.read_bytes()).hexdigest(), 'blocking_findings': [], 'surface': {'app/example.php': 'old'}}
            path.write_text(json.dumps(data))
            with self.assertRaisesRegex(ValueError, 'stale'):
                security.review()
            data['surface'] = {'app/example.php': 'current'}
            data['blocking_findings'] = ['unresolved']
            path.write_text(json.dumps(data))
            with self.assertRaisesRegex(ValueError, 'blocking'):
                security.review()
            data['blocking_findings'] = []
            path.write_text(json.dumps(data))
            with contextlib.redirect_stdout(io.StringIO()):
                security.review()
            report.write_text('Changed after review.')
            with self.assertRaisesRegex(ValueError, 'report is missing or changed'):
                security.review()


class RuntimeRiskContracts(unittest.TestCase):
    def setUp(self):
        self.policy = json.loads((ROOT / 'tools/maintainer/security/runtime-pending-upstream.json').read_text())

    def fixture(self):
        row = self.policy['os_findings'][0]
        return {'Results': [{'Class': 'os-pkgs', 'Type': 'alpine', 'Target': '/private/image.tar (alpine 3.24.2)',
                             'Vulnerabilities': [{'VulnerabilityID': row['id'], 'PkgName': row['package'],
                                                  'InstalledVersion': row['version'], 'Severity': row['severity'],
                                                  'FixedVersion': row['fixed_version']}]}]}

    def test_known_core_and_brotli_findings_only(self):
        report = self.fixture()
        report['Results'].append({'Target': 'usr/local/bin/php', 'Vulnerabilities': [
            {'VulnerabilityID': cve, 'PkgName': 'php-src', 'InstalledVersion': '8.4.26', 'Severity': 'CRITICAL', 'FixedVersion': '8.4.27'}
            for cve in self.policy['php_cves']]})
        self.assertEqual(len(runtime_risk.validate_findings(report, self.policy)), 3)
        for field, value in [('VulnerabilityID', 'CVE-NEW'), ('PkgName', 'other'), ('InstalledVersion', '8.4.25')]:
            changed = copy.deepcopy(report)
            changed['Results'][-1]['Vulnerabilities'][0][field] = value
            with self.subTest(field=field), self.assertRaises(ValueError):
                runtime_risk.validate_findings(changed, self.policy)

    def test_new_package_version_fix_and_secrets_stay_blocking(self):
        for field, value in [('PkgName', 'libexpat'), ('InstalledVersion', '1.2.0-r2'),
                             ('Severity', 'CRITICAL'), ('FixedVersion', 'new-fix')]:
            report = self.fixture()
            report['Results'][0]['Vulnerabilities'][0][field] = value
            with self.subTest(field=field), self.assertRaises(ValueError):
                runtime_risk.validate_findings(report, self.policy)
        for key in ('Secrets', 'Misconfigurations', 'CustomResources'):
            report = self.fixture()
            report['Results'][0][key] = [{}]
            with self.subTest(key=key), self.assertRaises(ValueError):
                runtime_risk.validate_findings(report, self.policy)
        for report in ({}, {'Results': []}, {'Results': [{'Vulnerabilities': []}]}):
            with self.subTest(report=report), self.assertRaises(ValueError):
                runtime_risk.validate_findings(report, self.policy)

    def test_receipt_refusal_precedes_docker_or_owner_policy(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'tools/maintainer').mkdir(parents=True)
            (root / '.git').mkdir()
            marker = root / '.git/runa-bootstrap.json'
            with patch.object(runtime_risk, 'ROOT', root), patch.object(runtime_risk, 'run') as run:
                marker.write_text('{}')
                with self.assertRaisesRegex(ValueError, 'customer'):
                    runtime_risk.apply('image', 'report', 'archive')
                marker.unlink()
                marker.symlink_to(root / 'missing')
                with self.assertRaisesRegex(ValueError, 'customer'):
                    runtime_risk.apply('image', 'report', 'archive')
                run.assert_not_called()

    def test_actual_archive_config_and_filesystem_are_bound_to_report_and_inspection(self):
        config = {'architecture': 'amd64', 'os': 'linux', 'config': {'User': 'runa'}, 'rootfs': {'diff_ids': ['sha256:layer']}}
        raw = json.dumps(config).encode()
        installed = {'Architecture': 'amd64', 'Config': config['config'], 'RootFS': {'Layers': ['sha256:layer']}}
        report = {'Metadata': {'ImageID': 'sha256:' + hashlib.sha256(raw).hexdigest(), 'ImageConfig': {'architecture': 'amd64'}}}
        with tempfile.TemporaryDirectory() as folder:
            archive = Path(folder) / 'image.tar'
            with tarfile.open(archive, 'w') as saved:
                for name, content in [('manifest.json', b'[{"Config":"config.json"}]'), ('config.json', raw)]:
                    member = tarfile.TarInfo(name)
                    member.size = len(content)
                    saved.addfile(member, io.BytesIO(content))
            self.assertEqual(runtime_risk.bind_php_report(report, installed, archive, self.policy), report['Metadata']['ImageID'])
            for field, value in [('Architecture', 'arm64'), ('Config', {'User': 'root'}), ('RootFS', {'Layers': ['sha256:foreign']})]:
                with self.subTest(field=field), self.assertRaises(ValueError):
                    runtime_risk.bind_php_report(report, dict(installed, **{field: value}), archive, self.policy)
            report['Metadata']['ImageID'] = 'sha256:other'
            with self.assertRaises(ValueError):
                runtime_risk.bind_php_report(report, installed, archive, self.policy)

    def test_live_php_identity_source_drift_and_expiry_remain_required(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'tools/maintainer/security').mkdir(parents=True)
            policy = dict(self.policy, source_hashes={})
            path = root / 'tools/maintainer/security/runtime-pending-upstream.json'
            path.write_text(json.dumps(policy))
            (path.parent / 'postgres-gosu-pending-upstream.json').write_text('{"image":"postgres:exact"}')
            report = root / 'report.json'
            original = json.dumps(self.fixture())
            report.write_text(original)
            image = 'runa-production:' + 'a' * 40
            identities = [['8.4.27', policy['php_binary_sha256']], ['8.4.26', 'wrong'], ['8.4.26', policy['php_binary_sha256']]]
            for identity in identities:
                outputs = [SimpleNamespace(stdout='[{"Id":"sha256:live"}]'), SimpleNamespace(stdout=json.dumps(identity))]
                with patch.object(runtime_risk, 'ROOT', root), patch.object(runtime_risk, 'bind_php_report', return_value='sha256:config'), patch.object(runtime_risk, 'run', side_effect=outputs), contextlib.redirect_stdout(io.StringIO()):
                    if identity == identities[-1]:
                        self.assertTrue(runtime_risk.apply(image, report, root / 'archive'))
                        self.assertEqual(report.read_text(), original)
                        self.assertEqual(json.loads(report.with_suffix('.disposition.json').read_text())['status'], 'accepted_risk_pending_upstream')
                    else:
                        with self.assertRaisesRegex(ValueError, 'binary/version'):
                            runtime_risk.apply(image, report, root / 'archive')
            for changes, message in [({'review_by': '2020-01-01'}, 'expired'), ({'source_hashes': {'report.json': 'wrong'}}, 'changed')]:
                path.write_text(json.dumps(dict(policy, **changes)))
                with patch.object(runtime_risk, 'ROOT', root), patch.object(runtime_risk, 'run') as run:
                    with self.assertRaisesRegex(ValueError, message):
                        runtime_risk.apply(image, report, root / 'archive')
                    run.assert_not_called()


if __name__ == '__main__':
    unittest.main()
