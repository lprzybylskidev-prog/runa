"""Opt-in deterministic external boundaries; never start a service implicitly."""

import json
from collections import deque
from dataclasses import dataclass, field
from datetime import UTC, datetime, timedelta

from tools.core.process import ProcessResult


@dataclass
class ManualClock:
    value: float = 0.0

    def monotonic(self) -> float:
        return self.value

    def now(self) -> datetime:
        return datetime(2026, 1, 1, tzinfo=UTC) + timedelta(seconds=self.value)

    def sleep(self, seconds: float) -> None:
        if seconds < 0:
            raise ValueError("Cannot move a clock backwards.")
        self.value += seconds


@dataclass
class RecordingHttp:
    outcomes: deque[object] = field(default_factory=deque)
    calls: list[tuple[str, str, object]] = field(default_factory=list)

    def request(self, method: str, url: str, body: object = None) -> object:
        self.calls.append((method, url, body))
        if not self.outcomes:
            raise AssertionError("Unexpected HTTP request: provide a scripted outcome.")
        value = self.outcomes.popleft()
        if isinstance(value, Exception):
            raise value
        return value


def compose_result(services: dict[str, object], *, project: str = "fixture") -> ProcessResult:
    """Script a Compose config response on RecordingRunner without Docker."""
    return ProcessResult(0, stdout=json.dumps({"name": project, "services": services}).encode())


def image_result(identity: str, *, labels: dict[str, str] | None = None) -> ProcessResult:
    """Script an exact image inspection; callers still exercise their own policies."""
    return ProcessResult(
        0, stdout=json.dumps([{"Id": identity, "Config": {"Labels": labels or {}}}]).encode()
    )
