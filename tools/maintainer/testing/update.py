#!/usr/bin/env python3
"""External updater failure-first source/plan contracts, real Git reference downloads."""
import contextlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'distribution'))
import bootstrap
import update
import update_contracts as contracts
import update_runtime


class Updater(unittest.TestCase):
    def git(self, root, *args):
        return subprocess.check_output(['git', '-C', str(root), *args], stderr=subprocess.DEVNULL).decode().strip()

    def commit(self, root):
        self.git(root, 'add', '--all')
        self.git(root, '-c', 'core.hooksPath=/dev/null', '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture')
        return self.git(root, 'rev-parse', 'HEAD')

    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.original, self.target, self.installed = [self.base / name for name in ('original', 'target', 'installed')]
        self.original.mkdir()
        files = {'VERSION': '0.2.1\n', 'src/runa/platform.json': json.dumps({'schema_version': 1, 'id': 'runa', 'name': 'Runa', 'version': '0.2.1'}),
                 'src/app/application.json': json.dumps({'schema_version': 1, 'version': '7.4.2', 'runa': '>=0.2.1 <0.4.0'}),
                 'src/app/composer.json': '{}', 'src/app/package.json': '{}', 'src/runa/composer.json': '{}', 'src/runa/package.json': '{}',
                 'src/composer.json': '{}', 'src/package.json': '{}', 'src/composer.lock': '{}', 'src/pnpm-lock.yaml': 'lock', 'src/pnpm-workspace.yaml': '{}',
                 'src/runa/Support/example.php': '<?php // protected', 'compose.yaml': 'protected infrastructure',
                 'src/runa/database/migrations/2026_01_01_000001_initial.php': '<?php // initial', 'src/runa/database/seeders/DatabaseSeeder.php': '<?php // default',
                 'tools/application/operations.py': 'SPARSE = ["/VERSION", "/src/", "/tools/application/"]\n', 'docs/app/README.md': 'customer'}
        for name, content in files.items():
            path = self.original / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
        self.git(self.original, 'init', '-q')
        self.before = self.commit(self.original)
        self.git(self.original, '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'tag', '-a', 'v0.2.1', '-m', 'stable')
        shutil.copytree(self.original, self.target)
        (self.target / 'VERSION').write_text('0.3.0\n')
        self.manifest(self.target, '0.3.0')
        (self.target / 'src/runa/database/migrations/2026_01_02_000001_upgrade.php').write_text('<?php // never execute')
        self.after = self.commit(self.target)
        self.git(self.target, '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'tag', '-a', 'v0.3.0', '-m', 'stable')
        shutil.copytree(self.original, self.installed)
        self.installed.chmod(0o700)
        (self.installed / '.git/runa-bootstrap.json').write_text(json.dumps({'schema_version': 1, 'repository': 'fixture/runa', 'mode': 'dev', 'projection': {'version': 1, 'excluded': list(bootstrap.OWNER_PATHS)}}))
        (self.installed / '.runtime/production').mkdir(parents=True)
        (self.installed / '.runtime/production/install-dev.json').write_text('{"state":"complete"}')
        self.client = bootstrap.GitHub('fixture/runa')
        self.runtime = {'project': 'fixture', 'services': ['app', 'postgres'], 'migrations': ['2026_01_01_000001_initial'], 'seed_runs': []}

    def manifest(self, root, version):
        (root / 'src/runa/platform.json').write_text(json.dumps({'schema_version': 1, 'id': 'runa', 'name': 'Runa', 'version': version}))

    def download(self, client, tag, destination, mode, expected_commit=None):
        source = self.original if tag == 'v0.2.1' else self.target
        shutil.copytree(source, destination)
        return {'tag': tag, 'commit': self.before if tag == 'v0.2.1' else self.after, 'tag_object': 'f' * 40}

    def run_plan(self, **kwargs):
        return update.preflight(self.installed, self.client, 'v0.3.0', download=self.download,
                                runtime_probe=lambda *a: self.runtime, lock_probe=lambda *a: 'sha256:' + 'f' * 64, **kwargs)

    def test_ready_plan_lists_only_platform_data_steps_and_preserves_installation(self):
        before = self.snapshot()
        result = self.run_plan()
        self.assertEqual(result['status'], 'ready', result)
        self.assertEqual(result['plan']['platform_migrations'], ['2026_01_02_000001_upgrade'])
        self.assertEqual(result['plan']['platform_seeder']['class'], 'Runa\\Database\\Seeders\\DatabaseSeeder')
        self.assertFalse(result['execution_available'])
        self.assertTrue(result['temporary_downloads_cleaned'])
        self.assertTrue(all(not Path(p).exists() for p in result['temporary_downloads']))
        self.assertEqual(self.snapshot(), before)

    def snapshot(self):
        return {p.relative_to(self.installed).as_posix(): (p.stat().st_mode, p.read_bytes()) for p in self.installed.rglob('*') if p.is_file() and not p.is_symlink()}

    def test_original_is_independently_downloaded_not_adopted_from_receipt_or_git(self):
        (self.installed / 'src/runa/Support/example.php').write_text('<?php // changed')
        self.commit(self.installed)
        receipt = json.loads((self.installed / '.git/runa-bootstrap.json').read_text())
        receipt['source_sha256'] = 'f' * 64
        (self.installed / '.git/runa-bootstrap.json').write_text(json.dumps(receipt))
        result = self.run_plan()
        self.assertEqual(result['status'], 'blocked')
        self.assertIn({'path': 'src/runa/Support/example.php', 'kind': 'changed'}, result['mismatches'])
        self.assertEqual(result['checks'][-1]['id'], 'protected-source-mismatch')

    def test_missing_unexpected_mode_and_unsafe_symlink_diagnostics(self):
        for mutation in ('missing', 'unexpected', 'mode', 'symlink', 'storage-php', 'cache-php'):
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as folder:
                root = Path(folder) / 'copy'
                shutil.copytree(self.installed, root)
                path = root / 'src/runa/Support/example.php'
                if mutation == 'missing': path.unlink()
                if mutation == 'unexpected': (root / 'src/runa/Support/unexpected.php').write_text('<?php')
                if mutation == 'mode': path.chmod(0o755)
                if mutation == 'symlink': path.unlink(); path.symlink_to('/etc/passwd')
                if mutation in ('storage-php', 'cache-php'):
                    path = root / ('src/storage/app/runa/private/escape.php' if mutation == 'storage-php' else 'src/bootstrap/cache/escape.php')
                    path.parent.mkdir(parents=True, exist_ok=True); path.write_text('<?php')
                self.assertTrue(contracts.compare(root, self.original, 'source'))

    def test_application_storage_and_known_cache_are_preserved_without_hiding_source(self):
        for name in ('src/app/new.php', 'docs/app/custom.md', 'src/storage/app/application/private/data.bin', 'src/bootstrap/cache/config.php'):
            path = self.installed / name; path.parent.mkdir(parents=True, exist_ok=True); path.write_text('customer/generated content')
        self.assertEqual(self.run_plan()['status'], 'ready')

    def test_incompatible_and_unsupported_ranges_block(self):
        for constraint in ('>=0.4.0', '*', '>=0.2.1 || *', '>=0.2.1 ||'):
            (self.installed / 'src/app/application.json').write_text(json.dumps({'schema_version': 1, 'version': '1.0.0', 'runa': constraint}))
            self.assertEqual(self.run_plan()['status'], 'blocked')

    def test_unknown_versions_or_failed_reference_resolution_never_adopt_local_source(self):
        def fail(*args, **kwargs):
            raise bootstrap.BootstrapError('Release unavailable.')
        result = update.preflight(self.installed, self.client, 'v0.3.0', download=fail)
        self.assertEqual(result['checks'][-1]['id'], 'release-acquisition')
        self.assertTrue(result['temporary_downloads_cleaned'])
        self.manifest(self.installed, '0.2.2')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'platform-identity')

    def test_dependency_conflict_applied_migration_edit_and_custom_infra_block(self):
        (self.installed / 'src/app/composer.json').write_text('{"require":{"test/package":"2"}}')
        (self.installed / 'src/composer.json').write_text('{"require":{"test/package":"2"}}')
        (self.target / 'src/runa/composer.json').write_text('{"require":{"test/package":"1"}}')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'dependency-conflict')
        (self.target / 'src/runa/composer.json').write_text('{}')
        migration = self.target / 'src/runa/database/migrations/2026_01_01_000001_initial.php'
        migration.write_text('changed applied migration')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'applied-migration-changed')
        migration.write_bytes((self.original / migration.relative_to(self.target)).read_bytes())
        (self.target / 'compose.yaml').write_text('new topology')
        result = self.run_plan()
        self.assertEqual(result['checks'][-1]['id'], 'infrastructure-transition-required')
        self.assertEqual(result['plan']['infrastructure_changes'][0]['path'], 'compose.yaml')

    def test_operation_lock_pending_journal_symlink_and_owner_target_fail_closed(self):
        lock = self.installed / '.runtime/production/operation.lock'
        import fcntl
        with lock.open('w') as stream:
            lock.chmod(0o600)
            fcntl.flock(stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
            self.assertEqual(self.run_plan()['checks'][-1]['id'], 'operation-running')
        (lock.parent / 'in-progress.json').write_text('{}')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'unresolved-operation')
        alias = self.base / 'alias'; alias.symlink_to(self.installed)
        self.assertEqual(update.preflight(alias, self.client, 'v0.3.0')['checks'][-1]['id'], 'installation-ownership')
        (self.installed / '.git/runa-bootstrap.json').unlink()
        self.assertEqual(self.run_plan()['status'], 'blocked')

    def test_actual_verified_git_download_checks_tag_and_content_without_install_mutation(self):
        # Only transport is adapted to local Git; acquisition still resolves,
        # checks annotated refs, fsck, VERSION and every file/blob/mode.
        original_git = bootstrap.git
        def local_git(source, home, *args, **kwargs):
            if args[:3] == ('remote', 'add', 'origin'):
                args = (*args[:3], str(self.target))
            env = bootstrap.git_environment(home); env['GIT_ALLOW_PROTOCOL'] = 'file'
            return bootstrap.process(['git', '-c', 'core.hooksPath=/dev/null', '-C', str(source), *args], env=env, input_data=kwargs.get('input_data'))
        def resolve(tag):
            return {'release_id': 1, 'tag_object': self.git(self.target, 'rev-parse', tag), 'commit': self.git(self.target, 'rev-parse', tag+'^{commit}')}
        # target copied before v0.2.1 annotation, which is present in both clones.
        with patch.object(bootstrap, 'git', side_effect=local_git), patch.object(self.client, 'resolve', side_effect=resolve), patch.object(self.client, 'verify_repository', return_value=1):
            result = update.preflight(self.installed, self.client, 'v0.3.0', runtime_probe=lambda *a: self.runtime, lock_probe=lambda *a: 'fixture')
        self.assertEqual(result['status'], 'ready', result)
        self.assertTrue(result['temporary_downloads_cleaned'])


    def test_runtime_failure_and_inside_installation_staging_never_change_customer_files(self):
        before = self.snapshot()
        def fail(*a):
            raise contracts.Blocker('runtime-unavailable', 'Required runtime is unavailable.')
        result = update.preflight(self.installed, self.client, 'v0.3.0', download=self.download,
                                  lock_probe=lambda *a: 'fixture', runtime_probe=fail)
        self.assertEqual(result['checks'][-1]['id'], 'runtime-unavailable')
        self.assertEqual(before, self.snapshot())
        with patch.dict(os.environ, {'TMPDIR': str(self.installed / '.runtime')}):
            result = self.run_plan()
        self.assertEqual(result['checks'][-1]['id'], 'unsafe-staging')
        self.assertEqual(before, self.snapshot())

    def test_interrupted_download_cleans_external_staging_and_preserves_installation(self):
        seen = []
        before = self.snapshot()
        def interrupted(client, tag, destination, mode):
            destination.mkdir(); (destination / 'partial').write_text('partial')
            seen.append(destination.parent)
            raise KeyboardInterrupt
        with self.assertRaises(KeyboardInterrupt):
            update.preflight(self.installed, self.client, 'v0.3.0', download=interrupted)
        self.assertTrue(all(not p.exists() for p in seen))
        self.assertEqual(before, self.snapshot())

    def test_insufficient_disk_is_a_blocker_not_permission_to_reset_or_replace_locks(self):
        from types import SimpleNamespace
        before = self.snapshot()
        with patch.object(update.shutil, 'disk_usage', return_value=SimpleNamespace(free=0)):
            result = self.run_plan()
        self.assertEqual(result['checks'][-1]['id'], 'disk-permissions')
        self.assertEqual(before, self.snapshot())

    def test_known_symlink_replaced_by_regular_file_is_a_type_mismatch(self):
        for root in (self.original, self.installed):
            path = root / 'launcher'; path.symlink_to('VERSION')
        (self.installed / 'launcher').unlink()
        (self.installed / 'launcher').write_text('VERSION')
        self.assertIn({'path': 'launcher', 'kind': 'type-or-mode'}, contracts.compare(self.installed, self.original, 'source'))

    def test_external_tool_package_runs_without_the_maintenance_checkout(self):
        package = self.base / 'owner-tools'; package.mkdir()
        for name in ('update.py', 'update_contracts.py', 'update_runtime.py', 'bootstrap.py'):
            shutil.copyfile(ROOT / 'distribution' / name, package / name)
        result = subprocess.run([sys.executable, str(package / 'update.py'), '--help'], cwd='/', capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn('--preflight', result.stdout)
        self.assertIn('--plan', result.stdout)
        self.assertNotIn('--force', result.stdout)

    @unittest.skipUnless(shutil.which('php'), 'Run in the application toolchain for PHP parity')
    def test_bounded_version_grammar_matches_the_existing_php_contract(self):
        queries = [(c, v) for c in ('>=0.2.1 <0.4.0', '^0.0.3', '^0.2.1', '^1.2.3', '~1.2.3', '=1.2.3',
                                    '>=0.2.1 || <0.0.1', '*', '>=0.2.1 || *', '>= 0.2.1', '', '^1000000000.0.0')
                   for v in ('0.0.3', '0.0.4', '0.2.1', '0.3.0', '1.2.3', '1.3.0', '1.2.3-beta')]
        program = 'require $argv[1]; $out=[]; foreach (json_decode($argv[2],true) as [$c,$v]) { try { $out[]=(new Runa\\Support\\VersionCompatibility)->supports($c,$v); } catch (Throwable) { $out[]="invalid"; } } echo json_encode($out);'
        expected = json.loads(subprocess.check_output(['php', '-r', program, str(ROOT / 'src/runa/Support/VersionCompatibility.php'), json.dumps(queries)]))
        actual = []
        for c, v in queries:
            try: actual.append(contracts.supports(c, v))
            except contracts.Blocker: actual.append('invalid')
        self.assertEqual(actual, expected)
        self.assertEqual(contracts.OWNER_PATHS, bootstrap.OWNER_PATHS)


    def test_runtime_probe_blocks_unsafe_or_orphaned_topology_without_mutating_commands(self):
        def configuration():
            return {'name':'fixture', 'services':{name:{'image':'fixture', 'environment':{'KNOWLEDGE_ENABLED':'false'}}
                    for name in ('app','nginx','postgres','queue','scheduler')},
                    'volumes':{'postgres-icu-data':{},'application-storage':{}}}
        for kind in ('privilege','knowledge','port','extra','missing'):
            config=configuration()
            if kind=='privilege': config['services']['app']['privileged']=True
            if kind=='knowledge': config['services']['app']['environment']['KNOWLEDGE_ENABLED']='true'
            if kind=='port': config['services']['postgres']['ports']=[{'published':'5432'}]
            if kind=='extra': config['services']['unexpected']={'image':'fixture'}
            def read(arguments, *args):
                if 'config' in arguments: return json.dumps(config)
                if 'ps' in arguments: return '[]'
                raise AssertionError('Unexpected/mutating probe command: '+repr(arguments))
            with self.subTest(kind=kind), patch.object(update_runtime,'compose',return_value=['compose']), patch.object(update_runtime,'command',side_effect=read):
                with self.assertRaises(contracts.Blocker): update_runtime.probe(self.installed,'prod',self.installed)

    def test_true_noop_requires_already_completed_exact_release_data_steps(self):
        self.runtime['seed_runs']=[{'release_sha':self.before,'seeder':'Runa\\Database\\Seeders\\DatabaseSeeder'}]
        result=update.preflight(self.installed,self.client,'v0.2.1',download=self.download,
                               runtime_probe=lambda *a:self.runtime,lock_probe=lambda *a:'fixture')
        self.assertEqual(result['status'],'ready',result)
        self.assertTrue(result['plan']['no_op'])
        self.assertEqual(result['plan']['platform_migrations'],[])

    def test_invalid_application_version_or_boolean_schema_is_not_compatibility_approval(self):
        for version,schema in (('latest',1),('1.0.0',True),('1.0.0-beta.01',1)):
            (self.installed/'src/app/application.json').write_text(json.dumps({'schema_version':schema,'version':version,'runa':'>=0.2.1'}))
            self.assertEqual(self.run_plan()['checks'][-1]['id'],'application-incompatible')

    def test_gpu_profile_is_literal_data_matching_trusted_generator(self):
        spec = importlib.util.spec_from_file_location('trusted_owner_dev', ROOT / 'tools/application/executors/dev.py')
        dev = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(dev)
        values = {**dict(zip(dev.KEYS, dev.PRESETS['standard'])), 'DEV_PROFILE': 'standard', 'RUNA_UID': '1000', 'RUNA_GID': '1000',
                  'EMBEDDINGS_DEVICE': 'cuda', 'EMBEDDINGS_CUDA': '1'}
        overlay = self.base / 'resources.json'
        with patch.object(dev, 'LOCAL', self.base / 'private.env'), patch.object(dev, 'OVERRIDE', overlay):
            dev.persist(values)
        self.assertEqual(update_runtime.gpu_descriptor(ROOT, values), json.loads(overlay.read_text())['services']['embeddings-gpu'])
        source = self.installed / 'tools/application/executors/dev.py'
        source.parent.mkdir(parents=True, exist_ok=True)
        source.write_text("siblings['embeddings-gpu'] = __import__('os').system('false')\n")
        config = self.installed / 'tools/application/knowledge/config.json'
        config.parent.mkdir(parents=True, exist_ok=True)
        config.write_text('{}')
        with self.assertRaises(contracts.Blocker):
            update_runtime.gpu_descriptor(self.installed, values)

    def test_standard_uid_overlay_normalization_preserves_topology_checks(self):
        base = {'services': {'qdrant': {'build': {'context': '.', 'dockerfile': 'Dockerfile'}}}}
        for unsafe in (False, True):
            resolved = json.loads(json.dumps(base))
            resolved['services']['qdrant']['build']['args'] = {'RUNA_UID': '1000', 'RUNA_GID': '1000'}
            if unsafe:
                resolved['services']['qdrant']['command'] = ['unreviewed']
            def read(arguments, *args):
                if 'config' in arguments:
                    return json.dumps(resolved if 'overlay' in arguments else base)
                if 'ps' in arguments:
                    return '[]'
                raise AssertionError('Unexpected command')
            with patch.object(update_runtime, 'compose', return_value=['compose', '-f', 'overlay']), patch.object(update_runtime, 'document', return_value={}), patch.object(update_runtime, 'command', side_effect=read):
                with self.assertRaises(contracts.Blocker) as blocked:
                    update_runtime.probe(self.installed, 'dev', self.installed)
                self.assertEqual(blocked.exception.code, 'custom-infrastructure' if unsafe else 'runtime-unavailable')

    def test_active_application_has_independent_compatibility_and_lock_checks(self):
        active = self.base / 'active'
        shutil.copytree(self.installed, active)
        (active / 'compose.yaml').unlink()
        with patch.object(update, 'installed_runtime', return_value=active):
            result = self.run_plan()
            self.assertEqual(result['status'], 'ready', result)
            self.assertEqual(result['dependency_checks'], ['operator', 'active-release'])
            application = active / 'src/app/application.json'
            value = json.loads(application.read_text()); value['runa'] = '<0.3.0'
            application.write_text(json.dumps(value))
            result = self.run_plan()
            self.assertEqual(result['checks'][-1]['id'], 'application-incompatible')
            self.assertIn('active-release', result['checks'][-1]['message'])


if __name__ == '__main__':
    unittest.main()
