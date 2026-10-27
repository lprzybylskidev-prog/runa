"""Production configuration and image policy, independent of deployment orchestration."""

import re
import uuid
from collections.abc import Mapping, Sequence
from pathlib import Path

from tools.application.deployment_context import OperationContext
from tools.core.context import ComposeContext, WorkspaceContext

SERVICES = ("app", "nginx", "postgres", "queue", "scheduler")


class ProductionRuntime:
    SERVICES = SERVICES

    def __init__(self, context: OperationContext) -> None:
        self.context = context

    def compose(self, root: Path | None = None, tls_root: Path | None = None) -> list[str]:
        root = root or self.context.workspace.root
        path = root / ".env.prod.compose"
        values: dict[str, str] = {}
        for line in path.read_text().splitlines():
            if not line.strip() or line.lstrip().startswith("#"):
                continue
            key, separator, value = line.partition("=")
            if (
                not separator
                or key
                not in (
                    "RUNA_IMAGE_TAG",
                    "RUNA_HTTP_PORT",
                    "RUNA_PRODUCTION_MODE",
                    "RUNA_TLS_MODE",
                    "RUNA_SIMULATION_PROJECT",
                )
                or key in values
            ):
                raise ValueError(
                    "Invalid production Compose setting; use the example without quoting or interpolation."
                )
            values[key] = value
        if not re.fullmatch(r"(?:v\d+\.\d+\.\d+|[a-f0-9]{40})", values.get("RUNA_IMAGE_TAG", "")):
            raise ValueError(
                "RUNA_IMAGE_TAG requires an exact vX.Y.Z release or full lowercase Git commit hash."
            )
        port = values.get("RUNA_HTTP_PORT", "8081")
        if not port.isascii() or not port.isdigit() or not 1024 <= int(port) <= 65535:
            raise ValueError("RUNA_HTTP_PORT must be an unprivileged TCP port (1024–65535).")
        mode = values.get("RUNA_PRODUCTION_MODE", "real")
        if mode not in ("real", "simulation"):
            raise ValueError("RUNA_PRODUCTION_MODE must be real or simulation.")
        project = "runa-prod-icu-simulation" if mode == "simulation" else "runa-prod"
        if "RUNA_SIMULATION_PROJECT" in values:
            if mode != "simulation" or not re.fullmatch(
                r"runa-prod-simulation-[a-z0-9-]{1,40}", values["RUNA_SIMULATION_PROJECT"]
            ):
                raise ValueError(
                    "Custom production project names are restricted to isolated simulations."
                )
            project = values["RUNA_SIMULATION_PROJECT"]
        files = [root / "compose.production.yaml"]
        if values.get("RUNA_TLS_MODE", "external") not in ("builtin", "external"):
            raise ValueError("RUNA_TLS_MODE must be builtin or external.")
        if values.get("RUNA_TLS_MODE") == "builtin":
            overlay = (tls_root or root) / ".runtime/production/tls/compose.json"
            if overlay.is_symlink() or not overlay.is_file():
                raise ValueError("Built-in TLS requires its installer-generated Compose overlay.")
            files.append(overlay)
        return list(ComposeContext(WorkspaceContext(root), tuple(files), path, project).command())

    def verify_images(
        self, command: Sequence[str], environment: Mapping[str, str], source: Path | None = None
    ) -> None:
        """Inspect the exact images selected by Compose, without starting a deployment."""
        images = self.context.run(
            [*command, "config", "--images"], env=environment, capture_output=True, text=True
        ).stdout.splitlines()
        php = next(image for image in images if image.startswith("runa-production:"))
        nginx = next(image for image in images if image.startswith("runa-nginx:"))
        for image, user in ((php, "runa"), (nginx, "nginx")):
            actual = self.context.run(
                ["docker", "image", "inspect", "--format", "{{.Config.User}}", image],
                capture_output=True,
                text=True,
            ).stdout.strip()
            if actual != user:
                raise ValueError("Unexpected runtime image user.")
        code = (
            (
                (source or self.context.workspace.root)
                / "tools/application/testing/production-runtime.php"
            )
            .read_text()
            .removeprefix("<?php")
            .lstrip()
        )
        self.context.run(
            [
                "docker",
                "run",
                "--rm",
                "--name",
                "runa-production-probe-" + uuid.uuid4().hex[:12],
                "--read-only",
                "--tmpfs",
                "/tmp",
                "--cap-drop=ALL",
                "--security-opt=no-new-privileges:true",
                "--entrypoint",
                "php",
                php,
                "-r",
                code,
            ]
        )
        self.context.run(
            [
                "docker",
                "run",
                "--rm",
                "--name",
                "runa-production-probe-" + uuid.uuid4().hex[:12],
                "--read-only",
                "--tmpfs",
                "/tmp",
                "--cap-drop=ALL",
                "--security-opt=no-new-privileges:true",
                "--entrypoint",
                "sh",
                php,
                "-c",
                'for tool in composer node npm pnpm codex python3 git gcc g++; do if command -v "$tool" >/dev/null 2>&1; then echo "Development tool in runtime: $tool" >&2; exit 1; fi; done',
            ]
        )
        self.context.run(
            [
                "docker",
                "run",
                "--rm",
                "--name",
                "runa-production-probe-" + uuid.uuid4().hex[:12],
                "--read-only",
                "--tmpfs",
                "/tmp",
                "--cap-drop=ALL",
                "--security-opt=no-new-privileges:true",
                "--entrypoint",
                "php-fpm",
                php,
                "-tt",
            ]
        )
        self.context.run(
            [
                "docker",
                "run",
                "--rm",
                "--name",
                "runa-production-probe-" + uuid.uuid4().hex[:12],
                "--read-only",
                "--tmpfs",
                "/tmp",
                "--add-host",
                "app:127.0.0.1",
                "--cap-drop=ALL",
                "--security-opt=no-new-privileges:true",
                nginx,
                "-t",
            ]
        )
