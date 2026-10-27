import contextlib
import json
import os
import shutil
import unittest
from pathlib import Path
from typing import Unpack, cast
from unittest.mock import Mock, patch

import distribution.update_contracts as update_contracts
import distribution.update_execution as execution
from distribution.update_execution_models import ExecutionOptions, ExecutionReport
from distribution.update_models import DatabaseMetadata, RuntimeSnapshot
from tools.core.state import json_value
from tools.maintainer.testing import update_host as acceptance_update_host
from tools.maintainer.tests.python.support.deployment import deployment
from tools.maintainer.tests.python.support.updater import UpdaterWorkspace

#!/usr/bin/env python3
"""Interrupted source switches, durable recovery and no replay of completed data."""


ROOT = Path(__file__).resolve().parents[5]


class Runtime:
    def __init__(self, fixture: UpdaterWorkspace) -> None:
        self.fixture = fixture
        self.root = fixture.installed
        self.fail: str | None = None
        self.events: list[str] = []
        self.fenced = False
        self.seeds = 0

    def bind(self, project: str) -> None:
        if project != "fixture":
            raise ValueError("Wrong project")

    def probe(self, root: Path, mode: str, operator: Path) -> RuntimeSnapshot:
        return self.fixture.runtime

    def metadata(self) -> DatabaseMetadata:
        return cast(DatabaseMetadata, json_value(self.fixture.runtime))

    def manual(self) -> None:
        pass

    def event(self, name: str) -> None:
        self.events.append(name)
        if self.fail == name:
            self.fail = None
            raise RuntimeError("Injected failure: " + name)

    def fence(self, identity: str) -> None:
        self.fenced = True
        self.event("fence")

    def build(self) -> None:
        self.event("build")

    def data(self, phase: str, release: str) -> None:
        self.event(phase)
        if phase == "migrations":
            self.fixture.runtime["migrations"] = sorted(
                p.stem for p in (self.fixture.target / "src/runa/database/migrations").glob("*.php")
            )
        elif phase == "required-seeds":
            self.fixture.runtime["seed_runs"].append(
                {"release_sha": release, "seeder": execution.SEEDER}
            )
            self.seeds += 1
        self.event(phase + "-committed")

    def activate(self, identity: str) -> None:
        self.fenced = False
        self.event("activate")

    def health(self) -> None:
        self.event("health")


class Execution(unittest.TestCase):
    def setUp(self) -> None:
        self.fixture = UpdaterWorkspace(self.addCleanup)
        (self.fixture.installed / ".runtime/production").chmod(0o700)
        modules = self.fixture.target / "src/runa/Support/Modules/Updates/ModuleUpdates.php"
        modules.parent.mkdir(parents=True, exist_ok=True)
        modules.write_text("<?php // target module update capability fixture")
        self.engine = Runtime(self.fixture)

    def apply(self, **kwargs: Unpack[ExecutionOptions]) -> ExecutionReport:
        kwargs.setdefault("choose", lambda review: kwargs.get("choice", "continue"))
        return execution.execute(
            self.fixture.installed,
            self.fixture.client,
            "v0.3.0",
            download=self.fixture.download,
            runtime_factory=lambda root: self.engine,
            lock_probe=lambda *a: "fixture",
            **kwargs,
        )

    def journal(self):
        return json.loads(
            (self.fixture.installed / ".runtime/production/in-progress.json").read_text()
        )

    def test_execution_resume_refuses_invalid_consumed_journal_without_mutation(self) -> None:
        self.engine.fail = "build"
        self.assertEqual(self.apply()["status"], "blocked")
        pending = self.fixture.installed / ".runtime/production/in-progress.json"
        original = pending.read_bytes()
        before = list(self.engine.events)
        invalid: tuple[tuple[tuple[str, ...], object], ...] = (
            (("schema_version",), True),
            (("target",), []),
            (("id",), 42),
            (("baseline", "migrations"), [True]),
            (("baseline", "seed_runs"), [{"release_sha": [], "seeder": "fixed"}]),
            (("baseline", "database_mounts"), [["volume", "name"]]),
            (("review", "fingerprint"), []),
            (("review", "services"), "app"),
            (("review", "devcontainer"), 1),
            (("review", "manual_reconciliation"), [None]),
            (("source_transition",), "true"),
            (("module_plan",), [{"module": "runa.fixture", "to": False}]),
            (("module_steps",), [{"module": "runa.fixture"}]),
        )
        for keys, value in invalid:
            with self.subTest(field=keys):
                changed = json_value(json.loads(original))
                assert isinstance(changed, dict)
                container = changed
                for key in keys[:-1]:
                    child = container[key]
                    assert isinstance(child, dict)
                    container = child
                container[keys[-1]] = json_value(value)
                pending.write_text(json.dumps(changed))
                evidence = pending.read_bytes()
                result = self.apply(resume=True)
                self.assertEqual(result["status"], "blocked", result)
                self.assertEqual(result["checks"][-1]["id"], "journal-identity", result)
                self.assertEqual(self.engine.events, before)
                self.assertEqual(pending.read_bytes(), evidence)
        pending.write_bytes(original)
        self.assertEqual(self.apply(resume=True)["status"], "completed")

    def test_execution_resume_preserves_extra_evidence_and_legacy_optional_fields(self) -> None:
        self.engine.fail = "build"
        self.assertEqual(self.apply()["status"], "blocked")
        pending = self.fixture.installed / ".runtime/production/in-progress.json"
        journal = self.journal()
        journal["future_evidence"] = {"diagnostic": ["retained", 17]}
        journal["baseline"]["future_baseline"] = {"retained": True}
        journal["review"]["future_review"] = {"retained": True}
        for key in ("module_steps", "module_plan"):
            journal.pop(key, None)
        journal["baseline"].pop("module_steps", None)
        journal["review"].pop("manual_reconciliation", None)
        pending.write_text(json.dumps(journal))
        legacy = json_value(journal)
        assert isinstance(legacy, dict)
        for key in ("file_actions", "file_action_history"):
            legacy.pop(key, None)
        self.assertIs(
            execution.execution_journal(
                legacy, self.fixture.installed, self.fixture.client.repository, "v0.3.0"
            ),
            legacy,
        )
        result = self.apply(resume=True)
        self.assertEqual(result["status"], "completed", result)
        self.assertEqual(result["manual_reconciliation"], [])
        complete = (
            self.fixture.installed / ".runtime/production/updates" / (journal["id"] + ".json")
        )
        retained = json.loads(complete.read_text())
        self.assertEqual(retained["future_evidence"], journal["future_evidence"])
        self.assertEqual(retained["baseline"]["future_baseline"], {"retained": True})
        self.assertEqual(retained["review"]["future_review"], {"retained": True})
        self.assertEqual(self.engine.seeds, 1)

    def test_execution_database_mount_identity_is_json_safe_and_blocks_changed_database(
        self,
    ) -> None:
        self.fixture.runtime["database_mounts"] = [
            ["volume", "fixture-postgres", "/var/lib/postgresql/data"]
        ]
        self.engine.fail = "build"
        self.assertEqual(self.apply()["status"], "blocked")
        self.assertEqual(
            self.journal()["baseline"]["database_mounts"], self.fixture.runtime["database_mounts"]
        )
        self.fixture.runtime["database_mounts"][0][1] = "another-database"
        before = list(self.engine.events)
        result = self.apply(resume=True)
        self.assertEqual(result["checks"][-1]["id"], "database-identity", result)
        self.assertEqual(self.engine.events, before)
        self.fixture.runtime["database_mounts"][0][1] = "fixture-postgres"
        self.assertEqual(self.apply(resume=True)["status"], "completed")

    def test_execution_replaces_only_platform_and_records_completion_without_git_writes(
        self,
    ) -> None:
        (self.fixture.installed / "src/app/customer.php").write_text("<?php // customer")
        before = {
            name: (self.fixture.installed / name).read_bytes()
            for name in (
                "compose.yaml",
                "src/app/application.json",
                "src/app/customer.php",
                ".git/config",
            )
        }
        result = self.apply()
        self.assertEqual(result["status"], "completed", result)
        self.assertEqual(update_contracts.platform(self.fixture.installed), "0.3.0")
        self.assertTrue(
            all(
                (self.fixture.installed / name).read_bytes() == content
                for name, content in before.items()
            )
        )
        self.assertEqual(
            self.fixture.git(self.fixture.installed, "rev-parse", "HEAD"), self.fixture.before
        )
        self.assertEqual(self.engine.seeds, 1)
        self.assertFalse(self.engine.fenced)
        self.assertFalse((self.fixture.installed / ".runtime/production/in-progress.json").exists())
        self.assertFalse(Path(result["source_staging_removed"]).exists())
        completed = list((self.fixture.installed / ".runtime/production/updates").glob("*.json"))
        self.assertEqual(len(completed), 1)
        self.assertEqual(json.loads(completed[0].read_text())["state"], "complete")
        self.assertEqual(self.apply()["status"], "completed")
        self.assertEqual(self.engine.seeds, 1)

    def test_execution_resume_refuses_lost_module_completion_history(self) -> None:
        row = {
            "module": "runa.fixture",
            "step": "upgrade:initialize:install:0.1.0",
            "fingerprint": "a" * 64,
            "status": "succeeded",
        }
        self.fixture.runtime["module_steps"] = [row]
        self.engine.fail = "health"
        self.assertEqual(self.apply()["status"], "blocked")
        self.fixture.runtime["module_steps"] = []
        result = self.apply(resume=True)
        self.assertEqual(result["status"], "blocked", result)
        self.assertEqual(result["checks"][-1]["id"], "module-history-missing")
        self.assertTrue(self.engine.fenced)

    def test_execution_legacy_target_without_module_phase_remains_supported(self) -> None:
        (self.fixture.target / "src/runa/Support/Modules/Updates/ModuleUpdates.php").unlink()
        result = self.apply()
        self.assertEqual(result["status"], "completed", result)
        self.assertNotIn("modules", self.engine.events)

    def test_execution_failure_boundaries_resume_without_replaying_completed_seed_effects(
        self,
    ) -> None:
        for phase in (
            "fence",
            "build",
            "migrations",
            "migrations-committed",
            "required-seeds",
            "required-seeds-committed",
            "modules",
            "modules-committed",
            "activate",
            "health",
        ):
            with self.subTest(phase=phase):
                # Each case starts with its own complete fixture and private state.
                case = Execution(
                    "test_execution_replaces_only_platform_and_records_completion_without_git_writes"
                )
                case.setUp()
                try:
                    case.engine.fail = phase
                    result = case.apply()
                    self.assertEqual(result["status"], "blocked", result)
                    self.assertTrue(case.engine.fenced)
                    self.assertEqual(case.journal()["state"], "failed")
                    resumed = case.apply(resume=True)
                    self.assertEqual(resumed["status"], "completed", resumed)
                    self.assertEqual(case.engine.seeds, 1)
                finally:
                    case.doCleanups()

    def test_execution_directory_rename_interruptions_revalidate_original_and_target_on_resume(
        self,
    ) -> None:
        original_rename = os.rename
        for boundary in (1, 2):
            case = Execution(
                "test_execution_replaces_only_platform_and_records_completion_without_git_writes"
            )
            case.setUp()
            calls = []

            def interrupted(source, destination, calls=calls, boundary=boundary):
                original_rename(source, destination)
                calls.append(destination)
                if len(calls) == boundary:
                    raise KeyboardInterrupt

            try:
                with (
                    patch.object(execution.os, "rename", side_effect=interrupted),
                    self.assertRaises(KeyboardInterrupt),
                ):
                    case.apply()
                self.assertEqual(case.journal()["state"], "interrupted")
                self.assertTrue(case.engine.fenced)
                result = case.apply(resume=True)
                self.assertEqual(result["status"], "completed", result)
                self.assertEqual(case.engine.seeds, 1)
            finally:
                case.doCleanups()

    def test_execution_gap_survives_another_failure_during_resume_fencing(self) -> None:
        original_rename = os.rename

        def interrupted(source, destination):
            original_rename(source, destination)
            raise KeyboardInterrupt

        with (
            patch.object(execution.os, "rename", side_effect=interrupted),
            self.assertRaises(KeyboardInterrupt),
        ):
            self.apply()
        self.engine.fail = "fence"
        self.assertEqual(self.apply(resume=True)["status"], "blocked")
        self.assertEqual(self.apply(resume=True)["status"], "completed")

    def test_execution_staging_symlink_is_rejected_before_source_or_data_mutations(self) -> None:
        self.engine.fail = "build"
        self.apply()
        operation = (
            self.fixture.installed / ".runtime/production/update-staging" / self.journal()["id"]
        )
        shutil.rmtree(operation / "incoming")
        (operation / "incoming").symlink_to(self.fixture.target, target_is_directory=True)
        before = list(self.engine.events)
        result = self.apply(resume=True)
        self.assertEqual(result["checks"][-1]["id"], "source-staging-changed", result)
        self.assertEqual(self.engine.events, before)

    def test_execution_customization_requires_explicit_choice_and_changed_resume_review(
        self,
    ) -> None:
        self.fixture.installed.joinpath("compose.yaml").write_text(
            '{"services":{"redis":{"image":"redis"}}}'
        )
        before = self.fixture.snapshot()
        self.assertEqual(
            self.apply(choose=None)["checks"][-1]["id"], "customization-decision-required"
        )
        self.assertEqual(self.fixture.snapshot(), before)
        self.assertEqual(self.apply(choice="abort")["checks"][-1]["id"], "customization-aborted")
        self.assertEqual(self.fixture.snapshot(), before)
        self.engine.fail = "build"
        self.assertEqual(self.apply(choice="continue")["status"], "blocked")
        self.fixture.installed.joinpath("compose.yaml").write_text(
            '{"services":{"redis":{"image":"redis","command":["changed"]}}}'
        )
        blocked = self.apply(resume=True, choice="continue", choose=None)
        self.assertEqual(blocked["checks"][-1]["id"], "customization-changed", blocked)
        fingerprint = blocked["customization_review"]["fingerprint"]
        self.assertEqual(
            self.apply(resume=True, choice="continue", fingerprint=fingerprint)["status"],
            "completed",
        )
        self.assertEqual(
            (self.fixture.installed / "compose.yaml").read_bytes(),
            (self.fixture.target / "compose.yaml").read_bytes(),
        )

    def test_reviewed_non_platform_add_replace_remove_and_instruction_preservation(self) -> None:
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            for name in ("README.md", "obsolete.txt", "AGENTS.md"):
                (root / name).write_text("baseline")
                self.fixture.git(root, "add", name)
        (self.fixture.target / "README.md").write_text("new readme")
        (self.fixture.installed / "README.md").write_text("customer customized readme")
        (self.fixture.installed / "AGENTS.md").write_text("customer instruction extension")
        (self.fixture.target / "AGENTS.md").write_text("new defaults")
        self.fixture.git(self.fixture.target, "rm", "-f", "obsolete.txt")
        (self.fixture.target / "new-guide.md").write_text("new guide")
        self.fixture.git(self.fixture.target, "add", "new-guide.md")
        result = self.apply()
        self.assertEqual(result["status"], "completed", result)
        self.assertEqual((self.fixture.installed / "README.md").read_text(), "new readme")
        self.assertEqual((self.fixture.installed / "new-guide.md").read_text(), "new guide")
        self.assertFalse((self.fixture.installed / "obsolete.txt").exists())
        self.assertEqual(
            (self.fixture.installed / "AGENTS.md").read_text(), "customer instruction extension"
        )
        self.assertIn("AGENTS.md", result["manual_reconciliation"])

    def test_file_rename_interruptions_resume_from_saved_before_after_state(self) -> None:
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            (root / "README.md").write_text("baseline")
            self.fixture.git(root, "add", "README.md")
        (self.fixture.target / "README.md").write_text("target")
        rename = execution.os.rename
        interrupted = False

        def interrupt(source, destination):
            nonlocal interrupted
            rename(source, destination)
            if Path(source) == self.fixture.installed / "README.md" and not interrupted:
                interrupted = True
                raise KeyboardInterrupt()

        with (
            patch.object(execution.os, "rename", side_effect=interrupt),
            self.assertRaises(KeyboardInterrupt),
        ):
            self.apply()
        self.assertFalse((self.fixture.installed / "README.md").exists())
        self.assertEqual(self.apply(resume=True)["status"], "completed")
        self.assertEqual((self.fixture.installed / "README.md").read_text(), "target")

    def test_customer_reconciles_configuration_before_publication_and_production_provision(
        self,
    ) -> None:
        from tools.application import installation_service as installer

        templates = (".env.dev.example", ".env.prod.example")
        defaults = {name: (ROOT / name).read_text() for name in templates}
        for root in (self.fixture.original, self.fixture.target, self.fixture.installed):
            for name, content in defaults.items():
                (root / name).write_text(content)
        self.fixture.before = self.fixture.commit(self.fixture.original)
        self.fixture.after = self.fixture.commit(self.fixture.target)
        for name in templates:
            with (self.fixture.installed / name).open("a") as stream:
                stream.write("\nTEAM_RELEASE_MARKER=\n")
        private_before = (self.fixture.installed / ".env").read_bytes()
        extra = {"image": "customer-redis", "command": ["redis-server", "--save", ""]}
        value = json.loads((self.fixture.installed / "compose.yaml").read_text())
        value["services"]["redis"] = extra
        (self.fixture.installed / "compose.yaml").write_text(json.dumps(value))
        result = self.apply()
        self.assertEqual(result["status"], "completed", result)
        target = json.loads((self.fixture.target / "compose.yaml").read_text())
        self.assertEqual(json.loads((self.fixture.installed / "compose.yaml").read_text()), target)
        for name, content in defaults.items():
            self.assertEqual((self.fixture.installed / name).read_text(), content)
        self.assertIn("redis", result["infrastructure_follow_up"]["services"])
        reconcile = acceptance_update_host.reconcile_fixture_configuration
        reconcile(self.fixture.installed, {"redis": extra})
        published = json.loads((self.fixture.installed / "compose.yaml").read_text())
        self.assertEqual(published["services"]["redis"], extra)
        self.assertEqual(
            {k: v for k, v in published["services"].items() if k != "redis"}, target["services"]
        )
        with self.assertRaisesRegex(RuntimeError, "existing release service"):
            reconcile(self.fixture.installed, {"app": extra})
        before = {
            name: (self.fixture.installed / name).read_bytes()
            for name in ("compose.yaml", *templates)
        }
        with self.assertRaisesRegex(RuntimeError, "existing environment instruction"):
            reconcile(self.fixture.installed, {"another": extra})
        self.assertEqual(
            before, {name: (self.fixture.installed / name).read_bytes() for name in before}
        )
        self.assertEqual((self.fixture.installed / ".env").read_bytes(), private_before)
        (self.fixture.installed / ".gitignore").write_text(".env\n.runtime/\n")
        self.fixture.commit(self.fixture.installed)
        production = self.fixture.base / "production-clone"
        self.fixture.git(
            self.fixture.base, "clone", "-q", str(self.fixture.installed), str(production)
        )
        self.assertFalse((production / ".env").exists())
        for name in templates:
            self.assertEqual((production / name).read_text().count("TEAM_RELEASE_MARKER=\n"), 1)
        self.assertFalse((production / ".env.prod").exists())
        settings = {
            "app_url": "https://fixture.example.invalid",
            "tls": {"mode": "external"},
            "smtp": {
                "host": "smtp.example.invalid",
                "port": 587,
                "scheme": "smtp",
                "from": "fixture@example.invalid",
            },
        }
        with contextlib.nullcontext(
            installer.InstallationService(deployment(production), Mock())
        ) as installer_service:
            installer_service.provision(settings, self.fixture.after, True)
            environment = production / ".env.prod"
            content = environment.read_text()
            self.assertEqual(content.count("TEAM_RELEASE_MARKER=\n"), 1)
            environment.write_text(
                content.replace("TEAM_RELEASE_MARKER=\n", "TEAM_RELEASE_MARKER=manually-prepared\n")
            )
            private = {
                name: (production / name).read_bytes()
                for name in (".env.prod", ".env.prod.database")
            }
            installer_service.provision(settings, self.fixture.after, True)
        self.assertEqual(private, {name: (production / name).read_bytes() for name in private})

    def test_execution_source_tampering_and_changed_release_identity_cannot_be_overridden(
        self,
    ) -> None:
        self.engine.fail = "build"
        self.apply()
        original = self.fixture.installed / "src/runa/Support/example.php"
        content = original.read_bytes()
        original.write_text("tampered")
        self.assertEqual(
            self.apply(resume=True, choice="continue")["checks"][-1]["id"],
            "protected-source-mismatch",
        )
        original.write_bytes(content)
        journal = self.journal()
        previous = (
            self.fixture.installed
            / ".runtime/production/update-staging"
            / journal["id"]
            / "previous/src/runa/Support/example.php"
        )
        previous.write_text("tampered backup")
        self.assertEqual(self.apply(resume=True)["checks"][-1]["id"], "protected-source-mismatch")

    def test_execution_preserves_unknown_staging_content_and_rejects_missing_database_history(
        self,
    ) -> None:
        self.engine.fail = "build"
        self.apply()
        self.fixture.runtime["migrations"] = []
        self.assertEqual(self.apply(resume=True)["checks"][-1]["id"], "database-identity")
        self.fixture.runtime["migrations"] = ["2026_01_01_000001_initial"]
        operation = (
            self.fixture.installed / ".runtime/production/update-staging" / self.journal()["id"]
        )
        sentinel = operation / "unrelated.txt"
        sentinel.write_text("preserve")
        result = self.apply(resume=True)
        self.assertEqual(result["checks"][-1]["id"], "source-staging-changed", result)
        self.assertEqual(sentinel.read_text(), "preserve")

    def test_execution_partial_staging_copy_restarts_only_verified_missing_files(self) -> None:
        original_copy = shutil.copytree
        interrupted = False

        def copy(source, destination, *args, **kwargs):
            nonlocal interrupted
            if str(destination).endswith("/incoming/src/runa") and not interrupted:
                interrupted = True
                Path(destination).mkdir(parents=True, exist_ok=True)
                shutil.copyfile(Path(source) / "platform.json", Path(destination) / "platform.json")
                raise KeyboardInterrupt
            return original_copy(source, destination, *args, **kwargs)

        with (
            patch.object(execution.shutil, "copytree", side_effect=copy),
            self.assertRaises(KeyboardInterrupt),
        ):
            self.apply()
        self.assertEqual(self.apply(resume=True)["status"], "completed")

    def test_execution_oneoff_transport_failure_removes_only_its_owned_writer(self) -> None:
        runtime = execution.DockerRuntime.__new__(execution.DockerRuntime)
        runtime.root = self.fixture.installed
        runtime.project = "fixture"
        runtime.args = ["docker", "compose"]
        commands = []

        def command(arguments, *args, **kwargs):
            commands.append(arguments)
            if arguments[:3] == ["docker", "compose", "run"]:
                raise RuntimeError("Lost client")
            if arguments[:3] == ["docker", "ps", "-aq"]:
                return "container-id"
            if arguments[:2] == ["docker", "inspect"]:
                name = commands[0][commands[0].index("--name") + 1]
                return json.dumps(
                    [
                        {
                            "Name": "/" + name,
                            "Config": {
                                "Labels": {
                                    "com.docker.compose.project": "fixture",
                                    "com.docker.compose.project.working_dir": str(
                                        self.fixture.installed
                                    ),
                                    "com.docker.compose.oneoff": "True",
                                }
                            },
                        }
                    ]
                )
            return ""

        with (
            patch.object(runtime, "transport", side_effect=command, create=True),
            self.assertRaises(RuntimeError),
        ):
            runtime.command(["run", "--rm", "app", "php", "prepare.php"])
        self.assertEqual(commands[-1], ["docker", "rm", "--force", "container-id"])

    def test_execution_resume_rejects_changed_tag_object_before_mutations(self) -> None:
        self.engine.fail = "build"
        self.apply()
        before = list(self.engine.events)
        download = self.fixture.download

        def changed(*args, **kwargs):
            identity = download(*args, **kwargs)
            identity["tag_object"] = "a" * 40
            return identity

        with patch.object(self.fixture, "download", side_effect=changed):
            blocked = self.apply(resume=True)
        self.assertEqual(blocked["checks"][-1]["id"], "release-changed", blocked)
        self.assertEqual(self.engine.events, before)

    def test_execution_completed_operation_is_not_resurrected_after_cleanup_failure(self) -> None:
        original_remove = shutil.rmtree

        def interrupted(path, *args, **kwargs):
            if "update-staging" in str(path):
                raise OSError("Cleanup failure")
            return original_remove(path, *args, **kwargs)

        with patch.object(execution.shutil, "rmtree", side_effect=interrupted):
            result = self.apply()
        self.assertEqual(result["status"], "completed", result)
        self.assertTrue(result["source_staging_requires_inspection"])
        self.assertFalse((self.fixture.installed / ".runtime/production/in-progress.json").exists())
        self.assertFalse(self.engine.fenced)
        self.assertEqual(self.apply()["status"], "completed")
        self.assertEqual(self.engine.seeds, 1)
