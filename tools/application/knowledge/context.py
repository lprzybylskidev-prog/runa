"""Explicit workspace, process and time dependencies for knowledge services."""

import os
from collections.abc import Mapping
from dataclasses import dataclass, field
from functools import partial
from pathlib import Path
from types import MappingProxyType

from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.clock import Clock, SystemClock
from tools.core.context import WorkspaceContext
from tools.core.process import OutputMode, ProcessRunner, ProcessSpec, SubprocessRunner


@dataclass(frozen=True)
class KnowledgeContext:
    workspace: WorkspaceContext
    runner: ProcessRunner
    environment: Mapping[str, str] = field(repr=False)
    clock: Clock
    owner_corpus: bool = False

    def __post_init__(self) -> None:
        object.__setattr__(self, "environment", MappingProxyType(dict(self.environment)))

    @classmethod
    def for_workspace(cls, root: Path) -> "KnowledgeContext":
        return cls(WorkspaceContext(root), SubprocessRunner(), dict(os.environ), SystemClock())

    def assert_development(self) -> None:
        processes = ApplicationProcesses(self.runner, self.environment)
        RepositoryPolicy(
            partial(processes.run, cwd=self.workspace.root), self.environment
        ).assert_development_context(self.workspace.root)

    def git(self, *arguments: str) -> bytes:
        return (
            self.runner.run(
                ProcessSpec(
                    ("git", *arguments),
                    self.workspace.root,
                    self.environment,
                    output=OutputMode.CAPTURE,
                )
            )
            .checked()
            .stdout
        )

    def git_value(self, *arguments: str) -> str:
        return self.git(*arguments).decode().strip()
