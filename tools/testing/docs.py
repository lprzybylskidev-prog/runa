#!/usr/bin/env python3
"""Current-state regressions with isolated files/Git; no developer data mutations."""
import contextlib
import io
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/project'))
import docs_checks
import quality


class DocumentationContracts(unittest.TestCase):
    def setUp(self):
        self.folder = tempfile.TemporaryDirectory()
        self.addCleanup(self.folder.cleanup)
        self.root = Path(self.folder.name)
        subprocess.run(['git', 'init', '-q', '-b', 'main', str(self.root)], check=True)
        self.write('VERSION', '0.1.0\n')
        self.write('docs/project/state.yml', 'version: 1\nproduction:\n  ever_deployed: false\n  first_deployed_at: null\n')
        self.write('docs/tooling/commands.md', '# Commands\n./bin/example\n')
        self.write('bin/example', '#!/bin/sh\nexit 0\n').chmod(0o755)
        self.readme = '# Runa\n\n## Current state\n\n<!-- runa:current-state -->\n\n- Current version: `0.1.0`\n- Active roadmap: `none`\n- Production ever deployed: `false`\n\n<!-- /runa:current-state -->\n'
        self.write('README.md', self.readme)

    def write(self, name, content):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content)
        return path

    def validate(self):
        with contextlib.redirect_stdout(io.StringIO()):
            docs_checks.validate(self.root)

    def doctor(self, area):
        # Exercise the actual CLI dispatcher with fixture sources and real validators.
        script = ('import runpy, sys\n'
                  + f'sys.path.insert(0, {str(ROOT / "tools/project")!r})\n'
                  + 'import runtime, checks\n'
                  + 'from pathlib import Path\n'
                  + f'checks.ROOT = Path({str(self.root)!r})\n'
                  + 'runtime.inside = lambda: True\n'
                  + f'sys.argv = ["doctor", {area!r}]\n'
                  + f'runpy.run_path({str(ROOT / "bin/doctor")!r}, run_name="__main__")\n')
        return subprocess.run([sys.executable, '-c', script], capture_output=True, text=True)

    def test_released_stale_readme_is_rejected_by_actual_doctor(self):
        # The four stale claims in v0.1.0 survived the old command-name-only check.
        stale = ('# Runa\n\n## Current state\n\n'
                 '`RM-2026-001-development-environment` is active.\n\n'
                 'Initial version: `0.0.0`.\n\n'
                 'Remaining quality, knowledge, development-service and production-operation requirements are not yet delivered; this is not a completed release.\n\n'
                 'The first feature roadmap is expected to produce `0.1.0` when it is fully verified, closed, merged, tagged, and released.\n')
        self.write('README.md', stale)
        result = self.doctor('docs')
        self.assertEqual(result.returncode, 1, result.stdout)
        self.assertIn('exactly one runa:current-state block', result.stderr)

    def test_independent_state_mismatches_are_rejected(self):
        self.validate()
        for old, new, message in [('`0.1.0`', '`0.0.0`', 'Current version'),
                                  ('`none`', '`RM-2026-001`', 'Active roadmap'),
                                  ('`false`', '`true`', 'Production ever deployed')]:
            with self.subTest(field=message):
                self.write('README.md', self.readme.replace(old, new))
                with self.assertRaisesRegex(ValueError, message):
                    self.validate()

    def test_missing_duplicate_malformed_and_reordered_fields_fail(self):
        for text in [self.readme.replace(docs_checks.END, ''), self.readme * 2,
                     self.readme.replace('- Active roadmap: `none`\n', ''),
                     self.readme.replace('Active roadmap', 'Current version'),
                     self.readme.replace('`false`', 'false'),
                     self.readme.replace(docs_checks.START, 'SWAP').replace(docs_checks.END, docs_checks.START).replace('SWAP', docs_checks.END)]:
            with self.subTest(text=text):
                self.write('README.md', text)
                with self.assertRaises(ValueError):
                    self.validate()

    def test_actual_doctor_accepts_history_but_rejects_unlisted_commands(self):
        self.write('README.md', self.readme + '\n## Release history\nInitial version 0.0.0. RM-2026-001 was active during bootstrap.\n')
        self.write('docs/roadmaps/closed/RM-2026-001-history.md', 'Historical release 0.0.0, not a current claim.\n')
        self.write('docs/workflows/example.md', '```yaml\nid: RM-2026-003\nstatus: active\n```\n')
        result = self.doctor('docs')
        self.assertEqual(result.returncode, 0, result.stderr)
        self.write('docs/tooling/commands.md', '# Commands\n')
        result = self.doctor('docs')
        self.assertEqual(result.returncode, 1)
        self.assertIn('Undocumented command: example', result.stderr)

    def test_active_roadmap_location_frontmatter_and_branch(self):
        path = self.write('docs/roadmaps/active/RM-2026-002-test.md',
                          '---\nid: RM-2026-002\nstatus: active\nbranch: main\n---\n# Fixture\n')
        self.write('README.md', self.readme.replace('`none`', '`RM-2026-002`'))
        self.validate()
        original = path.read_text()
        for content in [original.replace('status: active', 'status: closed'),
                        original.replace('branch: main', 'branch: other'),
                        original.replace('id: RM-2026-002', 'id: RM-2026-999')]:
            self.write(str(path.relative_to(self.root)), content)
            with self.assertRaises(ValueError):
                self.validate()
        path.write_text(original)
        self.write('docs/roadmaps/active/RM-2026-003-second.md', original)
        with self.assertRaisesRegex(ValueError, 'Multiple active'):
            self.validate()

    def test_production_metadata_types_and_timestamp_coherence(self):
        for content in ['version: true\nproduction: {}\n', 'version: 2\nproduction: {}\n',
                        'version: 1\nproduction:\n  ever_deployed: "false"\n  first_deployed_at: null\n',
                        'version: 1\nproduction:\n  ever_deployed: false\n',
                        'version: 1\nproduction:\n  ever_deployed: false\n  first_deployed_at: 2026-10-18\n',
                        'version: 1\nproduction:\n  ever_deployed: true\n  first_deployed_at: null\n',
                        'version: 1\nproduction:\n  ever_deployed: true\n  first_deployed_at: 42\n',
                        'version: 1\nproduction:\n  ever_deployed: true\n  first_deployed_at: "2026-10-18T12:00:00"\n']:
            self.write('docs/project/state.yml', content)
            with self.assertRaises(ValueError):
                self.validate()
        self.write('README.md', self.readme.replace('`false`', '`true`'))
        for stamp in ['2026-10-18T12:00:00+00:00', '"2026-10-18T12:00:00Z"']:
            self.write('docs/project/state.yml', 'version: 1\nproduction:\n  ever_deployed: true\n  first_deployed_at: ' + stamp + '\n')
            self.validate()

    def test_final_gate_includes_docs_without_documentation_impact(self):
        definition = quality.yaml(ROOT / 'docs/quality/gates.yml')
        selected = quality.select(definition, [], True)
        self.assertEqual(selected['version-changelog-roadmap']['command'], './bin/doctor release')
        self.assertIn('--with-docs-and-knowledge', selected['release-metadata-contract']['command'])
        # Closed fixture metadata would otherwise pass the actual release doctor.
        self.write('CHANGELOG.md', '# Changelog\n\n## [0.1.0]\nRM-2026-001\n')
        self.write('docs/roadmaps/closed/RM-2026-001-test.md',
                   '---\nid: RM-2026-001\nstatus: closed\nbranch: roadmap/RM-2026-001-test\nclosed_at: 2026-10-18\nrelease_type: feature\n---\n- [x] Complete\n## Final report\nRM-2026-001 delivers 0.1.0.\n')
        self.assertEqual(self.doctor('release').returncode, 0)
        self.write('README.md', self.readme.replace('`0.1.0`', '`0.0.0`'))
        result = self.doctor('release')
        self.assertEqual(result.returncode, 1)
        self.assertIn('README Current version', result.stderr)


if __name__ == '__main__':
    unittest.main()
