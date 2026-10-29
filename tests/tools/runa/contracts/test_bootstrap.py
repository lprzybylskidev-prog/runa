#!/usr/bin/env python3
"""Offline GitHub failure contracts and real Git/atomic filesystem acceptance."""

import contextlib
import io
import json
import os
import shutil
import subprocess
import sys
import tempfile
import unittest
import urllib.error
from collections.abc import Generator
from email.message import Message
from pathlib import Path
from typing import TypedDict, cast
from unittest.mock import Mock, patch

from distribution import bootstrap
from tools.application.deployment_service import DeploymentService
from tools.application.deployment_source import DELIVERY

ROOT = Path(__file__).resolve().parents[4]


class ExpectedSource(TypedDict, total=False):
    expected_commit: str
    expected_digest: str


class BootstrapContracts(unittest.TestCase):
    @staticmethod
    def prepared_source(
        client: bootstrap.ReleaseClient,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
        expected_digest: str | None = None,
        reference_module: bool = False,
    ) -> bootstrap.AcquisitionReceipt:
        gateway = destination / "tools/application/gateway.json"
        gateway.parent.mkdir(parents=True)
        gateway.write_text('{"schema_version": 1, "installation_protocol": 1}')
        return {
            "schema_version": 1,
            "repository": client.repository,
            "repository_id": 7,
            "tag": tag,
            "release_id": 1,
            "tag_object": "c" * 40,
            "commit": "a" * 40,
            "source_sha256": "b" * 64,
            "mode": mode,
            "state": "downloaded",
            "installed_profile": "source",
            "reference_module": reference_module,
            "projection": {"version": 2, "excluded": list(bootstrap.OWNER_PATHS)},
            "trust": "synthetic fixture",
        }

    def test_separately_delivered_single_file_runs_without_an_owner_checkout(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            launcher = Path(folder) / "customer-installer.py"
            shutil.copyfile(ROOT / "distribution/bootstrap.py", launcher)
            result = subprocess.run(
                [sys.executable, "-I", str(launcher), "--help"],
                cwd=folder,
                capture_output=True,
                text=True,
            )
            self.assertEqual(result.returncode, 0, result.stderr)
            for option in ("--install", "--expected-commit", "--expected-digest"):
                self.assertIn(option, result.stdout)
            self.assertEqual(
                sorted(path.name for path in Path(folder).iterdir()), ["customer-installer.py"]
            )

    def test_repository_and_tag_reject_urls_secrets_options_and_paths(self) -> None:
        for value in (
            "https://github.com/a/b",
            "a/b.git",
            "a/b/../c",
            "a/b?token=hidden",
            "-a/b",
            "a/b\n",
        ):
            with self.subTest(value=value), self.assertRaises(bootstrap.BootstrapError):
                bootstrap.GitHub(value)
        client = bootstrap.GitHub("owner/repository")
        for value in (
            "main",
            "v1.0",
            "v1.0.0/../main",
            "--upload-pack=false",
            "v01.0.0",
            "v1.0.0\n",
        ):
            with self.subTest(value=value), self.assertRaises(bootstrap.BootstrapError):
                client.resolve(value)

    def test_http_credentials_are_optional_and_never_sent_to_redirects(self) -> None:
        for token in ("", "synthetic_not_a_real_token"):
            client = bootstrap.GitHub("owner/repository", token)
            stream = Mock()
            stream.__enter__ = Mock(return_value=stream)
            stream.__exit__ = Mock(return_value=False)
            stream.read.return_value = b"{}"
            with patch.object(client.opener, "open", return_value=stream) as opening:
                client.get()
                request = opening.call_args.args[0]
                self.assertEqual(request.full_url, "https://api.github.com/repos/owner/repository")
                self.assertEqual(
                    request.get_header("Authorization"), "Bearer " + token if token else None
                )
            for target in (
                "https://evil.invalid",
                "https://api.github.com/other",
                "http://api.github.com/",
            ):
                with self.assertRaises(bootstrap.BootstrapError):
                    bootstrap.NoRedirect().redirect_request(
                        request, io.BytesIO(), 302, token, Message(), target
                    )

    def test_denied_revoked_rate_limited_and_interrupted_requests_are_redacted(self) -> None:
        client = bootstrap.GitHub("owner/repository", "synthetic_not_a_real_token")
        for status in (301, 401, 403, 404, 500):
            error = urllib.error.HTTPError(
                "https://invalid/?secret=synthetic",
                status,
                "synthetic_not_a_real_token",
                Message(),
                None,
            )
            with (
                patch.object(client.opener, "open", side_effect=error),
                self.assertRaises(bootstrap.BootstrapError) as caught,
            ):
                client.get()
            self.assertNotIn("synthetic", str(caught.exception))
        with (
            patch.object(client.opener, "open", side_effect=OSError("synthetic_not_a_real_token")),
            self.assertRaises(bootstrap.BootstrapError) as caught,
        ):
            client.get()
        self.assertNotIn("synthetic", str(caught.exception))

    def test_private_file_permissions_type_symlink_and_token_validation(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            file = Path(folder) / "credential"
            file.write_text("synthetic_not_a_real_token\n")
            file.chmod(0o600)
            self.assertEqual(bootstrap.token_from_file(file), "synthetic_not_a_real_token")
            file.chmod(0o644)
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.token_from_file(file)
            file.chmod(0o600)
            link = Path(folder) / "linked"
            link.symlink_to(file)
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.token_from_file(link)
            file.write_text("credential\ninjected-header")
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.token_from_file(file)
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.token_from_file(Path(folder))

    def test_git_secrets_are_transient_and_host_git_trace_tls_proxy_overrides_are_absent(
        self,
    ) -> None:
        with patch.dict(
            os.environ,
            {
                "GIT_TRACE": "1",
                "GIT_CONFIG_COUNT": "99",
                "GIT_SSL_NO_VERIFY": "1",
                "HTTPS_PROXY": "https://invalid",
            },
        ):
            env = bootstrap.git_environment(Path("/tmp/private"), "synthetic_not_a_real_token")
        for key in ("GIT_TRACE", "GIT_SSL_NO_VERIFY", "HTTPS_PROXY"):
            self.assertNotIn(key, env)
        self.assertEqual(env["GIT_CONFIG_KEY_0"], "http.https://github.com/.extraHeader")
        with patch.object(bootstrap, "process", return_value=b"") as process:
            bootstrap.git(
                Path("/source"),
                Path("/home"),
                "fetch",
                "origin",
                token="synthetic_not_a_real_token",
            )
            argv = process.call_args.args[0]
            self.assertNotIn("synthetic_not_a_real_token", " ".join(argv))
            self.assertIn("http.followRedirects=false", argv)
            self.assertIn("http.sslVerify=true", argv)

    def test_automation_missing_inputs_and_credential_prompts_fail_before_network_or_docker(
        self,
    ) -> None:
        for arguments in (
            [],
            ["--non-interactive"],
            ["--repository", "owner/repo", "--ask-token", "--non-interactive"],
        ):
            with (
                patch.object(bootstrap, "host_checks") as checks,
                patch("builtins.input", side_effect=AssertionError("prompt")),
                self.assertRaises(bootstrap.BootstrapError),
            ):
                bootstrap.main(arguments)
            checks.assert_not_called()

    def test_existing_directory_file_symlink_and_disk_shortage_are_preserved(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            path = Path(folder) / "destination"
            path.mkdir()
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.destination_path(str(path))
            path.rmdir()
            path.write_text("customer-owned")
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.destination_path(str(path))
            self.assertEqual(path.read_text(), "customer-owned")
            path.unlink()
            path.symlink_to("/missing")
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.destination_path(str(path))
            path.unlink()
            with (
                patch.object(bootstrap.shutil, "disk_usage", return_value=Mock(free=1)),
                self.assertRaises(bootstrap.BootstrapError),
            ):
                bootstrap.destination_path(str(path))

    def test_atomic_publish_cannot_replace_concurrently_created_empty_directory(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            source, destination = Path(folder) / "staging", Path(folder) / "destination"
            source.mkdir()
            destination.mkdir()
            with self.assertRaises(bootstrap.BootstrapError):
                bootstrap.publish(source, destination)
            self.assertTrue(source.is_dir())
            self.assertTrue(destination.is_dir())
            destination.rmdir()
            bootstrap.publish(source, destination)
            self.assertFalse(source.exists())
            self.assertTrue(destination.is_dir())

    def fixture(
        self,
        folder: str,
        link: str | None = None,
    ) -> tuple[bootstrap.GitHub, bootstrap.ReleaseIdentity]:
        repo = Path(folder) / "repository"
        repo.mkdir()

        def git(*args: str) -> str:
            return (
                subprocess.check_output(
                    [
                        "git",
                        "-C",
                        str(repo),
                        "-c",
                        "core.hooksPath=/dev/null",
                        "-c",
                        "user.name=Fixture",
                        "-c",
                        "user.email=fixture@example.invalid",
                        *args,
                    ],
                    stderr=subprocess.DEVNULL,
                )
                .decode()
                .strip()
            )

        git("init", "-q")
        (repo / "VERSION").write_text("1.0.0\n")
        (repo / "bin").mkdir()
        (repo / "bin/tool").write_text("fixture\n")
        (repo / "bin/tool").chmod(0o755)
        (repo / "bin/alias").symlink_to(link or "tool")
        for name in bootstrap.OWNER_PATHS:
            file = repo / (
                name + "/owner-tool" if name in ("distribution", "tools/maintainer") else name
            )
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text("owner-only source\n")
        (repo / ".githooks").mkdir()
        (repo / ".githooks/pre-commit").write_text("customer hook fixture\n")
        (repo / "tools/application").mkdir(parents=True)
        (repo / "tools/application/fixture.py").write_text("customer tool fixture\n")
        for name in (
            "src/app/modules/reference/module.json",
            "src/app/modules/module_probe/module.json",
            "docs/app/modules/reference/README.md",
        ):
            file = repo / name
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text("example fixture\n")
        git("add", ".")
        git("commit", "-qm", "fixture")
        git("tag", "-a", "v1.0.0", "-m", "fixture")
        identity: bootstrap.ReleaseIdentity = {
            "release_id": 1,
            "tag_object": git("rev-parse", "v1.0.0"),
            "commit": git("rev-parse", "HEAD"),
        }
        client = bootstrap.GitHub("owner/repository", "synthetic_not_a_real_token")
        client.resolve = Mock(return_value=identity)
        client.verify_repository = Mock(return_value=7)
        client.url = str(repo)
        return client, identity

    @contextlib.contextmanager
    def local_git(self) -> Generator[None, None, None]:
        environment = bootstrap.git_environment

        def local(home: Path, token: str = "") -> dict[str, str]:
            # Test-only local object transport; production exposes no URL/protocol overrides.
            env = environment(home)
            env["GIT_ALLOW_PROTOCOL"] = "file"
            return env

        with patch.object(bootstrap, "git_environment", side_effect=local):
            yield

    def test_real_git_download_verifies_objects_files_modes_links_and_clean_source(self) -> None:
        with tempfile.TemporaryDirectory() as folder, self.local_git():
            client, identity = self.fixture(folder)
            destination = Path(folder) / "download"
            receipt = bootstrap.acquire(client, "v1.0.0", destination, "dev", identity["commit"])
            self.assertEqual((destination / "bin/alias").read_text(), "fixture\n")
            self.assertEqual(receipt["state"], "downloaded")
            self.assertTrue((destination / ".githooks/pre-commit").is_file())
            self.assertTrue((destination / "tools/application/fixture.py").is_file())
            self.assertFalse((destination / ".git/runa-ownership.json").exists())
            self.assertEqual(destination.stat().st_mode & 0o777, 0o700)
            self.assertEqual(len(receipt["source_sha256"]), 64)
            self.assertNotIn("synthetic", (destination / ".git/config").read_text())
            self.assertNotIn("synthetic", json.dumps(receipt))
            self.assertEqual(
                subprocess.check_output(["git", "-C", str(destination), "status", "--porcelain"]),
                b"",
            )
            self.assertFalse(list(Path(folder).glob(".runa-bootstrap-*")))
            self.assertEqual(
                receipt["projection"], {"version": 2, "excluded": list(bootstrap.OWNER_PATHS)}
            )
            for name in bootstrap.OWNER_PATHS:
                self.assertFalse((destination / name).exists(), name)
            subprocess.run(
                ["git", "-C", str(destination), "checkout", "--detach", identity["commit"]],
                check=True,
                capture_output=True,
            )
            subprocess.run(
                ["git", "-C", str(destination), "sparse-checkout", "reapply"],
                check=True,
                capture_output=True,
            )
            self.assertTrue(
                all(not (destination / name).exists() for name in bootstrap.OWNER_PATHS)
            )
            self.assertEqual(
                subprocess.check_output(
                    ["git", "-C", str(destination), "remote", "get-url", "--push", "origin"]
                ).strip(),
                b"disabled://runa-read-only-upstream",
            )
            second = Path(folder) / "second"
            self.assertEqual(
                bootstrap.acquire(
                    client, "v1.0.0", second, "dev", expected_digest=receipt["source_sha256"]
                )["source_sha256"],
                receipt["source_sha256"],
            )
            self.assertTrue(all(not (second / name).exists() for name in bootstrap.OWNER_PATHS))

    def test_reference_choices_survive_reopen_and_production_projection(self) -> None:
        for include in (False, True):
            with (
                self.subTest(include=include),
                tempfile.TemporaryDirectory() as folder,
                self.local_git(),
            ):
                client, identity = self.fixture(folder)
                destination = Path(folder) / "download"
                receipt = bootstrap.acquire(
                    client, "v1.0.0", destination, "dev", reference_module=include
                )
                example = destination / "src/app/modules/reference/module.json"
                self.assertEqual(example.exists(), include)
                self.assertEqual(receipt["reference_module"], include)
                self.assertFalse((destination / "src/app/modules/module_probe").exists())
                if include:
                    example.write_text("customer edit\n")
                subprocess.run(
                    ["git", "-C", str(destination), "sparse-checkout", "reapply"],
                    check=True,
                    capture_output=True,
                )
                self.assertEqual(example.exists(), include)
                if include:
                    self.assertEqual(example.read_text(), "customer edit\n")
                production = Path(folder) / "production"
                subprocess.run(
                    ["git", "clone", "--quiet", "--no-checkout", client.url, str(production)],
                    check=True,
                    capture_output=True,
                )
                subprocess.run(
                    [
                        "git",
                        "-C",
                        str(production),
                        "sparse-checkout",
                        "set",
                        "--no-cone",
                        "--stdin",
                    ],
                    input="\n".join(DELIVERY) + "\n",
                    text=True,
                    check=True,
                    capture_output=True,
                )
                subprocess.run(
                    ["git", "-C", str(production), "checkout", "--detach", identity["commit"]],
                    check=True,
                    capture_output=True,
                )
                DeploymentService.for_workspace(production).source.assert_delivery(production)
                self.assertFalse((production / "src/app/modules/reference").exists())
                self.assertFalse((production / "src/app/modules/module_probe").exists())

    def test_delivered_application_contains_only_essential_extension_entrypoints(self) -> None:
        expected = {
            "Providers/AppServiceProvider.php",
            "providers.php",
            "resources/index.html",
            "resources/js/App.vue",
            "resources/js/main.ts",
            "package.json",
            "composer.json",
            "application.json",
            "routes/web.php",
            "config/branding.php",
            "database/seeders/DatabaseSeeder.php",
            "database/migrations/.gitignore",
            "config/.gitignore",
            "database/factories/.gitignore",
            "lang/.gitignore",
            "Actions/.gitignore",
        }
        actual = {
            path.relative_to(ROOT / "src/app").as_posix()
            for path in (ROOT / "src/app").rglob("*")
            if path.is_file() and "modules" not in path.relative_to(ROOT / "src/app").parts
        }
        self.assertEqual(actual, expected)
        self.assertIn("DefaultApplication", (ROOT / "src/app/resources/js/App.vue").read_text())
        self.assertNotIn("window.location", (ROOT / "src/app/resources/js/App.vue").read_text())
        self.assertIn("createApplication", (ROOT / "src/app/resources/js/main.ts").read_text())

    def test_customer_agent_projection_survives_reapply_and_teammate_clone(self) -> None:
        for include in (False, True):
            with (
                self.subTest(include=include),
                tempfile.TemporaryDirectory() as folder,
                self.local_git(),
            ):
                client, _ = self.fixture(folder)
                repo = Path(client.url)
                # Replace the synthetic owner marker with the actual authored product.
                marker = repo / "docs/runa/maintainer"
                marker.unlink()
                shutil.copytree(
                    ROOT / "docs/runa/maintainer/customer-agent", marker / "customer-agent"
                )
                (repo / "AGENTS.md").write_text("OWNER ONLY SENTINEL")
                subprocess.run(["git", "-C", str(repo), "add", "."], check=True)
                subprocess.run(
                    [
                        "git",
                        "-C",
                        str(repo),
                        "-c",
                        "user.name=Fixture",
                        "-c",
                        "user.email=fixture@example.invalid",
                        "commit",
                        "-qm",
                        "customer bundle",
                    ],
                    check=True,
                )
                subprocess.run(
                    [
                        "git",
                        "-C",
                        str(repo),
                        "-c",
                        "user.name=Fixture",
                        "-c",
                        "user.email=fixture@example.invalid",
                        "tag",
                        "-fa",
                        "v1.0.0",
                        "-m",
                        "customer bundle",
                    ],
                    check=True,
                    capture_output=True,
                )
                identity: bootstrap.ReleaseIdentity = {
                    **client.resolve("v1.0.0"),
                    "commit": subprocess.check_output(["git", "-C", str(repo), "rev-parse", "HEAD"])
                    .decode()
                    .strip(),
                    "tag_object": subprocess.check_output(
                        ["git", "-C", str(repo), "rev-parse", "v1.0.0"]
                    )
                    .decode()
                    .strip(),
                }
                cast(Mock, client.resolve).return_value = identity
                destination = Path(folder) / "customer"
                receipt = bootstrap.acquire(
                    client, "v1.0.0", destination, "dev", reference_module=include
                )
                expected = (ROOT / "docs/runa/maintainer/customer-agent/AGENTS.md").read_bytes()
                self.assertEqual((destination / "AGENTS.md").read_bytes(), expected)
                self.assertIn(b"## Audit awareness", expected)
                self.assertIn(b"explicit manual Audit events", expected)
                for guidance in (ROOT / "docs/runa/maintainer/customer-agent/skills").glob(
                    "*/references/application.md"
                ):
                    relative = guidance.relative_to(
                        ROOT / "docs/runa/maintainer/customer-agent/skills"
                    )
                    self.assertEqual(
                        (destination / ".agents/skills" / relative).read_bytes(),
                        guidance.read_bytes(),
                    )
                self.assertEqual(len(list((destination / ".agents/skills").glob("*/SKILL.md"))), 9)
                assert "customer_agent" in receipt
                self.assertIn("AGENTS.md", receipt["customer_agent"]["files"])
                self.assertFalse((destination / "docs/runa/maintainer").exists())
                custom_frontend = {
                    "src/app/resources/js/App.vue": "<template><main>Customer screen</main></template>\n",
                    "src/app/resources/js/errorLayout.ts": 'export { default } from "./CustomerError.vue";\n',
                    "src/app/resources/js/CustomerError.vue": "<template><main><slot /></main></template>\n",
                    "src/app/resources/css/customer.css": ":root { --customer-spacing: 2rem; }\n",
                }
                for name, content in custom_frontend.items():
                    path = destination / name
                    path.parent.mkdir(parents=True, exist_ok=True)
                    path.write_text(content)
                subprocess.run(
                    ["git", "-C", str(destination), "sparse-checkout", "reapply"],
                    check=True,
                    capture_output=True,
                )
                self.assertEqual((destination / "AGENTS.md").read_bytes(), expected)
                for name, content in custom_frontend.items():
                    self.assertEqual((destination / name).read_text(), content)
                for area in ("frontend", "vue", "tailwind"):
                    self.assertIn(
                        "docs/runa/handbook/development/frontend/customization.md",
                        (
                            destination / ".agents/skills" / area / "references/application.md"
                        ).read_text(),
                    )
                # Customer repository commits its projected working tree, never owner files.
                exported = Path(folder) / "exported"
                shutil.copytree(destination, exported, ignore=shutil.ignore_patterns(".git"))
                subprocess.run(["git", "init", "-q", str(exported)], check=True)
                subprocess.run(["git", "-C", str(exported), "add", "."], check=True)
                subprocess.run(
                    [
                        "git",
                        "-C",
                        str(exported),
                        "-c",
                        "user.name=Fixture",
                        "-c",
                        "user.email=fixture@example.invalid",
                        "commit",
                        "-qm",
                        "application",
                    ],
                    check=True,
                )
                clone = Path(folder) / "teammate"
                subprocess.run(["git", "clone", "-q", str(exported), str(clone)], check=True)
                self.assertEqual((clone / "AGENTS.md").read_bytes(), expected)
                for name, content in custom_frontend.items():
                    self.assertEqual((clone / name).read_text(), content)
                for area in ("frontend", "vue", "tailwind"):
                    self.assertIn(
                        "docs/runa/handbook/development/frontend/customization.md",
                        (clone / ".agents/skills" / area / "references/application.md").read_text(),
                    )
                for guidance in (ROOT / "docs/runa/maintainer/customer-agent/skills").glob(
                    "*/references/application.md"
                ):
                    relative = guidance.relative_to(
                        ROOT / "docs/runa/maintainer/customer-agent/skills"
                    )
                    self.assertEqual(
                        (clone / ".agents/skills" / relative).read_bytes(), guidance.read_bytes()
                    )
                self.assertFalse((clone / ".git/runa-bootstrap.json").exists())
                self.assertFalse((clone / "docs/runa/maintainer").exists())

    def test_mutated_tag_and_independent_commit_or_digest_mismatch_never_publish(self) -> None:
        for failure in ("commit", "digest", "changed-ref", "fetched-ref"):
            with (
                self.subTest(failure=failure),
                tempfile.TemporaryDirectory() as folder,
                self.local_git(),
            ):
                client, identity = self.fixture(folder)
                kwargs: ExpectedSource = {}
                if failure == "commit":
                    kwargs["expected_commit"] = "a" * 40
                elif failure == "digest":
                    kwargs["expected_digest"] = "b" * 64
                elif failure == "changed-ref":
                    cast(Mock, client.resolve).side_effect = [
                        identity,
                        identity | {"commit": "c" * 40},
                    ]
                else:
                    cast(Mock, client.resolve).return_value = identity | {"tag_object": "d" * 40}
                destination = Path(folder) / "download"
                with self.assertRaises(bootstrap.BootstrapError):
                    bootstrap.acquire(client, "v1.0.0", destination, "dev", **kwargs)
                self.assertFalse(destination.exists())
                self.assertFalse(list(Path(folder).glob(".runa-bootstrap-*")))

    def test_unsafe_absolute_escaping_dangling_and_cyclic_symlinks_never_publish(self) -> None:
        for link in ("/etc/passwd", "../../outside", "missing", "alias"):
            with self.subTest(link=link), tempfile.TemporaryDirectory() as folder, self.local_git():
                client, _ = self.fixture(folder, link)
                with self.assertRaises(bootstrap.BootstrapError):
                    bootstrap.acquire(client, "v1.0.0", Path(folder) / "download", "dev")
                self.assertFalse((Path(folder) / "download").exists())

    def test_tree_rejects_path_traversal_submodules_and_size_limit_before_checkout(self) -> None:
        records = [
            b"100644 blob " + b"a" * 40 + b" 1\t../escape\0",
            b"100644 blob " + b"a" * 40 + b" 1\t.git/config\0",
            b"160000 commit " + b"a" * 40 + b" -\tsubmodule\0",
            b"100644 blob " + b"a" * 40 + b" 999999999\tlarge\0",
        ]
        for record in records:
            with (
                patch.object(bootstrap, "git", return_value=record),
                self.assertRaises(bootstrap.BootstrapError),
            ):
                bootstrap.inspect_tree(Path("/source"), Path("/home"), "a" * 40)

    def test_interruption_removes_staging_and_does_not_publish(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            client = bootstrap.GitHub("owner/repository")
            client.resolve = Mock(return_value={"commit": "a" * 40})
            client.verify_repository = Mock(return_value=7)
            with (
                patch.object(bootstrap, "git", side_effect=KeyboardInterrupt),
                self.assertRaises(KeyboardInterrupt),
            ):
                bootstrap.acquire(client, "v1.0.0", Path(folder) / "download", "dev")
            self.assertFalse(list(Path(folder).glob(".runa-bootstrap-*")))
            self.assertFalse((Path(folder) / "download").exists())

    def test_source_tampering_is_detected_against_git_objects(self) -> None:
        with tempfile.TemporaryDirectory() as folder, self.local_git():
            client, identity = self.fixture(folder)
            source = Path(client.url)
            entries = bootstrap.inspect_tree(source, Path(folder), identity["commit"])
            (source / "bin/tool").write_text("changed\n")
            with self.assertRaisesRegex(bootstrap.BootstrapError, "hash"):
                bootstrap.verify_files(source, entries)

    def test_release_identity_rejects_lightweight_tags_and_repository_mismatch(self) -> None:
        client = bootstrap.GitHub("owner/repository")
        with (
            patch.object(
                client,
                "get",
                return_value={"id": 7, "full_name": "other/repository", "clone_url": client.url},
            ),
            self.assertRaises(bootstrap.BootstrapError),
        ):
            client.verify_repository()
        release = {
            "id": 1,
            "tag_name": "v1.0.0",
            "published_at": "2026-10-20",
            "draft": False,
            "prerelease": False,
        }
        ref = {"ref": "refs/tags/v1.0.0", "object": {"type": "commit", "sha": "a" * 40}}
        with (
            patch.object(client, "get", side_effect=[release, ref]),
            self.assertRaisesRegex(bootstrap.BootstrapError, "annotated"),
        ):
            client.resolve("v1.0.0")

    def test_host_versions_and_failed_child_have_safe_errors_and_timeout(self) -> None:
        for outputs in (
            [b"git version 2.38.0"],
            [b"git version 2.39.0", b"25.0.0"],
            [b"git version 2.39.0", b"26.0.0", b"2.34.0"],
        ):
            with (
                patch.object(bootstrap, "process", side_effect=outputs),
                self.assertRaises(bootstrap.BootstrapError),
            ):
                bootstrap.host_checks()
        with self.assertRaises(bootstrap.BootstrapError) as caught:
            bootstrap.process(
                [
                    sys.executable,
                    "-c",
                    'import sys; print("synthetic_private_error", file=sys.stderr); sys.exit(1)',
                ]
            )
        self.assertNotIn("synthetic", str(caught.exception))
        with self.assertRaisesRegex(bootstrap.BootstrapError, "timed out"):
            bootstrap.process([sys.executable, "-c", "import time; time.sleep(10)"], timeout=0.05)

    def test_interactive_lists_releases_and_asks_postponement(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            destination = Path(folder) / "download"
            answers = ["owner/repository", "n", "v1.0.0", str(destination), "later", "y"]
            with (
                patch.object(sys.stdin, "isatty", return_value=True),
                patch.object(sys.stderr, "isatty", return_value=True),
                patch("builtins.input", side_effect=answers),
                patch.object(bootstrap, "host_checks"),
                patch.object(bootstrap.GitHub, "verify_repository", return_value=7),
                patch.object(bootstrap.GitHub, "releases", return_value=["v1.0.0"]),
                patch.object(
                    bootstrap,
                    "acquire",
                    side_effect=self.prepared_source,
                ),
                contextlib.redirect_stdout(io.StringIO()) as output,
            ):
                self.assertEqual(bootstrap.main([]), 0)
            self.assertIn("Accessible stable releases: v1.0.0", output.getvalue())

    def test_postponement_prints_exact_command_without_invoking_installer(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            destination = Path(folder) / "download"
            output = io.StringIO()
            with (
                patch.object(bootstrap, "host_checks"),
                patch.object(bootstrap.GitHub, "verify_repository", return_value=7),
                patch.object(
                    bootstrap,
                    "acquire",
                    side_effect=self.prepared_source,
                ),
                patch.object(bootstrap.subprocess, "run") as run,
                contextlib.redirect_stdout(output),
            ):
                self.assertEqual(
                    bootstrap.main(
                        [
                            "--non-interactive",
                            "--repository",
                            "owner/repository",
                            "--tag",
                            "v1.0.0",
                            "--destination",
                            str(destination),
                            "--install",
                            "later",
                        ]
                    ),
                    0,
                )
            run.assert_not_called()
            self.assertIn("Installation has not run", output.getvalue())
            self.assertIn(
                "-m tools.application.installation_gateway --protocol=1", output.getvalue()
            )

    def test_unattended_reference_option_reaches_acquisition(self) -> None:
        for option, included in (("include", True), ("exclude", False)):
            with self.subTest(option=option), tempfile.TemporaryDirectory() as folder:
                with (
                    patch.object(bootstrap, "host_checks"),
                    patch.object(bootstrap.GitHub, "verify_repository", return_value=7),
                    patch.object(
                        bootstrap,
                        "acquire",
                        side_effect=self.prepared_source,
                    ) as acquire,
                    contextlib.redirect_stdout(io.StringIO()),
                ):
                    bootstrap.main(
                        [
                            "--non-interactive",
                            "--repository",
                            "owner/repository",
                            "--tag",
                            "v1.0.0",
                            "--destination",
                            str(Path(folder) / "download"),
                            "--install",
                            "later",
                            "--reference-module",
                            option,
                        ]
                    )
                self.assertEqual(acquire.call_args.kwargs["reference_module"], included)

    def test_external_handoffs_ignore_customer_bin_and_installer_files(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / "bin").mkdir()
            (root / "bin/artisan").touch()
            with self.assertRaisesRegex(bootstrap.BootstrapError, "historical installer"):
                bootstrap.install_command(root, "dev", "a" * 40)
            gateway = root / "tools/application/gateway.json"
            gateway.parent.mkdir(parents=True)
            gateway.write_text('{"schema_version": 1, "installation_protocol": 1}')
            command = bootstrap.install_command(root, "dev", "a" * 40)
            self.assertEqual(
                command[:3], [sys.executable, "-m", "tools.application.installation_gateway"]
            )
            self.assertIn("--workspace=" + str(root), command)
            marker = root / "distribution/installation.py"
            marker.parent.mkdir(parents=True)
            marker.touch()
            self.assertEqual(
                bootstrap.install_command(root, "dev", "a" * 40)[-2:], ["dev", "a" * 40]
            )
            self.assertEqual(
                bootstrap.install_command(root, "prod", "a" * 40)[-2:], ["prod", "a" * 40]
            )

    def test_release_acquisition_cannot_initialize_production(self) -> None:
        with self.assertRaisesRegex(bootstrap.BootstrapError, "development-only"):
            bootstrap.acquire(
                bootstrap.GitHub("owner/repository"), "v1.0.0", Path("/unused"), "prod"
            )

    def test_interactive_private_token_uses_hidden_input_and_is_not_printed(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            args = [
                "--repository",
                "owner/repository",
                "--tag",
                "v1.0.0",
                "--destination",
                str(Path(folder) / "download"),
                "--install",
                "later",
                "--ask-token",
                "--reference-module",
                "exclude",
            ]
            with (
                patch.object(sys.stdin, "isatty", return_value=True),
                patch.object(sys.stderr, "isatty", return_value=True),
                patch.object(
                    bootstrap.getpass, "getpass", return_value="synthetic_not_a_real_token"
                ) as hidden,
                patch.object(bootstrap, "host_checks"),
                patch.object(bootstrap.GitHub, "verify_repository", return_value=7),
                patch.object(
                    bootstrap,
                    "acquire",
                    side_effect=self.prepared_source,
                ) as acquire,
                contextlib.redirect_stdout(io.StringIO()) as output,
            ):
                bootstrap.main(args)
            hidden.assert_called_once()
            self.assertEqual(acquire.call_args.args[0].token, "synthetic_not_a_real_token")
            self.assertNotIn("synthetic", output.getvalue())

    def test_install_now_automation_closes_stdin_and_reports_failure_without_retrying_download(
        self,
    ) -> None:
        with tempfile.TemporaryDirectory() as folder:
            destination = Path(folder) / "download"
            with (
                patch.object(bootstrap, "host_checks"),
                patch.object(
                    bootstrap,
                    "install_command",
                    return_value=[sys.executable, "-c", "fixture", "dev", "a" * 40],
                ),
                patch.object(bootstrap.GitHub, "verify_repository", return_value=7),
                patch.object(
                    bootstrap,
                    "acquire",
                    side_effect=self.prepared_source,
                ),
                patch.object(bootstrap.subprocess, "run", return_value=Mock(returncode=17)) as run,
                contextlib.redirect_stdout(io.StringIO()),
                self.assertRaisesRegex(
                    bootstrap.BootstrapError, "Source is ready; installation failed"
                ),
            ):
                bootstrap.main(
                    [
                        "--non-interactive",
                        "--repository",
                        "owner/repository",
                        "--tag",
                        "v1.0.0",
                        "--destination",
                        str(destination),
                        "--install",
                        "now",
                    ]
                )
            self.assertIs(run.call_args.kwargs["stdin"], subprocess.DEVNULL)
            self.assertEqual(run.call_args.args[0][-3:-1], ["dev", "a" * 40])
