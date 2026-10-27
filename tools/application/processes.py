"""Application command policies over the core runner; no implicit workspace or environment."""

import subprocess
import sys
from collections.abc import Mapping, Sequence
from dataclasses import dataclass, field
from pathlib import Path
from typing import Literal, TextIO, overload

from tools.core.process import InputMode, OutputMode, ProcessRunner, ProcessSpec, ProcessTimeout


@dataclass(frozen=True)
class ApplicationProcesses:
    runner: ProcessRunner
    environment: Mapping[str, str] = field(repr=False)

    @overload
    def run(
        self,
        argv: Sequence[str],
        *,
        cwd: Path,
        input: bytes | str | None = None,
        capture_output: bool = False,
        text: Literal[True],
        check: bool = False,
        timeout: float | None = None,
        env: Mapping[str, str] | None = None,
        stdin: int | None = None,
        stdout: int | TextIO | None = None,
        stderr: int | TextIO | None = None,
    ) -> subprocess.CompletedProcess[str]: ...

    @overload
    def run(
        self,
        argv: Sequence[str],
        *,
        cwd: Path,
        input: bytes | str | None = None,
        capture_output: bool = False,
        text: Literal[False] = False,
        check: bool = False,
        timeout: float | None = None,
        env: Mapping[str, str] | None = None,
        stdin: int | None = None,
        stdout: int | TextIO | None = None,
        stderr: int | TextIO | None = None,
    ) -> subprocess.CompletedProcess[bytes]: ...

    def run(
        self,
        argv: Sequence[str],
        *,
        cwd: Path,
        input: bytes | str | None = None,
        capture_output: bool = False,
        text: bool = False,
        check: bool = False,
        timeout: float | None = None,
        env: Mapping[str, str] | None = None,
        stdin: int | None = None,
        stdout: int | TextIO | None = None,
        stderr: int | TextIO | None = None,
    ) -> subprocess.CompletedProcess[str] | subprocess.CompletedProcess[bytes]:
        redirected = stdout is not None or stderr is not None
        if redirected and not (
            stdout is sys.stderr
            and stderr is sys.stderr
            or stdout == subprocess.DEVNULL
            and stderr in (None, subprocess.DEVNULL)
        ):
            raise ValueError("Unsupported application stream redirection.")
        try:
            result = self.runner.run(
                ProcessSpec(
                    tuple(argv),
                    cwd,
                    self.environment if env is None else env,
                    stdin=InputMode.BYTES
                    if input is not None
                    else (InputMode.CLOSED if stdin == subprocess.DEVNULL else InputMode.INHERIT),
                    input=input.encode() if isinstance(input, str) else input,
                    output=OutputMode.CAPTURE
                    if capture_output
                    else (
                        OutputMode.QUIET
                        if stdout == subprocess.DEVNULL
                        else (OutputMode.STDERR if redirected else OutputMode.INHERIT)
                    ),
                    timeout=timeout,
                )
            )
        except ProcessTimeout:
            raise subprocess.TimeoutExpired(["application command"], timeout) from None
        if check and result.returncode:
            raise subprocess.CalledProcessError(result.returncode, ["application command"])
        if text:
            return subprocess.CompletedProcess(
                argv, result.returncode, result.stdout.decode(), result.stderr.decode()
            )
        return subprocess.CompletedProcess(argv, result.returncode, result.stdout, result.stderr)

    def check_output(self, argv: Sequence[str], *, cwd: Path) -> bytes:
        return self.run(argv, cwd=cwd, capture_output=True, check=True).stdout
