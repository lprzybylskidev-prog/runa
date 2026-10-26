"""Literal process execution with explicit policies and owned process groups."""

import math
import os
import signal
import subprocess
from collections.abc import Mapping
from dataclasses import dataclass, field
from enum import Enum
from pathlib import Path
from types import MappingProxyType
from typing import Protocol

from tools.core.clock import Clock, SystemClock
from tools.core.context import absolute_path
from tools.core.lifecycle import Cancellation
from tools.core.reporting import SafeFailure


class InputMode(Enum):
    INHERIT = "inherit"
    CLOSED = "closed"
    BYTES = "bytes"


class OutputMode(Enum):
    INHERIT = "inherit"
    CAPTURE = "capture"


@dataclass(frozen=True)
class ProcessSpec:
    argv: tuple[str, ...] = field(repr=False)
    cwd: Path
    env: Mapping[str, str] = field(repr=False)
    stdin: InputMode = InputMode.CLOSED
    input: bytes | None = field(default=None, repr=False)
    output: OutputMode = OutputMode.INHERIT
    timeout: float | None = None
    terminate_grace: float = 10

    def __post_init__(self) -> None:
        absolute_path(self.cwd)
        if not self.argv or not self.argv[0] or any("\0" in arg for arg in self.argv):
            raise ValueError("Process requires literal non-NUL arguments.")
        if (self.stdin is InputMode.BYTES) != (self.input is not None):
            raise ValueError("Byte input requires explicit BYTES stdin mode.")
        for duration in (self.timeout, self.terminate_grace):
            if duration is not None and (not math.isfinite(duration) or duration <= 0):
                raise ValueError("Process deadlines must be positive finite durations.")
        object.__setattr__(self, "env", MappingProxyType(dict(self.env)))


@dataclass(frozen=True)
class ProcessResult:
    returncode: int
    stdout: bytes = field(default=b"", repr=False)
    stderr: bytes = field(default=b"", repr=False)

    def checked(self) -> "ProcessResult":
        if self.returncode:
            raise SafeFailure("Child process failed.", self.returncode)
        return self


class ProcessRunner(Protocol):
    def run(self, spec: ProcessSpec) -> ProcessResult: ...


class ProcessTimeout(SafeFailure):
    def __init__(self) -> None:
        super().__init__("Child process exceeded its timeout.", 124)


def signal_group(child: subprocess.Popen[bytes], signum: int) -> None:
    try:
        os.killpg(child.pid, signum)
    except ProcessLookupError:
        pass


def reap(child: subprocess.Popen[bytes], grace: float) -> None:
    """Terminate descendants even when their leader has already exited."""
    previous = signal.pthread_sigmask(signal.SIG_BLOCK, (signal.SIGTERM, signal.SIGINT))
    try:
        signal_group(child, signal.SIGTERM)
        try:
            child.wait(timeout=grace)
        except subprocess.TimeoutExpired:
            pass
        finally:
            signal_group(child, signal.SIGKILL)
            child.wait()
    finally:
        signal.pthread_sigmask(signal.SIG_SETMASK, previous)


@dataclass(frozen=True)
class SubprocessRunner:
    cancellation: Cancellation | None = None
    clock: Clock = field(default_factory=SystemClock)

    def run(self, spec: ProcessSpec) -> ProcessResult:
        if self.cancellation is not None:
            self.cancellation.checkpoint()
        stdin = {
            InputMode.INHERIT: None,
            InputMode.CLOSED: subprocess.DEVNULL,
            InputMode.BYTES: subprocess.PIPE,
        }[spec.stdin]
        output = subprocess.PIPE if spec.output is OutputMode.CAPTURE else None
        started = self.clock.monotonic()
        try:
            child = subprocess.Popen(
                spec.argv,
                cwd=spec.cwd,
                env=spec.env,
                stdin=stdin,
                stdout=output,
                stderr=output,
                start_new_session=True,
            )
        except OSError:
            raise SafeFailure(
                "Unable to start child process; check access and required tools."
            ) from None
        pending_input = spec.input
        try:
            while True:
                if self.cancellation is not None:
                    self.cancellation.checkpoint()
                remaining = (
                    None
                    if spec.timeout is None
                    else spec.timeout - (self.clock.monotonic() - started)
                )
                if remaining is not None and remaining <= 0:
                    raise ProcessTimeout
                interval = min(remaining, 0.1) if remaining is not None else 0.1
                try:
                    stdout, stderr = child.communicate(pending_input, timeout=interval)
                    assert child.returncode is not None
                    return ProcessResult(child.returncode, stdout or b"", stderr or b"")
                except subprocess.TimeoutExpired:
                    # communicate retains partially written input across timeout retries.
                    pending_input = None
        finally:
            try:
                reap(child, spec.terminate_grace)
            finally:
                for stream in (child.stdin, child.stdout, child.stderr):
                    if stream is not None:
                        stream.close()
