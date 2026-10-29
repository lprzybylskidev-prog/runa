"""Explicit native acceptance process/Git/Compose context; imports start nothing."""

from __future__ import annotations

import os
import subprocess
from collections.abc import Callable, Mapping, Sequence
from dataclasses import dataclass, field
from pathlib import Path
from typing import NotRequired, Protocol, TypedDict, Unpack

from tools.application.development_runtime import development_compose
from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner
from tools.core.state import JsonValue


class OutputOptions(TypedDict, total=False):
    cwd: Path | None
    env: Mapping[str, str] | None
    check: bool


class AcceptanceCall(Protocol):
    def __call__(
        self,
        arguments: Sequence[str],
        *,
        cwd: Path | None = None,
        env: Mapping[str, str] | None = None,
        capture: bool = False,
        check: bool = True,
    ) -> subprocess.CompletedProcess[str]: ...


class AcceptanceOutput(Protocol):
    def __call__(self, arguments: Sequence[str], **options: Unpack[OutputOptions]) -> str: ...


Check = Callable[[bool, str], None]


class ComposeFixture(TypedDict):
    services: dict[str, dict[str, JsonValue]]
    volumes: dict[str, JsonValue]
    name: NotRequired[str]
    networks: NotRequired[dict[str, JsonValue]]


class GitOptions(TypedDict, total=False):
    token: str
    input_data: str | None


class RemovedResources(TypedDict):
    project: str
    containers: list[str]
    networks: list[str]
    volumes: list[str]


class DeploymentReport(TypedDict):
    first: str
    second: str
    successful_images: dict[str, str]
    pending_phase: str


class KnowledgeReport(TypedDict):
    qdrant_volume: str
    preserved_model_cache: str
    sources: list[str]


class PhaseRecord(TypedDict):
    result: str
    started_at: str
    finished_at: str | None
    duration_seconds: float | None


class Report(TypedDict, total=False):
    result: str
    checks: list[str]
    preserved: list[str]
    cleanup: list[RemovedResources]
    customer_command_transport: bool
    customer_deployment: DeploymentReport
    customer_knowledge: KnowledgeReport
    team_update: dict[str, str]
    update_preflight: dict[str, dict[str, str]]
    application_browser_artifacts: str
    dev_sha: str | None
    prod_sha: str
    images: dict[str, str]
    removed_images: list[str]
    natural_queue_rollovers: list[str]
    fixture_root: str
    phases: dict[str, PhaseRecord]
    quality_binding: dict[str, JsonValue]
    ownership: dict[str, JsonValue]


Snapshot = dict[str, tuple[int, str]]


@dataclass(frozen=True)
class AcceptanceContext:
    workspace: WorkspaceContext
    processes: ApplicationProcesses = field(
        default_factory=lambda: ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    )

    def call(
        self,
        arguments: Sequence[str],
        *,
        cwd: Path | None = None,
        env: Mapping[str, str] | None = None,
        capture: bool = False,
        check: bool = True,
    ) -> subprocess.CompletedProcess[str]:
        return self.processes.run(
            arguments,
            cwd=cwd or self.workspace.root,
            env=env,
            capture_output=capture,
            text=True,
            check=check,
            stdin=subprocess.DEVNULL,
        )

    def output(self, arguments: Sequence[str], **kwargs: Unpack[OutputOptions]) -> str:
        return self.call(arguments, capture=True, **kwargs).stdout.strip()

    def compose(self, *arguments: str) -> list[str]:
        return list(development_compose(self.workspace).command(*arguments))

    def app(self, *arguments: str) -> list[str]:
        return self.compose("exec", "-T", "app", *arguments)

    def commit(self, root: Path) -> str:
        self.call(["git", "add", "--all"], cwd=root)
        self.call(
            [
                "git",
                "-c",
                "core.hooksPath=/dev/null",
                "-c",
                "user.name=Acceptance Fixture",
                "-c",
                "user.email=fixture@example.invalid",
                "commit",
                "--quiet",
                "-m",
                "test(RM-2026-012): isolated fixture",
            ],
            cwd=root,
        )
        return self.output(["git", "rev-parse", "HEAD"], cwd=root)

    def protected_containers(self) -> list[str]:
        # Select by actual checkout identity, independent of project/container names.
        listing = self.output(
            [
                "docker",
                "ps",
                "--no-trunc",
                "--filter",
                "label=com.docker.compose.project.working_dir=" + str(self.workspace.root),
                "--format",
                '{{.ID}}\t{{.Label "runa.testing.run"}}\t{{.Label "com.docker.compose.project.working_dir"}}',
            ]
        )
        return [
            identity
            for row in listing.splitlines()
            for identity, browser_run, directory in [row.split("\t")]
            if directory == str(self.workspace.root) and not browser_run
        ]
