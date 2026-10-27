"""Concurrent quality scheduling with explicit transport and owned child groups."""

import os
import signal
import subprocess
import time
from collections.abc import Callable, Sequence
from dataclasses import dataclass, field
from datetime import UTC, datetime
from pathlib import Path
from types import FrameType
from typing import Protocol, TextIO

from tools.application.quality_models import QualityStep
from tools.application.quality_reporting import save
from tools.application.quality_state import RunRecord
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import reap, signal_group


class QualitySpawn(Protocol):
    def __call__(
        self,
        args: Sequence[str],
        *,
        cwd: Path,
        env: dict[str, str],
        stdout: TextIO,
        stderr: int,
        start_new_session: bool,
    ) -> subprocess.Popen[bytes]: ...


@dataclass(frozen=True)
class QualityExecutor:
    workspace: WorkspaceContext
    location: ExecutionLocation
    app_command: Callable[[Sequence[str]], Sequence[str]]
    spawn: QualitySpawn = subprocess.Popen
    environment: dict[str, str] = field(default_factory=lambda: dict(os.environ), repr=False)

    def execute(self, directory: Path, state: RunRecord) -> int:
        definitions = {key: QualityStep.parse(node) for key, node in state["steps"].items()}
        running: dict[str, tuple[subprocess.Popen[bytes], TextIO]] = {}
        started: dict[str, float] = {}
        run_started = time.monotonic()
        state["started_at"] = datetime.now(UTC).isoformat()
        interrupted = False

        def stop(signum: int, frame: FrameType | None) -> None:
            nonlocal interrupted
            interrupted = True

        old = {
            sig: signal.signal(sig, stop) for sig in (signal.SIGTERM, signal.SIGINT, signal.SIGHUP)
        }
        try:
            while True:
                for key, (process, log) in list(running.items()):
                    code = process.poll()
                    if code is not None:
                        reap(process, 10)
                        log.close()
                        state["steps"][key].update(
                            status="passed" if code == 0 else "failed",
                            exit_code=code,
                            finished_at=datetime.now(UTC).isoformat(),
                            duration_seconds=round(time.monotonic() - started.pop(key), 3),
                        )
                        del running[key]
                if interrupted:
                    for key, (process, _log) in running.items():
                        signal_group(process, signal.SIGTERM)
                        state["steps"][key]["status"] = "interrupted"
                    for key, (process, log) in running.items():
                        reap(process, 10)
                        log.close()
                        state["steps"][key].update(
                            finished_at=datetime.now(UTC).isoformat(),
                            duration_seconds=round(time.monotonic() - started.pop(key), 3),
                        )
                    state["status"] = "interrupted"
                    break
                groups = {definitions[k].exclusive_group for k in running}
                for key, node in state["steps"].items():
                    if node["status"] != "pending":
                        continue
                    definition = definitions[key]
                    deps = [state["steps"][d]["status"] for d in definition.dependencies]
                    if any(s in ("failed", "blocked", "interrupted") for s in deps):
                        node["status"] = "blocked"
                    elif all(s == "passed" for s in deps) and len(running) < 4:
                        group = definition.exclusive_group
                        if group and group in groups:
                            continue
                        log = (directory / (key + ".log")).open("w")
                        started[key] = time.monotonic()
                        node["started_at"] = datetime.now(UTC).isoformat()
                        try:
                            arguments: Sequence[str] = list(definition.command)
                            if definition.execution_context == "host":
                                if self.location is ExecutionLocation.CONTAINER:
                                    raise OSError(
                                        "This gate requires the host coordinator: invoke the selected explicit quality workflow through the Docker host executor."
                                    )
                            elif self.location is ExecutionLocation.HOST:
                                arguments = self.app_command(arguments)
                            process = self.spawn(
                                arguments,
                                cwd=self.workspace.root,
                                env=self.environment,
                                stdout=log,
                                stderr=subprocess.STDOUT,
                                start_new_session=True,
                            )
                        except OSError:
                            log.write("Required quality command could not be started.\n")
                            log.close()
                            node.update(
                                status="failed",
                                exit_code=127,
                                finished_at=datetime.now(UTC).isoformat(),
                                duration_seconds=round(time.monotonic() - started.pop(key), 3),
                            )
                            continue
                        running[key] = (process, log)
                        node.update(status="running", pid=process.pid)
                        state["last_step"] = key
                        if group:
                            groups.add(group)
                save(directory, state, anchor=self.workspace.root)
                if not running and all(n["status"] != "pending" for n in state["steps"].values()):
                    state["status"] = (
                        "failed"
                        if any(
                            n["status"] in ("failed", "blocked")
                            and definitions[k].severity == "hard_fail"
                            for k, n in state["steps"].items()
                        )
                        else "passed"
                    )
                    break
                time.sleep(0.1)
        finally:
            for key, (process, log) in running.items():
                try:
                    reap(process, 10)
                finally:
                    log.close()
                    state["steps"][key]["status"] = "interrupted"
                    state["status"] = "interrupted"
            state.update(
                finished_at=datetime.now(UTC).isoformat(),
                duration_seconds=round(time.monotonic() - run_started, 3),
            )
            try:
                save(directory, state, anchor=self.workspace.root)
            finally:
                for sig, handler in old.items():
                    signal.signal(sig, handler)
        return 0 if state["status"] == "passed" else 1
