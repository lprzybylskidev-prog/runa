#!/usr/bin/env python3
"""Authenticated local host boundary, replay, framing and private state regressions."""
import base64
import fcntl
import signal
import contextlib
import io
import json
import os
from pathlib import Path
import socket
import sys
import tempfile
import threading
import time
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'tools/application'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
sys.path.insert(0, str(ROOT / 'distribution'))
import host_executor as host
import management


class HostContracts(unittest.TestCase):
    def setUp(self):
        self.credentials = {'token': 'a' * 64, 'workspace': 'b' * 64}
        self.challenge = 'c' * 64
        self.value = {'version': 1, **self.credentials, 'challenge': self.challenge, 'id': 'd' * 32,
                      'command': 'runa:dev:status', 'arguments': [], 'interactive': False}

    def test_request_is_bound_to_private_credentials_workspace_and_one_time_challenge(self):
        self.assertEqual(host.validate_request(self.value, self.credentials, self.challenge), self.value)
        changes = [{'token': 'e' * 64}, {'workspace': 'e' * 64}, {'challenge': 'e' * 64}, {'id': '../escape'},
                   {'command': 'sh'}, {'arguments': ['--host-request=' + 'd' * 32]}, {'arguments': ['--plan']},
                   {'arguments': ['x\0y']}, {'cwd': '/tmp'}, {'environment': {}}, {'version': True}, {'interactive': 1}]
        for change in changes:
            with self.subTest(change=change), self.assertRaises(ValueError):
                host.validate_request(self.value | change, self.credentials, self.challenge)

    def test_every_registered_command_name_can_reach_independent_validation(self):
        definitions = json.loads((ROOT / 'src/runa/management/operations.json').read_text()) + json.loads((ROOT / 'src/runa/management/development.json').read_text())
        for entry in definitions:
            value = self.value | {'command': entry['name']}
            self.assertEqual(host.validate_request(value, self.credentials, self.challenge), value)
        for name in ('runa::test', 'runa:test;e2e', 'runa:2test', 'runa:Test', 'runa:test e2e'):
            with self.subTest(name=name), self.assertRaises(ValueError):
                host.validate_request(self.value | {'command': name}, self.credentials, self.challenge)

    def test_transport_rejects_duplicate_and_oversized_frames(self):
        for content in (b'{"id":1,"id":2}\n', b'x' * (host.LIMIT + 1), b'{}', b''):
            with self.subTest(content=content[:20]), self.assertRaises(ValueError):
                host.read_json(io.BytesIO(content))

    def test_trusted_artisan_revalidates_literal_args_without_network_mounts_or_caller_shell(self):
        plan = {'schema_version': 1, 'request_id': self.value['id'], 'operation': 'development.status', 'parameters': {}}
        with patch.object(management, 'bounded_output', return_value=(0, json.dumps(plan), '')) as bounded, patch.object(host.subprocess, 'run'):
            self.assertEqual(host.validated_plan(self.value, 'sha256:' + 'a' * 64), plan)
            command = bounded.call_args.args[0]
            self.assertIn('--network', command)
            self.assertIn('none', command)
            self.assertNotIn('--mount', command)
            self.assertIn('--host-request=' + self.value['id'], command)

    def test_registry_rejection_never_returns_an_executable_plan(self):
        with patch.object(management, 'bounded_output', return_value=(2, 'private-error', '')), patch.object(host.subprocess, 'run'), self.assertRaises(ValueError):
            host.validated_plan(self.value, 'sha256:' + 'a' * 64)

    def test_stop_recovers_only_known_workspace_paths_owned_by_the_operator(self):
        with tempfile.TemporaryDirectory() as folder:
            directory = Path(folder)
            credentials = directory / 'client.json'
            lock = (directory / 'daemon.lock').open('w')
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            self.addCleanup(lock.close)
            pid = os.getpid()
            for script, allowed in ((ROOT / 'tools/project/host_executor.py', True), (ROOT / 'tools/application/host_executor.py', True), (Path('/other/host_executor.py'), False)):
                credentials.write_text(json.dumps({'pid': pid}))
                argv = os.fsencode('/usr/bin/python3') + b'\0' + os.fsencode(script) + b'\0--serve\0'
                with patch.object(host, 'directory', return_value=directory), patch.object(Path, 'read_bytes', return_value=argv), patch.object(host.os, 'kill', side_effect=lambda *_: credentials.unlink()) as kill:
                    if allowed:
                        host.stop()
                        kill.assert_called_once_with(pid, signal.SIGTERM)
                    else:
                        with self.assertRaisesRegex(ValueError, 'ownership is uncertain'):
                            host.stop()
                        kill.assert_not_called()

    def test_state_directories_refuse_foreign_permissions_and_symlinks(self):
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / 'private'
            management.private_directory(path)
            self.assertEqual(path.stat().st_mode & 0o777, 0o700)
            path.chmod(0o755)
            with self.assertRaises(ValueError):
                management.private_directory(path)
            link = Path(folder) / 'link'
            link.symlink_to(path)
            with self.assertRaises(ValueError):
                management.private_directory(link)

    def test_real_unix_authentication_replay_and_durable_completion(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            directory = root / '.runtime/host'
            directory.mkdir(parents=True, mode=0o700)
            (directory / 'operations').mkdir(mode=0o700)
            executed = []
            def run_operation(handler, plan, interactive, state, receipt):
                executed.append(plan)
                state.update(state='completed', exit_code=0)
                handler.wfile.write(host.encode({'type': 'exit', 'code': 0}))
            plan = {'schema_version': 1, 'request_id': self.value['id'], 'operation': 'development.status', 'parameters': {}}
            with patch.object(host, 'ROOT', root), patch.object(management, 'image_identity', return_value='test'), patch.object(host, 'validated_plan', return_value=plan), patch.object(host.Handler, 'run_operation', run_operation):
                with host.Server(str(root / 'socket'), host.Handler) as server:
                    server.credentials, server.identity, server.image = self.credentials, 'test', 'fixture'
                    thread = threading.Thread(target=server.serve_forever, daemon=True)
                    thread.start()
                    try:
                        def request(value):
                            with socket.socket(socket.AF_UNIX) as client:
                                client.connect(str(root / 'socket'))
                                stream = client.makefile('rwb', buffering=0)
                                challenge = host.read_json(stream)
                                stream.write(host.encode(value | {'challenge': challenge['value']}))
                                frame = host.read_json(stream)
                                return host.read_json(stream) if frame['type'] == 'accepted' else frame
                        self.assertEqual(request(self.value | {'token': 'invalid'})['type'], 'error')
                        self.assertEqual(executed, [])
                        self.assertEqual(request(self.value)['type'], 'exit')
                        # Receipt is finally persisted before the next request finishes.
                        time.sleep(0.02)
                        self.assertEqual(request(self.value)['type'], 'error')
                        self.assertEqual(len(executed), 1)
                        receipt = json.loads((directory / 'operations' / (self.value['id'] + '.json')).read_text())
                        self.assertEqual(receipt['state'], 'completed')
                        self.assertNotIn('token', receipt)
                        self.assertEqual((directory / 'operations' / (self.value['id'] + '.json')).stat().st_mode & 0o777, 0o600)
                    finally:
                        server.shutdown()
                        thread.join(timeout=5)


if __name__ == '__main__':
    unittest.main()
