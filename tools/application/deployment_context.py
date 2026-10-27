"""Operator workspace, production process policy and durable operation state."""

import os
from contextlib import AbstractContextManager
from dataclasses import dataclass
from datetime import UTC, datetime
from functools import partial
from pathlib import Path

from tools.application.customer_source_service import CustomerSource
from tools.application.deployment_models import DeploymentState, deployment_state
from tools.application.development_runtime import execution_location
from tools.application.processes import ApplicationProcesses, WorkspaceProcess
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.context import ExecutionLocation, ReleaseContext, WorkspaceContext
from tools.core.process import SubprocessRunner
from tools.core.state import FileLock, JsonStore, StateDirectory, json_value


def stamp() -> str:
    return datetime.now(UTC).isoformat()


def read_env(path: Path) -> dict[str, str]:
    from tools.core.environment import raw_environment

    return raw_environment(path.read_text())


@dataclass(frozen=True)
class OperationContext:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation

    @classmethod
    def create(
        cls,
        workspace: WorkspaceContext,
        *,
        processes: ApplicationProcesses | None = None,
        location: ExecutionLocation | None = None,
    ) -> "OperationContext":
        return cls(
            workspace,
            processes or ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
            location or execution_location(),
        )

    @property
    def policy(self) -> RepositoryPolicy:

        return RepositoryPolicy(
            partial(self.processes.run, cwd=self.workspace.root), self.processes.environment
        )

    @property
    def customer_source(self) -> CustomerSource:

        return CustomerSource(
            partial(self.processes.run, cwd=self.workspace.root), self.processes.environment
        )

    @property
    def state(self) -> Path:
        return self.workspace.runtime("production")

    def release(self, source: Path, revision: str) -> ReleaseContext:
        return ReleaseContext(self.workspace, WorkspaceContext(source), revision)

    def inside(self) -> bool:
        return self.location is ExecutionLocation.CONTAINER

    def environment(self) -> dict[str, str]:
        return {
            key: value
            for key, value in self.processes.environment.items()
            if not key.startswith(("COMPOSE_", "DOCKER_", "RUNA_"))
        }

    @property
    def run(self) -> WorkspaceProcess:
        return partial(self.processes.run, cwd=self.workspace.root, check=True)

    def store(self, directory: Path) -> JsonStore:
        return JsonStore(StateDirectory(directory, private=False, anchor=self.workspace.root))

    def atomic(self, path: Path, value: object) -> None:
        self.store(path.parent).write(path.name, json_value(value))

    def read_state(self) -> DeploymentState:
        path = self.state / "deployment.json"
        if not path.exists() and not path.is_symlink():
            return {"current": None, "previous": None}
        return deployment_state(self.store(self.state).read(path.name), self.state)

    def lock(self) -> AbstractContextManager[None]:
        return FileLock(
            StateDirectory(self.state, private=False, anchor=self.workspace.root),
            "operation.lock",
            "Another production operation is running.",
            private=False,
        ).acquire()
