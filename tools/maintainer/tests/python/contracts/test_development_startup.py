#!/usr/bin/env python3
"""Application startup failure boundaries and independent knowledge recovery."""

import contextlib
import io
import json
import subprocess
import tempfile
import unittest
from contextlib import contextmanager
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import Mock, patch

from tools.application import runtime
from tools.application.development import DevelopmentService
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[5]


class DevelopmentFixture(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.processes = Mock()
        self.processes.environment = {}
        self.dev = DevelopmentService(
            WorkspaceContext(Path(self.temporary.name)), self.processes, ExecutionLocation.HOST
        )
        self.arguments = []

    @contextmanager
    def bind(self, root, real=False):
        previous = self.dev
        processes = ApplicationProcesses(SubprocessRunner(), {}) if real else self.processes
        self.dev = DevelopmentService(WorkspaceContext(root), processes, ExecutionLocation.HOST)
        try:
            yield
        finally:
            self.dev = previous

    @contextmanager
    def arguments_for(self, argv):
        self.arguments = argv[1:]
        yield


class StartupContracts(DevelopmentFixture):
    def test_pre_autoload_setup_uses_the_private_executor(self):
        self.assertIn(
            "command: [python3, tools/application/executors/dev.py, setup, --if-needed]",
            (ROOT / "compose.yaml").read_text(),
        )
        self.assertFalse((ROOT / "bin/dev").exists())
        editor = json.loads((ROOT / ".devcontainer/devcontainer.json").read_text())
        self.assertEqual(editor["initializeCommand"], ["./bin/artisan", "runa:dev:prepare"])
        self.assertEqual(
            editor["postCreateCommand"],
            ["python3", "tools/application/executors/dev.py", "setup", "--if-needed"],
        )

    def host_context(self):
        return contextlib.nullcontext()

    def test_application_wait_excludes_optional_ai_and_optional_failure_is_degraded(self):
        values = dict(EMBEDDINGS_CUDA="1")
        results = [
            SimpleNamespace(returncode=0),
            SimpleNamespace(returncode=1),
            SimpleNamespace(returncode=0),
        ]
        with (
            self.host_context(),
            patch.object(type(self.dev.configuration), "load", return_value=values),
            patch.object(type(self.dev.configuration), "resolve_embeddings"),
            patch.object(type(self.dev.configuration), "persist"),
            patch.object(self.processes, "run", side_effect=results) as run,
            self.arguments_for(["dev", "up"]),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            self.assertEqual(self.dev.main(self.arguments), 0)
        wait, optional, diagnostics = [c.args[0] for c in run.call_args_list]
        self.assertIn("--wait", wait)
        self.assertNotIn("qdrant", wait)
        self.assertNotIn("embeddings-gpu", wait)
        self.assertIn("setup", wait)
        self.assertIn("--no-deps", optional)
        self.assertIn("embeddings-gpu", optional)
        self.assertIn("knowledge-init", optional)
        self.assertEqual(diagnostics[-1], "runa:dev:knowledge-status")

    def test_selective_build_and_recreate_do_not_touch_siblings(self):
        for action, service in (("build", "mailpit"), ("recreate", "qdrant")):
            with (
                self.host_context(),
                patch.object(
                    type(self.dev.configuration), "load", return_value={"EMBEDDINGS_CUDA": "0"}
                ),
                patch.object(type(self.dev.configuration), "resolve_embeddings"),
                patch.object(type(self.dev.configuration), "persist"),
                patch.object(
                    self.processes, "run", return_value=SimpleNamespace(returncode=0)
                ) as run,
                self.arguments_for(["dev", action, service]),
                contextlib.redirect_stdout(io.StringIO()),
            ):
                self.assertEqual(self.dev.main(self.arguments), 0)
            command = run.call_args.args[0]
            self.assertEqual(command[-1], service)
            self.assertNotIn("app", command)
            if action == "recreate":
                self.assertIn("--no-deps", command)
                self.assertIn("--force-recreate", command)
                self.assertNotIn("--wait", command)

    def test_invalid_selective_scope_never_mutates_profile_or_calls_docker(self):
        for args in (
            ["recreate"],
            ["recreate", "nginx;false"],
            ["build", "postgres"],
            ["build", "mailpit", "mailpit"],
            ["build", "embeddings-gpu"],
        ):
            with (
                self.host_context(),
                patch.object(
                    type(self.dev.configuration), "load", return_value={"EMBEDDINGS_CUDA": "0"}
                ),
                patch.object(type(self.dev.configuration), "resolve_embeddings") as resolve,
                patch.object(type(self.dev.configuration), "persist") as persist,
                patch.object(self.processes, "run") as run,
                self.arguments_for(["dev", *args]),
            ):
                with self.assertRaises(ValueError):
                    self.dev.main(self.arguments)
                run.assert_not_called()
                persist.assert_not_called()
                resolve.assert_not_called()

    def test_config_validation_never_prints_expanded_environment(self):
        with (
            self.host_context(),
            patch.object(
                type(self.dev.configuration), "load", return_value={"EMBEDDINGS_CUDA": "0"}
            ),
            patch.object(type(self.dev.configuration), "resolve_embeddings"),
            patch.object(type(self.dev.configuration), "persist"),
            patch.object(self.processes, "run", return_value=SimpleNamespace(returncode=0)) as run,
            self.arguments_for(["dev", "config"]),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            self.assertEqual(self.dev.main(self.arguments), 0)
        self.assertEqual(run.call_args.args[0][-2:], ["config", "--quiet"])

    def test_application_failure_is_not_swallowed(self):
        with (
            self.host_context(),
            patch.object(
                type(self.dev.configuration), "load", return_value={"EMBEDDINGS_CUDA": "0"}
            ),
            patch.object(type(self.dev.configuration), "resolve_embeddings"),
            patch.object(type(self.dev.configuration), "persist"),
            patch.object(self.processes, "run", return_value=SimpleNamespace(returncode=7)) as run,
            self.arguments_for(["dev", "up"]),
        ):
            self.assertEqual(self.dev.main(self.arguments), 7)
            self.assertEqual(run.call_count, 1)

    def test_failed_application_setup_never_writes_success_marker(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / ".env").write_text("APP_KEY=test\n")
            (root / ".env.testing").write_text("APP_KEY=test\n")
            exists = Path.exists

            def present(path):
                return str(path) == "/.dockerenv" or exists(path)

            def command(args, **kwargs):
                if args[:3] == ["php", "artisan", "runa:config:validate"]:
                    raise subprocess.CalledProcessError(1, args)
                self.assertNotIn("knowledge", " ".join(args))
                return SimpleNamespace(returncode=0)

            with (
                self.bind(root),
                patch.object(type(self.dev.configuration), "load", return_value={}),
                patch.object(type(self.dev.configuration), "environment_files"),
                patch.object(type(self.dev.setup), "register_boost"),
                patch.object(Path, "exists", present),
                patch.object(self.processes, "run", side_effect=command),
                self.arguments_for(["dev", "setup"]),
            ):
                with self.assertRaises(subprocess.CalledProcessError):
                    self.dev.main(self.arguments)
            self.assertFalse((root / ".runtime/dev/setup.sha256").exists())

    def setup_fixture(self, root):
        for name in (
            "src/vendor/autoload.php",
            "src/node_modules/.modules.yaml",
            "src/public/build/index.html",
            "src/public/runa-error/styles.css",
            "src/public/runa-error/pages/503-pl-system.html",
        ):
            path = root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.touch()
        (root / ".env").write_text("APP_KEY=test\n")
        (root / ".env.testing").write_text("APP_KEY=test\n")
        marker = root / ".runtime/dev/setup.sha256"
        marker.parent.mkdir(parents=True)
        marker.write_text("current\n")
        return marker

    def test_current_setup_skips_install_and_build_with_generated_error_document(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            marker = self.setup_fixture(root)
            with (
                self.bind(root),
                patch.object(type(self.dev.configuration), "environment_files"),
                patch.object(type(self.dev.setup), "register_boost") as register,
                patch.object(type(self.dev.setup), "setup_fingerprint", return_value="current"),
                patch.object(
                    self.processes, "run", return_value=SimpleNamespace(returncode=0)
                ) as run,
                contextlib.redirect_stdout(io.StringIO()) as output,
            ):
                self.assertEqual(self.dev.application_setup(["--if-needed"]), 0)
            commands = [call.args[0] for call in run.call_args_list]
            self.assertFalse(any(command[0] in ("composer", "pnpm") for command in commands))
            self.assertIn(["php", "artisan", "runa:config:validate"], commands)
            self.assertFalse(
                any(
                    command[:3] in (["php", "artisan", "migrate"], ["php", "artisan", "db:seed"])
                    for command in commands
                )
            )
            register.assert_called_once()
            self.assertIn("frontend build skipped", output.getvalue())
            self.assertEqual(marker.read_text(), "current\n")

    def test_missing_current_document_or_stale_fingerprint_runs_full_setup(self):
        for reason in ("missing_document", "stale_fingerprint"):
            with self.subTest(reason=reason), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                marker = self.setup_fixture(root)
                if reason == "missing_document":
                    (root / "src/public/runa-error/pages/503-pl-system.html").unlink()
                    (root / "src/public/runa-error/pages/503-pl.html").touch()
                else:
                    marker.write_text("stale\n")
                with (
                    self.bind(root),
                    patch.object(type(self.dev.configuration), "environment_files"),
                    patch.object(type(self.dev.setup), "register_boost"),
                    patch.object(type(self.dev.setup), "setup_fingerprint", return_value="current"),
                    patch.object(
                        self.processes, "run", return_value=SimpleNamespace(returncode=0)
                    ) as run,
                ):
                    self.assertEqual(self.dev.application_setup(["--if-needed"]), 0)
                commands = [call.args[0] for call in run.call_args_list]
                self.assertIn(
                    [
                        "composer",
                        "--working-dir=src",
                        "install",
                        "--no-interaction",
                        "--prefer-dist",
                    ],
                    commands,
                )
                self.assertIn(["pnpm", "--dir", "src", "run", "build"], commands)
                self.assertIn(["php", "src/runa/resources/errors/build.php"], commands)
                self.assertEqual(marker.read_text(), "current\n")

    def test_optional_probe_has_process_deadline(self):
        with (
            patch.object(
                self.processes, "run", side_effect=subprocess.TimeoutExpired("probe", 5)
            ) as run,
            contextlib.redirect_stdout(io.StringIO()) as output,
        ):
            self.dev.knowledge_availability()
        self.assertEqual(run.call_args.kwargs["timeout"], 5)
        self.assertIn("DEGRADED", output.getvalue())

    def test_app_health_does_not_require_ai(self):
        rows = [
            {"Service": name, "State": "running", "Health": "healthy"}
            for name in ("app", "nginx", "postgres", "queue", "scheduler", "mailpit")
        ]
        rows.append({"Service": "setup", "State": "exited", "ExitCode": 0})
        with (
            patch.object(runtime, "run", return_value=SimpleNamespace(stdout=json.dumps(rows))),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            runtime.host_stack_health()


class CustomerHookSetup(DevelopmentFixture):
    def test_customer_setup_configures_hooks_once_and_preserves_custom_choice(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            subprocess.run(["git", "init", "-q", str(root)], check=True)
            (root / ".githooks").mkdir()
            for name in ("pre-commit", "commit-msg"):
                (root / ".githooks" / name).write_text("#!/bin/sh\nexit 0\n")
            with self.bind(root, real=True):
                self.dev.setup.register_hooks()
                result = subprocess.check_output(
                    ["git", "config", "--local", "--get", "core.hooksPath"], cwd=root, text=True
                ).strip()
                self.assertEqual(result, ".githooks")
                subprocess.run(
                    ["git", "config", "--local", "core.hooksPath", "custom-hooks"],
                    cwd=root,
                    check=True,
                )
                self.dev.setup.register_hooks()
                self.assertEqual(
                    subprocess.check_output(
                        ["git", "config", "--local", "--get", "core.hooksPath"], cwd=root, text=True
                    ).strip(),
                    "custom-hooks",
                )


class EnvironmentOwnership(DevelopmentFixture):
    def test_repeat_preserves_secrets_and_reports_testing_mismatch_without_values(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            for name in (".env.dev.example", ".env.test.example"):
                (root / name).write_text("DB_PASSWORD=\nAPP_KEY=\n")
            with self.bind(root):
                self.dev.configuration.environment_files()
                before = {p.name: p.read_bytes() for p in root.glob(".env*")}
                self.dev.configuration.environment_files()
                self.assertEqual(before, {p.name: p.read_bytes() for p in root.glob(".env*")})
                (root / ".env.testing").write_text("DB_PASSWORD=synthetic-mismatch\n")
                with self.assertRaisesRegex(
                    ValueError, "Testing and database initialization credentials differ"
                ) as failure:
                    self.dev.configuration.environment_files()
                self.assertNotIn("synthetic-mismatch", str(failure.exception))
                self.assertEqual(
                    (root / ".env.dev.database").read_bytes(), before[".env.dev.database"]
                )
                (root / ".env.testing").chmod(0o644)
                with self.assertRaisesRegex(ValueError, "private owned regular"):
                    self.dev.configuration.environment_files()

    def test_dangling_secret_links_are_rejected_before_any_creation(self):
        for name in (".env", ".env.dev.database", ".env.testing"):
            with self.subTest(name=name), tempfile.TemporaryDirectory() as directory:
                root = Path(directory)
                (root / ".env.dev.example").write_text("DB_PASSWORD=\n")
                outside = root / "untouched"
                (root / name).symlink_to(outside)
                with self.bind(root), self.assertRaisesRegex(ValueError, "symlinks"):
                    self.dev.configuration.environment_files()
                self.assertFalse(outside.exists())
                self.assertTrue((root / name).is_symlink())

    def test_linked_secret_files_are_preserved_and_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / ".env.dev.example").write_text("DB_PASSWORD=\n")
            outside = root / "unrelated"
            outside.write_text("DB_PASSWORD=preserved\n")
            (root / ".env").symlink_to(outside)
            with self.bind(root), self.assertRaisesRegex(ValueError, "symlinks"):
                self.dev.configuration.environment_files()
            self.assertEqual(outside.read_text(), "DB_PASSWORD=preserved\n")
