"""Application command policies over the core runner; no implicit workspace or environment."""

import subprocess
import sys
from collections.abc import Mapping, Sequence
from dataclasses import dataclass, field
from pathlib import Path

from tools.core.process import InputMode, OutputMode, ProcessRunner, ProcessSpec, ProcessTimeout


@dataclass(frozen=True)
class ApplicationProcesses:
    runner: ProcessRunner
    environment: Mapping[str, str] = field(repr=False)

    def run(
        self,
        argv: Sequence[str],
        *,
        cwd: Path,
        capture_output: bool = False,
        text: bool = False,
        check: bool = False,
        timeout: float | None = None,
        env: Mapping[str, str] | None = None,
        stdin: int | None = None,
        stdout: int | None = None,
        stderr: int | None = None,
    ):
        redirected = stdout is not None or stderr is not None
        if redirected and (stdout is not sys.stderr or stderr is not sys.stderr):
            raise ValueError("Unsupported application stream redirection.")
        try:
            result = self.runner.run(
                ProcessSpec(
                    tuple(argv),
                    cwd,
                    self.environment if env is None else env,
                    stdin=InputMode.CLOSED if stdin == subprocess.DEVNULL else InputMode.INHERIT,
                    output=OutputMode.CAPTURE
                    if capture_output
                    else (OutputMode.STDERR if redirected else OutputMode.INHERIT),
                    timeout=timeout,
                )
            )
        except ProcessTimeout:
            raise subprocess.TimeoutExpired(["application command"], timeout) from None
        out = result.stdout.decode() if text else result.stdout
        err = result.stderr.decode() if text else result.stderr
        if check and result.returncode:
            raise subprocess.CalledProcessError(result.returncode, ["application command"])
        return subprocess.CompletedProcess(argv, result.returncode, out, err)

    def check_output(self, argv: Sequence[str], *, cwd: Path):
        return self.run(argv, cwd=cwd, capture_output=True, check=True).stdout
