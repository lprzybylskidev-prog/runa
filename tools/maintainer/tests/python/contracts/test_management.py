#!/usr/bin/env python3
"""Host-plan trust boundary, locking and bounded transport regression checks."""

import contextlib
import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

from tools.application import test_resources
from tools.application.host_plan import OPERATIONS
from tools.application.management_service import ManagementService
from tools.core.context import WorkspaceContext

ROOT = Path(__file__).resolve().parents[5]


class ManagementContracts(unittest.TestCase):
    request = "a" * 32

    def plan(self, **changes):
        return dict(
            schema_version=1,
            request_id=self.request,
            operation="development.status",
            parameters={},
            **changes,
        )

    def test_published_schema_and_executor_allowlist_agree(self):
        schema = json.loads((ROOT / "src/runa/management/host-plan.v1.schema.json").read_text())
        self.assertEqual(set(schema["properties"]["operation"]["enum"]), set(OPERATIONS))
        self.assertFalse(schema["additionalProperties"])
        self.assertFalse(schema["properties"]["parameters"]["additionalProperties"])
        self.assertEqual(set(schema["required"]), set(self.plan()))

    def test_rejects_unversioned_unbound_and_executable_payloads(self):
        variants = [
            self.plan() | {"operation": "tooling." + retired}
            for retired in ("backup", "integrity", "project")
        ] + [
            None,
            [],
            {},
            self.plan() | {"schema_version": True},
            self.plan() | {"schema_version": 2},
            self.plan() | {"request_id": "b" * 32},
            self.plan() | {"operation": "development.up; touch /tmp/injected"},
            self.plan() | {"operation": []},
            self.plan() | {"parameters": []},
            self.plan() | {"parameters": {"argv": ["sh", "-c", "false"]}},
            self.plan() | {"shell": "false"},
        ]
        for value in variants:
            with self.subTest(value=value), self.assertRaises(ValueError):
                ManagementService.for_workspace(ROOT).validate_plan(json.dumps(value), self.request)
        with self.assertRaisesRegex(ValueError, "Duplicate"):
            ManagementService.for_workspace(ROOT).validate_plan(
                json.dumps(self.plan()).replace(
                    '"schema_version": 1', '"schema_version": 1, "schema_version": 1'
                ),
                self.request,
            )

    def test_tool_arguments_remain_literal_and_private_plan_fields_are_rejected(self):
        arguments = ["--filter=literal $(false); value", "line\nbreak"]
        plan = self.plan() | {"operation": "tooling.test", "parameters": {"arguments": arguments}}
        validated = ManagementService.for_workspace(ROOT).validate_plan(
            json.dumps(plan), self.request
        )
        command = ManagementService.for_workspace(ROOT).execution_command(validated)
        self.assertEqual(list(command[-5:]), ["php", "artisan", "runa:test", *arguments])
        self.assertNotIn("sh", command)
        for parameters in (
            {"arguments": "false"},
            {"arguments": [False]},
            {"arguments": ["a" * 4097]},
            {"arguments": [], "executable": "sh"},
        ):
            with self.subTest(parameters=parameters), self.assertRaises(ValueError):
                ManagementService.for_workspace(ROOT).validate_plan(
                    json.dumps(plan | {"parameters": parameters}), self.request
                )

    def test_reverse_transport_uses_explicit_names_and_preserves_literal_arguments(self):
        cases = [
            ("dev", ["profile"], ["runa:dev:profile"]),
            (
                "dev",
                ["profile", "custom", "DEV_CPU_LIMIT=2"],
                ["runa:dev:profile:set", "custom", "DEV_CPU_LIMIT=2"],
            ),
            (
                "test",
                ["frontend", "--filter=literal $(false)"],
                ["runa:test:frontend", "--filter=literal $(false)"],
            ),
            ("test", ["--filter=literal $(false)"], ["runa:test", "--filter=literal $(false)"]),
            ("deploy", ["rollback"], ["runa:deploy:rollback"]),
            (
                "hook",
                ["commit-msg", "literal;message"],
                ["runa:hook:commit-msg", "literal;message"],
            ),
        ]
        for tool, arguments, expected in cases:
            with self.subTest(tool=tool, arguments=arguments):
                self.assertEqual(
                    ManagementService.for_workspace(ROOT).public_command(tool, arguments), expected
                )
        with self.assertRaises(ValueError):
            ManagementService.for_workspace(ROOT).public_command("unregistered", ["false"])


class TestResourceContracts(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.processes = Mock()
        self.endpoints = test_resources.EndpointService(WorkspaceContext(self.root), self.processes)
        self.project = "isolated-test"
        self.run = "b" * 32
        self.labels = {
            "com.docker.compose.project": self.project,
            "com.docker.compose.project.working_dir": str(self.root),
            "com.docker.compose.service": "app-test",
            test_resources.LABEL: self.run,
        }

    def test_cleanup_targets_only_run_labels_and_preserves_volumes(self):
        calls = []

        def read(command):
            calls.append(command)
            return "owned-id\n" if command[1] == "ps" else json.dumps(self.labels)

        with (
            contextlib.nullcontext(),
            patch.object(self.endpoints, "read", side_effect=read),
            patch.object(self.processes, "run") as remove,
        ):
            self.endpoints.cleanup({"project": self.project, "run": self.run}, self.project)
        self.assertIn("label=runa.testing.run=" + self.run, calls[0])
        self.assertEqual(
            [call.args[0] for call in remove.call_args_list],
            [["docker", "stop", "-t", "10", "owned-id"], ["docker", "rm", "owned-id"]],
        )

    def test_uncertain_container_or_journal_ownership_prevents_all_removal(self):
        for field, value in [
            ("com.docker.compose.project", "foreign"),
            ("com.docker.compose.project.working_dir", "/foreign"),
            ("com.docker.compose.service", "postgres"),
            (test_resources.LABEL, "foreign"),
        ]:
            with (
                self.subTest(field=field),
                contextlib.nullcontext(),
                patch.object(
                    self.endpoints,
                    "read",
                    side_effect=["owned-id\n", json.dumps(self.labels | {field: value})],
                ),
                patch.object(self.processes, "run") as remove,
            ):
                with self.assertRaisesRegex(ValueError, "ownership"):
                    self.endpoints.cleanup({"project": self.project, "run": self.run}, self.project)
                remove.assert_not_called()
        with patch.object(self.endpoints, "read") as read:
            with self.assertRaisesRegex(ValueError, "ownership"):
                self.endpoints.cleanup({"project": "foreign", "run": self.run}, self.project)
            read.assert_not_called()

    def endpoint(self, existing=()):
        def read(command):
            if "config" in command:
                return json.dumps({"name": self.project})
            if "ps" in command:
                return "\n".join(existing)
            return json.dumps(self.labels | {"com.docker.compose.service": command[-1]})

        return read

    def test_manual_endpoint_is_not_recreated_or_owned_and_failure_still_cleans(self):
        with (
            contextlib.nullcontext(),
            patch.object(
                self.endpoints, "read", side_effect=self.endpoint(("app-test", "nginx-test"))
            ),
            patch(
                "tools.application.repository_boundary_service.RepositoryPolicy.assert_development_context"
            ),
            patch.object(self.endpoints, "cleanup") as cleanup,
            patch.object(self.processes, "run") as start,
        ):
            with self.assertRaisesRegex(RuntimeError, "test failure"):
                with self.endpoints.endpoint():
                    override = json.loads(
                        (self.root / ".runtime/tests/endpoint.compose.json").read_text()
                    )
                    self.assertEqual(override, {"services": {}})
                    self.assertIn("--no-recreate", start.call_args.args[0])
                    raise RuntimeError("test failure")
            cleanup.assert_called_once()
            self.assertFalse((self.root / ".runtime/tests/endpoint.json").exists())

    def test_partial_startup_failure_has_journal_before_creation_and_cleans(self):
        def fail(command, **kwargs):
            self.assertTrue((self.root / ".runtime/tests/endpoint.json").exists())
            override = json.loads((self.root / ".runtime/tests/endpoint.compose.json").read_text())
            self.assertEqual(set(override["services"]), set(test_resources.SERVICES))
            raise subprocess.CalledProcessError(1, command)

        with (
            contextlib.nullcontext(),
            patch.object(self.endpoints, "read", side_effect=self.endpoint()),
            patch(
                "tools.application.repository_boundary_service.RepositoryPolicy.assert_development_context"
            ),
            patch.object(self.endpoints, "cleanup") as cleanup,
            patch.object(self.processes, "run", side_effect=fail),
        ):
            with self.assertRaises(subprocess.CalledProcessError):
                with self.endpoints.endpoint():
                    self.fail("Cannot run against a failed endpoint")
            cleanup.assert_called_once()

    def test_interrupted_startup_retains_journal_and_next_run_recovers_it(self):
        with (
            contextlib.nullcontext(),
            patch.object(self.endpoints, "read", side_effect=self.endpoint()),
            patch(
                "tools.application.repository_boundary_service.RepositoryPolicy.assert_development_context"
            ),
            patch.object(self.endpoints, "cleanup") as cleanup,
            patch.object(self.processes, "run", side_effect=KeyboardInterrupt),
        ):
            with self.assertRaises(KeyboardInterrupt):
                with self.endpoints.endpoint():
                    pass
            old = json.loads((self.root / ".runtime/tests/endpoint.json").read_text())
            self.assertEqual(cleanup.call_args.args[0], old)
        with (
            contextlib.nullcontext(),
            patch.object(self.endpoints, "read", side_effect=self.endpoint()),
            patch(
                "tools.application.repository_boundary_service.RepositoryPolicy.assert_development_context"
            ),
            patch.object(self.endpoints, "cleanup") as cleanup,
            patch.object(self.processes, "run"),
        ):
            with self.endpoints.endpoint():
                self.assertEqual(cleanup.call_args_list[0].args[0], old)
            self.assertEqual(cleanup.call_count, 2)
            self.assertFalse((self.root / ".runtime/tests/endpoint.json").exists())

    def test_busy_suite_prevents_endpoint_startup_and_orphan_recovery(self):
        import fcntl

        directory = self.root / ".runtime/tests"
        directory.mkdir(parents=True)
        with (directory / "suite.lock").open("a") as suite:
            fcntl.flock(suite, fcntl.LOCK_EX)
            with (
                contextlib.nullcontext(),
                patch(
                    "tools.application.repository_boundary_service.RepositoryPolicy.assert_development_context"
                ),
                patch.object(self.endpoints, "read") as read,
                patch.object(self.endpoints, "cleanup") as cleanup,
            ):
                with self.assertRaisesRegex(ValueError, "Another test suite"):
                    with self.endpoints.endpoint():
                        self.fail("Cannot acquire a busy suite")
                cleanup.assert_not_called()
                read.assert_not_called()

    def test_owner_runner_preserves_test_failure_and_reports_cleanup_failure(self):
        import owner_tests

        for child_status, cleanup_status, expected in [(5, 1, 5), (0, 1, 1), (0, 0, 0)]:
            with (
                self.subTest(child=child_status, cleanup=cleanup_status),
                patch.object(owner_tests, "ROOT", self.root),
                patch.object(
                    owner_tests, "worker_cleanup", side_effect=[0, cleanup_status]
                ) as cleanup,
                patch.object(owner_tests.subprocess, "Popen") as popen,
            ):
                popen.return_value.__enter__.return_value.wait.return_value = child_status
                self.assertEqual(owner_tests.execute("backend", ["--filter=Example"]), expected)
                self.assertEqual(cleanup.call_count, 2)

    def test_owner_runner_refuses_to_start_after_failed_orphan_cleanup(self):
        import owner_tests

        with (
            patch.object(owner_tests, "ROOT", self.root),
            patch.object(owner_tests, "worker_cleanup", return_value=1),
            patch.object(owner_tests.subprocess, "Popen") as popen,
        ):
            self.assertEqual(owner_tests.execute("backend", []), 1)
            popen.assert_not_called()
