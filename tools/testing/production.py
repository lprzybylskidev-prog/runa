#!/usr/bin/env python3
"""Production topology and command-boundary contracts without Docker privileges."""
import contextlib
import io
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import production
from checks import yaml


class ProductionContracts(unittest.TestCase):
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
        self.assertEqual(public['volume']['subpath'], 'app/public')
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
