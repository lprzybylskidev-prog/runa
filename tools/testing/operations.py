#!/usr/bin/env python3
"""Failure-first contracts for deployment identity and destructive backup boundaries."""
import contextlib
import io
import json
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import operations as ops


class OperationContracts(unittest.TestCase):
    def test_pause_keeps_nginx_serving_and_marks_maintenance_before_stopping_writers(self):
        events = []
        with patch.object(ops, 'http_maintenance', side_effect=lambda enabled, release: events.append(('marker', enabled))), patch.object(ops, 'dc', side_effect=lambda args, release: events.append(('compose', args))):
            ops.pause_http('/release')
        self.assertEqual(events[0], ('marker', True))
        self.assertEqual(events[1][1], ['up', '-d', '--no-build', '--no-deps', 'nginx'])
        self.assertEqual(events[2][1], ['stop', 'queue', 'scheduler', 'app'])

    def test_resume_waits_for_writers_and_restores_maintenance_on_nginx_health_failure(self):
        events = []
        def dc(args, release):
            events.append(('compose', args))
            if args == ['up', '-d', '--wait', '--no-build', '--no-deps', 'nginx']:
                raise ValueError('nginx health failed')
        with patch.object(ops, 'http_maintenance', side_effect=lambda enabled, release: events.append(('marker', enabled))), patch.object(ops, 'dc', side_effect=dc):
            with self.assertRaisesRegex(ValueError, 'nginx health failed'):
                ops.resume_http('/release', recreate=True)
        self.assertIn('--wait', events[0][1])
        self.assertNotIn('nginx', events[0][1])
        self.assertEqual(events[2], ('marker', False))
        self.assertEqual(events[-1], ('marker', True))

    def test_compose_cannot_consume_remaining_parent_script_input(self):
        code = """import sys
sys.path.insert(0, 'tools/project')
import operations
operations.command = lambda release=None: [sys.executable, '-c', 'import sys; print("child:" + sys.stdin.read())']
operations.dc([])
print('parent:' + sys.stdin.read())
"""
        result = subprocess.run([sys.executable, '-c', code], cwd=ROOT, input='remaining shell commands', text=True, capture_output=True, check=True)
        self.assertEqual(result.stdout, 'child:\nparent:remaining shell commands\n')

    def test_explicit_backup_stream_and_input_are_preserved(self):
        with patch.object(ops, 'command', return_value=['compose']), patch.object(ops, 'run') as run:
            stream = object()
            ops.dc(['exec'], stdin=stream)
            self.assertIs(run.call_args.kwargs['stdin'], stream)
            ops.dc(['exec'], input=b'archive')
            self.assertEqual(run.call_args.kwargs['input'], b'archive')
            self.assertNotIn('stdin', run.call_args.kwargs)

    def test_atomic_evidence_is_private_and_valid_json(self):
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / 'private/state.json'
            ops.atomic(path, {'phase': 'migrations'})
            self.assertEqual(json.loads(path.read_text()), {'phase': 'migrations'})
            self.assertEqual(path.stat().st_mode & 0o777, 0o600)
            self.assertFalse(path.with_suffix('.tmp').exists())

    def test_actual_sparse_checkout_preserves_operations_and_excludes_knowledge(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / 'repository'
            root.mkdir()
            files = {'VERSION': '0.0.0', 'docs/project/state.yml': 'production: {}', 'src/app/example.php': '<?php',
                     'src/runa/Http/Controllers/HealthController.php': '<?php', 'src/runa/lang/pl/validation.php': '<?php',
                     'src/runa/management/example.php': 'excluded', 'src/runa/tools/example.py': 'excluded', 'src/runa/tests/example.php': 'excluded',
                     'docker/php/Dockerfile': 'FROM example', '.agents/example': 'excluded', 'bin/knowledge': 'excluded',
                     'tools/knowledge/example.py': 'excluded', 'bin/operations': 'launcher', 'bin/production': 'launcher',
                     'docs/ai/example.md': 'excluded', 'docker/embeddings/example': 'excluded'}
            for name, text in files.items():
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(text)
            subprocess.run(['git', 'init', '-q', str(root)], check=True)
            subprocess.run(['git', '-C', str(root), 'add', '.'], check=True)
            subprocess.run(['git', '-C', str(root), '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture'], check=True)
            sha = subprocess.check_output(['git', '-C', str(root), 'rev-parse', 'HEAD'], text=True).strip()
            import runtime
            with patch.object(ops, 'ROOT', root), patch.object(runtime, 'ROOT', root), patch.object(ops, 'STATE', Path(folder) / 'state'):
                release = ops.checkout(sha)
                ops.assert_sparse(release)
                self.assertTrue((release / 'src/app/example.php').is_file())
                self.assertTrue((release / 'src/runa/Http/Controllers/HealthController.php').is_file())
                self.assertTrue((release / 'src/runa/lang/pl/validation.php').is_file())
                for excluded in ('management', 'tools', 'tests'):
                    self.assertFalse((release / 'src/runa' / excluded).exists())
                self.assertTrue((release / 'bin/operations').is_file())
                self.assertTrue((release / 'docker/php/Dockerfile').is_file())
                (release / 'VERSION').write_text('tampered')
                with self.assertRaisesRegex(ValueError, 'modified'):
                    ops.checkout(sha)

    def test_exact_target_rejects_shell_options_and_mutable_refs(self):
        with patch.object(ops, 'run') as call:
            for target in ('main', 'latest', '--help', 'abc123', 'v1.2.3;false', 'v1.2.3\n'):
                with self.assertRaises(ValueError):
                    ops.resolve(target)
            call.assert_not_called()

    def test_failed_deploy_keeps_recovery_marker_when_schema_sources_change(self):
        self.check_failed_deploy(compatible=False)

    def test_failed_deploy_returns_to_previous_compatible_images_but_still_fails(self):
        self.check_failed_deploy(compatible=True)

    def check_failed_deploy(self, compatible):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            state = root / 'state'
            sha = 'a' * 40
            previous = {'sha': 'b' * 40, 'path': '/previous', 'database_tree': 'same' if compatible else 'different', 'simulation': True, 'images': {'postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873': 'fixture'}}
            config = root / '.env.prod.compose'
            config.write_text('RUNA_IMAGE_TAG=' + previous['sha'] + '\nRUNA_PRODUCTION_MODE=simulation\n')
            calls = []
            def dc(args, release=None, **kwargs):
                calls.append((args, release))
                return subprocess.CompletedProcess(args, 0, stdout='')
            with patch.object(ops, 'ROOT', root), patch.object(ops, 'STATE', state), patch.object(ops, 'resolve', return_value=sha), patch.object(ops, 'read_state', return_value={'current': previous}), patch.object(ops, 'checkout', return_value=root / sha), patch.object(ops, 'dc', side_effect=dc), patch.object(ops.production, 'verify_images'), patch.object(ops, 'command', return_value=[]), patch.object(ops, 'validate'), patch.object(ops, 'build_images'), patch.object(ops, 'create_backup'), patch.object(ops, 'tree_hash', return_value='same'), patch.object(ops, 'health') as health, patch.object(ops, 'artisan', side_effect=ValueError('migration failed')), contextlib.redirect_stdout(io.StringIO()):
                with self.assertRaisesRegex(ValueError, 'migration failed'):
                    ops.deploy(sha, True)
                self.assertEqual((state / 'in-progress.json').exists(), not compatible)
                if compatible:
                    health.assert_called_once_with('/previous', sparse=True)
                    self.assertIn('RUNA_IMAGE_TAG=' + previous['sha'], config.read_text())
                else:
                    health.assert_not_called()
                self.assertFalse(any('migrate:rollback' in arg for args, _ in calls for arg in args))

    def test_exclusive_operation_lock(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'STATE', Path(folder)):
            with ops.lock():
                with self.assertRaisesRegex(ValueError, 'Another production'):
                    with ops.lock():
                        pass

    def test_environment_file_rejects_duplicate_keys_and_multiline(self):
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / 'env'
            for text in ('DB_PASSWORD=x\nDB_PASSWORD=y\n', 'INVALID LINE\n', 'BAD_KEY=a\x00b\n'):
                path.write_text(text)
                with self.assertRaises(ValueError):
                    ops.read_env(path)
            path.write_text('DB_PASSWORD=$literal#value\n')
            self.assertEqual(ops.read_env(path)['DB_PASSWORD'], '$literal#value')

    def test_rollback_blocks_migration_or_seeder_change_before_any_mutation(self):
        current = {'database_tree': 'new'}
        previous = {'database_tree': 'old'}
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'STATE', Path(folder)), patch.object(ops, 'read_state', return_value={'current': current, 'previous': previous}), patch.object(ops, 'dc') as dc, patch.object(ops, 'set_tag') as tag:
            with self.assertRaisesRegex(ValueError, 'Migration/seeder'):
                ops.rollback()
            dc.assert_not_called()
            tag.assert_not_called()

    def test_release_commands_pin_their_own_sha_and_clear_ambient_context(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'ROOT', Path(folder)):
            root = Path(folder)
            sha = 'a' * 40
            release = root / 'releases' / sha
            release.mkdir(parents=True)
            for name, text in (('.env.prod', 'APP_KEY=x\n'), ('.env.prod.database', 'POSTGRES_PASSWORD=y\n'), ('.env.prod.compose', 'RUNA_IMAGE_TAG=' + 'b' * 40 + '\nRUNA_PRODUCTION_MODE=simulation\n')):
                (root / name).write_text(text)
            command = ops.command(release)
            self.assertEqual(command[command.index('--project-name') + 1], 'runa-prod-icu-simulation')
            self.assertIn('RUNA_IMAGE_TAG=' + sha, (release / '.env.prod.compose').read_text())
            self.assertEqual((release / '.env.prod').stat().st_mode & 0o777, 0o600)

    def test_backup_path_and_symlink_rejection(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'backup_root', return_value=(Path(folder), 7)):
            for name in ('../other', '/tmp/archive', 'backup-latest'):
                with self.assertRaises(ValueError):
                    ops.backup_path(name)
            path = Path(folder) / 'backup-20261017T123456123456Z'
            path.symlink_to(Path(folder), target_is_directory=True)
            with self.assertRaises(ValueError):
                ops.backup_path(path.name)

    def test_tamper_and_archive_escape_rejected_before_pg_restore(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'pg') as pg:
            root = Path(folder)
            (root / 'database.dump').write_bytes(b'dump')
            for member_name in ('app/../../etc/passwd', '/etc/passwd', 'app/link'):
                with tarfile.open(root / 'storage.tar.gz', 'w:gz') as archive:
                    directory = tarfile.TarInfo('app')
                    directory.type = tarfile.DIRTYPE
                    archive.addfile(directory)
                    member = tarfile.TarInfo(member_name)
                    member.type = tarfile.SYMTYPE if member_name.endswith('link') else tarfile.REGTYPE
                    member.linkname = '/etc/passwd'
                    archive.addfile(member)
                manifest = {'format': 1, 'files': {name: ops.digest(root / name) for name in ('database.dump', 'storage.tar.gz')}}
                (root / 'manifest.json').write_text(json.dumps(manifest))
                with self.assertRaisesRegex(ValueError, 'Unsafe storage'):
                    ops.verify_backup_files(root, None)
                pg.assert_not_called()
            (root / 'database.dump').write_bytes(b'corrupt')
            with self.assertRaisesRegex(ValueError, 'integrity mismatch'):
                ops.verify_backup_files(root, None)
            pg.assert_not_called()

    def test_failed_isolated_restore_always_drops_only_generated_validation_db(self):
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'backup_path', return_value=Path(folder)), patch.object(ops, 'active_release', return_value=None), patch.object(ops, 'verify_backup_files'), patch.object(ops, 'pg') as pg:
            (Path(folder) / 'database.dump').write_bytes(b'dump')
            pg.side_effect = [None, ValueError('restore failed'), None]
            with self.assertRaisesRegex(ValueError, 'restore failed'):
                ops.verify_backup('backup-id')
            create = pg.call_args_list[0].args[0]
            drop = pg.call_args_list[-1].args[0]
            self.assertEqual(drop[0], 'dropdb')
            self.assertEqual(create[-1], drop[-1])
            self.assertTrue(drop[-1].startswith('runa_backup_verify_'))

    def test_restore_rejects_cross_version_and_simulation_before_mutation(self):
        current = {'sha': 'a' * 40, 'path': '/release', 'simulation': True}
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'STATE', Path(folder)), patch.object(ops, 'read_state', return_value={'current': current}), patch.object(ops, 'backup_path', return_value=Path(folder)), patch.object(ops, 'verify_backup_files', return_value={'deployment': dict(current, simulation=False)}), patch.object(ops, 'dc') as dc:
            with self.assertRaisesRegex(ValueError, 'same application SHA'):
                ops.restore('backup-id', True)
            dc.assert_not_called()

    def test_backup_failure_never_publishes_complete_archive_and_restarts_services(self):
        current = {'path': '/release'}
        with tempfile.TemporaryDirectory() as folder, patch.object(ops, 'STATE', Path(folder) / 'state'), patch.object(ops, 'read_state', return_value={'current': current}), patch.object(ops, 'health'), patch.object(ops, 'backup_root', return_value=(Path(folder), 7)), patch.object(ops, 'dc') as dc, patch.object(ops, 'pg', side_effect=ValueError('dump failed')):
            with self.assertRaisesRegex(ValueError, 'dump failed'):
                ops.create_backup()
            self.assertEqual(dc.call_args_list[-1].args[0], ['up', '-d', '--wait', '--no-build', '--no-deps', 'nginx'])
            self.assertTrue(all(path.name.endswith('.partial') for path in Path(folder).iterdir() if path.name != 'state'))


if __name__ == '__main__':
    unittest.main()
