"""External credential-isolated process policy over the shared stdlib runner."""

import os
import subprocess
from pathlib import Path

from tools.core.process import InputMode, OutputMode, ProcessSpec, ProcessTimeout, SubprocessRunner


class ExternalProcesses:
    def __init__(self, runner=None, environment=None):
        self.runner = runner or SubprocessRunner()
        self.environment = dict(os.environ if environment is None else environment)

    def run(
        self,
        arguments,
        *,
        cwd=None,
        env=None,
        input=None,
        timeout=None,
        check=False,
        capture_output=False,
        text=False,
        stdin=None,
        stdout=None,
        stderr=None,
    ):
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
            raise subprocess.TimeoutExpired(["external command"], timeout) from None
        if check and result.returncode:
            raise subprocess.CalledProcessError(result.returncode, ["external command"])
        return subprocess.CompletedProcess(
            arguments,
            result.returncode,
            result.stdout.decode() if text else result.stdout,
            result.stderr.decode() if text else result.stderr,
        )
