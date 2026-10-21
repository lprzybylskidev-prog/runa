#!/usr/bin/env python3
"""Interrupted source switches, durable recovery and no replay of completed data."""
import json
import os
from pathlib import Path
import runpy
import shutil
import sys
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[3]
sys.path.insert(0, str(ROOT / 'distribution'))
import update_execution as execution
import update_customization
import update_contracts
UpdaterFixture = runpy.run_path(str(ROOT / 'tools/maintainer/testing/update.py'))['Updater']


class Runtime:
    def __init__(self, fixture):
        self.fixture = fixture
        self.root = fixture.installed
        self.fail = None
        self.events = []
        self.fenced = False
        self.seeds = 0

    def bind(self, project):
        if project != 'fixture': raise ValueError('Wrong project')

    def probe(self, *args):
        return self.fixture.runtime

    def metadata(self):
        return json.loads(json.dumps(self.fixture.runtime))

    def manual(self):
        pass

    def event(self, name):
        self.events.append(name)
        if self.fail == name:
            self.fail = None
            raise RuntimeError('Injected failure: ' + name)

    def fence(self, identity):
        self.fenced = True
        self.event('fence')

    def build(self):
        self.event('build')

    def data(self, phase, release):
        self.event(phase)
        if phase == 'migrations':
            self.fixture.runtime['migrations'] = sorted(p.stem for p in (self.fixture.target / 'src/runa/database/migrations').glob('*.php'))
        else:
            self.fixture.runtime['seed_runs'].append({'release_sha': release, 'seeder': execution.SEEDER})
            self.seeds += 1
        self.event(phase + '-committed')

    def activate(self, identity):
        self.fenced = False
        self.event('activate')

    def health(self):
        self.event('health')


class Execution(UpdaterFixture):
    def setUp(self):
        super().setUp()
        (self.installed / '.runtime/production').chmod(0o700)
        self.engine = Runtime(self)

    def apply(self, **kwargs):
        return execution.execute(self.installed, self.client, 'v0.3.0', download=self.download,
                                 runtime_factory=lambda root: self.engine, lock_probe=lambda *a: 'fixture', **kwargs)

    def journal(self):
        return json.loads((self.installed / '.runtime/production/in-progress.json').read_text())

    def test_execution_replaces_only_platform_and_records_completion_without_git_writes(self):
        (self.installed / 'src/app/customer.php').write_text('<?php // customer')
        before = {name: (self.installed / name).read_bytes() for name in ('VERSION', 'compose.yaml', 'src/app/application.json', 'src/app/customer.php', '.git/config')}
        result = self.apply()
        self.assertEqual(result['status'], 'completed', result)
        self.assertEqual(update_contracts.platform(self.installed), '0.3.0')
        self.assertTrue(all((self.installed / name).read_bytes() == content for name, content in before.items()))
        self.assertEqual(self.git(self.installed, 'rev-parse', 'HEAD'), self.before)
        self.assertEqual(self.engine.seeds, 1)
        self.assertFalse(self.engine.fenced)
        self.assertFalse((self.installed / '.runtime/production/in-progress.json').exists())
        self.assertFalse(Path(result['source_staging_removed']).exists())
        completed = list((self.installed / '.runtime/production/updates').glob('*.json'))
        self.assertEqual(len(completed), 1)
        self.assertEqual(json.loads(completed[0].read_text())['state'], 'complete')
        self.assertEqual(self.apply()['status'], 'completed')
        self.assertEqual(self.engine.seeds, 1)

    def test_execution_failure_boundaries_resume_without_replaying_completed_seed_effects(self):
        for phase in ('fence', 'build', 'migrations', 'migrations-committed', 'required-seeds', 'required-seeds-committed', 'activate', 'health'):
            with self.subTest(phase=phase):
                # Each case starts with its own complete fixture and private state.
                case = Execution('test_execution_replaces_only_platform_and_records_completion_without_git_writes')
                case.setUp()
                try:
                    case.engine.fail = phase
                    result = case.apply()
                    self.assertEqual(result['status'], 'blocked', result)
                    self.assertTrue(case.engine.fenced)
                    self.assertEqual(case.journal()['state'], 'failed')
                    resumed = case.apply(resume=True)
                    self.assertEqual(resumed['status'], 'completed', resumed)
                    self.assertEqual(case.engine.seeds, 1)
                finally: case.doCleanups()

    def test_execution_directory_rename_interruptions_revalidate_original_and_target_on_resume(self):
        original_rename = os.rename
        for boundary in (1, 2):
            case = Execution('test_execution_replaces_only_platform_and_records_completion_without_git_writes'); case.setUp()
            calls = []
            def interrupted(source, destination):
                original_rename(source, destination); calls.append(destination)
                if len(calls) == boundary: raise KeyboardInterrupt
            try:
                with patch.object(execution.os, 'rename', side_effect=interrupted), self.assertRaises(KeyboardInterrupt): case.apply()
                self.assertEqual(case.journal()['state'], 'interrupted')
                self.assertTrue(case.engine.fenced)
                result = case.apply(resume=True)
                self.assertEqual(result['status'], 'completed', result)
                self.assertEqual(case.engine.seeds, 1)
            finally: case.doCleanups()

    def test_execution_gap_survives_another_failure_during_resume_fencing(self):
        original_rename = os.rename
        def interrupted(source, destination):
            original_rename(source, destination)
            raise KeyboardInterrupt
        with patch.object(execution.os, 'rename', side_effect=interrupted), self.assertRaises(KeyboardInterrupt): self.apply()
        self.engine.fail = 'fence'
        self.assertEqual(self.apply(resume=True)['status'], 'blocked')
        self.assertEqual(self.apply(resume=True)['status'], 'completed')

    def test_execution_staging_symlink_is_rejected_before_source_or_data_mutations(self):
        self.engine.fail = 'build'; self.apply()
        operation = self.installed / '.runtime/production/update-staging' / self.journal()['id']
        shutil.rmtree(operation / 'incoming')
        (operation / 'incoming').symlink_to(self.target, target_is_directory=True)
        before = list(self.engine.events)
        result = self.apply(resume=True)
        self.assertEqual(result['checks'][-1]['id'], 'source-staging-changed', result)
        self.assertEqual(self.engine.events, before)

    def test_execution_customization_requires_explicit_choice_and_changed_resume_review(self):
        self.installed.joinpath('compose.yaml').write_text('{"services":{"redis":{"image":"redis"}}}')
        before = self.snapshot()
        self.assertEqual(self.apply()['checks'][-1]['id'], 'customization-decision-required')
        self.assertEqual(self.snapshot(), before)
        self.assertEqual(self.apply(choice='abort')['checks'][-1]['id'], 'customization-aborted')
        self.assertEqual(self.snapshot(), before)
        self.engine.fail = 'build'
        self.assertEqual(self.apply(choice='continue')['status'], 'blocked')
        self.installed.joinpath('compose.yaml').write_text('{"services":{"redis":{"image":"redis","command":["changed"]}}}')
        blocked = self.apply(resume=True, choice='continue')
        self.assertEqual(blocked['checks'][-1]['id'], 'customization-changed', blocked)
        fingerprint = blocked['customization_review']['fingerprint']
        self.assertEqual(self.apply(resume=True, choice='continue', fingerprint=fingerprint)['status'], 'completed')
        self.assertIn('changed', (self.installed / 'compose.yaml').read_text())

    def test_execution_source_tampering_and_changed_release_identity_cannot_be_overridden(self):
        self.engine.fail = 'build'; self.apply()
        original = self.installed / 'src/runa/Support/example.php'
        content = original.read_bytes(); original.write_text('tampered')
        self.assertEqual(self.apply(resume=True, choice='continue')['checks'][-1]['id'], 'protected-source-mismatch')
        original.write_bytes(content)
        journal = self.journal()
        previous = self.installed / '.runtime/production/update-staging' / journal['id'] / 'previous/src/runa/Support/example.php'
        previous.write_text('tampered backup')
        self.assertEqual(self.apply(resume=True)['checks'][-1]['id'], 'protected-source-mismatch')

    def test_execution_preserves_unknown_staging_content_and_rejects_missing_database_history(self):
        self.engine.fail = 'build'; self.apply()
        self.runtime['migrations'] = []
        self.assertEqual(self.apply(resume=True)['checks'][-1]['id'], 'database-identity')
        self.runtime['migrations'] = ['2026_01_01_000001_initial']
        operation = self.installed / '.runtime/production/update-staging' / self.journal()['id']
        sentinel = operation / 'unrelated.txt'; sentinel.write_text('preserve')
        result = self.apply(resume=True)
        self.assertEqual(result['checks'][-1]['id'], 'source-staging-changed', result)
        self.assertEqual(sentinel.read_text(), 'preserve')

    def test_execution_partial_staging_copy_restarts_only_verified_missing_files(self):
        original_copy = shutil.copytree
        interrupted = False
        def copy(source, destination, *args, **kwargs):
            nonlocal interrupted
            if str(destination).endswith('/incoming/src/runa') and not interrupted:
                interrupted = True
                Path(destination).mkdir(parents=True, exist_ok=True)
                shutil.copyfile(Path(source) / 'platform.json', Path(destination) / 'platform.json')
                raise KeyboardInterrupt
            return original_copy(source, destination, *args, **kwargs)
        with patch.object(execution.shutil, 'copytree', side_effect=copy), self.assertRaises(KeyboardInterrupt): self.apply()
        self.assertEqual(self.apply(resume=True)['status'], 'completed')

    def test_execution_oneoff_transport_failure_removes_only_its_owned_writer(self):
        runtime = execution.DockerRuntime.__new__(execution.DockerRuntime)
        runtime.root = self.installed; runtime.project = 'fixture'; runtime.args = ['docker', 'compose']
        commands = []
        def command(arguments, *args, **kwargs):
            commands.append(arguments)
            if arguments[:3] == ['docker', 'compose', 'run']: raise RuntimeError('Lost client')
            if arguments[:3] == ['docker', 'ps', '-aq']: return 'container-id'
            if arguments[:2] == ['docker', 'inspect']:
                name = commands[0][commands[0].index('--name') + 1]
                return json.dumps([{'Name': '/' + name, 'Config': {'Labels': {
                    'com.docker.compose.project': 'fixture', 'com.docker.compose.project.working_dir': str(self.installed),
                    'com.docker.compose.oneoff': 'True'}}}])
            return ''
        with patch.object(execution.update_runtime, 'command', side_effect=command), self.assertRaises(RuntimeError):
            runtime.command(['run', '--rm', 'app', 'php', 'prepare.php'])
        self.assertEqual(commands[-1], ['docker', 'rm', '--force', 'container-id'])

    def test_execution_resume_rejects_changed_tag_object_before_mutations(self):
        self.engine.fail = 'build'; self.apply()
        before = list(self.engine.events)
        download = self.download
        def changed(*args, **kwargs):
            identity = download(*args, **kwargs); identity['tag_object'] = 'a' * 40
            return identity
        with patch.object(self, 'download', side_effect=changed):
            blocked = self.apply(resume=True)
        self.assertEqual(blocked['checks'][-1]['id'], 'release-changed', blocked)
        self.assertEqual(self.engine.events, before)

    def test_execution_completed_operation_is_not_resurrected_after_cleanup_failure(self):
        original_remove = shutil.rmtree
        def interrupted(path, *args, **kwargs):
            if 'update-staging' in str(path): raise OSError('Cleanup failure')
            return original_remove(path, *args, **kwargs)
        with patch.object(execution.shutil, 'rmtree', side_effect=interrupted): result = self.apply()
        self.assertEqual(result['status'], 'completed', result)
        self.assertTrue(result['source_staging_requires_inspection'])
        self.assertFalse((self.installed / '.runtime/production/in-progress.json').exists())
        self.assertFalse(self.engine.fenced)
        self.assertEqual(self.apply()['status'], 'completed')
        self.assertEqual(self.engine.seeds, 1)


def load_tests(loader, tests, pattern):
    return unittest.TestSuite(Execution(name) for name in Execution.__dict__ if name.startswith('test_execution_'))


if __name__ == '__main__': unittest.main()
