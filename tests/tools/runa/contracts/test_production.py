#!/usr/bin/env python3
"""Production topology and command-boundary contracts without Docker privileges."""

from __future__ import annotations

import contextlib
import io
import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import Mock, patch

from tests.tools.runa.support.deployment import bind_deployment, deployment
from tools.application import production_runtime as production
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.maintainer.checks import yaml

ROOT = Path(__file__).resolve().parents[4]


class ProductionContracts(unittest.TestCase):
    def setUp(self) -> None:
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.deployment = deployment(Path(self.temporary.name))
        boundary = patch.object(
            RepositoryPolicy, "assert_installation", return_value={"mode": "prod"}
        )
        boundary.start()
        self.addCleanup(boundary.stop)

    def test_team_deployment_compares_production_projection_and_refuses_excluded_source(
        self,
    ) -> None:
        from tests.tools.runa.acceptance.team_update_host import (
            production_source_matches as matches,
        )

        with tempfile.TemporaryDirectory() as folder:
            peer, active = Path(folder) / "peer", Path(folder) / "active"
            names = ["src/app/main.php", "src/app/modules/reference/backend/Contracts/Ping.php"]
            for root in (peer, active):
                (root / names[0]).parent.mkdir(parents=True)
                (root / names[0]).write_text("customer")
            self.assertTrue(matches(active, peer, names))
            (active / names[0]).write_text("changed")
            self.assertFalse(matches(active, peer, names))
            (active / names[0]).write_text("customer")
            (active / "src/app/modules/reference").mkdir(parents=True)
            self.assertFalse(matches(active, peer, names))
            (active / "src/app/modules/reference").rmdir()
            (active / "tools/maintainer").mkdir(parents=True)
            self.assertFalse(matches(active, peer, names))

    def test_customer_redis_is_supported_while_actual_ai_services_and_privileges_remain_forbidden(
        self,
    ) -> None:
        config = {
            "services": {
                name: {"image": "fixture", "environment": {"KNOWLEDGE_ENABLED": "false"}}
                for name in (*production.SERVICES, "redis")
            }
        }

        def dc(arguments: list[str], *args: object, **kwargs: object) -> Mock:
            return subprocess.CompletedProcess(arguments, 0, arguments[-1])

        def run(arguments: list[str], **kwargs: object) -> Mock:
            actual = {
                "Image": "exact",
                "HostConfig": {},
                "Config": {"Env": ["KNOWLEDGE_ENABLED=false"]},
                "Mounts": [],
            }
            return subprocess.CompletedProcess(
                arguments, 0, json.dumps([actual]) if arguments[1] == "inspect" else "exact"
            )

        with (
            patch.object(self.deployment.runtime, "resolved", return_value=config),
            patch.object(self.deployment.runtime, "dc", side_effect=dc),
            patch.object(self.deployment.context, "run", side_effect=run),
            patch.object(self.deployment.runtime, "command", return_value=["compose"]),
            patch.object(self.deployment.runtime.production, "verify_images"),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            self.deployment.runtime.isolation()
            config["services"]["redis"]["privileged"] = True
            with self.assertRaisesRegex(ValueError, "privileges"):
                self.deployment.runtime.isolation()
            config["services"]["redis"].pop("privileged")
            config["services"]["qdrant"] = {"image": "qdrant/qdrant:fixture"}
            with self.assertRaisesRegex(ValueError, "AI/development"):
                self.deployment.runtime.isolation()

    def test_runtime_topology_and_isolation(self) -> None:
        data = yaml(ROOT / "compose.production.yaml")
        services = data["services"]
        self.assertEqual(set(services), set(production.SERVICES))
        for key, service in services.items():
            if key != "nginx":
                self.assertIn("healthcheck", service)
            self.assertEqual(service["logging"]["options"], {"max-size": "10m", "max-file": "3"})
            self.assertFalse(service.get("privileged", False))
            self.assertNotIn("ports", service if key != "nginx" else {})
        for key in ("app", "queue", "scheduler"):
            service = services[key]
            self.assertTrue(service["read_only"])
            self.assertEqual(service["environment"]["KNOWLEDGE_ENABLED"], "false")
            self.assertEqual(service["environment"]["QUEUE_CONNECTION"], "database")
            self.assertEqual(service["cap_drop"], ["ALL"])
        public = services["nginx"]["volumes"][0]
        self.assertTrue(public["read_only"])
        self.assertEqual(public["volume"]["subpath"], "app/application/public")
        self.assertEqual(
            [
                (mount["volume"]["subpath"], mount["read_only"])
                for mount in services["nginx"]["volumes"]
            ],
            [
                ("app/application/public", True),
                ("app/runa/public", True),
                ("framework/maintenance", True),
            ],
        )
        self.assertEqual(set(data["volumes"]), {"application-storage", "postgres-icu-data"})
        for service in services.values():
            for mount in service.get("tmpfs", []):
                self.assertTrue(
                    mount.startswith("/"), "tmpfs options must stay inside one quoted mount string"
                )
        self.assertEqual(len(services["nginx"]["tmpfs"]), 1)
        nginx_config = (ROOT / "docker/nginx/production.conf").read_text()
        self.assertIn("location = / { rewrite ^ /index.php last; }", nginx_config)
        production_stage = (
            (ROOT / "docker/php/Dockerfile")
            .read_text()
            .split("FROM production-runtime AS production\n")[1]
            .split("FROM nginx:")[0]
        )
        self.assertIn('ENTRYPOINT ["runa-production-entrypoint"]', production_stage)
        self.assertIn('CMD ["php-fpm", "-F"]', production_stage)

    def test_emergency_scope_excludes_php_errors_and_sensitive_storage(self) -> None:
        config = (ROOT / "docker/nginx/production.conf").read_text()
        self.assertIn("fastcgi_intercept_errors off;", config)
        self.assertIn("error_page 502 503 504 =503 $runa_error_document;", config)
        self.assertIn("location ^~ /runa-error/pages/ {\n            internal;", config)
        mounts = yaml(ROOT / "compose.production.yaml")["services"]["nginx"]["volumes"]
        maintenance = next(mount for mount in mounts if mount["target"] == "/run/runa-maintenance")
        self.assertTrue(maintenance["read_only"])
        self.assertEqual(maintenance["volume"]["subpath"], "framework/maintenance")
        image_source = (ROOT / "docker/php/Dockerfile").read_text()
        self.assertIn("RUN php runa/build/errors/build.php", image_source)
        self.assertEqual(
            image_source.count(
                "/tmp/runa-build/src/public/runa-error/ /workspace/src/public/runa-error/"
            ),
            2,
        )

    def test_native_mail_runtime_inputs_are_shipped_without_review_fixtures(self) -> None:
        stage = (
            (ROOT / "docker/php/Dockerfile")
            .read_text()
            .split("FROM production-runtime AS production\n")[1]
            .split("FROM nginx:")[0]
        )
        for source in (
            "src/runa/resources/views/",
            "src/runa/resources/images/runa-mail-logo.png",
            "src/runa/resources/css/light-palette.css",
            "src/runa/platform.json",
        ):
            self.assertTrue((ROOT / source).exists())
            self.assertIn("COPY --chown=runa:runa " + source + " /workspace/" + source, stage)
        self.assertNotIn("src/runa/resources/review", stage)
        self.assertNotIn(
            "COPY --chown=runa:runa src/runa/resources/ /workspace/src/runa/resources/", stage
        )

    def test_shared_nginx_runtime_digest_stays_consistent(self) -> None:
        development = yaml(ROOT / "compose.yaml")["services"]
        image = development["nginx"]["image"]
        self.assertRegex(image, r"^nginx:[^@]+@sha256:[a-f0-9]{64}$")
        self.assertEqual(development["nginx-test"]["image"], image)
        self.assertIn(
            "FROM " + image + " AS production-nginx", (ROOT / "docker/php/Dockerfile").read_text()
        )

    def test_context_and_argument_validation(self) -> None:
        from tools.application.production_cli import main

        with (
            tempfile.TemporaryDirectory() as folder,
            bind_deployment(self, Path(folder)),
            patch(
                "tools.application.repository_boundary_service.RepositoryPolicy.assert_production_installation"
            ),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            config = Path(folder) / ".env.prod.compose"
            config.write_text("RUNA_IMAGE_TAG=v0.1.0\nRUNA_HTTP_PORT=8081\n")
            self.deployment.context.run.return_value = subprocess.CompletedProcess([], 0)
            self.deployment.context.run.side_effect = None
            main(["down"], self.deployment)
            command = self.deployment.context.run.call_args.args[0]
            self.assertEqual(command[-1], "down")
            self.assertNotIn("-v", command)
            self.assertEqual(command[command.index("--project-name") + 1], "runa-prod")
            for args in (["down", "-v"], ["logs", "qdrant"], ["up", "--force-recreate"]):
                with self.assertRaises(ValueError):
                    main(args, self.deployment)
            for tag in ("latest", "main", "v0.1.0; false", "abc123"):
                config.write_text("RUNA_IMAGE_TAG=" + tag + "\n")
                with self.assertRaises(ValueError):
                    self.deployment.runtime.production.compose()
            self.deployment.context.inside.return_value = True
            with self.assertRaisesRegex(ValueError, "host terminal"):
                main(["build"], self.deployment)
