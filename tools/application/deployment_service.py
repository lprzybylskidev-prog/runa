"""Deployment and rollback orchestration over explicit services."""

import argparse
import re
import time
from collections.abc import Callable, Generator, Sequence
from contextlib import contextmanager
from pathlib import Path

from tools.application.deployment_context import OperationContext, read_env, stamp
from tools.application.deployment_models import DeploymentProgress, ReleaseRecord, manifest_version
from tools.application.deployment_runtime import DeploymentRuntime
from tools.application.deployment_source import SourceAcquisition
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext


class DeploymentService:
    @classmethod
    def for_workspace(
        cls,
        root: Path,
        *,
        processes: ApplicationProcesses | None = None,
        location: ExecutionLocation | None = None,
    ) -> "DeploymentService":
        from tools.application.production_runtime import ProductionRuntime

        context = OperationContext.create(
            WorkspaceContext(root), processes=processes, location=location
        )
        source = SourceAcquisition(context)
        runtime = DeploymentRuntime(context, source=source, production=ProductionRuntime(context))
        return cls(context, source, runtime)

    def __init__(
        self, context: OperationContext, source: SourceAcquisition, runtime: DeploymentRuntime
    ) -> None:
        self.context = context
        self.source = source
        self.runtime = runtime

    def active_release(self) -> str | None:
        current = self.context.read_state()["current"]
        return current["path"] if current else None

    def set_tag(self, sha: str) -> str:
        path = self.context.workspace.root / ".env.prod.compose"
        old = path.read_text()
        if not re.search(r"^RUNA_IMAGE_TAG=.*$", old, re.M):
            raise ValueError("Missing image tag configuration.")
        path.write_text(re.sub(r"^RUNA_IMAGE_TAG=.*$", "RUNA_IMAGE_TAG=" + sha, old, flags=re.M))
        path.chmod(0o600)
        return old

    def deploy(
        self,
        target: str = "main",
        simulation: bool = False,
        initial_resume: bool = False,
        verification: Callable[[Path], None] | None = None,
        database_setup: Callable[[Path], None] | None = None,
    ) -> None:
        self.context.policy.assert_production_installation(self.context.workspace.root)
        if database_setup is not None:
            # Private external installer callback; never selected by customer commands.
            return self.deploy_revision(
                self.source.resolve(target),
                simulation,
                initial_resume,
                verification,
                database_setup,
            )
        installed = self.context.read_state()["current"]
        if not installed:
            raise ValueError(
                "Customer deployment requires a completed platform installation; use the external owner installer first."
            )
        with self.context.customer_source.fetch(
            self.context.workspace.root,
            self.context.state,
            target,
            installed=Path(installed["path"]),
        ) as (source, sha):
            return self.deploy_revision(sha, simulation, source=source)

    def deploy_revision(
        self,
        sha: str,
        simulation: bool,
        initial_resume: bool = False,
        verification: Callable[[Path], None] | None = None,
        database_setup: Callable[[Path], None] | None = None,
        source: Path | None = None,
    ) -> None:
        costs: dict[str, float] = {}

        @contextmanager
        def measure(name: str) -> Generator[None, None, None]:
            started = time.monotonic()
            try:
                yield
            finally:
                costs[name] = round(time.monotonic() - started, 3)

        previous = self.context.read_state()["current"]
        mode = read_env(self.context.workspace.root / ".env.prod.compose").get(
            "RUNA_PRODUCTION_MODE", "real"
        )
        if simulation != (mode == "simulation"):
            raise ValueError(
                "The simulation flag must match RUNA_PRODUCTION_MODE in the private Compose settings."
            )
        if not simulation:
            self.source.assert_delivery(self.context.workspace.root)
        if previous and previous["simulation"] != simulation:
            raise ValueError("Cannot switch between real and simulation deployment contexts.")
        if previous and self.runtime.maintenance_active(previous["path"]):
            raise ValueError(
                "Maintenance is already active. Preserve operator ownership and resolve it before deployment."
            )
        if previous and previous["sha"] == sha:
            self.runtime.health(previous["path"], verify_source=True)
            print("PASS: requested customer revision is already deployed; no data steps repeated.")
            return
        if not previous:
            existing = self.runtime.dc(
                ["ps", "--all", "--quiet"], capture_output=True, text=True
            ).stdout.strip()
            project_command = self.runtime.command()
            project = project_command[project_command.index("--project-name") + 1]
            volumes = self.context.run(
                [
                    "docker",
                    "volume",
                    "ls",
                    "--filter",
                    "label=com.docker.compose.project=" + project,
                    "--format",
                    "{{.Name}}",
                ],
                capture_output=True,
                text=True,
            ).stdout.strip()
            if (existing or volumes) and not initial_resume:
                raise ValueError(
                    "Unmanaged production project already exists. Preserve it and choose a separate simulation context; do not adopt unknown data."
                )
            if (
                verification is not None
                and not (self.context.state / "install-resources.json").exists()
            ):
                self.context.atomic(
                    self.context.state / "install-resources.json",
                    {"sha": sha, "project": project, "started_at": stamp()},
                )
        with measure("source_preparation"):
            release = (
                self.source.checkout(sha, source)
                if source is not None
                else self.source.checkout(sha)
            )
        old_config = self.set_tag(sha)
        mutation_started = False
        pending: DeploymentProgress = {
            "target": sha,
            "previous": previous,
            "started_at": stamp(),
            "phase": "prepared",
            "data_steps_started": False,
            "phase_costs": costs,
        }

        def phase(value: str) -> None:
            pending["phase"] = value
            self.context.atomic(self.context.state / "in-progress.json", pending)

        try:
            self.runtime.dc(["config", "--quiet"], release)
            # Never overwrite an immutable image identity with a different source tree.
            with measure("image_build_and_dependencies"):
                self.runtime.build_images(release)
            with measure("configuration_and_image_assertions"):
                self.runtime.validate(release)
            phase("startup-and-maintenance")
            mutation_started = True
            with measure("startup_and_maintenance"):
                self.runtime.dc(["up", "-d", "--wait", "--no-build", "postgres"], release)
                self.runtime.pause_http(release)
            with measure("forward_data"):
                pending["data_steps_started"] = True
                if database_setup is not None:
                    phase("platform-installation")
                    database_setup(release)
                else:
                    phase("platform-migrations")
                    self.runtime.platform_data(sha, "migrations", release)
                    phase("platform-seeding")
                    self.runtime.platform_data(sha, "required-seeds", release)
                    if any(
                        (release / path).is_file()
                        for path in (
                            "src/runa/ModuleFramework/Updates/ModuleUpdates.php",
                            "src/runa/Support/Modules/Updates/ModuleUpdates.php",
                        )
                    ):
                        phase("platform-modules")
                        self.runtime.platform_data(sha, "modules", release)
                    phase("application-migrations")
                    self.runtime.artisan(["migrate", "--force"], release)
                    phase("application-seeding")
                    self.runtime.artisan(["db:seed", "--force"], release)
            # Cache is per container. Entrypoint builds configuration; routes/events are safe to cache here only per-container.
            phase("restart-and-health")
            with measure("restart_and_health"):
                self.runtime.resume_http(release, recreate=True)
                self.runtime.health(release, verify_source=True)
            if verification is not None:
                with measure("endpoint_assertions"):
                    verification(release)
            identity: ReleaseRecord = {
                "sha": sha,
                "version": manifest_version(release / "src/runa/platform.json"),
                "path": str(release),
                "application_version": manifest_version(release / "src/app/application.json"),
                "database_tree": self.source.tree_hash(sha, release),
                "rollback_safe": False,
                "deployed_at": stamp(),
                "simulation": simulation,
                "images": self.runtime.images(release),
                "phase_costs": costs,
            }
            self.context.atomic(
                self.context.state / "deployment.json", {"current": identity, "previous": previous}
            )
            (self.context.state / "in-progress.json").unlink()
            print("PASS: deployed " + sha + (" (simulation)." if simulation else "."))
        except BaseException:
            if not mutation_started:
                (self.context.workspace.root / ".env.prod.compose").write_text(old_config)
            else:
                try:
                    self.runtime.pause_http(release)
                except BaseException:
                    print(
                        "Recovery fencing failed; immediately stop production HTTP/writers through the operator environment."
                    )
                print(
                    "Deployment FAILED at "
                    + pending["phase"]
                    + ". Maintenance/recovery state retained; inspect .runtime/production/in-progress.json before any retry. No schema/data reversal or automatic seeder replay was attempted."
                )
            raise

    def rollback(self) -> None:
        self.context.policy.assert_production_installation(self.context.workspace.root)
        state = self.context.read_state()
        current, previous = state["current"], state["previous"]
        if not current or not previous or (self.context.state / "in-progress.json").exists():
            raise ValueError(
                "Rollback needs two successful deployments and no unresolved failed deployment."
            )
        if current["database_tree"] != previous["database_tree"]:
            raise ValueError(
                "Migration/seeder sources changed. Automatic rollback is blocked; use the documented recovery strategy."
            )
        if not current.get("rollback_safe"):
            raise ValueError(
                "Schema/data compatibility is unproven after deployment data steps. Code rollback cannot reverse data; recover under operator review."
            )
        if self.runtime.maintenance_active(current["path"]):
            raise ValueError(
                "Maintenance is already active; preserve operator ownership before rollback."
            )
        old = self.set_tag(previous["sha"])
        try:
            if self.runtime.images(previous["path"]) != previous["images"]:
                raise ValueError(
                    "Previous image identity changed or is missing; refusing rollback."
                )
            self.runtime.validate(previous["path"])
        except BaseException:
            (self.context.workspace.root / ".env.prod.compose").write_text(old)
            raise
        self.context.atomic(
            self.context.state / "in-progress.json",
            {"phase": "rollback", "target": previous["sha"], "started_at": stamp()},
        )
        self.runtime.pause_http(current["path"])
        try:
            self.runtime.resume_http(previous["path"], recreate=True)
            self.runtime.health(previous["path"], verify_source=True)
        except BaseException:
            self.runtime.http_maintenance(True, previous["path"])
            raise
        self.context.atomic(
            self.context.state / "deployment.json", {"current": previous, "previous": current}
        )
        (self.context.state / "in-progress.json").unlink()
        print(
            "PASS: application rollback "
            + current["sha"]
            + " -> "
            + previous["sha"]
            + "; database unchanged."
        )

    def main(self, name: str, args: Sequence[str]) -> None:
        if name != "deploy":
            raise ValueError("Unsupported internal operation family.")
        parser = argparse.ArgumentParser(prog="./bin/" + name)
        parser.add_argument(
            "target",
            nargs="?",
            default="main",
            help="Customer branch/tag/full SHA (default: main), or internal rollback",
        )
        parser.add_argument(
            "--simulation", action="store_true", help="Isolated local production simulation"
        )
        options = parser.parse_args(args)
        if self.context.inside():
            raise ValueError(
                "Run production operations in a host terminal outside the devcontainer."
            )
        self.context.policy.assert_production_installation(self.context.workspace.root)
        with self.context.lock():
            if options.target == "rollback":
                self.rollback()
            else:
                if (self.context.state / "in-progress.json").exists():
                    raise ValueError(
                        "Unresolved failed deployment; inspect in-progress.json before retrying."
                    )
                self.deploy(options.target, options.simulation)
