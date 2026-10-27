"""Consumed acquisition metadata and injected customer-source process boundaries."""

import contextlib
import io
import unittest
from collections import deque
from pathlib import Path
from unittest.mock import patch

from distribution import bootstrap, initialize
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import InputMode, OutputMode, ProcessResult, ProcessTimeout


class AcquisitionBoundaries(unittest.TestCase):
    def test_repository_identity_rejects_wrong_shapes_and_boolean_ids(self) -> None:
        client = bootstrap.GitHub("owner/repository")
        valid: dict[str, object] = {
            "full_name": "owner/repository",
            "clone_url": client.url,
            "id": 7,
            "future_evidence": {"kept": True},
        }
        invalid: list[object] = [
            [],
            None,
            "synthetic_private_body",
            valid | {"id": True},
            valid | {"id": 0},
            valid | {"full_name": []},
            valid | {"clone_url": 17},
        ]
        for value in invalid:
            with self.subTest(value=value), patch.object(client, "get", return_value=value):
                with self.assertRaises(bootstrap.BootstrapError) as caught:
                    client.verify_repository()
                self.assertNotIn("synthetic", str(caught.exception))
        with patch.object(client, "get", return_value=valid):
            self.assertEqual(client.verify_repository(), 7)

    def test_release_listing_validates_consumed_fields_and_filters_stable_tags(self) -> None:
        client = bootstrap.GitHub("owner/repository")
        invalid: list[object] = [
            {},
            [None],
            [{"tag_name": []}],
            [{"tag_name": "v1.0.0", "draft": "false"}],
            [{"tag_name": "v1.0.0", "prerelease": 0}],
        ]
        for value in invalid:
            with self.subTest(value=value), patch.object(client, "get", return_value=value):
                with self.assertRaises(bootstrap.BootstrapError):
                    client.releases()
        releases = [
            {"tag_name": "v1.0.0", "unknown": "evidence"},
            {"tag_name": "v2.0.0", "draft": True},
            {"tag_name": "v3.0.0", "prerelease": True},
            {"tag_name": "main"},
        ]
        with patch.object(client, "get", return_value=releases):
            self.assertEqual(client.releases(), ["v1.0.0"])

    def test_release_resolution_checks_nested_types_before_selecting_identity(self) -> None:
        client = bootstrap.GitHub("owner/repository")
        release: dict[str, object] = {
            "id": 9,
            "tag_name": "v1.0.0",
            "published_at": "2026-10-27T00:00:00Z",
            "draft": False,
            "prerelease": False,
        }
        ref: dict[str, object] = {
            "ref": "refs/tags/v1.0.0",
            "object": {"type": "tag", "sha": "a" * 40},
        }
        annotation: dict[str, object] = {
            "sha": "a" * 40,
            "tag": "v1.0.0",
            "object": {"type": "commit", "sha": "b" * 40},
        }
        invalid = [
            [release | {"id": True}],
            [release | {"published_at": 7}],
            [release | {"draft": "false"}],
            [release, ref | {"object": None}],
            [release, ref | {"object": {"type": "tag", "sha": []}}],
            [release, ref, annotation | {"object": []}],
            [release, ref, annotation | {"object": {"type": "commit", "sha": False}}],
        ]
        for values in invalid:
            with self.subTest(values=values), patch.object(client, "get", side_effect=values):
                with self.assertRaises(bootstrap.BootstrapError):
                    client.resolve("v1.0.0")
        with patch.object(client, "get", side_effect=[release, ref, annotation]):
            self.assertEqual(
                client.resolve("v1.0.0"),
                {
                    "release_id": 9,
                    "tag_object": "a" * 40,
                    "commit": "b" * 40,
                },
            )

    def test_customer_git_uses_selected_runner_environment_and_safe_failures(self) -> None:
        runner = RecordingRunner(
            deque(
                [
                    ProcessResult(0, b"source"),
                    ProcessResult(17, b"", b"synthetic_private_error"),
                    ProcessTimeout(),
                    OSError("synthetic_private_error"),
                ]
            )
        )
        environment = {"PATH": "/usr/bin", "GIT_TRACE": "synthetic", "CUSTOMER_AUTH": "literal$"}
        source = initialize.CustomerSource(runner, environment)
        environment["CUSTOMER_AUTH"] = "changed"
        self.assertEqual(source.git(Path("/customer"), "status", "--porcelain"), b"source")
        call = runner.calls[0]
        self.assertEqual(call.cwd, Path("/customer"))
        self.assertEqual(call.argv[-2:], ("status", "--porcelain"))
        self.assertNotIn("GIT_TRACE", call.env)
        self.assertEqual(call.env["GIT_TERMINAL_PROMPT"], "0")
        self.assertEqual(call.env["CUSTOMER_AUTH"], "literal$")
        self.assertIs(call.stdin, InputMode.CLOSED)
        self.assertIs(call.output, OutputMode.CAPTURE)
        self.assertEqual(call.timeout, 600)
        for _ in range(3):
            with self.assertRaises(bootstrap.BootstrapError) as caught:
                source.git(Path("/customer"), "status")
            self.assertNotIn("synthetic", str(caught.exception))
        other = initialize.CustomerSource(RecordingRunner(), {"PATH": "/other/bin"})
        self.assertEqual(other.environment, {"PATH": "/other/bin"})
        self.assertEqual(source.environment["CUSTOMER_AUTH"], "literal$")

    def test_customer_install_handoff_uses_same_runner_closed_input_and_native_exit(self) -> None:
        runner = RecordingRunner(deque([ProcessResult(23)]))
        destination = Path("/customer/project")
        command = ["python3", "-m", "tools.application.installation_gateway", "prod", "a" * 40]
        with (
            patch.object(bootstrap, "host_checks"),
            patch.object(bootstrap, "destination_path", return_value=destination),
            patch.object(initialize.CustomerSource, "acquire", return_value="a" * 40),
            patch.object(bootstrap, "install_command", return_value=command),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            status = initialize.main(
                [
                    "--repository=/customer/repo",
                    "--destination=/customer/project",
                    "--install=now",
                ],
                runner=runner,
            )
        self.assertEqual(status, 23)
        self.assertEqual(len(runner.calls), 1)
        call = runner.calls[0]
        self.assertEqual(
            call.argv,
            (
                "python3",
                "-m",
                "tools.application.installation_gateway",
                "prod",
                "a" * 40,
                "--non-interactive",
            ),
        )
        self.assertEqual(call.argv[-1], "--non-interactive")
        self.assertEqual(call.cwd, destination)
        self.assertIs(call.stdin, InputMode.CLOSED)
        self.assertIs(call.output, OutputMode.INHERIT)
