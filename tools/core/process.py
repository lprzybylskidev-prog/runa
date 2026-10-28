"""Literal process execution with explicit policies and owned process groups."""

from __future__ import annotations

import math
import os
import selectors
import signal
import subprocess
import time
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
    STDERR = "stderr"
    QUIET = "quiet"


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
    output_limit: int | None = None

    def __post_init__(self) -> None:
        absolute_path(self.cwd)
        if self.output_limit is not None and (
            self.output_limit < 1 or self.output is not OutputMode.CAPTURE
        ):
            raise ValueError("Output limits require captured output and a positive bound.")
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

    def checked(self) -> ProcessResult:
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
        clock = SystemClock()
        deadline = clock.monotonic() + grace
        signal_group(child, signal.SIGTERM)
        try:
            child.wait(timeout=grace)
            # A transport leader can exit before its children finish their own
            # cleanup (for example Playwright stopping its separate web server).
            # Honour the same bounded grace for surviving process-group members.
            while clock.monotonic() < deadline:
                try:
                    os.killpg(child.pid, 0)
                except ProcessLookupError:
                    break
                time.sleep(min(0.02, max(0, deadline - clock.monotonic())))
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
        output = (
            subprocess.PIPE
            if spec.output is OutputMode.CAPTURE
            else (
                2
                if spec.output is OutputMode.STDERR
                else (subprocess.DEVNULL if spec.output is OutputMode.QUIET else None)
            )
        )
        started = self.clock.monotonic()
        try:
            child = subprocess.Popen(
                spec.argv,
                cwd=spec.cwd,
                env=spec.env,
                stdin=stdin,
                stdout=output,
                stderr=None if spec.output is OutputMode.QUIET else output,
                start_new_session=True,
            )
        except OSError:
            raise SafeFailure(
                "Unable to start child process; check access and required tools."
            ) from None
        try:
            if spec.output_limit is not None or spec.stdin is InputMode.BYTES:
                buffers = [bytearray(), bytearray()]
                total = 0
                input_offset = 0
                payload = spec.input or b""
                with selectors.DefaultSelector() as selector:
                    for stream, channel in ((child.stdout, 0), (child.stderr, 1)):
                        if stream is not None:
                            selector.register(stream, selectors.EVENT_READ, channel)
                    if child.stdin is not None:
                        if payload:
                            selector.register(child.stdin, selectors.EVENT_WRITE, 2)
                        else:
                            child.stdin.close()
                    while selector.get_map() or child.poll() is None:
                        if self.cancellation is not None:
                            self.cancellation.checkpoint()
                        if (
                            spec.timeout is not None
                            and self.clock.monotonic() - started >= spec.timeout
                        ):
                            raise ProcessTimeout
                        for key, _ in selector.select(0.1):
                            if key.data == 2:
                                try:
                                    input_offset += os.write(
                                        key.fd, payload[input_offset : input_offset + 4096]
                                    )
                                except BrokenPipeError:
                                    input_offset = len(payload)
                                if input_offset >= len(payload):
                                    selector.unregister(key.fd)
                                    assert child.stdin is not None
                                    child.stdin.close()
                                continue
                            chunk = os.read(key.fd, 4096)
                            if not chunk:
                                selector.unregister(key.fd)
                                continue
                            total += len(chunk)
                            if spec.output_limit is not None and total > spec.output_limit:
                                raise SafeFailure("Child output exceeded the transport limit.")
                            buffers[int(key.data)].extend(chunk)
                    return ProcessResult(child.wait(), bytes(buffers[0]), bytes(buffers[1]))
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
                    stdout, stderr = child.communicate(timeout=interval)
                    assert child.returncode is not None
                    return ProcessResult(child.returncode, stdout or b"", stderr or b"")
                except subprocess.TimeoutExpired:
                    continue
        finally:
            try:
                reap(child, spec.terminate_grace)
            finally:
                for stream in (child.stdin, child.stdout, child.stderr):
                    if stream is not None:
                        stream.close()
