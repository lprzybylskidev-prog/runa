"""Host-owned, journaled lifecycle for disposable browser endpoint containers."""

from __future__ import annotations

import contextlib
import fcntl
import json
import os
import re
import signal
import uuid
from collections.abc import Iterator, Sequence
from contextlib import AbstractContextManager
from dataclasses import dataclass
from pathlib import Path
from types import FrameType

from tools.application.development_runtime import development_compose
from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner
from tools.core.state import JsonValue

SERVICES = ("app-test", "nginx-test")
LABEL = "runa.testing.run"


def interrupted(signum: int, frame: FrameType | None) -> None:
    raise KeyboardInterrupt


@dataclass
class EndpointService:
    workspace: WorkspaceContext
    processes: ApplicationProcesses

    def read(self, command: Sequence[str]) -> str:
        return self.processes.run(
            command, cwd=self.workspace.root, check=True, capture_output=True, text=True
        ).stdout

    def cleanup(self, state: dict[str, JsonValue], project: str) -> None:
        if (
            set(state) != {"project", "run"}
            or state["project"] != project
            or not isinstance(state["run"], str)
            or not re.fullmatch("[a-f0-9]{32}", state["run"])
        ):
            raise ValueError("Uncertain test endpoint ownership; journal preserved.")
        ids = self.read(
            [
                "docker",
                "ps",
                "-aq",
                "--no-trunc",
                "--filter",
                "label=com.docker.compose.project=" + project,
                "--filter",
                "label=" + LABEL + "=" + state["run"],
            ]
        ).splitlines()
        # Inspect all candidates before any mutation; never print environments.
        for identity in ids:
            labels = json.loads(
                self.read(["docker", "inspect", "--format", "{{json .Config.Labels}}", identity])
            )
            if (
                labels.get("com.docker.compose.project") != project
                or labels.get("com.docker.compose.project.working_dir") != str(self.workspace.root)
                or labels.get("com.docker.compose.service") not in SERVICES
                or labels.get(LABEL) != state["run"]
            ):
                raise ValueError("Uncertain test container ownership; preserved " + identity)
        print("Scoped test container cleanup: " + json.dumps(ids), flush=True)
        if ids:
            self.processes.run(
                ["docker", "stop", "-t", "10", *ids], cwd=self.workspace.root, check=True
            )
            self.processes.run(["docker", "rm", *ids], cwd=self.workspace.root, check=True)

    @contextlib.contextmanager
    def endpoint(self) -> Iterator[None]:
        """Preserve manual endpoints; label/journal only containers this run creates."""
        from tools.application.repository_boundary_service import RepositoryPolicy

        RepositoryPolicy.for_workspace(self.workspace.root).assert_development_context(
            self.workspace.root
        )
        directory = self.workspace.runtime("tests")
        directory.mkdir(parents=True, exist_ok=True)
        journal = directory / "endpoint.json"
        override = directory / "endpoint.compose.json"
        if any(
            path.is_symlink()
            for path in (directory, journal, override, directory / "endpoint.lock")
        ):
            raise ValueError("Test endpoint state must not traverse symlinks.")
        with (directory / "endpoint.lock").open("a") as lock:
            try:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                raise ValueError("Another browser endpoint lifecycle is running.") from None
            # A detached runner after loss of its Docker client still holds this lock.
            # Refuse recovery/startup until it has actually finished.
            with (directory / "suite.lock").open("a") as suite:
                try:
                    fcntl.flock(suite, fcntl.LOCK_EX | fcntl.LOCK_NB)
                except BlockingIOError:
                    raise ValueError("Another test suite is running in this checkout.") from None
                project = json.loads(
                    self.read(
                        list(development_compose(self.workspace).command())
                        + ["config", "--format", "json"]
                    )
                )["name"]
                if journal.exists():
                    self.cleanup(json.loads(journal.read_text()), project)
                    journal.unlink()
                    override.unlink(missing_ok=True)
                ids = self.read(
                    list(development_compose(self.workspace).command())
                    + ["ps", "--all", "--quiet", *SERVICES]
                ).splitlines()
                existing = set()
                for identity in ids:
                    labels = json.loads(
                        self.read(
                            ["docker", "inspect", "--format", "{{json .Config.Labels}}", identity]
                        )
                    )
                    if (
                        labels.get("com.docker.compose.project") != project
                        or labels.get("com.docker.compose.project.working_dir")
                        != str(self.workspace.root)
                        or labels.get("com.docker.compose.service") not in SERVICES
                    ):
                        raise ValueError("Uncertain existing endpoint ownership.")
                    existing.add(labels["com.docker.compose.service"])
                state = {"project": project, "run": uuid.uuid4().hex}
                # Persist ownership before Compose can create anything, including partial
                # startup and SIGKILL. The next supported browser run recovers this label.
                temporary = directory / ("endpoint-" + state["run"] + ".tmp")
                temporary.write_text(json.dumps(state) + "\n")
                os.replace(temporary, journal)
                override.write_text(
                    json.dumps(
                        {
                            "services": {
                                service: {"labels": {LABEL: state["run"]}}
                                for service in SERVICES
                                if service not in existing
                            }
                        }
                    )
                    + "\n"
                )
            previous = signal.getsignal(signal.SIGTERM)
            signal.signal(signal.SIGTERM, interrupted)
            was_interrupted = False
            try:
                self.processes.run(
                    list(development_compose(self.workspace).command())
                    + [
                        "-f",
                        str(override),
                        "--profile",
                        "testing",
                        "up",
                        "-d",
                        "--wait",
                        "--no-recreate",
                        "nginx-test",
                    ],
                    cwd=self.workspace.root,
                    check=True,
                )
                yield
            except KeyboardInterrupt:
                was_interrupted = True
                raise
            finally:
                try:
                    with (directory / "suite.lock").open("a") as suite:
                        try:
                            fcntl.flock(suite, fcntl.LOCK_EX | fcntl.LOCK_NB)
                        except BlockingIOError:
                            raise ValueError(
                                "A test process is still active; endpoint journal and containers preserved for recovery."
                            ) from None
                        self.cleanup(state, project)
                    if not was_interrupted:
                        journal.unlink()
                        override.unlink()
                finally:
                    signal.signal(signal.SIGTERM, previous)


def endpoint() -> AbstractContextManager[None]:
    root = Path(__file__).resolve().parents[2]
    return EndpointService(
        WorkspaceContext(root), ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    ).endpoint()
