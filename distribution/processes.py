"""External credential-isolated process policy over the shared stdlib runner."""

import os
import subprocess
from collections.abc import Mapping, Sequence
from pathlib import Path
from typing import Literal, overload

from tools.core.process import (
    InputMode,
    OutputMode,
    ProcessRunner,
    ProcessSpec,
    ProcessTimeout,
    SubprocessRunner,
)


class ExternalProcesses:
    def __init__(
        self, runner: ProcessRunner | None = None, environment: Mapping[str, str] | None = None
    ) -> None:
        self.runner = runner or SubprocessRunner()
        self.environment = dict(os.environ if environment is None else environment)

    @overload
    def run(
        self,
        arguments: Sequence[str],
        *,
        cwd: Path | None = None,
        env: Mapping[str, str] | None = None,
        input: bytes | str | None = None,
        timeout: float | None = None,
        check: bool = False,
        capture_output: bool = False,
        text: Literal[True],
        stdin: int | None = None,
        stdout: int | None = None,
        stderr: int | None = None,
    ) -> subprocess.CompletedProcess[str]: ...

    @overload
    def run(
        self,
        arguments: Sequence[str],
        *,
        cwd: Path | None = None,
        env: Mapping[str, str] | None = None,
        input: bytes | str | None = None,
        timeout: float | None = None,
        check: bool = False,
        capture_output: bool = False,
        text: Literal[False] = False,
        stdin: int | None = None,
        stdout: int | None = None,
        stderr: int | None = None,
    ) -> subprocess.CompletedProcess[bytes]: ...

    def run(
        self,
        arguments: Sequence[str],
        *,
        cwd: Path | None = None,
        env: Mapping[str, str] | None = None,
        input: bytes | str | None = None,
        timeout: float | None = None,
        check: bool = False,
        capture_output: bool = False,
        text: bool = False,
        stdin: int | None = None,
        stdout: int | None = None,
        stderr: int | None = None,
    ) -> subprocess.CompletedProcess[str] | subprocess.CompletedProcess[bytes]:
        environment = self.environment if env is None else env
        if stdout not in (None, subprocess.DEVNULL) or stderr not in (None, subprocess.DEVNULL):
            raise ValueError("External process redirection is unsupported.")
        try:
            result = self.runner.run(
                ProcessSpec(
                    tuple(arguments),
                    Path(cwd or Path.cwd()).absolute(),
                    dict(environment),
                    stdin=InputMode.BYTES if input is not None else InputMode.CLOSED,
                    input=input.encode() if isinstance(input, str) else input,
                    output=OutputMode.CAPTURE
                    if capture_output
                    else (OutputMode.QUIET if stdout == subprocess.DEVNULL else OutputMode.INHERIT),
                    timeout=timeout,
                )
            )
        except ProcessTimeout:
            assert timeout is not None
            raise subprocess.TimeoutExpired(["external command"], timeout) from None
        if check and result.returncode:
            raise subprocess.CalledProcessError(result.returncode, ["external command"])
        if text:
            return subprocess.CompletedProcess(
                arguments, result.returncode, result.stdout.decode(), result.stderr.decode()
            )
        return subprocess.CompletedProcess(
            arguments, result.returncode, result.stdout, result.stderr
        )
