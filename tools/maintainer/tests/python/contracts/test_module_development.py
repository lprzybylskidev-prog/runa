#!/usr/bin/env python3
"""Module version policy regressions and live owned-source acceptance."""

import json
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from tools.application.module_versions import assess, check, version

ROOT = Path(__file__).resolve().parents[5]


class ModuleVersionContracts(unittest.TestCase):
    def test_stable_versions_and_bump_requirement(self):
        for invalid in ["01.0.0", "1.0", "1.0.0-rc.1", "1.0.0+build", "1.0.1000000000"]:
            with self.assertRaises(ValueError):
                version(invalid)
        self.assertEqual(assess("1.0.0", "1.0.0", False, ""), [])
        self.assertIn(
            "changed_module_requires_version_bump",
            assess("1.0.0", "1.0.0", True, "## 1.0.0\n- Change."),
        )
        self.assertIn("module_version_downgrade", assess("1.0.0", "0.9.0", False, "## 0.9.0"))
        for bump in ["1.0.1", "1.1.0", "2.0.0"]:
            self.assertEqual(
                assess("1.0.0", bump, True, "## [" + bump + "] - 2026-10-22\n- Change."), []
            )
        self.assertEqual(assess(None, "0.1.0", True, "## 0.1.0\n- Initial skeleton."), [])
        self.assertEqual(
            assess("1.0.0", "1.0.1", True, "## 1.0.0"), ["module_version_requires_changelog_entry"]
        )

    def test_real_git_implementation_test_only_and_new_module_changes(self):
        with tempfile.TemporaryDirectory(prefix="runa-module-versions-") as directory:
            root = Path(directory)

            def git(*args):
                return subprocess.run(
                    ["git", "-C", directory, *args], check=True, capture_output=True
                )

            git("init", "-q", "--initial-branch=main")
            git("config", "user.name", "Module fixture")
            git("config", "user.email", "fixture@example.test")
            manifest = root / "src/app/modules/example/module.json"
            manifest.parent.mkdir(parents=True)
            manifest.write_text(json.dumps({"id": "app.example", "version": "1.0.0"}))
            source = manifest.parent / "backend/Handler.php"
            source.parent.mkdir()
            source.write_text("<?php // baseline")
            git("add", ".")
            git("commit", "-qm", "baseline")
            self.assertEqual(check(root, "app", "main")[0]["errors"], [])
            test = manifest.parent / "tests/ExampleTest.php"
            test.parent.mkdir()
            test.write_text("<?php // test only")
            self.assertEqual(check(root, "app", "main")[0]["errors"], [])
            source.write_text("<?php // changed")
            self.assertIn(
                "changed_module_requires_version_bump", check(root, "app", "main")[0]["errors"]
            )
            manifest.write_text(json.dumps({"id": "app.example", "version": "1.0.1"}))
            docs = root / "docs/app/modules/example/CHANGELOG.md"
            docs.parent.mkdir(parents=True)
            docs.write_text("## 1.0.1\n\n- Fix handler.")
            self.assertEqual(check(root, "app", "main")[0]["errors"], [])
            docs.unlink()
            docs.symlink_to(source)
            with self.assertRaises(ValueError):
                check(root, "app", "main")
            with self.assertRaises(subprocess.CalledProcessError):
                check(root, "app", "missing-ref")

    def test_installed_module_source_and_version_contracts(self):
        for owner in ("runa", "app"):
            subprocess.run(
                [str(ROOT / "bin/artisan"), "runa:modules:check", owner], cwd=ROOT, check=True
            )
            subprocess.run(
                [
                    sys.executable,
                    str(ROOT / "tools/application/module_versions.py"),
                    "--owner",
                    owner,
                ],
                cwd=ROOT,
                check=True,
            )
