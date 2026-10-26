"""Recording process support; no real process, network or Docker by default."""

from collections import deque
from collections.abc import Mapping, Sequence
from dataclasses import dataclass, field
from pathlib import Path


@dataclass(frozen=True)
class RecordedProcess:
    argv: tuple[str, ...]
    cwd: Path
    env: tuple[tuple[str, str], ...]
    stdin: bytes | None = None


@dataclass(frozen=True)
class FakeResult:
    returncode: int = 0
    stdout: bytes = b""
    stderr: bytes = b""


@dataclass
class RecordingRunner:
    outcomes: deque[FakeResult | Exception] = field(default_factory=deque)
    calls: list[RecordedProcess] = field(default_factory=list)

    def run(
        self,
        argv: Sequence[str],
        *,
        cwd: Path,
        env: Mapping[str, str],
        stdin: bytes | None = None,
    ) -> FakeResult:
        self.calls.append(RecordedProcess(tuple(argv), cwd, tuple(sorted(env.items())), stdin))
        if not self.outcomes:
            raise AssertionError("Unexpected process execution: provide a scripted outcome.")
        result = self.outcomes.popleft()
        if isinstance(result, Exception):
            raise result
        return result
