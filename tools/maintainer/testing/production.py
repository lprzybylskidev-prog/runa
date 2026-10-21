#!/usr/bin/env python3
"""Production topology and command-boundary contracts without Docker privileges."""
import contextlib
import io
import json
import subprocess
import os
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import production
from checks import yaml

import repository_boundary


class ProductionContracts(unittest.TestCase):
    def setUp(self):
        boundary = patch.object(repository_boundary, 'assert_installation', return_value={'mode': 'prod'})
        boundary.start()
        self.addCleanup(boundary.stop)

    def test_every_production_action_is_blocked_before_locks_or_host_work_in_dev(self):
        import operations
        actions = ['validate', 'build', 'up', 'down', 'restart', 'status', 'logs', 'self-test', 'verify-ai-isolation']
        for mode in ('dev', None, 'unknown'):
            for action in actions:
                for entrypoint in (production.main, production.dispatch):
                    with self.subTest(mode=mode, action=action, entrypoint=entrypoint.__name__), patch.object(repository_boundary, 'assert_installation', return_value={'mode': mode}), patch.object(production, 'inside', return_value=False), patch.object(production, 'run') as run, patch.object(production, 'compose') as compose, patch.object(operations, 'lock') as lock, patch.object(operations, 'active_release') as active, patch.dict(os.environ, {'APP_ENV': 'production'}):
                        with self.assertRaisesRegex(ValueError, 'require a production installation'):
                            entrypoint([action])
                        run.assert_not_called()
                        compose.assert_not_called()
                        lock.assert_not_called()
                        active.assert_not_called()

    def test_customer_redis_is_supported_while_actual_ai_services_and_privileges_remain_forbidden(self):
        import operations
        config = {'services': {name: {'image': 'fixture', 'environment': {'KNOWLEDGE_ENABLED': 'false'}} for name in (*production.SERVICES, 'redis')}}
        def dc(arguments, *args, **kwargs):
            return subprocess.CompletedProcess(arguments, 0, arguments[-1])
        def run(arguments, **kwargs):
            actual = {'Image': 'exact', 'HostConfig': {}, 'Config': {'Env': ['KNOWLEDGE_ENABLED=false']}, 'Mounts': []}
            return subprocess.CompletedProcess(arguments, 0, json.dumps([actual]) if arguments[1] == 'inspect' else 'exact')
        with patch.object(operations, 'resolved', return_value=config), patch.object(operations, 'dc', side_effect=dc), patch.object(operations, 'run', side_effect=run), patch.object(operations, 'command', return_value=['compose']), patch.object(production, 'verify_images'), contextlib.redirect_stdout(io.StringIO()):
            operations.isolation()
            config['services']['redis']['privileged'] = True
            with self.assertRaisesRegex(ValueError, 'privileges'): operations.isolation()
            config['services']['redis'].pop('privileged')
            config['services']['qdrant'] = {'image': 'qdrant/qdrant:fixture'}
            with self.assertRaisesRegex(ValueError, 'AI/development'): operations.isolation()

    def test_dev_block_preserves_files_even_with_production_settings_present(self):
        import operations
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            settings = root / '.env.prod.compose'
            settings.write_text('RUNA_PRODUCTION_MODE=simulation\nRUNA_IMAGE_TAG=v0.2.1\n')
            original = settings.read_bytes()
            with patch.object(repository_boundary, 'assert_installation', return_value={'mode': 'dev'}), patch.object(production, 'ROOT', root), patch.object(production, 'inside', return_value=False), patch.object(operations, 'STATE', root / '.runtime/production'):
                with self.assertRaises(ValueError):
                    production.main(['build'])
            self.assertEqual(settings.read_bytes(), original)
            self.assertFalse((root / '.runtime').exists())

    def test_runtime_topology_and_isolation(self):
        data = yaml(ROOT / 'compose.production.yaml')
        services = data['services']
        self.assertEqual(set(services), set(production.SERVICES))
        for key, service in services.items():
            if key != 'nginx':
                self.assertIn('healthcheck', service)
            self.assertEqual(service['logging']['options'], {'max-size': '10m', 'max-file': '3'})
            self.assertFalse(service.get('privileged', False))
            self.assertNotIn('ports', service if key != 'nginx' else {})
        for key in ('app', 'queue', 'scheduler'):
            service = services[key]
            self.assertTrue(service['read_only'])
            self.assertEqual(service['environment']['KNOWLEDGE_ENABLED'], 'false')
            self.assertEqual(service['environment']['QUEUE_CONNECTION'], 'database')
            self.assertEqual(service['cap_drop'], ['ALL'])
        public = services['nginx']['volumes'][0]
        self.assertTrue(public['read_only'])
        self.assertEqual(public['volume']['subpath'], 'app/application/public')
        self.assertEqual([(mount['volume']['subpath'], mount['read_only']) for mount in services['nginx']['volumes']],
                         [('app/application/public', True), ('app/runa/public', True), ('framework/maintenance', True)])
        self.assertEqual(set(data['volumes']), {'application-storage', 'postgres-icu-data'})
        for service in services.values():
            for mount in service.get('tmpfs', []):
                self.assertTrue(mount.startswith('/'), 'tmpfs options must stay inside one quoted mount string')
        self.assertEqual(len(services['nginx']['tmpfs']), 1)
        nginx_config = (ROOT / 'docker/nginx/production.conf').read_text()
        self.assertIn('location = / { rewrite ^ /index.php last; }', nginx_config)
        production_stage = (ROOT / 'docker/php/Dockerfile').read_text().split('FROM production-runtime AS production\n')[1].split('FROM nginx:')[0]
        self.assertIn('ENTRYPOINT ["runa-production-entrypoint"]', production_stage)
        self.assertIn('CMD ["php-fpm", "-F"]', production_stage)

    def test_emergency_scope_excludes_php_errors_and_sensitive_storage(self):
        config = (ROOT / 'docker/nginx/production.conf').read_text()
        self.assertIn('fastcgi_intercept_errors off;', config)
        self.assertIn('error_page 502 503 504 =503 $runa_error_document;', config)
        self.assertIn('location ^~ /runa-error/pages/ {\n            internal;', config)
        mounts = yaml(ROOT / 'compose.production.yaml')['services']['nginx']['volumes']
        maintenance = next(mount for mount in mounts if mount['target'] == '/run/runa-maintenance')
        self.assertTrue(maintenance['read_only'])
        self.assertEqual(maintenance['volume']['subpath'], 'framework/maintenance')
        image_source = (ROOT / 'docker/php/Dockerfile').read_text()
        self.assertIn('RUN php runa/resources/errors/build.php', image_source)
        self.assertEqual(image_source.count('/tmp/runa-build/src/public/runa-error/ /workspace/src/public/runa-error/'), 2)

    def test_native_mail_runtime_inputs_are_shipped_without_review_fixtures(self):
        stage = (ROOT / 'docker/php/Dockerfile').read_text().split('FROM production-runtime AS production\n')[1].split('FROM nginx:')[0]
        for source in ('src/runa/resources/views/', 'src/runa/resources/images/runa-mail-logo.png', 'src/runa/resources/css/light-palette.css', 'src/runa/platform.json'):
            self.assertTrue((ROOT / source).exists())
            self.assertIn('COPY --chown=runa:runa ' + source + ' /workspace/' + source, stage)
        self.assertNotIn('src/runa/resources/review', stage)
        self.assertNotIn('COPY --chown=runa:runa src/runa/resources/ /workspace/src/runa/resources/', stage)

    def test_shared_nginx_runtime_digest_stays_consistent(self):
        development = yaml(ROOT / 'compose.yaml')['services']
        image = development['nginx']['image']
        self.assertRegex(image, r'^nginx:[^@]+@sha256:[a-f0-9]{64}$')
        self.assertEqual(development['nginx-test']['image'], image)
        self.assertIn('FROM ' + image + ' AS production-nginx', (ROOT / 'docker/php/Dockerfile').read_text())

    def test_context_and_argument_validation(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(production, 'ROOT', Path(folder)), patch.object(production, 'inside', return_value=False), patch.object(production, 'run') as run, patch.dict(production.os.environ, {'RUNA_IMAGE_TAG': 'latest', 'RUNA_HTTP_PORT': '80', 'COMPOSE_PROJECT_NAME': 'runa-dev'}), contextlib.redirect_stdout(io.StringIO()):
            config = Path(folder) / '.env.prod.compose'
            config.write_text('RUNA_IMAGE_TAG=v0.1.0\nRUNA_HTTP_PORT=8081\n')
            production.main(['down'])
            command = run.call_args.args[0]
            self.assertEqual(command[-1], 'down')
            self.assertNotIn('-v', command)
            self.assertIn(str(Path(folder) / 'compose.production.yaml'), command)
            self.assertNotIn('compose.yaml', command)
            self.assertEqual(command[command.index('--project-name') + 1], 'runa-prod')
            self.assertNotIn('RUNA_IMAGE_TAG', run.call_args.kwargs['env'])
            for args in (['down', '-v'], ['logs', 'qdrant'], ['up', '--force-recreate']):
                with self.assertRaises(ValueError):
                    production.main(args)
            for tag in ('latest', 'main', 'v0.1.0; false', 'abc123'):
                config.write_text('RUNA_IMAGE_TAG=' + tag + '\n')
                with self.assertRaises(ValueError):
                    production.compose()
            with patch.object(production, 'inside', return_value=True), self.assertRaisesRegex(ValueError, 'host terminal'):
                production.main(['build'])


if __name__ == '__main__':
    unittest.main()
