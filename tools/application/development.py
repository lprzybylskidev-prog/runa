"""Workspace-scoped development command adapter; startup never requires AI."""

import os
import subprocess
import sys
from dataclasses import dataclass, field
from functools import partial
from pathlib import Path

from tools.application.development_config import KEYS, PRESETS, DevelopmentConfig
from tools.application.development_runtime import development_compose, execution_location
from tools.application.development_setup import DevelopmentSetup
from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.lifecycle import interrupt_signals
from tools.core.process import SubprocessRunner
from tools.core.reporting import SafeFailure
from tools.core.state import FileLock, StateDirectory


@dataclass(frozen=True)
class DevelopmentService:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation
    configuration: DevelopmentConfig = field(init=False)
    setup: DevelopmentSetup = field(init=False)

    def __post_init__(self):
        configuration = DevelopmentConfig(self.workspace, self.processes, self.location)
        object.__setattr__(self, "configuration", configuration)
        object.__setattr__(
            self, "setup", DevelopmentSetup(self.workspace, self.processes, configuration)
        )

    def application_setup(self, arguments):
        return self.setup.application_setup(arguments)

    @property
    def policy(self):
        return RepositoryPolicy(
            partial(self.processes.run, cwd=self.workspace.root), self.processes.environment
        )

    def knowledge_availability(self):
        # A separate process bounds DNS/connect/read and prevents optional probes from
        # inheriting the indexing transport's long retries.
        try:
            result = self.processes.run(
                [
                    sys.executable,
                    "-m",
                    "tools.application.knowledge.startup_cli",
                    "probe",
                ],
                cwd=self.workspace.root,
                timeout=5,
                check=False,
            )
            if result.returncode:
                print("DEGRADED: optional knowledge unavailable; use source search.")
                return
            result = self.processes.run(
                ["php", "artisan", "runa:knowledge:status"],
                cwd=self.workspace.root,
                timeout=8,
                check=False,
            )
            if result.returncode:
                print(
                    "DEGRADED: knowledge freshness unavailable/stale; use source search or ./bin/artisan runa:knowledge:index."
                )
        except subprocess.TimeoutExpired:
            print("DEGRADED: optional knowledge probe timed out; use source search.")

    def main(self, arguments):
        args = list(arguments)
        if not args or args[0] in ("--help", "-h"):
            print(
                "Usage: ./bin/artisan runa:dev:profile [low|standard|high|custom KEY=VALUE ...]\n"
                "       ./bin/artisan runa:dev:initialize|prepare|up|down|config|shell|status|doctor\n"
                "       ./bin/artisan runa:dev:build [service ...] | recreate service [service ...] (host only)\n"
                "       ./bin/artisan runa:dev:restart|logs [service] (host only)\n"
                "       ./bin/artisan runa:dev:embeddings:set auto|cpu|cuda (host only)\n"
                "       ./bin/artisan runa:dev:setup [--if-needed] (inside the devcontainer)\n"
                "       ./bin/artisan runa:dev:knowledge-init|knowledge-status (inside the devcontainer)\n"
                "Run Docker lifecycle commands on the host. Profile changes apply on container recreation.\n"
                "down preserves volumes; destructive volume deletion is not supported."
            )
            return 0
        self.policy.assert_development_context(self.workspace.root)
        values = self.configuration.load()
        command = args.pop(0)
        if command == "initialize":
            if args or self.location is ExecutionLocation.CONTAINER:
                raise ValueError(
                    "Initialize the customer development clone through the host transport without extra arguments."
                )
            if (self.workspace.root / ".env.prod").exists() or (
                self.workspace.root / ".env.prod.compose"
            ).exists():
                raise ValueError(
                    "Development initialization requires a dedicated clone without production settings."
                )
            self.policy.prepare_customer_context(self.workspace.root, "dev")
            helper = "tools.application.development"
            self.processes.run(
                [sys.executable, "-m", helper, "prepare"], cwd=self.workspace.root, check=True
            )

            context = list(development_compose(self.workspace).command())
            self.processes.run(
                [*context, "up", "-d", "--wait", "postgres", "app", "setup"],
                cwd=self.workspace.root,
                check=True,
            )
            self.processes.run([*context, "wait", "setup"], cwd=self.workspace.root, check=True)
            sha = (
                self.processes.check_output(
                    ["git", "rev-parse", "--verify", "HEAD"], cwd=self.workspace.root
                )
                .decode()
                .strip()
            )
            self.processes.run(
                [*context, "exec", "-T", "app", "php", "src/runa/database/prepare.php", sha],
                cwd=self.workspace.root,
                check=True,
            )
            self.processes.run(
                [
                    *context,
                    "exec",
                    "-T",
                    "-e",
                    "APP_ENV=testing",
                    "app",
                    "php",
                    "src/runa/database/prepare.php",
                    sha,
                ],
                cwd=self.workspace.root,
                check=True,
            )
            for command in (["migrate", "--force"], ["db:seed", "--force"]):
                self.processes.run(
                    [*context, "exec", "-T", "app", "php", "artisan", *command, "--no-interaction"],
                    cwd=self.workspace.root,
                    check=True,
                )
            self.processes.run(
                [sys.executable, "-m", helper, "up"], cwd=self.workspace.root, check=True
            )
            print(
                "PASS: customer development clone prepared; source, secrets and both data owners preserved."
            )
            return 0
        if command == "embeddings":
            if (
                self.location is ExecutionLocation.CONTAINER
                or len(args) != 1
                or args[0] not in ("auto", "cpu", "cuda")
            ):
                raise ValueError(
                    "Run ./bin/artisan runa:dev:embeddings:set auto|cpu|cuda on the host."
                )
            values["EMBEDDINGS_DEVICE"] = args[0]
            self.configuration.resolve_embeddings(values)
            self.configuration.persist(values)
            print(
                "Apply using ./bin/artisan runa:dev:build and ./bin/artisan runa:dev:up (persistent data preserved)."
            )
            return 0
        if command == "profile":
            if args:
                profile = args.pop(0)
                if profile not in (*PRESETS, "custom"):
                    raise ValueError("Unknown resource profile.")
                values["DEV_PROFILE"] = profile
                if profile != "custom":
                    if args:
                        raise ValueError("Overrides require the custom profile.")
                    values.update(zip(KEYS, PRESETS[profile], strict=True))
                for setting in args:
                    key, sep, value = setting.partition("=")
                    if not sep or key not in KEYS:
                        raise ValueError("Custom settings must use the documented resource keys.")
                    values[key] = value
                self.configuration.persist(values)
            print("\n".join(f"{key}={values[key]}" for key in ("DEV_PROFILE", *KEYS)))
            return 0
        if command in ("knowledge-init", "knowledge-status"):
            if args or self.location is not ExecutionLocation.CONTAINER:
                raise ValueError(
                    "Run knowledge-init/knowledge-status inside the development container."
                )
            if command == "knowledge-status":
                self.knowledge_availability()
                return 0
            return self.processes.run(
                [
                    sys.executable,
                    "-m",
                    "tools.application.knowledge.startup_cli",
                    "initialize",
                ],
                cwd=self.workspace.root,
            ).returncode
        if command == "setup":
            if (
                args not in ([], ["--if-needed"])
                or self.location is not ExecutionLocation.CONTAINER
            ):
                raise ValueError(
                    "Run ./bin/artisan runa:dev:setup [--if-needed] inside the devcontainer."
                )
            lock_dir = self.workspace.root / ".runtime/dev"
            lock_dir.mkdir(parents=True, exist_ok=True)
            with FileLock(
                StateDirectory(lock_dir, private=False, anchor=self.workspace.root),
                "setup.lock",
                private=False,
                blocking=True,
            ).acquire():
                return self.application_setup(args)
        if command in ("restart", "logs", "status"):
            if self.location is ExecutionLocation.CONTAINER:
                raise ValueError(
                    "Run Docker lifecycle commands in a host terminal, outside the devcontainer."
                )
            services = (
                "app",
                "nginx",
                "postgres",
                "queue",
                "scheduler",
                "mailpit",
                "qdrant",
                "embeddings",
                "embeddings-gpu",
                "setup",
                "knowledge-init",
            )
            if len(args) > 1 or (args and (args[0] not in services or command == "status")):
                raise ValueError(
                    "Provide at most one known service for restart/logs; status takes no arguments."
                )
            action = {
                "status": ["ps", "--all"],
                "restart": ["restart"],
                "logs": ["logs", "--tail", "100"],
            }[command]
            return self.processes.run(
                list(development_compose(self.workspace).command(*action, *args)),
                cwd=self.workspace.root,
            ).returncode
        if command in ("build", "recreate"):
            if self.location is ExecutionLocation.CONTAINER:
                raise ValueError(
                    "Run Docker lifecycle commands in a host terminal, outside the devcontainer."
                )
            buildable = (
                "app",
                "setup",
                "queue",
                "scheduler",
                "knowledge-init",
                "mailpit",
                "qdrant",
                "embeddings",
                "embeddings-gpu",
            )
            known = (*buildable, "nginx", "postgres")
            if command == "recreate" and not args:
                raise ValueError(
                    "Recreate requires explicit service names; review dependencies before using --no-deps."
                )
            if len(args) != len(set(args)) or any(
                name not in (buildable if command == "build" else known) for name in args
            ):
                raise ValueError(
                    "Provide unique known services; build requires services with a local build definition."
                )
            if "embeddings-gpu" in args and values["EMBEDDINGS_CUDA"] != "1":
                raise ValueError(
                    "CUDA service is not configured; select supported CUDA hardware first."
                )
            self.configuration.resolve_embeddings(values)
            self.configuration.persist(values)
            selected = args or [
                "app",
                "mailpit",
                "qdrant",
                "embeddings",
                *(["embeddings-gpu"] if values["EMBEDDINGS_CUDA"] == "1" else []),
            ]
            action = (
                ["build"] if command == "build" else ["up", "-d", "--no-deps", "--force-recreate"]
            )
            if command == "recreate":
                print(
                    "Recreating only selected services; prerequisites must already be ready. Check health after recreation."
                )
            return self.processes.run(
                list(development_compose(self.workspace).command(*action, *selected)),
                cwd=self.workspace.root,
            ).returncode
        if args:
            raise ValueError(
                "Unexpected arguments. Use php artisan list runa and help <exact-command>."
            )
        self.configuration.resolve_embeddings(values)
        self.configuration.persist(values)
        if command == "prepare":
            self.configuration.environment_files()
            if (
                self.location is not ExecutionLocation.CONTAINER
                and self.processes.environment.get("RUNA_HOST_WORKER") != "1"
            ):
                from tools.application.host_transport import HostExecutor

                HostExecutor(self.workspace, self.processes).ensure()
            print("Development profile prepared; persistent data preserved.")
            return 0
        if self.location is ExecutionLocation.CONTAINER:
            raise ValueError(
                "Run Docker lifecycle commands in a host terminal, outside the devcontainer."
            )
        commands = {
            "up": [
                "up",
                "-d",
                "--wait",
                "postgres",
                "app",
                "setup",
                "nginx",
                "queue",
                "scheduler",
                "mailpit",
            ],
            "down": ["down"],
            "config": ["config", "--quiet"],
            "shell": ["exec", "app", "bash"],
        }
        if command not in commands:
            raise ValueError("Unknown command. Use php artisan list runa and help <exact-command>.")
        result = self.processes.run(
            list(development_compose(self.workspace).command(*commands[command])),
            cwd=self.workspace.root,
            check=False,
        )
        if command == "up" and result.returncode == 0:
            if self.processes.environment.get("RUNA_HOST_WORKER") != "1":
                from tools.application.host_transport import HostExecutor

                HostExecutor(self.workspace, self.processes).ensure()
            context = list(development_compose(self.workspace).command())
            optional = [
                "qdrant",
                "embeddings",
                *(["embeddings-gpu"] if values["EMBEDDINGS_CUDA"] == "1" else []),
                "knowledge-init",
            ]
            ai = self.processes.run(
                [*context, "up", "-d", "--no-deps", *optional], cwd=self.workspace.root, check=False
            )
            if ai.returncode:
                print(
                    "DEGRADED: optional AI services failed to start; application readiness passed. Retry ./bin/artisan runa:dev:up after repair."
                )
            self.processes.run(
                [*context, "exec", "-T", "app", "php", "artisan", "runa:dev:knowledge-status"],
                cwd=self.workspace.root,
                check=False,
            )
        if command == "config" and result.returncode == 0:
            print("Development Compose configuration is valid.")
        return result.returncode


def main(arguments=None):
    workspace = WorkspaceContext(Path(__file__).resolve().parents[2])
    location = execution_location()
    service = DevelopmentService(
        workspace, ApplicationProcesses(SubprocessRunner(), dict(os.environ)), location
    )
    try:
        with interrupt_signals():
            return service.main(sys.argv[1:] if arguments is None else arguments)
    except KeyboardInterrupt:
        return 130
    except (ValueError, OSError, subprocess.CalledProcessError, SafeFailure):
        print(
            "Development command failed; inspect configuration and required services.",
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
