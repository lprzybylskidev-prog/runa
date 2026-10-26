"""Owner tool lifecycle composed over core process and lock contracts."""

from collections.abc import Mapping
from dataclasses import dataclass

from tools.core.context import WorkspaceContext
from tools.core.lifecycle import interrupt_signals
from tools.core.process import InputMode, ProcessRunner, ProcessSpec
from tools.core.state import FileLock, StateDirectory


@dataclass(frozen=True)
class PythonToolExecutor:
    workspace: WorkspaceContext
    runner: ProcessRunner
    environment: Mapping[str, str]

    def execute(self, command: tuple[str, ...]) -> int:
        state = self.workspace.runtime("python")
        (state / "reports").mkdir(parents=True, exist_ok=True)
        (state / "cache/pyright").mkdir(parents=True, exist_ok=True)
        lock = FileLock(
            StateDirectory(
                self.workspace.runtime("tests"), private=False, anchor=self.workspace.root
            ),
            "suite.lock",
            "Another test suite is running in this checkout.",
            private=False,
        )
        with lock.acquire(), interrupt_signals():
            result = self.runner.run(
                ProcessSpec(
                    command,
                    self.workspace.root,
                    self.environment,
                    stdin=InputMode.INHERIT,
                )
            )
            return result.returncode
