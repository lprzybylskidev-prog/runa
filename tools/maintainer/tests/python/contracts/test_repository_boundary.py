#!/usr/bin/env python3
"""Real Git role fencing: no maintenance/customer source or remote repair."""

import json
import os
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

import tools.application.repository_boundary_service as boundary
from tools.application.deployment_service import DeploymentService
from tools.application.installation_service import InstallationService
from tools.application.management_service import ManagementService
from tools.application.repository_boundary_service import RepositoryPolicy

ROOT = Path(__file__).resolve().parents[5]


class RepositoryBoundary(unittest.TestCase):
    def git(self, root, *arguments):
        return (
            subprocess.check_output(["git", "-C", str(root), *arguments], stderr=subprocess.DEVNULL)
            .decode()
            .strip()
        )

    def fixture(self, root):
        self.git(root, "init", "--quiet")
        self.git(root, "remote", "add", "origin", "https://github.com/fixture/runa.git")
        self.git(root, "config", "remote.origin.pushurl", "disabled://runa-read-only-upstream")
        RepositoryPolicy.for_workspace(root).write_staging_receipt(
            root,
            {
                "schema_version": 1,
                "repository": "fixture/runa",
                "projection": {"version": 1, "excluded": list(boundary.OWNER_PATHS)},
            },
        )

    def test_unknown_and_maintenance_repositories_are_not_mutated(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.git(root, "init", "--quiet")
            self.git(
                root, "remote", "add", "origin", "https://github.com/lprzybylskidev-prog/runa.git"
            )
            original = (root / ".git/config").read_bytes()
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(root).assert_installation(root)
            with self.assertRaises(ValueError):
                InstallationService(
                    DeploymentService.for_workspace(root), ManagementService.for_workspace(root)
                ).install("dev", "a" * 40)
            with self.assertRaises(ValueError):
                DeploymentService.for_workspace(root).deploy("a" * 40, False)
            with self.assertRaises(ValueError):
                DeploymentService.for_workspace(root).main("deploy", ["a" * 40])
            self.assertEqual((root / ".git/config").read_bytes(), original)
            self.assertEqual(self.git(root, "status", "--porcelain"), "")

    def test_production_mode_uses_the_real_receipt_and_never_changes_it(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            path = root / ".git/runa-bootstrap.json"
            data = json.loads(path.read_text())
            for mode in ("dev", None, "unknown", "prod"):
                data["mode"] = mode
                RepositoryPolicy.for_workspace(root).write_staging_receipt(root, data)
                before = path.read_bytes()
                if mode == "prod":
                    self.assertEqual(
                        RepositoryPolicy.for_workspace(root).assert_production_installation(root)[
                            "mode"
                        ],
                        "prod",
                    )
                else:
                    with self.assertRaisesRegex(ValueError, "require a production installation"):
                        RepositoryPolicy.for_workspace(root).assert_production_installation(root)
                self.assertEqual(path.read_bytes(), before)

    def test_development_context_preserves_cold_and_owner_bootstrap_but_denies_production(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            with patch.dict(os.environ, {"APP_ENV": "local"}):
                RepositoryPolicy.for_workspace(root).assert_development_context(root)
                for text in ("APP_ENV=production\n", 'export APP_ENV="production" # target\n'):
                    (root / ".env").write_text(text)
                    with self.assertRaisesRegex(ValueError, "production environment"):
                        RepositoryPolicy.for_workspace(root).assert_development_context(root)
                (root / ".env").write_text("APP_ENV=local\n")
                RepositoryPolicy.for_workspace(root).assert_development_context(root)
                self.fixture(root)
                data = json.loads((root / ".git/runa-bootstrap.json").read_text())
                data["mode"] = "dev"
                RepositoryPolicy.for_workspace(root).write_staging_receipt(root, data)
                RepositoryPolicy.for_workspace(root).assert_development_context(root)
                data["mode"] = "prod"
                RepositoryPolicy.for_workspace(root).write_staging_receipt(root, data)
                with self.assertRaisesRegex(ValueError, "blocked in production"):
                    RepositoryPolicy.for_workspace(root).assert_development_context(root)
            self.assertFalse((root / ".runtime").exists())

    def test_customer_read_only_role_and_existing_customer_remote_preservation(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            self.assertEqual(
                RepositoryPolicy.for_workspace(root).assert_installation(root)["repository"],
                "fixture/runa",
            )
            with (
                patch.dict(os.environ, {"GIT_DIR": str(ROOT / ".git")}),
                self.assertRaises(ValueError),
            ):
                RepositoryPolicy.for_workspace(root).assert_installation(root)
            self.git(
                root, "remote", "set-url", "origin", "https://github.com/customer/application.git"
            )
            config = (root / ".git/config").read_bytes()
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(root).assert_installation(root)
            self.assertEqual((root / ".git/config").read_bytes(), config)

    def test_ambient_push_origin_alias_escape_and_restored_owner_source_are_denied(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / "installation"
            root.mkdir()
            self.fixture(root)
            alias = Path(folder) / "alias"
            alias.symlink_to(root)
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(alias).assert_installation(alias)
            self.git(
                root,
                "config",
                "--add",
                "remote.origin.pushurl",
                "https://github.com/lprzybylskidev-prog/runa.git",
            )
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(root).assert_installation(root)
            self.git(
                root,
                "config",
                "--replace-all",
                "remote.origin.pushurl",
                "disabled://runa-read-only-upstream",
            )
            (root / "distribution").mkdir()
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(root).assert_installation(root)

    def test_customer_command_registry_excludes_maintenance_even_if_restored(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            source = root / "src/runa/management"
            shutil.copytree(ROOT / "src/runa/management", source)
            program = 'require $argv[1]; $classes = require $argv[2]; foreach ($classes as $command) { echo $command->getName(), "\\n"; }'

            def names():
                return (
                    subprocess.check_output(
                        [
                            "php",
                            "-r",
                            program,
                            str(ROOT / "src/vendor/autoload.php"),
                            str(source / "commands.php"),
                        ]
                    )
                    .decode()
                    .splitlines()
                )

            self.fixture(root)
            for restored in (False, True):
                if restored:
                    owner = root / "distribution/maintenance"
                    owner.mkdir(parents=True)
                    (owner / "commands.php").write_text(
                        '<?php throw new RuntimeException("Owner registry must not load");'
                    )
                registered = names()
                for name in ("runa:backup", "runa:integrity", "runa:project", "runa:install"):
                    self.assertNotIn(name, registered)
                self.assertIn("runa:dev:up", registered)
                self.assertIn("runa:hook:pre-commit", registered)
            (root / ".git/runa-bootstrap.json").unlink()
            self.assertNotIn("runa:install", names())

    def test_distribution_projection_is_fixed_and_shared(self):
        from distribution import bootstrap as standalone

        self.assertEqual(standalone.OWNER_PATHS, boundary.OWNER_PATHS)
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            self.fixture(root)
            path = root / ".git/runa-bootstrap.json"
            data = json.loads(path.read_text())
            data["projection"]["excluded"].append("src/app")
            path.write_text(json.dumps(data))
            with self.assertRaises(ValueError):
                RepositoryPolicy.for_workspace(root).assert_installation(root)
