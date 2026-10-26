"""Recording process support; no real process, network or Docker by default."""

from collections import deque
from dataclasses import dataclass, field

from tools.core.process import ProcessResult, ProcessSpec


@dataclass
class RecordingRunner:
    outcomes: deque[ProcessResult | Exception] = field(default_factory=deque)
    calls: list[ProcessSpec] = field(default_factory=list)

    def run(self, spec: ProcessSpec) -> ProcessResult:
        self.calls.append(spec)
        if not self.outcomes:
            raise AssertionError("Unexpected process execution: provide a scripted outcome.")
        result = self.outcomes.popleft()
        if isinstance(result, Exception):
            raise result
        return result
