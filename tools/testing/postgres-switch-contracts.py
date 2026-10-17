#!/usr/bin/env python3
"""Switch preflight rejects unknown/reused contexts before mutations."""
import contextlib
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
spec = importlib.util.spec_from_file_location('icu_switch', ROOT / 'tools/testing/switch-postgres-icu.py')
switch = importlib.util.module_from_spec(spec)
spec.loader.exec_module(switch)


class SwitchContracts(unittest.TestCase):
    def test_existing_target_refuses_before_stop_or_recreate(self):
        config = {'name': 'dev-fixture', 'services': {'postgres': {'image': switch.IMAGE}}, 'volumes': {'postgres-icu-data': {'name': 'dev-fixture_postgres-icu-data'}}}
        original = {'Config': {'Image': 'postgres:18.6-bookworm', 'Labels': {'com.docker.compose.project': 'dev-fixture'}}, 'Mounts': [{'Destination': '/var/lib/postgresql', 'Type': 'volume', 'Name': 'dev-fixture_postgres-data'}]}
        with tempfile.TemporaryDirectory() as folder, patch.object(switch, 'ROOT', Path(folder)), patch.object(switch, 'inside', return_value=False), patch.object(switch, 'output', side_effect=[json.dumps(config), 'old-id', 'app-id']), patch.object(switch, 'inspect', return_value=original), patch.object(switch, 'run') as run, patch.object(switch.subprocess, 'run', return_value=subprocess.CompletedProcess([], 0)):
            with self.assertRaisesRegex(ValueError, 'Target volume already exists'):
                switch.main()
            run.assert_not_called()
            self.assertFalse((Path(folder) / '.runtime/postgres-icu-switch').exists())

    def test_fingerprint_mount_is_readonly_and_has_no_socket(self):
        with patch.object(switch, 'output', return_value='digest') as output:
            self.assertEqual(switch.fingerprint('old-volume', 'sha256:old-image'), 'digest')
            args = output.call_args.args[0]
            self.assertIn('type=volume,src=old-volume,dst=/old,readonly', args)
            self.assertIn('sha256:old-image', args)
            self.assertEqual(args[args.index('--user') + 1], 'postgres')
            self.assertNotIn('docker.sock', ' '.join(args))
            self.assertNotIn('rm -', args[-1])
            self.assertIn('&&', args[-1])

    def test_container_context_refuses_without_docker_call(self):
        with patch.object(switch, 'inside', return_value=True), patch.object(switch, 'run') as run:
            with self.assertRaisesRegex(ValueError, 'Docker host'):
                switch.main()
            run.assert_not_called()


if __name__ == '__main__':
    unittest.main()
