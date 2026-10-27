#!/usr/bin/env python3
"""Failure-first contracts for deployment identity and maintenance recovery boundaries."""

import contextlib
import io
import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from tools.application import repository_boundary_service as repository_boundary
from tools.application.customer_source_service import CustomerSource
from tools.application.deployment_context import read_env
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.maintainer.tests.python.support.deployment import bind_deployment, deployment

ROOT = Path(__file__).resolve().parents[5]


class OperationContracts(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.deployment = deployment(Path(self.temporary.name))
        boundary = patch.object(
            RepositoryPolicy, "assert_installation", return_value={"mode": "prod"}
        )
        boundary.start()
        self.addCleanup(boundary.stop)

    def test_exact_release_builds_or_reuses_the_complete_php_nginx_postgres_set(self):
        images = [
            "runa-production:" + "a" * 40,
            "runa-nginx:" + "a" * 40,
            "runa-postgres:" + "a" * 40,
        ]
        for codes in ([1, 1, 1], [0, 0, 0], [0, 1, 0]):

            def dc(arguments, *args, **kwargs):
                return subprocess.CompletedProcess(arguments, 0, stdout="\n".join(images))

            results = [subprocess.CompletedProcess([], code) for code in codes]
            with (
                self.subTest(codes=codes),
                patch("tools.application.dependencies.materialize"),
                patch.object(self.deployment.runtime, "dc", side_effect=dc) as compose,
                patch.object(self.deployment.context, "run", side_effect=results),
                patch.object(self.deployment.runtime.production, "verify_images") as verify,
            ):
                if codes == [0, 1, 0]:
                    with self.assertRaisesRegex(ValueError, "Incomplete immutable image set"):
                        self.deployment.runtime.build_images()
                    verify.assert_not_called()
                else:
                    self.deployment.runtime.build_images()
                    verify.assert_called_once()
                    builds = [
                        call.args[0]
                        for call in compose.call_args_list
                        if call.args[0][0] == "build"
                    ]
                    self.assertEqual(
                        builds,
                        [["build", "app", "nginx", "postgres"]] if codes == [1, 1, 1] else [],
                    )

    def test_deploy_and_rollback_require_production_mode_before_any_state_or_source_work(self):
        for mode in ("dev", None, "unknown"):
            for simulation in (False, True):
                with (
                    self.subTest(mode=mode, simulation=simulation),
                    patch.object(
                        RepositoryPolicy, "assert_installation", return_value={"mode": mode}
                    ),
                    patch.object(self.deployment.context, "inside", return_value=False),
                    patch.object(self.deployment.source, "resolve") as resolve,
                    patch.object(self.deployment.context, "read_state") as read,
                    patch.object(self.deployment.context, "lock") as lock,
                    patch.object(self.deployment.context, "run") as run,
                ):
                    for invoke in (
                        lambda simulation=simulation: self.deployment.deploy("a" * 40, simulation),
                        self.deployment.rollback,
                        lambda simulation=simulation: self.deployment.main(
                            "deploy", ["a" * 40, *(["--simulation"] if simulation else [])]
                        ),
                        lambda: self.deployment.main("deploy", ["rollback"]),
                    ):
                        with self.assertRaisesRegex(
                            ValueError, "require a production installation"
                        ):
                            invoke()
                    resolve.assert_not_called()
                    read.assert_not_called()
                    lock.assert_not_called()
                    run.assert_not_called()

    def test_pause_keeps_nginx_serving_and_marks_maintenance_before_stopping_writers(self):
        events = []
        with (
            patch.object(
                self.deployment.runtime,
                "http_maintenance",
                side_effect=lambda enabled, release: events.append(("marker", enabled)),
            ),
            patch.object(
                self.deployment.runtime,
                "dc",
                side_effect=lambda args, release: events.append(("compose", args)),
            ),
        ):
            self.deployment.runtime.pause_http("/release")
        self.assertEqual(events[0], ("marker", True))
        self.assertEqual(events[1][1], ["up", "-d", "--no-build", "--no-deps", "nginx"])
        self.assertEqual(events[2][1], ["stop", "queue", "scheduler", "app"])

    def test_resume_waits_for_writers_and_restores_maintenance_on_nginx_health_failure(self):
        events = []

        def dc(args, release):
            events.append(("compose", args))
            if args == ["up", "-d", "--wait", "--no-build", "--no-deps", "nginx"]:
                raise ValueError("nginx health failed")

        with (
            patch.object(
                self.deployment.runtime,
                "resolved",
                return_value={
                    "services": {
                        name: {} for name in ("postgres", "app", "queue", "scheduler", "redis")
                    }
                },
            ),
            patch.object(
                self.deployment.runtime,
                "http_maintenance",
                side_effect=lambda enabled, release: events.append(("marker", enabled)),
            ),
            patch.object(self.deployment.runtime, "dc", side_effect=dc),
        ):
            with self.assertRaisesRegex(ValueError, "nginx health failed"):
                self.deployment.runtime.resume_http("/release", recreate=True)
        self.assertIn("--wait", events[0][1])
        self.assertNotIn("nginx", events[0][1])
        self.assertIn("redis", events[0][1])
        self.assertEqual(events[2], ("marker", False))
        self.assertEqual(events[-1], ("marker", True))

    def test_atomic_evidence_is_private_and_valid_json(self):
        with tempfile.TemporaryDirectory() as folder, bind_deployment(self, Path(folder)):
            path = Path(folder) / "private/state.json"
            self.deployment.context.atomic(path, {"phase": "migrations"})
            self.assertEqual(json.loads(path.read_text()), {"phase": "migrations"})
            self.assertEqual(path.stat().st_mode & 0o777, 0o600)
            self.assertFalse(path.with_suffix(".tmp").exists())

    def test_complete_customer_projection_preserves_development_source_and_excludes_owner_tools(
        self,
    ):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder) / "repository"
            root.mkdir()
            files = {
                "VERSION": "0.0.0",
                "src/app/example.php": "<?php",
                "src/runa/platform.json": "{}",
                "src/runa/Http/Controllers/HealthController.php": "<?php",
                "src/runa/lang/pl/validation.php": "<?php",
                "src/runa/management/commands.php": "operational registry",
                "src/runa/management/development.json": "excluded",
                "src/runa/management/development.php": "excluded",
                "tools/application/deployment_cli.py": "operational helper",
                "tools/application/knowledge/cli.py": "excluded",
                "src/runa/tools/example.py": "excluded",
                "src/runa/tests/example.php": "excluded",
                "docker/php/Dockerfile": "FROM example",
                ".agents/example": "excluded",
                "bin/knowledge": "excluded",
                "tools/application/knowledge/example.py": "excluded",
                "bin/operations": "launcher",
                "bin/production": "launcher",
                "docs/runa/handbook/ai/example.md": "excluded",
                "docs/runa/maintainer/internal.md": "excluded",
                "docker/embeddings/example": "excluded",
            }
            for name, text in files.items():
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(text)
            subprocess.run(["git", "init", "-q", str(root)], check=True)
            subprocess.run(["git", "-C", str(root), "add", "."], check=True)
            subprocess.run(
                [
                    "git",
                    "-C",
                    str(root),
                    "-c",
                    "user.name=Fixture",
                    "-c",
                    "user.email=fixture@example.invalid",
                    "commit",
                    "-qm",
                    "fixture",
                ],
                check=True,
            )
            sha = subprocess.check_output(
                ["git", "-C", str(root), "rev-parse", "HEAD"], text=True
            ).strip()
            # Start from the actual delivered customer source projection, not a materialized owner tree.
            patterns = "/*\n" + "".join(
                "!/" + name + "\n" for name in repository_boundary.OWNER_PATHS
            )
            subprocess.run(
                ["git", "-C", str(root), "sparse-checkout", "init", "--no-cone"], check=True
            )
            subprocess.run(
                ["git", "-C", str(root), "sparse-checkout", "set", "--no-cone", "--stdin"],
                input=patterns,
                text=True,
                check=True,
            )
            RepositoryPolicy.for_workspace(root).write_staging_receipt(
                root,
                {
                    "schema_version": 1,
                    "repository": "fixture/runa",
                    "projection": {"version": 1, "excluded": list(repository_boundary.OWNER_PATHS)},
                },
            )
            subprocess.run(
                [
                    "git",
                    "-C",
                    str(root),
                    "remote",
                    "add",
                    "origin",
                    "https://github.com/customer/application.git",
                ],
                check=True,
            )
            subprocess.run(
                ["git", "-C", str(root), "config", "--local", "runa.applicationRemote", "origin"],
                check=True,
            )
            with (
                bind_deployment(self, root),
                patch.object(self.deployment.context, "state", Path(folder) / "state"),
            ):
                release = self.deployment.source.checkout(sha)
                self.deployment.source.assert_delivery(release)
                self.assertTrue((release / "src/app/example.php").is_file())
                self.assertTrue(
                    (release / "src/runa/Http/Controllers/HealthController.php").is_file()
                )
                self.assertTrue((release / "src/runa/lang/pl/validation.php").is_file())
                self.assertTrue((release / "src/runa/platform.json").is_file())
                self.assertTrue((release / "src/runa/management/commands.php").is_file())
                self.assertTrue((release / "tools/application/deployment_cli.py").is_file())
                self.assertTrue((release / "src/runa/management/development.json").exists())
                self.assertTrue((release / "src/runa/management/development.php").exists())
                self.assertTrue((release / "tools/application/knowledge/cli.py").exists())
                for excluded in ("tools", "tests"):
                    self.assertFalse((release / "src/runa" / excluded).exists())
                self.assertTrue((release / "bin/operations").is_file())
                self.assertTrue((release / "docker/php/Dockerfile").is_file())
                self.assertEqual(self.deployment.source.checkout(sha), release)
                alias = Path(folder) / "aliased-state"
                alias.symlink_to(Path(folder) / "state")
                with (
                    patch.object(self.deployment.context, "state", alias),
                    self.assertRaisesRegex(ValueError, "symlinks"),
                ):
                    self.deployment.source.checkout(sha)
                (release / "VERSION").write_text("tampered")
                with self.assertRaisesRegex(ValueError, "modified"):
                    self.deployment.source.checkout(sha)

    def test_exact_target_rejects_shell_options_and_mutable_refs(self):
        with patch.object(self.deployment.context, "run") as call:
            for target in ("main", "latest", "--help", "abc123", "v1.2.3;false", "v1.2.3\n"):
                with self.assertRaises(ValueError):
                    self.deployment.source.resolve(target)
            call.assert_not_called()

    def test_failed_deploy_keeps_recovery_marker_when_schema_sources_change(self):
        self.check_failed_deploy(compatible=False)

    def test_equal_source_hashes_do_not_prove_safe_data_rollback(self):
        self.check_failed_deploy(compatible=True)

    def check_failed_deploy(self, compatible):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            state = root / "state"
            sha = "a" * 40
            previous = {
                "sha": "b" * 40,
                "path": "/previous",
                "database_tree": "same" if compatible else "different",
                "simulation": True,
                "images": {
                    "postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873": "fixture"
                },
            }
            config = root / ".env.prod.compose"
            config.write_text(
                "RUNA_IMAGE_TAG=" + previous["sha"] + "\nRUNA_PRODUCTION_MODE=simulation\n"
            )
            calls = []

            def dc(args, release=None, **kwargs):
                calls.append((args, release))
                return subprocess.CompletedProcess(args, 0, stdout="")

            with (
                bind_deployment(self, root),
                patch.object(self.deployment.context, "state", state),
                patch.object(self.deployment.runtime, "maintenance_active", return_value=False),
                patch.object(
                    self.deployment.context, "read_state", return_value={"current": previous}
                ),
                patch.object(self.deployment.source, "checkout", return_value=root / sha),
                patch.object(self.deployment.runtime, "dc", side_effect=dc),
                patch.object(self.deployment.runtime.production, "verify_images"),
                patch.object(self.deployment.runtime, "command", return_value=[]),
                patch.object(self.deployment.runtime, "validate"),
                patch.object(self.deployment.runtime, "build_images"),
                patch.object(self.deployment.source, "tree_hash", return_value="same"),
                patch.object(self.deployment.runtime, "health") as health,
                patch.object(
                    self.deployment.runtime, "artisan", side_effect=ValueError("migration failed")
                ),
                contextlib.redirect_stdout(io.StringIO()),
            ):
                with self.assertRaisesRegex(ValueError, "migration failed"):
                    self.deployment.deploy_revision(sha, True)
                self.assertTrue((state / "in-progress.json").exists())
                self.assertEqual(
                    json.loads((state / "in-progress.json").read_text())["phase"],
                    "application-migrations",
                )
                health.assert_not_called()
                self.assertEqual(calls[-1][0], ["stop", "queue", "scheduler", "app"])
                self.assertFalse(
                    any("migrate:rollback" in arg for args, _ in calls for arg in args)
                )

    def test_manual_maintenance_blocks_before_checkout_build_or_configuration_changes(self):
        previous = {
            "simulation": True,
            "path": "/previous",
            "images": {
                "postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873": "fixture"
            },
        }
        with (
            patch.object(self.deployment.context, "read_state", return_value={"current": previous}),
            patch(
                "tools.application.deployment_service.read_env",
                return_value={"RUNA_PRODUCTION_MODE": "simulation"},
            ),
            patch.object(self.deployment.runtime, "maintenance_active", return_value=True),
            patch.object(self.deployment.source, "checkout") as checkout,
            patch.object(self.deployment, "set_tag") as tag,
        ):
            with self.assertRaisesRegex(ValueError, "Maintenance is already active"):
                self.deployment.deploy_revision("a" * 40, True)
            checkout.assert_not_called()
            tag.assert_not_called()

    def test_default_customer_target_is_main_and_not_a_local_platform_ref(self):

        with (
            patch.object(CustomerSource, "fetch") as fetch,
            patch.object(
                self.deployment.context,
                "read_state",
                return_value={"current": {"sha": "b" * 40, "path": "/installed"}},
            ),
            patch.object(self.deployment, "deploy_revision") as deploy,
            patch.object(self.deployment.source, "resolve") as resolve,
        ):
            fetch.return_value.__enter__.return_value = (Path("/customer"), "a" * 40)
            self.deployment.deploy()
            fetch.assert_called_once_with(
                self.deployment.context.workspace.root,
                self.deployment.context.state,
                "main",
                installed=Path("/installed"),
            )
            deploy.assert_called_once_with("a" * 40, False, source=Path("/customer"))
            resolve.assert_not_called()

    def test_acquired_but_uninstalled_source_cannot_replace_platform_installation(self):

        with (
            patch.object(self.deployment.context, "read_state", return_value={"current": None}),
            patch.object(CustomerSource, "fetch") as fetch,
        ):
            with self.assertRaisesRegex(ValueError, "completed platform installation"):
                self.deployment.deploy()
            fetch.assert_not_called()

    def test_unchanged_migration_sources_do_not_authorize_code_rollback_after_seeding(self):
        with (
            tempfile.TemporaryDirectory() as folder,
            patch.object(self.deployment.context, "state", Path(folder)),
            patch.object(
                self.deployment.context,
                "read_state",
                return_value={
                    "current": {"database_tree": "same", "rollback_safe": False},
                    "previous": {"database_tree": "same"},
                },
            ),
            patch.object(self.deployment, "set_tag") as tag,
        ):
            with self.assertRaisesRegex(ValueError, "Schema/data compatibility is unproven"):
                self.deployment.rollback()
            tag.assert_not_called()

    def test_build_seeding_and_health_failures_have_distinct_recovery_boundaries(self):
        sha = "a" * 40
        previous = {
            "sha": "b" * 40,
            "path": "/previous",
            "database_tree": "same",
            "simulation": True,
            "images": {
                "postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873": "fixture"
            },
        }
        for failure in (
            "build",
            "platform-migration",
            "platform-seed",
            "platform-module",
            "seed",
            "health",
        ):
            with (
                self.subTest(failure=failure),
                tempfile.TemporaryDirectory() as folder,
                contextlib.ExitStack() as stack,
            ):
                root = Path(folder)
                state = root / "state"
                original = (
                    "RUNA_IMAGE_TAG=" + previous["sha"] + "\nRUNA_PRODUCTION_MODE=simulation\n"
                )
                (root / ".env.prod.compose").write_text(original)
                modules = root / sha / "src/runa/Support/Modules/Updates/ModuleUpdates.php"
                modules.parent.mkdir(parents=True)
                modules.write_text("<?php // module update capability fixture")
                calls = []
                stack.enter_context(bind_deployment(self, root))
                stack.enter_context(patch.object(self.deployment.context, "state", state))
                stack.enter_context(
                    patch.object(
                        self.deployment.context, "read_state", return_value={"current": previous}
                    )
                )
                stack.enter_context(
                    patch.object(self.deployment.runtime, "maintenance_active", return_value=False)
                )
                stack.enter_context(
                    patch.object(self.deployment.source, "checkout", return_value=root / sha)
                )
                stack.enter_context(
                    patch.object(
                        self.deployment.runtime,
                        "dc",
                        side_effect=lambda args, *a, calls=calls, **kw: calls.append(args),
                    )
                )
                stack.enter_context(
                    patch.object(
                        self.deployment.runtime,
                        "resolved",
                        return_value={
                            "services": {
                                name: {} for name in ("postgres", "app", "queue", "scheduler")
                            }
                        },
                    )
                )
                stack.enter_context(patch.object(self.deployment.runtime, "validate"))
                stack.enter_context(
                    patch.object(
                        self.deployment.runtime,
                        "build_images",
                        side_effect=ValueError("build failed") if failure == "build" else None,
                    )
                )

                def artisan(args, release, failure=failure):
                    if failure == "seed" and args[0] == "db:seed":
                        raise ValueError("seed failed")

                data = stack.enter_context(
                    patch.object(self.deployment.runtime, "artisan", side_effect=artisan)
                )

                def platform_data(sha, phase, release, failure=failure):
                    if (
                        (failure == "platform-migration" and phase == "migrations")
                        or (failure == "platform-seed" and phase == "required-seeds")
                        or (failure == "platform-module" and phase == "modules")
                    ):
                        raise ValueError(failure + " failed")

                stack.enter_context(
                    patch.object(
                        self.deployment.runtime, "platform_data", side_effect=platform_data
                    )
                )
                stack.enter_context(
                    patch.object(
                        self.deployment.runtime, "health", side_effect=ValueError("health failed")
                    )
                )
                stack.enter_context(contextlib.redirect_stdout(io.StringIO()))
                with self.assertRaisesRegex(ValueError, failure + " failed"):
                    self.deployment.deploy_revision(sha, True)
                if failure == "build":
                    self.assertEqual((root / ".env.prod.compose").read_text(), original)
                    self.assertFalse((state / "in-progress.json").exists())
                    data.assert_not_called()
                else:
                    pending = json.loads((state / "in-progress.json").read_text())
                    self.assertTrue(pending["data_steps_started"])
                    phases = {
                        "platform-migration": "platform-migrations",
                        "platform-seed": "platform-seeding",
                        "platform-module": "platform-modules",
                        "seed": "application-seeding",
                        "health": "restart-and-health",
                    }
                    self.assertEqual(pending["phase"], phases[failure])
                    self.assertEqual(calls[-1], ["stop", "queue", "scheduler", "app"])
                    self.assertEqual(
                        [call.args[0][0] for call in data.call_args_list],
                        [] if failure.startswith("platform-") else ["migrate", "db:seed"],
                    )

    def test_same_commit_verifies_health_without_replaying_data_steps(self):
        previous = {
            "sha": "a" * 40,
            "path": "/previous",
            "simulation": True,
            "images": {
                "postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873": "fixture"
            },
        }
        with (
            patch.object(self.deployment.context, "read_state", return_value={"current": previous}),
            patch(
                "tools.application.deployment_service.read_env",
                return_value={"RUNA_PRODUCTION_MODE": "simulation"},
            ),
            patch.object(self.deployment.runtime, "maintenance_active", return_value=False),
            patch.object(self.deployment.source, "checkout") as checkout,
            patch.object(self.deployment.runtime, "artisan") as artisan,
            patch.object(self.deployment.runtime, "health") as health,
        ):
            self.deployment.deploy_revision("a" * 40, True)
            health.assert_called_once_with("/previous", verify_source=True)
            checkout.assert_not_called()
            artisan.assert_not_called()

    def test_exclusive_operation_lock(self):
        with (
            tempfile.TemporaryDirectory() as folder,
            patch.object(self.deployment.context, "state", Path(folder)),
        ):
            with self.deployment.context.lock():
                with self.assertRaisesRegex(ValueError, "Another production"):
                    with self.deployment.context.lock():
                        pass

    def test_environment_file_rejects_duplicate_keys_and_multiline(self):
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / "env"
            for text in ("DB_PASSWORD=x\nDB_PASSWORD=y\n", "INVALID LINE\n", "BAD_KEY=a\x00b\n"):
                path.write_text(text)
                with self.assertRaises(ValueError):
                    read_env(path)
            path.write_text("DB_PASSWORD=$literal#value\n")
            self.assertEqual(read_env(path)["DB_PASSWORD"], "$literal#value")

    def test_rollback_blocks_migration_or_seeder_change_before_any_mutation(self):
        current = {"database_tree": "new"}
        previous = {"database_tree": "old"}
        with (
            tempfile.TemporaryDirectory() as folder,
            patch.object(self.deployment.context, "state", Path(folder)),
            patch.object(
                self.deployment.context,
                "read_state",
                return_value={"current": current, "previous": previous},
            ),
            patch.object(self.deployment.runtime, "dc") as dc,
            patch.object(self.deployment, "set_tag") as tag,
        ):
            with self.assertRaisesRegex(ValueError, "Migration/seeder"):
                self.deployment.rollback()
            dc.assert_not_called()
            tag.assert_not_called()

    def test_release_commands_pin_their_own_sha_and_clear_ambient_context(self):
        with tempfile.TemporaryDirectory() as folder, bind_deployment(self, Path(folder)):
            root = Path(folder)
            sha = "a" * 40
            release = root / "releases" / sha
            release.mkdir(parents=True)
            for name, text in (
                (".env.prod", "APP_KEY=x\n"),
                (".env.prod.database", "POSTGRES_PASSWORD=y\n"),
                (
                    ".env.prod.compose",
                    "RUNA_IMAGE_TAG=" + "b" * 40 + "\nRUNA_PRODUCTION_MODE=simulation\n",
                ),
            ):
                (root / name).write_text(text)
            command = self.deployment.runtime.command(release)
            self.assertEqual(
                command[command.index("--project-name") + 1], "runa-prod-icu-simulation"
            )
            self.assertIn("RUNA_IMAGE_TAG=" + sha, (release / ".env.prod.compose").read_text())
            self.assertEqual((release / ".env.prod").stat().st_mode & 0o777, 0o600)
