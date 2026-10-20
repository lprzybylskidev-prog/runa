#!/usr/bin/env python3
"""Failure-first first-install, source preservation and private configuration contracts."""
import contextlib
import copy
import io
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import Mock, patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import installation as installer
import operations as ops
import production
import management
import host_executor

import repository_boundary


class InstallationContracts(unittest.TestCase):
    def setUp(self):
        boundary = patch.object(repository_boundary, 'assert_installation', return_value={'mode': 'prod'})
        boundary.start()
        self.addCleanup(boundary.stop)
        executor = patch.object(host_executor, 'ensure')
        executor.start()
        self.addCleanup(executor.stop)
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.state = self.root / '.runtime/production'
        self.state.mkdir(parents=True, mode=0o700)
        (self.root / 'src/app').mkdir(parents=True)
        (self.root / 'src/app/application.json').write_text('{"version":"2.4.1","name":"Customer"}\n')
        shutil.copyfile(ROOT / '.env.prod.example', self.root / '.env.prod.example')
        self.settings = {'app_url': 'https://customer.example', 'http_port': 18081, 'tls': {'mode': 'external'},
                         'smtp': {'host': 'smtp.customer.example', 'port': 587, 'scheme': 'smtp', 'username': 'fixture', 'password': 'synthetic_not_a_real_password', 'from': 'sender@example.invalid', 'recipient': 'recipient@example.invalid'}}
        for module, name, value in ((installer, 'ROOT', self.root), (ops, 'ROOT', self.root), (ops, 'STATE', self.state), (production, 'ROOT', self.root)):
            patcher = patch.object(module, name, value)
            patcher.start()
            self.addCleanup(patcher.stop)
        self.sha = 'a' * 40

    def settings_file(self):
        path = self.root / 'settings.json'
        path.write_text(json.dumps(self.settings))
        path.chmod(0o600)
        return path

    @contextlib.contextmanager
    def tools(self, failure=None):
        def run(arguments, **kwargs):
            if arguments[:3] == ['git', 'rev-parse', 'HEAD']:
                return subprocess.CompletedProcess(arguments, 0, self.sha + '\n')
            if arguments[:3] == ['git', 'status', '--porcelain']:
                return subprocess.CompletedProcess(arguments, 0, '')
            if failure:
                raise failure
            return subprocess.CompletedProcess(arguments, 0, '')
        with patch.object(ops, 'resolve', return_value=self.sha), patch.object(installer, 'run', side_effect=run) as process, contextlib.redirect_stdout(io.StringIO()), contextlib.redirect_stderr(io.StringIO()):
            yield process

    def test_schema_rejects_credentials_in_url_unknown_fields_newlines_bad_tls_ports_and_settings_permissions(self):
        variants = [{'app_url': 'https://user:password@customer.example'}, {'extra': True},
                    {'smtp': self.settings['smtp'] | {'password': 'one\nDB_PASSWORD=two'}},
                    {'tls': {'mode': 'none'}}, {'http_port': True}, {'http_port': 80},
                    {'simulation_project': 'runa-dev'}, {'backup': {'directory': '/', 'retention': 0}}]
        for change in variants:
            with self.subTest(change=change), self.assertRaises((ValueError, TypeError)):
                installer.validate_settings(self.settings | change, False)
        path = self.settings_file()
        self.assertEqual(installer.private_json(path), self.settings)
        path.chmod(0o644)
        with self.assertRaises(ValueError):
            installer.private_json(path)

    def test_initial_provision_and_repeated_provision_preserve_every_secret_and_customer_version(self):
        external = self.root / '.env.prod.backup'
        external.write_text('customer-managed backup configuration\n')
        installer.provision(self.settings, self.sha, True)
        self.assertEqual(external.read_text(), 'customer-managed backup configuration\n')
        paths = [self.root / name for name in ('.env.prod', '.env.prod.database', '.env.prod.compose')]
        contents = {path: path.read_bytes() for path in paths}
        installer.provision(self.settings, self.sha, True)
        self.assertEqual(contents, {path: path.read_bytes() for path in paths})
        for path in paths:
            self.assertEqual(path.stat().st_mode & 0o777, 0o600)
        self.assertIn('MAIL_PASSWORD=synthetic_not_a_real_password', (self.root / '.env.prod').read_text())
        self.assertNotEqual(ops.read_env(self.root / '.env.prod.database')['POSTGRES_PASSWORD'], ops.read_env(self.root / '.env.prod')['DB_PASSWORD'])
        self.assertFalse((self.state / 'first-deploy.json').exists())

    def test_partial_configuration_resume_reuses_original_application_key_and_database_password(self):
        installer.provision(self.settings, self.sha, True)
        original = (self.root / '.env.prod').read_bytes()
        (self.root / '.env.prod.database').unlink()
        installer.provision(self.settings, self.sha, True)
        self.assertEqual((self.root / '.env.prod').read_bytes(), original)
        self.assertEqual(ops.read_env(self.root / '.env.prod')['DB_PASSWORD'], ops.read_env(self.root / '.env.prod.database')['APP_DB_PASSWORD'])

    def test_scaffold_is_once_only_and_preserves_customer_files_manifest_and_removed_extension_dirs(self):
        business = self.root / 'src/app/custom.php'
        business.write_text('customer sentinel')
        original = (self.root / 'src/app/application.json').read_bytes()
        installer.scaffold(self.sha, 'dev')
        self.assertTrue((self.root / 'src/app/modules').is_dir())
        shutil.rmtree(self.root / 'src/app/modules')
        installer.scaffold(self.sha, 'dev')
        self.assertFalse((self.root / 'src/app/modules').exists())
        self.assertEqual(business.read_text(), 'customer sentinel')
        self.assertEqual((self.root / 'src/app/application.json').read_bytes(), original)

    def test_dev_fresh_repeat_failure_and_explicit_resume_reuse_existing_mechanics(self):
        with self.tools(subprocess.CalledProcessError(1, ['fixture'])):
            with self.assertRaises(subprocess.CalledProcessError):
                installer.install('dev', self.sha, profile='low')
        self.assertEqual(json.loads((self.state / 'install-dev.json').read_text())['state'], 'failed')
        with self.tools() as process:
            with self.assertRaisesRegex(ValueError, '--resume'):
                installer.install('dev', self.sha)
            installer.install('dev', self.sha, resume=True)
            self.assertTrue(any(call.args[0][-2:] == ['profile', 'low'] for call in process.call_args_list))
            calls = [call.args[0] for call in process.call_args_list]
            wait = next(i for i, call in enumerate(calls) if call[-2:] == ['wait', 'setup'])
            database = [i for i, call in enumerate(calls) if '-r' in call]
            self.assertEqual(len(database), 2)
            self.assertTrue(all(i > wait for i in database))
            self.assertEqual(calls[database[0]][-3:], ['migrate', '--force', '--no-interaction'])
            self.assertEqual(calls[database[1]][-2:], ['install-seeds', self.sha])
            count = len(process.call_args_list)
            installer.install('dev', self.sha)
            # Repeat reads source identity but never launches setup/services again.
            self.assertEqual(len(process.call_args_list), count + 1)
        self.assertFalse((self.state / 'first-deploy.json').exists())

    def test_failed_dependency_setup_stops_before_platform_database_initialization(self):
        with self.tools() as process:
            original = process.side_effect
            def fail_setup(arguments, **kwargs):
                if arguments[-2:] == ['wait', 'setup']:
                    raise subprocess.CalledProcessError(1, arguments)
                return original(arguments, **kwargs)
            process.side_effect = fail_setup
            with self.assertRaises(subprocess.CalledProcessError):
                installer.install('dev', self.sha)
            self.assertFalse(any('-r' in call.args[0] for call in process.call_args_list))
        self.assertEqual(json.loads((self.state / 'install-dev.json').read_text())['state'], 'failed')

    def test_production_requires_explicit_settings_and_refuses_unowned_existing_configuration(self):
        with self.tools(), self.assertRaisesRegex(ValueError, '--settings-file'):
            installer.install('prod', self.sha, non_interactive=True)
        self.assertFalse((self.state / 'install-prod.json').exists())
        (self.root / '.env.prod').touch()
        with self.tools(), self.assertRaisesRegex(ValueError, 'existing production settings'):
            installer.install('prod', self.sha, settings_file=self.settings_file())

    def test_failed_production_install_keeps_configuration_and_resumes_without_regeneration(self):
        with self.tools(), patch.object(ops, 'deploy', side_effect=ValueError('synthetic')):
            with self.assertRaises(ValueError):
                installer.install('prod', self.sha, simulation=True, settings_file=self.settings_file())
        original = (self.root / '.env.prod').read_bytes()
        with self.tools(), patch.object(installer, 'resume_is_owned', return_value=False), patch.object(ops, 'deploy') as deploy:
            installer.install('prod', self.sha, simulation=True, resume=True)
            deploy.assert_called_once()
            self.assertIsNotNone(deploy.call_args.kwargs['verification'])
            self.assertIsNotNone(deploy.call_args.kwargs['database_setup'])
        self.assertEqual((self.root / '.env.prod').read_bytes(), original)
        self.assertEqual(json.loads((self.state / 'install-prod.json').read_text())['state'], 'complete')
        self.assertFalse((self.state / 'first-deploy.json').exists())

    def test_resume_blocks_a_pending_update_or_another_target(self):
        ops.atomic(self.state / 'in-progress.json', {'target': 'b' * 40, 'previous': None})
        with self.assertRaisesRegex(ValueError, 'not this initial installation'):
            installer.resume_is_owned({'sha': self.sha})

    def test_explicit_smtp_correction_on_resume_preserves_application_and_database_secrets(self):
        installer.provision(self.settings, self.sha, True)
        original = ops.read_env(self.root / '.env.prod')
        database = (self.root / '.env.prod.database').read_bytes()
        ops.atomic(self.state / 'install-settings.json', self.settings)
        self.settings['smtp']['password'] = 'synthetic_corrected_password'
        corrected = installer.resume_settings(self.settings_file(), True)
        effective = ops.read_env(self.root / '.env.prod')
        self.assertEqual(corrected['smtp']['password'], 'synthetic_corrected_password')
        self.assertEqual(effective['MAIL_PASSWORD'], 'synthetic_corrected_password')
        self.assertEqual(effective['APP_KEY'], original['APP_KEY'])
        self.assertEqual(effective['DB_PASSWORD'], original['DB_PASSWORD'])
        self.assertEqual((self.root / '.env.prod.database').read_bytes(), database)
        self.settings['app_url'] = 'https://other.example'
        with self.assertRaisesRegex(ValueError, 'infrastructure'):
            installer.resume_settings(self.settings_file(), True)

    def test_corrected_certificate_inputs_refresh_private_readonly_mounts_on_resume(self):
        directory = self.root / 'inputs'
        directory.mkdir(mode=0o700)
        for name in ('certificate.pem', 'private-key.pem'):
            (directory / name).write_text('synthetic fixture input')
            (directory / name).chmod(0o600)
        (self.root / 'docker/nginx').mkdir(parents=True)
        shutil.copyfile(ROOT / 'docker/nginx/production.conf', self.root / 'docker/nginx/production.conf')
        self.settings['tls'] = {'mode': 'builtin', 'certificate': str(directory / 'certificate.pem'), 'private_key': str(directory / 'private-key.pem'), 'bind': '127.0.0.1', 'https_port': 443}
        with patch.object(management, 'ensure_image', return_value='sha256:' + 'a' * 64), patch.object(installer, 'run'):
            installer.provision_tls(self.settings)
            (directory / 'private-key.pem').write_text('synthetic corrected fixture key')
            installer.provision_tls(self.settings)
        self.assertEqual((self.state / 'tls/private-key.pem').read_text(), 'synthetic corrected fixture key')
        self.assertEqual((self.state / 'tls').stat().st_mode & 0o777, 0o700)
        self.assertEqual((self.state / 'tls/private-key.pem').stat().st_mode & 0o777, 0o444)
        overlay = json.loads((self.state / 'tls/compose.json').read_text())
        self.assertTrue(all(mount['read_only'] for mount in overlay['services']['nginx']['volumes']))

    def test_development_refuses_to_take_over_another_workspaces_containers(self):
        def run(arguments, **kwargs):
            return Mock(stdout='fixture-id\n' if arguments[:2] == ['docker', 'ps'] else json.dumps([{'Config': {'Labels': {'com.docker.compose.project.working_dir': '/other/workspace'}}}]))
        with patch.object(installer, 'run', side_effect=run), self.assertRaisesRegex(ValueError, 'another checkout'):
            installer.development_context()

    def test_production_tls_compose_overlay_is_explicit_and_simulation_project_cannot_target_dev(self):
        (self.root / '.env.prod.compose').write_text('RUNA_IMAGE_TAG=' + self.sha + '\nRUNA_PRODUCTION_MODE=simulation\nRUNA_TLS_MODE=builtin\nRUNA_SIMULATION_PROJECT=runa-prod-simulation-fixture\n')
        with self.assertRaisesRegex(ValueError, 'TLS'):
            production.compose()
        (self.state / 'tls').mkdir()
        (self.state / 'tls/compose.json').write_text('{}')
        command = production.compose()
        self.assertEqual(command[command.index('--project-name') + 1], 'runa-prod-simulation-fixture')
        self.assertEqual(command[-2:], ['-f', str(self.state / 'tls/compose.json')])
        (self.root / '.env.prod.compose').write_text('RUNA_IMAGE_TAG=' + self.sha + '\nRUNA_PRODUCTION_MODE=real\nRUNA_SIMULATION_PROJECT=runa-prod-simulation-fixture\n')
        with self.assertRaises(ValueError):
            production.compose()

    def test_customer_host_transport_rejects_installation(self):
        plan = {'schema_version': 1, 'request_id': 'a' * 32, 'operation': 'tooling.install', 'parameters': {'arguments': ['dev', self.sha]}}
        with self.assertRaises(ValueError):
            management.validate_plan(json.dumps(plan), 'a' * 32)


if __name__ == '__main__':
    unittest.main()
