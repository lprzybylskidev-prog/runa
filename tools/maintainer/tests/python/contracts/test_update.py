#!/usr/bin/env python3
"""External updater failure-first source/plan contracts, real Git reference downloads."""

import json
import os
import shutil
import subprocess
import sys
import tempfile
import unittest
from collections import deque
from pathlib import Path
from unittest.mock import patch

import pytest

import distribution.bootstrap as bootstrap
import distribution.update_contracts as contracts
import distribution.update_customization as update_customization
import distribution.update_modules as update_modules
import distribution.update_planning as update
import distribution.update_runtime as update_runtime
from distribution.processes import ExternalProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import ProcessResult
from tools.maintainer.testing import update_host as acceptance_update_host
from tools.maintainer.tests.python.support.updater import UpdaterWorkspace

ROOT = Path(__file__).resolve().parents[5]


class Updater(unittest.TestCase):
    def setUp(self):
        self.fixture = UpdaterWorkspace(self.addCleanup)

    def test_native_update_uses_installed_source_tag_and_strictly_newer_target(self):
        release_tags = acceptance_update_host.release_tags
        for installed, expected in [
            ("0.2.1", "v0.2.2"),
            ("0.3.0", "v0.3.1"),
            ("0.3.1", "v0.3.2"),
            ("1.0.9", "v1.0.10"),
        ]:
            with self.subTest(installed=installed):
                original, target = release_tags(installed)
                self.assertEqual(original, "v" + installed)
                self.assertEqual(target, expected)
                self.assertNotEqual(original, target)

    def test_native_release_tags_bind_original_git_source_after_platform_release(self):
        release_tags = acceptance_update_host.release_tags
        for version in ("0.3.0", "0.3.1"):
            with self.subTest(version=version), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                repository, installed, reference = [
                    root / name for name in ("repository", "installed", "reference")
                ]
                (repository / "src/runa").mkdir(parents=True)
                manifest = repository / "src/runa/platform.json"
                manifest.write_text(json.dumps({"version": version}))
                self.fixture.git(repository, "init", "-q")
                self.fixture.commit(repository)
                original, target = release_tags(version)
                self.fixture.git(repository, "tag", original)
                shutil.copytree(repository / "src", installed / "src")
                manifest.write_text(json.dumps({"version": target.removeprefix("v")}))
                (repository / "src/runa/upgrade.php").write_text("<?php // synthetic target source")
                self.fixture.commit(repository)
                self.fixture.git(repository, "tag", target)
                subprocess.run(
                    [
                        "git",
                        "clone",
                        "--quiet",
                        "--branch",
                        original,
                        str(repository),
                        str(reference),
                    ],
                    check=True,
                    capture_output=True,
                )
                self.assertEqual(update.compare(installed, reference), [])
                self.fixture.git(reference, "checkout", "-q", target)
                self.assertTrue(update.compare(installed, reference))

    def test_ready_plan_lists_only_platform_data_steps_and_preserves_installation(self):
        before = self.fixture.snapshot()
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "ready", result)
        self.assertEqual(result["plan"]["platform_migrations"], ["2026_01_02_000001_upgrade"])
        self.assertEqual(
            result["plan"]["platform_seeder"]["class"], "Runa\\Database\\Seeders\\DatabaseSeeder"
        )
        self.assertTrue(result["execution_available"])
        self.assertTrue(result["temporary_downloads_cleaned"])
        self.assertTrue(all(not Path(p).exists() for p in result["temporary_downloads"]))
        self.assertEqual(self.fixture.snapshot(), before)

    def test_original_is_independently_downloaded_not_adopted_from_receipt_or_git(self):
        (self.fixture.installed / "src/runa/Support/example.php").write_text("<?php // changed")
        self.fixture.commit(self.fixture.installed)
        receipt = json.loads((self.fixture.installed / ".git/runa-bootstrap.json").read_text())
        receipt["source_sha256"] = "f" * 64
        (self.fixture.installed / ".git/runa-bootstrap.json").write_text(json.dumps(receipt))
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "blocked")
        self.assertIn(
            {"path": "src/runa/Support/example.php", "kind": "changed"}, result["mismatches"]
        )
        self.assertEqual(result["checks"][-1]["id"], "protected-source-mismatch")

    def test_missing_unexpected_mode_and_unsafe_symlink_diagnostics(self):
        for mutation in ("missing", "unexpected", "mode", "symlink", "generated-php"):
            with self.subTest(mutation=mutation), tempfile.TemporaryDirectory() as folder:
                root = Path(folder) / "copy"
                shutil.copytree(self.fixture.installed, root)
                path = root / "src/runa/Support/example.php"
                if mutation == "missing":
                    path.unlink()
                if mutation == "unexpected":
                    (root / "src/runa/Support/unexpected.php").write_text("<?php")
                if mutation == "mode":
                    path.chmod(0o755)
                if mutation == "symlink":
                    path.unlink()
                    path.symlink_to("/etc/passwd")
                if mutation == "generated-php":
                    path = root / "src/runa/resources/views/errors/generated/escape.php"
                    path.parent.mkdir(parents=True)
                    path.write_text("<?php")
                self.assertTrue(contracts.compare(root, self.fixture.original))

    def test_application_storage_and_known_cache_are_preserved_without_hiding_source(self):
        for name in (
            "src/app/new.php",
            "src/app/modules/reference/module.json",
            "docs/app/modules/reference/README.md",
            "docs/app/custom.md",
            "src/storage/app/application/private/data.bin",
            "src/bootstrap/cache/config.php",
        ):
            path = self.fixture.installed / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text("customer/generated content")
        before = self.fixture.snapshot()
        self.assertEqual(self.fixture.run_plan()["status"], "ready")
        self.assertEqual(before, self.fixture.snapshot())

    def test_incompatible_and_unsupported_ranges_block(self):
        for constraint in (">=0.4.0", "*", ">=0.2.1 || *", ">=0.2.1 ||"):
            (self.fixture.installed / "src/app/application.json").write_text(
                json.dumps({"schema_version": 1, "version": "1.0.0", "runa": constraint})
            )
            self.assertEqual(self.fixture.run_plan()["status"], "blocked")

    def test_unknown_versions_or_failed_reference_resolution_never_adopt_local_source(self):
        def fail(*args, **kwargs):
            raise bootstrap.BootstrapError("Release unavailable.")

        result = update.preflight(
            self.fixture.installed, self.fixture.client, "v0.3.0", download=fail
        )
        self.assertEqual(result["checks"][-1]["id"], "release-acquisition")
        self.assertTrue(result["temporary_downloads_cleaned"])
        self.fixture.manifest(self.fixture.installed, "0.2.2")
        self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "original-release")

    def test_dependency_conflict_and_applied_migration_edit_block_but_infra_is_manual(self):
        (self.fixture.installed / "src/app/composer.json").write_text(
            '{"require":{"test/package":"2"}}'
        )
        (self.fixture.installed / "src/composer.json").write_text(
            '{"require":{"test/package":"2"}}'
        )
        (self.fixture.target / "src/runa/composer.json").write_text(
            '{"require":{"test/package":"1"}}'
        )
        self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "dependency-conflict")
        (self.fixture.target / "src/runa/composer.json").write_text("{}")
        migration = (
            self.fixture.target / "src/runa/database/migrations/2026_01_01_000001_initial.php"
        )
        migration.write_text("changed applied migration")
        self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "applied-migration-changed")
        migration.write_bytes(
            (self.fixture.original / migration.relative_to(self.fixture.target)).read_bytes()
        )
        (self.fixture.target / "compose.yaml").write_text(
            '{"services": {"redis": {"image": "redis"}}}'
        )
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "ready", result)
        self.assertTrue(result["customization_review"]["required"])

    def test_operation_lock_pending_journal_symlink_and_owner_target_fail_closed(self):
        lock = self.fixture.installed / ".runtime/production/operation.lock"
        import fcntl

        with lock.open("w") as stream:
            lock.chmod(0o600)
            fcntl.flock(stream, fcntl.LOCK_EX | fcntl.LOCK_NB)
            self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "operation-running")
        (lock.parent / "in-progress.json").write_text("{}")
        self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "unresolved-operation")
        alias = self.fixture.base / "alias"
        alias.symlink_to(self.fixture.installed)
        self.assertEqual(
            update.preflight(alias, self.fixture.client, "v0.3.0")["checks"][-1]["id"],
            "installation-ownership",
        )
        (lock.parent / "in-progress.json").unlink()
        (self.fixture.installed / ".git/runa-bootstrap.json").unlink()
        self.assertEqual(self.fixture.run_plan()["status"], "ready")

    def test_actual_verified_git_download_checks_tag_and_content_without_install_mutation(self):
        # Only transport is adapted to local Git; acquisition still resolves,
        # checks annotated refs, fsck, VERSION and every file/blob/mode.

        def local_git(source, home, *args, **kwargs):
            if args[:3] == ("remote", "add", "origin"):
                args = (*args[:3], str(self.fixture.target))
            env = bootstrap.git_environment(home)
            env["GIT_ALLOW_PROTOCOL"] = "file"
            return bootstrap.process(
                ["git", "-c", "core.hooksPath=/dev/null", "-C", str(source), *args],
                env=env,
                input_data=kwargs.get("input_data"),
            )

        def resolve(tag):
            return {
                "release_id": 1,
                "tag_object": self.fixture.git(self.fixture.target, "rev-parse", tag),
                "commit": self.fixture.git(self.fixture.target, "rev-parse", tag + "^{commit}"),
            }

        # target copied before v0.2.1 annotation, which is present in both clones.
        with (
            patch.object(bootstrap, "git", side_effect=local_git),
            patch.object(self.fixture.client, "resolve", side_effect=resolve),
            patch.object(self.fixture.client, "verify_repository", return_value=1),
        ):
            result = update.preflight(
                self.fixture.installed,
                self.fixture.client,
                "v0.3.0",
                runtime_probe=lambda *a: self.fixture.runtime,
                lock_probe=lambda *a: "fixture",
            )
        self.assertEqual(result["status"], "ready", result)
        self.assertTrue(result["temporary_downloads_cleaned"])

    def test_runtime_failure_and_inside_installation_staging_never_change_customer_files(self):
        before = self.fixture.snapshot()

        def fail(*a):
            raise contracts.Blocker("runtime-unavailable", "Required runtime is unavailable.")

        result = update.preflight(
            self.fixture.installed,
            self.fixture.client,
            "v0.3.0",
            download=self.fixture.download,
            lock_probe=lambda *a: "fixture",
            runtime_probe=fail,
        )
        self.assertEqual(result["checks"][-1]["id"], "runtime-unavailable")
        self.assertEqual(before, self.fixture.snapshot())
        with patch.dict(os.environ, {"TMPDIR": str(self.fixture.installed / ".runtime")}):
            result = self.fixture.run_plan()
        self.assertEqual(result["checks"][-1]["id"], "unsafe-staging")
        self.assertEqual(before, self.fixture.snapshot())

    def test_interrupted_download_cleans_external_staging_and_preserves_installation(self):
        seen = []
        before = self.fixture.snapshot()

        def interrupted(client, tag, destination, mode):
            destination.mkdir()
            (destination / "partial").write_text("partial")
            seen.append(destination.parent)
            raise KeyboardInterrupt

        with self.assertRaises(KeyboardInterrupt):
            update.preflight(
                self.fixture.installed, self.fixture.client, "v0.3.0", download=interrupted
            )
        self.assertTrue(all(not p.exists() for p in seen))
        self.assertEqual(before, self.fixture.snapshot())

    def test_insufficient_disk_is_a_blocker_not_permission_to_reset_or_replace_locks(self):
        from types import SimpleNamespace

        before = self.fixture.snapshot()
        with patch.object(update.shutil, "disk_usage", return_value=SimpleNamespace(free=0)):
            result = self.fixture.run_plan()
        self.assertEqual(result["checks"][-1]["id"], "disk-permissions")
        self.assertEqual(before, self.fixture.snapshot())

    def test_frontend_and_error_customizations_remain_application_owned_across_review(self):
        names = (
            "src/app/resources/js/App.vue",
            "src/app/resources/js/main.ts",
            "src/app/resources/js/errorLayout.ts",
            "src/app/resources/css/customer.css",
        )
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            for name in names:
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(
                    "customer customization"
                    if root == self.fixture.installed
                    else "upstream default"
                )
                self.fixture.git(root, "add", name)
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "ready", result)
        rows = {row["path"]: row for row in result["customization_review"]["files"]}
        for name in names:
            self.assertEqual(rows[name]["ownership"], "application")
            self.assertEqual(rows[name]["action"], "preserve")
            self.assertNotIn("current", rows[name])
            self.assertEqual((self.fixture.installed / name).read_text(), "customer customization")

    def test_scaffold_edits_and_teammate_clones_need_no_original_receipt(self):
        (self.fixture.installed / ".git/runa-bootstrap.json").unlink()
        (self.fixture.installed / "VERSION").write_text("customer version")
        (self.fixture.installed / "src/bootstrap/custom.php").parent.mkdir(exist_ok=True)
        (self.fixture.installed / "src/bootstrap/custom.php").write_text("<?php")
        self.assertEqual(self.fixture.run_plan()["status"], "ready")

    def test_production_rejected_before_download_or_probes(self):
        marker = self.fixture.installed / ".git/runa-bootstrap.json"
        value = json.loads(marker.read_text())
        value["mode"] = "prod"
        marker.write_text(json.dumps(value))
        result = self.fixture.run_plan()
        self.assertEqual(result["checks"][-1]["id"], "development-only")
        self.assertEqual(result["temporary_downloads"], [])

    def test_module_plan_isolated_mounts_do_not_expose_private_settings_or_run_application(self):
        directory = self.fixture.target / "src/runa/modules/example"
        directory.mkdir(parents=True)
        (directory / "module.json").write_text("{}")
        runner = RecordingRunner(deque([ProcessResult(0, b"[]"), ProcessResult(0)]))
        self.assertEqual(
            update_modules.plan(
                self.fixture.installed,
                self.fixture.target,
                self.fixture.runtime,
                "sha256:" + "a" * 64,
                processes=ExternalProcesses(runner, {}),
            ),
            [],
        )
        command = runner.calls[0].argv
        self.assertIn("--read-only", command)
        self.assertEqual(command[command.index("--network") + 1], "none")
        mounts = [command[i + 1] for i, value in enumerate(command) if value == "--mount"]
        self.assertIn(
            "type=bind,src="
            + str(self.fixture.installed / "src/vendor")
            + ",dst=/installed/vendor,readonly",
            mounts,
        )
        self.assertFalse(
            any(str(self.fixture.installed / "src") + ",dst=" in value for value in mounts)
        )
        self.assertNotIn("artisan", command)

    def test_external_tool_package_runs_without_the_maintenance_checkout(self):
        package = self.fixture.base / "owner-tools"
        package.mkdir()
        for name in (
            "update.py",
            "update_contracts.py",
            "update_runtime.py",
            "update_customization.py",
            "update_execution.py",
            "update_modules.py",
            "update_files.py",
            "module-plan.php",
            "bootstrap.py",
        ):
            shutil.copyfile(ROOT / "distribution" / name, package / name)
        result = subprocess.run(
            [sys.executable, str(package / "update.py"), "--help"],
            cwd="/",
            capture_output=True,
            text=True,
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("--preflight", result.stdout)
        self.assertIn("--plan", result.stdout)
        self.assertNotIn("--force", result.stdout)

    def test_bounded_version_grammar_matches_the_existing_php_contract(self):
        queries = [
            (c, v)
            for c in (
                ">=0.2.1 <0.4.0",
                "^0.0.3",
                "^0.2.1",
                "^1.2.3",
                "~1.2.3",
                "=1.2.3",
                ">=0.2.1 || <0.0.1",
                "*",
                ">=0.2.1 || *",
                ">= 0.2.1",
                "",
                "^1000000000.0.0",
            )
            for v in ("0.0.3", "0.0.4", "0.2.1", "0.3.0", "1.2.3", "1.3.0", "1.2.3-beta")
        ]
        program = 'require $argv[1]; $out=[]; foreach (json_decode($argv[2],true) as [$c,$v]) { try { $out[]=(new Runa\\Support\\VersionCompatibility)->supports($c,$v); } catch (Throwable) { $out[]="invalid"; } } echo json_encode($out);'
        expected = json.loads(
            subprocess.check_output(
                [
                    "php",
                    "-r",
                    program,
                    str(ROOT / "src/runa/Support/VersionCompatibility.php"),
                    json.dumps(queries),
                ]
            )
        )
        actual = []
        for c, v in queries:
            try:
                actual.append(contracts.supports(c, v))
            except contracts.Blocker:
                actual.append("invalid")
        self.assertEqual(actual, expected)
        self.assertEqual(contracts.OWNER_PATHS, bootstrap.OWNER_PATHS)

    def test_runtime_probe_blocks_missing_services_without_mutation(self):
        with (
            patch.object(update_runtime, "compose", return_value=["compose"]),
            patch.object(update_runtime, "command", return_value='{"services":{}}'),
        ):
            with self.assertRaises(contracts.Blocker) as error:
                update_runtime.probe(self.fixture.installed, "dev", self.fixture.installed)
        self.assertEqual(error.exception.code, "runtime-unavailable")

    def test_true_noop_requires_already_completed_exact_release_data_steps(self):
        self.fixture.runtime["seed_runs"] = [
            {
                "release_sha": self.fixture.before,
                "seeder": "Runa\\Database\\Seeders\\DatabaseSeeder",
            }
        ]
        result = update.preflight(
            self.fixture.installed,
            self.fixture.client,
            "v0.2.1",
            download=self.fixture.download,
            runtime_probe=lambda *a: self.fixture.runtime,
            lock_probe=lambda *a: "fixture",
        )
        self.assertEqual(result["status"], "ready", result)
        self.assertTrue(result["plan"]["no_op"])
        self.assertEqual(result["plan"]["platform_migrations"], [])

    def test_invalid_application_version_or_boolean_schema_is_not_compatibility_approval(self):
        for version, schema in (("latest", 1), ("1.0.0", True), ("1.0.0-beta.01", 1)):
            (self.fixture.installed / "src/app/application.json").write_text(
                json.dumps({"schema_version": schema, "version": version, "runa": ">=0.2.1"})
            )
            self.assertEqual(
                self.fixture.run_plan()["checks"][-1]["id"], "application-incompatible"
            )

    def test_complete_project_review_distinguishes_upstream_local_and_instruction_conflicts(self):
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            for name, value in {
                "README.md": "baseline",
                "AGENTS.md": "customer defaults",
                "obsolete.txt": "remove upstream",
            }.items():
                (root / name).write_text(value)
            self.fixture.git(root, "add", "README.md", "AGENTS.md", "obsolete.txt")
        (self.fixture.target / "README.md").write_text("target readme")
        (self.fixture.installed / "README.md").write_text("customer readme")
        audit_guidance = (ROOT / "docs/runa/maintainer/customer-agent/AGENTS.md").read_text()
        self.assertIn("## Audit awareness", audit_guidance)
        (self.fixture.target / "AGENTS.md").write_text(audit_guidance)
        (self.fixture.installed / "AGENTS.md").write_text("customer extension")
        for area in ("backend", "laravel", "postgresql", "security", "frontend"):
            relative = Path(".agents/skills") / area / "references/application.md"
            for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
                (root / relative).parent.mkdir(parents=True, exist_ok=True)
                (root / relative).write_text("baseline domain guidance")
                self.fixture.git(root, "add", str(relative))
            guidance = (
                ROOT
                / "docs/runa/maintainer/customer-agent/skills"
                / area
                / "references/application.md"
            ).read_text()
            (self.fixture.target / relative).write_text(guidance)
            (self.fixture.installed / relative).write_text("customer domain extension")
        self.fixture.git(self.fixture.target, "rm", "-f", "obsolete.txt")
        (self.fixture.target / "new-guide.md").write_text("target addition")
        self.fixture.git(self.fixture.target, "add", "new-guide.md")
        (self.fixture.installed / "new-guide.md").write_text("untracked customer collision")
        (self.fixture.installed / ".env.private").write_text("PRIVATE_VALUE=never report")
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "ready", result)
        rows = {r["path"]: r for r in result["customization_review"]["files"]}
        self.assertEqual(rows["README.md"]["action"], "update")
        self.assertEqual(rows["README.md"]["local"], "modified")
        self.assertEqual(rows["AGENTS.md"]["action"], "manual-reconciliation")
        self.assertEqual((self.fixture.installed / "AGENTS.md").read_text(), "customer extension")
        self.assertIn(
            "explicit manual Audit events", (self.fixture.target / "AGENTS.md").read_text()
        )
        for area in ("backend", "laravel", "postgresql", "security", "frontend"):
            relative = ".agents/skills/" + area + "/references/application.md"
            self.assertEqual(rows[relative]["action"], "manual-reconciliation")
            self.assertIn(
                "docs/runa/handbook/modules/auth/README.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/transport.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/sessions.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/security.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/profile.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/preferences.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/auth/policies-and-operators.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/permissions/README.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/modules/permissions/authority.md",
                (self.fixture.target / relative).read_text(),
            )
            self.assertIn(
                "docs/runa/handbook/data/seeding.md#explicit-module-development-contributors",
                (self.fixture.target / relative).read_text(),
            )
            self.assertEqual(
                (self.fixture.installed / relative).read_text(), "customer domain extension"
            )
        self.assertEqual(rows["obsolete.txt"]["action"], "remove")
        self.assertEqual(rows["new-guide.md"]["action"], "manual-reconciliation")
        self.assertNotIn("never report", json.dumps(result))
        self.assertEqual((self.fixture.installed / "README.md").read_text(), "customer readme")

    def test_project_review_tracks_executable_modes_and_rejects_symlink_parents(self):
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            path = root / "scripts/task"
            path.parent.mkdir()
            path.write_text("task")
            self.fixture.git(root, "add", "scripts/task")
        (self.fixture.target / "scripts/task").chmod(0o755)
        review = self.fixture.run_plan()["customization_review"]
        row = next(r for r in review["files"] if r["path"] == "scripts/task")
        self.assertEqual(row["target"]["mode"], "100755")
        (self.fixture.installed / "scripts/task").unlink()
        (self.fixture.installed / "scripts").rmdir()
        (self.fixture.installed / "scripts").symlink_to(self.fixture.target / "scripts")
        self.assertEqual(self.fixture.run_plan()["status"], "blocked")

    def test_customization_reports_added_removed_services_without_secret_values(self):
        (self.fixture.installed / "compose.yaml").write_text(
            '{"services": {"redis": {"image": "redis", "environment": {"PASSWORD": "secret-value"}}}}'
        )
        before = self.fixture.snapshot()
        result = self.fixture.run_plan()
        self.assertEqual(result["status"], "ready", result)
        review = result["customization_review"]
        self.assertTrue(review["required"])
        self.assertEqual(review["services"], ["app", "redis"])
        self.assertNotIn("secret-value", json.dumps(result))
        self.assertEqual(self.fixture.snapshot(), before)
        with self.assertRaises(contracts.Blocker):
            update_customization.decision(review, None)
        with self.assertRaises(contracts.Blocker):
            update_customization.decision(review, "abort")
        self.assertEqual(update_customization.decision(review, "continue"), review["fingerprint"])
        with self.assertRaises(contracts.Blocker):
            update_customization.decision(review, "continue", "old-review")
        (self.fixture.installed / "src/runa/Support/example.php").write_text("tampered")
        self.assertEqual(self.fixture.run_plan()["checks"][-1]["id"], "protected-source-mismatch")

    def test_untracked_infrastructure_changes_are_bound_to_exact_review(self):
        extra = self.fixture.installed / "compose.override.yaml"
        extra.write_text('{"services":{"redis":{"image":"redis"}}}')
        before = self.fixture.run_plan()["customization_review"]
        self.assertIn("compose.override.yaml", [r["path"] for r in before["files"]])
        extra.write_text('{"services":{"redis":{"image":"redis","command":["changed"]}}}')
        after = self.fixture.run_plan()["customization_review"]
        self.assertNotEqual(before["fingerprint"], after["fingerprint"])

    def test_multiple_devcontainer_compose_files_and_override_services_are_reviewed(self):
        (self.fixture.installed / ".devcontainer").mkdir()
        (self.fixture.installed / "containers").mkdir()
        (self.fixture.installed / ".devcontainer/devcontainer.json").write_text(
            json.dumps({"dockerComposeFile": ["../compose.yaml", "../containers/extra.yml"]})
        )
        (self.fixture.installed / "containers/extra.yml").write_text(
            '{"services":{"redis":{"image":"redis"}}}'
        )
        (self.fixture.installed / "compose.override.yaml").write_text(
            '{"services":{"worker":{"image":"worker"}}}'
        )
        review = self.fixture.run_plan()["customization_review"]
        self.assertEqual(review["services"], ["redis", "worker"])
        self.assertTrue(review["devcontainer"])

    @pytest.mark.docker
    def test_native_compose_parser_preserves_literal_env_and_reports_custom_services(self):
        self.fixture.installed.joinpath("compose.yaml").write_text(
            'services: {redis: {image: redis, environment: {PASSWORD: "${CUSTOM_SECRET:-private-value}"}}}'
        )
        with patch.object(update_customization, "configuration", self.fixture.native_configuration):
            result = update_customization.review(self.fixture.installed, self.fixture.original)
        self.assertEqual(result["services"], ["app", "redis"])
        self.assertNotIn("private-value", json.dumps(result))

    def test_shared_dockerfile_and_workspace_only_changes_are_reviewed(self):
        for root in (self.fixture.original, self.fixture.installed, self.fixture.target):
            (root / "docker").mkdir()
            (root / "docker/Dockerfile").write_text("FROM scratch")
            (root / "compose.yaml").write_text(
                json.dumps(
                    {
                        "services": {
                            name: {"build": {"context": ".", "dockerfile": "docker/Dockerfile"}}
                            for name in ("app", "queue")
                        }
                    }
                )
            )
        (self.fixture.installed / "docker/Dockerfile").write_text("FROM scratch\nLABEL changed=yes")
        review = self.fixture.run_plan()["customization_review"]
        self.assertEqual(review["services"], ["app", "queue"])
        (self.fixture.installed / "docker/Dockerfile").write_text("FROM scratch")
        (self.fixture.installed / ".devcontainer").mkdir()
        (self.fixture.installed / ".devcontainer/devcontainer.json").write_text(
            '{"workspaceFolder":"/custom"}'
        )
        review = self.fixture.run_plan()["customization_review"]
        self.assertTrue(review["devcontainer"])
        self.assertEqual(review["services"], [])
