"""Production Compose, images, health and forward data transitions."""

import re
import shutil
import subprocess
from collections.abc import Sequence
from pathlib import Path
from typing import Literal, Unpack, overload

from tools.application.deployment_context import OperationContext, read_env
from tools.application.deployment_models import (
    ComposeConfiguration,
    compose_configuration,
    container_inspection,
    service_image,
    service_status,
)
from tools.application.deployment_source import SourceAcquisition
from tools.application.processes import ProcessOptions
from tools.application.production_runtime import ProductionRuntime

WRITERS = ["queue", "scheduler", "app"]


class DeploymentRuntime:
    def __init__(
        self, context: OperationContext, source: SourceAcquisition, production: ProductionRuntime
    ) -> None:
        self.context = context
        self.source = source
        self.production = production

    def command(self, release: Path | str | None = None) -> list[str]:
        selected = Path(release) if release else self.context.workspace.root
        identity = self.context.release(selected, selected.name if release else "operator")
        context = identity.source.root
        # Compose env-file paths are relative to the release; sync only private operator files.
        if context != self.context.workspace.root:
            for name in (".env.prod", ".env.prod.database", ".env.prod.compose"):
                source = self.context.workspace.root / name
                if source.is_symlink() or not source.is_file():
                    raise ValueError("Missing regular production environment file: " + name)
                destination = context / name
                if destination.is_symlink():
                    raise ValueError("Production environment destination must not be a symlink.")
                destination.touch(mode=0o600, exist_ok=True)
                destination.chmod(0o600)
                shutil.copyfile(source, destination)
                if name == ".env.prod.compose":
                    destination.write_text(
                        re.sub(
                            r"^RUNA_IMAGE_TAG=.*$",
                            "RUNA_IMAGE_TAG=" + context.name,
                            destination.read_text(),
                            flags=re.M,
                        )
                    )
        return self.production.compose(context, tls_root=self.context.workspace.root)

    @overload
    def dc(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: Literal[True],
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[str]: ...

    @overload
    def dc(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: Literal[False] = False,
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[bytes]: ...

    def dc(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: bool = False,
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[str] | subprocess.CompletedProcess[bytes]:
        # Noninteractive Compose clients must not consume the caller's shell script.
        if "stdin" not in kwargs and "input" not in kwargs:
            kwargs["stdin"] = subprocess.DEVNULL
        kwargs["env"] = self.context.environment()
        command = [*self.command(release), *args]
        if text:
            return self.context.run(command, text=True, **kwargs)
        return self.context.run(command, **kwargs)

    @overload
    def artisan(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: Literal[True],
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[str]: ...

    @overload
    def artisan(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: Literal[False] = False,
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[bytes]: ...

    def artisan(
        self,
        args: Sequence[str],
        release: Path | str | None = None,
        *,
        text: bool = False,
        **kwargs: Unpack[ProcessOptions],
    ) -> subprocess.CompletedProcess[str] | subprocess.CompletedProcess[bytes]:
        command = [
            "run",
            "--rm",
            "--no-deps",
            "-T",
            "app",
            "php",
            "artisan",
            *args,
            "--no-interaction",
        ]
        if text:
            return self.dc(command, release, text=True, **kwargs)
        return self.dc(command, release, **kwargs)

    def http_maintenance(self, enabled: bool, release: Path | str | None = None) -> None:
        # Dedicated shared directory: Nginx never needs session/cache/private-upload access.
        program = (
            '$directory = "/workspace/src/storage/framework/maintenance"; '
            'if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) { throw new RuntimeException("Cannot create HTTP maintenance directory."); } '
            '$path = $directory . "/enabled"; '
        )
        if enabled:
            program += 'if (file_put_contents($path, "maintenance\\n", LOCK_EX) === false) { throw new RuntimeException("Cannot enable HTTP maintenance."); }'
        else:
            program += 'if (is_file($path) && !unlink($path)) { throw new RuntimeException("Cannot disable HTTP maintenance."); }'
        self.dc(
            ["run", "--rm", "--no-deps", "-T", "--entrypoint", "php", "app", "-r", program], release
        )

    def pause_http(self, release: Path | str | None = None) -> None:
        self.http_maintenance(True, release)
        # Keep the static serving process alive even when app DNS/PHP is absent.
        self.dc(["up", "-d", "--no-build", "--no-deps", "nginx"], release)
        self.dc(["stop", *WRITERS], release)

    def resume_http(self, release: Path | str | None = None, recreate: bool = False) -> None:
        self.dc(
            [
                "up",
                "-d",
                "--wait",
                "--no-build",
                *(["--force-recreate"] if recreate else []),
                *[
                    name
                    for name, value in self.resolved(release)["services"].items()
                    if name != "nginx" and not value.get("profiles")
                ],
            ],
            release,
        )
        # Refresh Nginx's public-storage subpath mount when recreating the stack.
        self.dc(
            [
                "up",
                "-d",
                "--no-build",
                "--no-deps",
                *(["--force-recreate"] if recreate else []),
                "nginx",
            ],
            release,
        )
        self.http_maintenance(False, release)
        try:
            self.dc(["up", "-d", "--wait", "--no-build", "--no-deps", "nginx"], release)
        except BaseException:
            self.http_maintenance(True, release)
            raise

    def validate(self, release: Path | str | None = None) -> None:
        for name in (".env.prod", ".env.prod.database", ".env.prod.compose"):
            path = self.context.workspace.root / name
            if path.is_symlink() or not path.is_file() or path.stat().st_mode & 0o077:
                raise ValueError(
                    "Production env files must be regular private files (chmod 600): " + name
                )
        app = read_env(self.context.workspace.root / ".env.prod")
        database = read_env(self.context.workspace.root / ".env.prod.database")
        if app.get("DB_PASSWORD") != database.get("APP_DB_PASSWORD") or not app.get("DB_PASSWORD"):
            raise ValueError("Application/database credentials do not agree.")
        if (
            len(database.get("POSTGRES_PASSWORD", "")) < 24
            or database.get("POSTGRES_PASSWORD") == app["DB_PASSWORD"]
        ):
            raise ValueError(
                "Use a distinct administrative database password of at least 24 characters."
            )
        self.dc(["config", "--quiet"], release)
        self.artisan(["runa:config:validate"], release)

    def resolved(self, release: Path | str | None = None) -> ComposeConfiguration:
        return compose_configuration(
            self.dc(["config", "--format", "json"], release, capture_output=True, text=True).stdout
        )

    def platform_data(self, sha: str, phase: str, release: Path | str | None = None) -> None:
        self.dc(
            [
                "run",
                "--rm",
                "--no-deps",
                "-T",
                "app",
                "php",
                "src/runa/database/prepare.php",
                sha,
                phase,
            ],
            release,
        )

    def images(self, release: Path | str | None) -> dict[str, str]:
        config = self.resolved(release)
        names = {
            service_image(service)
            for service in config["services"].values()
            if not service.get("profiles")
        }
        return {
            name: self.context.run(
                ["docker", "image", "inspect", "--format", "{{.Id}}", name],
                capture_output=True,
                text=True,
            ).stdout.strip()
            for name in sorted(names)
        }

    def health(self, release: Path | str | None = None, verify_source: bool = False) -> None:
        current = self.context.read_state()["current"]
        if (
            current
            and str(release) == current["path"]
            and self.images(release) != current["images"]
        ):
            raise ValueError("Selected image IDs differ from the recorded successful deployment.")
        config = self.resolved(release)
        payload = self.dc(
            ["ps", "--all", "--format", "json"], release, capture_output=True, text=True
        ).stdout.strip()
        rows = service_status(payload)
        services = {row["Service"]: row for row in rows}
        required = {name for name, value in config["services"].items() if not value.get("profiles")}
        if not required <= services.keys() or services.keys() - config["services"].keys():
            raise ValueError(
                "Missing selected services or undeclared running/stopped services; reconcile the production project explicitly."
            )
        for service, row in services.items():
            definition = config["services"][service]
            needs_health = service in self.production.SERVICES or bool(
                definition.get("healthcheck")
            )
            if row.get("State") != "running" or (needs_health and row.get("Health") != "healthy"):
                raise ValueError("Production service is not healthy/running: " + service)
        self.dc(
            ["exec", "-T", "nginx", "wget", "-qO", "/dev/null", "http://127.0.0.1:8080/"], release
        )
        self.dc(
            ["exec", "-T", "nginx", "wget", "-qO", "/dev/null", "http://127.0.0.1:8080/api/ready"],
            release,
        )
        self.isolation(release, verify_source)
        print("PASS: production services, root page, readiness and isolation.")

    def isolation(self, release: Path | str | None = None, verify_source: bool = False) -> None:
        config = self.resolved(release)
        if not set(self.production.SERVICES) <= config["services"].keys():
            raise ValueError("Required production runtime services are missing.")
        forbidden = {
            "qdrant",
            "embeddings",
            "embeddings-gpu",
            "knowledge-init",
            "setup",
            "mailpit",
            "app-test",
            "nginx-test",
        }
        for name, service in config["services"].items():
            if name in forbidden or any(
                part in service.get("image", "").lower()
                for part in ("qdrant", "text-embeddings-inference", "runa-embeddings")
            ):
                raise ValueError("AI/development services cannot run in production.")
            if (
                service.get("privileged")
                or service.get("network_mode") == "host"
                or service.get("cap_add")
                or any("docker.sock" in str(mount) for mount in service.get("volumes", []))
            ):
                raise ValueError("Unsafe production privileges/mount/network.")
            if (
                name in ("app", "queue", "scheduler")
                and service.get("environment", {}).get("KNOWLEDGE_ENABLED") != "false"
            ):
                raise ValueError("Knowledge must be disabled.")
        self.production.verify_images(
            self.command(release),
            self.context.environment(),
            source=Path(release) if release else self.context.workspace.root,
        )
        for name, service in config["services"].items():
            container = self.dc(
                ["ps", "--quiet", name], release, capture_output=True, text=True
            ).stdout.strip()
            if not container and service.get("profiles"):
                continue
            if not container:
                raise ValueError("Missing production container: " + name)
            actual = container_inspection(
                self.context.run(
                    ["docker", "inspect", container], capture_output=True, text=True
                ).stdout
            )
            expected_image = self.context.run(
                ["docker", "image", "inspect", "--format", "{{.Id}}", service_image(service)],
                capture_output=True,
                text=True,
            ).stdout.strip()
            if actual["Image"] != expected_image:
                raise ValueError(
                    "Running image differs from the selected production identity: " + name
                )
            if (
                actual["HostConfig"].get("Privileged")
                or actual["HostConfig"].get("CapAdd")
                or actual["HostConfig"].get("NetworkMode") == "host"
                or any(
                    "docker.sock" in mount.get("Source", "") for mount in actual.get("Mounts", [])
                )
            ):
                raise ValueError("Running production privileges/mount/network violate isolation.")
            if name in ("app", "queue", "scheduler") and "KNOWLEDGE_ENABLED=false" not in (
                actual["Config"]["Env"] or []
            ):
                raise ValueError("Knowledge is not disabled in the running container.")
        if verify_source:
            if not release:
                raise ValueError("Source isolation requires a managed release checkout.")
            self.source.assert_delivery(Path(release))
        print(
            "PASS: production Compose/environment/image"
            + ("/customer-source" if verify_source else "")
            + " isolation."
        )

    def build_images(self, release: Path | str | None = None) -> None:
        from tools.application.dependencies import materialize

        materialize(Path(release) if release else self.context.workspace.root, check=True)
        selected = self.dc(
            ["config", "--images"], release, capture_output=True, text=True
        ).stdout.splitlines()
        app_images = sorted(
            {
                image
                for image in selected
                if image.startswith(("runa-production:", "runa-nginx:", "runa-postgres:"))
            }
        )
        present = [
            self.context.run(
                ["docker", "image", "inspect", image], capture_output=True, check=False
            ).returncode
            == 0
            for image in app_images
        ]
        if any(present) and not all(present):
            raise ValueError(
                "Incomplete immutable image set; inspect/remove the failed build explicitly before retrying."
            )
        if not any(present):
            self.dc(["build", "app", "nginx", "postgres"], release)
        self.production.verify_images(
            self.command(release),
            self.context.environment(),
            source=Path(release) if release else self.context.workspace.root,
        )

    def maintenance_active(self, release: Path | str | None) -> bool:
        result = self.dc(
            [
                "run",
                "--rm",
                "--no-deps",
                "-T",
                "--entrypoint",
                "php",
                "app",
                "-r",
                'echo (is_file("/workspace/src/storage/framework/maintenance/enabled") || is_file("/workspace/src/storage/framework/down")) ? "active" : "clear";',
            ],
            release,
            capture_output=True,
            text=True,
        )
        return result.stdout.strip() != "clear"
