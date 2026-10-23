#!/usr/bin/env python3
"""External updater failure-first source/plan contracts, real Git reference downloads."""
import contextlib
import json
import os
import runpy
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'distribution'))
sys.path.insert(0, str(ROOT / 'tools/maintainer'))
import bootstrap
import update
import update_contracts as contracts
import update_runtime
import update_customization


class Updater(unittest.TestCase):
    def test_native_update_uses_installed_source_tag_and_strictly_newer_target(self):
        release_tags = runpy.run_path(str(ROOT / 'tools/maintainer/testing/update-host.py'))['release_tags']
        for installed, expected in [('0.2.1', 'v0.2.2'), ('0.3.0', 'v0.3.1'), ('0.3.1', 'v0.3.2'), ('1.0.9', 'v1.0.10')]:
            with self.subTest(installed=installed):
                original, target = release_tags(installed)
                self.assertEqual(original, 'v' + installed)
                self.assertEqual(target, expected)
                self.assertNotEqual(original, target)

    def test_native_release_tags_bind_original_git_source_after_platform_release(self):
        release_tags = runpy.run_path(str(ROOT / 'tools/maintainer/testing/update-host.py'))['release_tags']
        for version in ('0.3.0', '0.3.1'):
            with self.subTest(version=version), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                repository, installed, reference = [root / name for name in ('repository', 'installed', 'reference')]
                (repository / 'src/runa').mkdir(parents=True)
                manifest = repository / 'src/runa/platform.json'
                manifest.write_text(json.dumps({'version': version}))
                self.git(repository, 'init', '-q')
                self.commit(repository)
                original, target = release_tags(version)
                self.git(repository, 'tag', original)
                shutil.copytree(repository / 'src', installed / 'src')
                manifest.write_text(json.dumps({'version': target.removeprefix('v')}))
                (repository / 'src/runa/upgrade.php').write_text('<?php // synthetic target source')
                self.commit(repository)
                self.git(repository, 'tag', target)
                subprocess.run(['git', 'clone', '--quiet', '--branch', original, str(repository), str(reference)], check=True, capture_output=True)
                self.assertEqual(update.compare(installed, reference), [])
                self.git(reference, 'checkout', '-q', target)
                self.assertTrue(update.compare(installed, reference))

    def git(self, root, *args):
        return subprocess.check_output(['git', '-C', str(root), *args], stderr=subprocess.DEVNULL).decode().strip()

    def commit(self, root):
        self.git(root, 'add', '--all')
        self.git(root, '-c', 'core.hooksPath=/dev/null', '-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture')
        return self.git(root, 'rev-parse', 'HEAD')

    def setUp(self):
        # Pure review contracts use JSON Compose data; native host acceptance exercises Docker's parser.
        self.native_configuration = update_customization.configuration
        parser = patch.object(update_customization, 'configuration', side_effect=lambda root, name: json.loads((root / name).read_text())['services'])
        parser.start(); self.addCleanup(parser.stop)
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.original, self.target, self.installed = [self.base / name for name in ('original', 'target', 'installed')]
        self.original.mkdir()
        files = {'VERSION': '0.2.1\n', 'src/runa/platform.json': json.dumps({'schema_version': 1, 'id': 'runa', 'name': 'Runa', 'version': '0.2.1'}),
                 'src/app/application.json': json.dumps({'schema_version': 1, 'version': '7.4.2', 'runa': '>=0.2.1 <0.4.0'}),
                 'src/app/composer.json': '{}', 'src/app/package.json': '{}', 'src/runa/composer.json': '{}', 'src/runa/package.json': '{}',
                 'src/composer.json': '{}', 'src/package.json': '{}', 'src/composer.lock': '{}', 'src/pnpm-lock.yaml': 'lock', 'src/pnpm-workspace.yaml': '{}',
                 'src/runa/Support/example.php': '<?php // protected', 'compose.yaml': '{"services": {"app": {"image": "fixture"}}}',
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
        (self.installed / '.env').write_text('APP_ENV=local\n')
        (self.installed / '.env').chmod(0o600)
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
        self.assertTrue(result['execution_available'])
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
        for mutation in ('missing', 'unexpected', 'mode', 'symlink', 'generated-php'):
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as folder:
                root = Path(folder) / 'copy'
                shutil.copytree(self.installed, root)
                path = root / 'src/runa/Support/example.php'
                if mutation == 'missing': path.unlink()
                if mutation == 'unexpected': (root / 'src/runa/Support/unexpected.php').write_text('<?php')
                if mutation == 'mode': path.chmod(0o755)
                if mutation == 'symlink': path.unlink(); path.symlink_to('/etc/passwd')
                if mutation == 'generated-php':
                    path = root / 'src/runa/resources/views/errors/generated/escape.php'
                    path.parent.mkdir(parents=True); path.write_text('<?php')
                self.assertTrue(contracts.compare(root, self.original))

    def test_application_storage_and_known_cache_are_preserved_without_hiding_source(self):
        for name in ('src/app/new.php', 'src/app/modules/reference/module.json', 'docs/app/modules/reference/README.md', 'docs/app/custom.md', 'src/storage/app/application/private/data.bin', 'src/bootstrap/cache/config.php'):
            path = self.installed / name; path.parent.mkdir(parents=True, exist_ok=True); path.write_text('customer/generated content')
        before = self.snapshot()
        self.assertEqual(self.run_plan()['status'], 'ready')
        self.assertEqual(before, self.snapshot())

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
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'original-release')

    def test_dependency_conflict_and_applied_migration_edit_block_but_infra_is_manual(self):
        (self.installed / 'src/app/composer.json').write_text('{"require":{"test/package":"2"}}')
        (self.installed / 'src/composer.json').write_text('{"require":{"test/package":"2"}}')
        (self.target / 'src/runa/composer.json').write_text('{"require":{"test/package":"1"}}')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'dependency-conflict')
        (self.target / 'src/runa/composer.json').write_text('{}')
        migration = self.target / 'src/runa/database/migrations/2026_01_01_000001_initial.php'
        migration.write_text('changed applied migration')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'applied-migration-changed')
        migration.write_bytes((self.original / migration.relative_to(self.target)).read_bytes())
        (self.target / 'compose.yaml').write_text('{"services": {"redis": {"image": "redis"}}}')
        result = self.run_plan()
        self.assertEqual(result['status'], 'ready', result)
        self.assertTrue(result['customization_review']['required'])

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
        (lock.parent / 'in-progress.json').unlink()
        (self.installed / '.git/runa-bootstrap.json').unlink()
        self.assertEqual(self.run_plan()['status'], 'ready')

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

    def test_scaffold_edits_and_teammate_clones_need_no_original_receipt(self):
        (self.installed / '.git/runa-bootstrap.json').unlink()
        (self.installed / 'VERSION').write_text('customer version')
        (self.installed / 'src/bootstrap/custom.php').parent.mkdir(exist_ok=True)
        (self.installed / 'src/bootstrap/custom.php').write_text('<?php')
        self.assertEqual(self.run_plan()['status'], 'ready')

    def test_production_rejected_before_download_or_probes(self):
        marker = self.installed / '.git/runa-bootstrap.json'
        value = json.loads(marker.read_text()); value['mode'] = 'prod'; marker.write_text(json.dumps(value))
        result = self.run_plan()
        self.assertEqual(result['checks'][-1]['id'], 'development-only')
        self.assertEqual(result['temporary_downloads'], [])

    def test_module_plan_isolated_mounts_do_not_expose_private_settings_or_run_application(self):
        import update_modules
        directory = self.target / 'src/runa/modules/example'
        directory.mkdir(parents=True)
        (directory / 'module.json').write_text('{}')
        from unittest.mock import patch
        result = subprocess.CompletedProcess([], 0, stdout='[]', stderr='')
        with patch.object(update_modules.subprocess, 'run', return_value=result) as execute:
            self.assertEqual(update_modules.plan(self.installed, self.target, self.runtime, 'sha256:' + 'a' * 64), [])
        command = execute.call_args_list[0].args[0]
        self.assertIn('--read-only', command)
        self.assertEqual(command[command.index('--network') + 1], 'none')
        mounts = [command[i + 1] for i, value in enumerate(command) if value == '--mount']
        self.assertIn('type=bind,src=' + str(self.installed / 'src/vendor') + ',dst=/installed/vendor,readonly', mounts)
        self.assertFalse(any(str(self.installed / 'src') + ',dst=' in value for value in mounts))
        self.assertNotIn('artisan', command)

    def test_external_tool_package_runs_without_the_maintenance_checkout(self):
        package = self.base / 'owner-tools'; package.mkdir()
        for name in ('update.py', 'update_contracts.py', 'update_runtime.py', 'update_customization.py', 'update_execution.py', 'update_modules.py', 'update_files.py', 'module-plan.php', 'bootstrap.py'):
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


    def test_runtime_probe_blocks_missing_services_without_mutation(self):
        with patch.object(update_runtime, 'compose', return_value=['compose']), patch.object(update_runtime, 'command', return_value='{"services":{}}'):
            with self.assertRaises(contracts.Blocker) as error:
                update_runtime.probe(self.installed, 'dev', self.installed)
        self.assertEqual(error.exception.code, 'runtime-unavailable')

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

    def test_complete_project_review_distinguishes_upstream_local_and_instruction_conflicts(self):
        for root in (self.original, self.target, self.installed):
            for name, value in {'README.md': 'baseline', 'AGENTS.md': 'customer defaults', 'obsolete.txt': 'remove upstream'}.items():
                (root / name).write_text(value)
            self.git(root, 'add', 'README.md', 'AGENTS.md', 'obsolete.txt')
        (self.target / 'README.md').write_text('target readme')
        (self.installed / 'README.md').write_text('customer readme')
        audit_guidance = (ROOT / 'docs/runa/maintainer/customer-agent/AGENTS.md').read_text()
        self.assertIn('## Audit awareness', audit_guidance)
        (self.target / 'AGENTS.md').write_text(audit_guidance)
        (self.installed / 'AGENTS.md').write_text('customer extension')
        for area in ('backend', 'laravel', 'postgresql', 'security'):
            relative = Path('.agents/skills') / area / 'references/application.md'
            for root in (self.original, self.target, self.installed):
                (root / relative).parent.mkdir(parents=True, exist_ok=True)
                (root / relative).write_text('baseline domain guidance')
                self.git(root, 'add', str(relative))
            guidance = (ROOT / 'docs/runa/maintainer/customer-agent/skills' / area / 'references/application.md').read_text()
            (self.target / relative).write_text(guidance)
            (self.installed / relative).write_text('customer domain extension')
        self.git(self.target, 'rm', '-f', 'obsolete.txt')
        (self.target / 'new-guide.md').write_text('target addition')
        self.git(self.target, 'add', 'new-guide.md')
        (self.installed / 'new-guide.md').write_text('untracked customer collision')
        (self.installed / '.env.private').write_text('PRIVATE_VALUE=never report')
        result = self.run_plan()
        self.assertEqual(result['status'], 'ready', result)
        rows = {r['path']: r for r in result['customization_review']['files']}
        self.assertEqual(rows['README.md']['action'], 'update')
        self.assertEqual(rows['README.md']['local'], 'modified')
        self.assertEqual(rows['AGENTS.md']['action'], 'manual-reconciliation')
        self.assertEqual((self.installed / 'AGENTS.md').read_text(), 'customer extension')
        self.assertIn('explicit manual Audit events', (self.target / 'AGENTS.md').read_text())
        for area in ('backend', 'laravel', 'postgresql', 'security'):
            relative = '.agents/skills/' + area + '/references/application.md'
            self.assertEqual(rows[relative]['action'], 'manual-reconciliation')
            self.assertIn('docs/runa/handbook/modules/auth/README.md', (self.target / relative).read_text())
            self.assertIn('docs/runa/handbook/modules/auth/transport.md', (self.target / relative).read_text())
            self.assertIn('docs/runa/handbook/modules/auth/sessions.md', (self.target / relative).read_text())
            self.assertIn('docs/runa/handbook/modules/auth/security.md', (self.target / relative).read_text())
            self.assertIn('docs/runa/handbook/modules/auth/profile.md', (self.target / relative).read_text())
            self.assertEqual((self.installed / relative).read_text(), 'customer domain extension')
        self.assertEqual(rows['obsolete.txt']['action'], 'remove')
        self.assertEqual(rows['new-guide.md']['action'], 'manual-reconciliation')
        self.assertNotIn('never report', json.dumps(result))
        self.assertEqual((self.installed / 'README.md').read_text(), 'customer readme')

    def test_project_review_tracks_executable_modes_and_rejects_symlink_parents(self):
        for root in (self.original, self.target, self.installed):
            path = root / 'scripts/task'
            path.parent.mkdir()
            path.write_text('task')
            self.git(root, 'add', 'scripts/task')
        (self.target / 'scripts/task').chmod(0o755)
        review = self.run_plan()['customization_review']
        row = next(r for r in review['files'] if r['path'] == 'scripts/task')
        self.assertEqual(row['target']['mode'], '100755')
        (self.installed / 'scripts/task').unlink()
        (self.installed / 'scripts').rmdir()
        (self.installed / 'scripts').symlink_to(self.target / 'scripts')
        self.assertEqual(self.run_plan()['status'], 'blocked')

    def test_customization_reports_added_removed_services_without_secret_values(self):
        (self.installed / 'compose.yaml').write_text('{"services": {"redis": {"image": "redis", "environment": {"PASSWORD": "secret-value"}}}}')
        before = self.snapshot()
        result = self.run_plan()
        self.assertEqual(result['status'], 'ready', result)
        review = result['customization_review']
        self.assertTrue(review['required'])
        self.assertEqual(review['services'], ['app', 'redis'])
        self.assertNotIn('secret-value', json.dumps(result))
        self.assertEqual(self.snapshot(), before)
        with self.assertRaises(contracts.Blocker): update_customization.decision(review, None)
        with self.assertRaises(contracts.Blocker): update_customization.decision(review, 'abort')
        self.assertEqual(update_customization.decision(review, 'continue'), review['fingerprint'])
        with self.assertRaises(contracts.Blocker): update_customization.decision(review, 'continue', 'old-review')
        (self.installed / 'src/runa/Support/example.php').write_text('tampered')
        self.assertEqual(self.run_plan()['checks'][-1]['id'], 'protected-source-mismatch')

    def test_untracked_infrastructure_changes_are_bound_to_exact_review(self):
        extra = self.installed / 'compose.override.yaml'
        extra.write_text('{"services":{"redis":{"image":"redis"}}}')
        before = self.run_plan()['customization_review']
        self.assertIn('compose.override.yaml', [r['path'] for r in before['files']])
        extra.write_text('{"services":{"redis":{"image":"redis","command":["changed"]}}}')
        after = self.run_plan()['customization_review']
        self.assertNotEqual(before['fingerprint'], after['fingerprint'])

    def test_multiple_devcontainer_compose_files_and_override_services_are_reviewed(self):
        (self.installed / '.devcontainer').mkdir()
        (self.installed / 'containers').mkdir()
        (self.installed / '.devcontainer/devcontainer.json').write_text(json.dumps({'dockerComposeFile': ['../compose.yaml', '../containers/extra.yml']}))
        (self.installed / 'containers/extra.yml').write_text('{"services":{"redis":{"image":"redis"}}}')
        (self.installed / 'compose.override.yaml').write_text('{"services":{"worker":{"image":"worker"}}}')
        review = self.run_plan()['customization_review']
        self.assertEqual(review['services'], ['redis', 'worker'])
        self.assertTrue(review['devcontainer'])

    @unittest.skipUnless(shutil.which('docker'), 'Native host Compose parser acceptance')
    def test_native_compose_parser_preserves_literal_env_and_reports_custom_services(self):
        self.installed.joinpath('compose.yaml').write_text('services: {redis: {image: redis, environment: {PASSWORD: "${CUSTOM_SECRET:-private-value}"}}}')
        with patch.object(update_customization, 'configuration', self.native_configuration):
            result = update_customization.review(self.installed, self.original)
        self.assertEqual(result['services'], ['app', 'redis'])
        self.assertNotIn('private-value', json.dumps(result))

    def test_shared_dockerfile_and_workspace_only_changes_are_reviewed(self):
        for root in (self.original, self.installed, self.target):
            (root / 'docker').mkdir()
            (root / 'docker/Dockerfile').write_text('FROM scratch')
            (root / 'compose.yaml').write_text(json.dumps({'services': {name: {'build': {'context': '.', 'dockerfile': 'docker/Dockerfile'}} for name in ('app', 'queue')}}))
        (self.installed / 'docker/Dockerfile').write_text('FROM scratch\nLABEL changed=yes')
        review = self.run_plan()['customization_review']
        self.assertEqual(review['services'], ['app', 'queue'])
        (self.installed / 'docker/Dockerfile').write_text('FROM scratch')
        (self.installed / '.devcontainer').mkdir()
        (self.installed / '.devcontainer/devcontainer.json').write_text('{"workspaceFolder":"/custom"}')
        review = self.run_plan()['customization_review']
        self.assertTrue(review['devcontainer'])
        self.assertEqual(review['services'], [])


if __name__ == '__main__':
    unittest.main()
