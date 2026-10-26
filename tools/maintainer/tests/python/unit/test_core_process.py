import os
import signal
import sys
from collections import deque
from datetime import UTC, datetime
from io import StringIO
from pathlib import Path
from threading import Timer

import pytest

from tools.core.context import WorkspaceContext
from tools.core.lifecycle import Cancellation
from tools.core.process import (
    InputMode,
    OutputMode,
    ProcessSpec,
    ProcessTimeout,
    SubprocessRunner,
)
from tools.core.reporting import CommandResult, SafeFailure, TextReporter, command_boundary
from tools.core.state import FileLock, StateDirectory
from tools.maintainer.python_execution import PythonToolExecutor


def test_literal_argv_bytes_and_native_status_do_not_use_shell(tmp_path: Path) -> None:
    environment = {"RAW": "$literal"}
    spec = ProcessSpec(
        (
            sys.executable,
            "-c",
            "import os,sys;sys.stdout.buffer.write(sys.stdin.buffer.read());"
            "print(sys.argv[1], os.environ['RAW']);sys.exit(7)",
            "$(touch forbidden)",
        ),
        tmp_path,
        environment,
        stdin=InputMode.BYTES,
        input=b"bytes\x00input\n",
        output=OutputMode.CAPTURE,
        timeout=5,
    )
    environment["RAW"] = "changed"
    result = SubprocessRunner().run(spec)
    assert result.returncode == 7
    assert result.stdout == b"bytes\x00input\n$(touch forbidden) $literal\n"
    assert not (tmp_path / "forbidden").exists()
    with pytest.raises(SafeFailure) as error:
        result.checked()
    assert error.value.exit_code == 7
    assert "$literal" not in str(error.value)


def test_closed_stdin_and_missing_executable_are_safe(tmp_path: Path) -> None:
    result = SubprocessRunner().run(
        ProcessSpec(
            (sys.executable, "-c", "import sys;print(len(sys.stdin.buffer.read()))"),
            tmp_path,
            {},
            output=OutputMode.CAPTURE,
            timeout=5,
        )
    )
    assert result.stdout == b"0\n"
    with pytest.raises(SafeFailure) as error:
        SubprocessRunner().run(ProcessSpec(("/missing/secret-value",), tmp_path, {}))
    assert "secret-value" not in str(error.value)


class DeadlineClock:
    def __init__(self) -> None:
        self.times = deque([0.0, 1.0, 6.0])

    def monotonic(self) -> float:
        return self.times.popleft()

    def now(self) -> datetime:
        return datetime(2026, 10, 26, tzinfo=UTC)


def test_injected_clock_controls_timeout(tmp_path: Path) -> None:
    with pytest.raises(ProcessTimeout):
        SubprocessRunner(clock=DeadlineClock()).run(
            ProcessSpec(
                (sys.executable, "-c", "import time;time.sleep(30)"),
                tmp_path,
                {},
                timeout=5,
                terminate_grace=0.1,
            )
        )


def test_timeout_reaps_process_group_before_releasing_lock(tmp_path: Path) -> None:
    pid_file = tmp_path / "child.pid"
    code = (
        "import os,signal,time,pathlib;"
        "signal.signal(signal.SIGTERM,signal.SIG_IGN);"
        "pid=os.fork();"
        f"pathlib.Path({str(pid_file)!r}).write_text(str(os.getpid())) if pid==0 else None;"
        "time.sleep(30)"
    )
    lock = FileLock(StateDirectory(tmp_path / "private", anchor=tmp_path), "operation.lock")
    with pytest.raises(ProcessTimeout), lock.acquire():
        SubprocessRunner().run(
            ProcessSpec(
                (sys.executable, "-c", code),
                tmp_path,
                {},
                timeout=1,
                terminate_grace=0.1,
            )
        )
    pid = int(pid_file.read_text())
    with lock.acquire():
        # Orphan zombies await the host's init; they cannot execute or retain locks.
        status = Path(f"/proc/{pid}/stat")
        if status.exists():
            assert status.read_text().split(") ", 1)[1].startswith("Z")


def test_cancellation_releases_suite_lock_and_restores_signals(tmp_path: Path) -> None:
    before = signal.getsignal(signal.SIGTERM)
    cancellation = Cancellation()
    timer = Timer(0.2, cancellation.cancel)
    executor = PythonToolExecutor(WorkspaceContext(tmp_path), SubprocessRunner(cancellation), {})
    timer.start()
    try:
        with pytest.raises(KeyboardInterrupt):
            executor.execute((sys.executable, "-c", "import time;time.sleep(30)"))
    finally:
        timer.join()
    assert signal.getsignal(signal.SIGTERM) == before
    with FileLock(
        StateDirectory(tmp_path / ".runtime/tests", private=False, anchor=tmp_path), "suite.lock"
    ).acquire():
        pass


def test_real_sigterm_reaps_and_restores_handler(tmp_path: Path) -> None:
    before = signal.getsignal(signal.SIGTERM)
    timer = Timer(0.2, lambda: os.kill(os.getpid(), signal.SIGTERM))
    executor = PythonToolExecutor(WorkspaceContext(tmp_path), SubprocessRunner(), {})
    timer.start()
    try:
        with pytest.raises(KeyboardInterrupt):
            executor.execute((sys.executable, "-c", "import time;time.sleep(30)"))
    finally:
        timer.join()
    assert signal.getsignal(signal.SIGTERM) == before


def test_command_boundary_does_not_report_sensitive_exception_payloads() -> None:
    stream = StringIO()
    reporter = TextReporter(stream)

    def unavailable() -> CommandResult:
        raise OSError("credential=secret")

    assert command_boundary(unavailable, reporter) == 1
    assert "secret" not in stream.getvalue()
    assert command_boundary(lambda: CommandResult(7), reporter) == 7
    redacted = StringIO()
    TextReporter(redacted, secrets=("secret-value",)).error("Refused secret-value")
    assert redacted.getvalue() == "Refused [redacted]\n"


def test_byte_input_survives_slow_consumer_and_bidirectional_backpressure(tmp_path: Path) -> None:
    payload = b"x" * 300_000
    code = (
        "import sys,time; time.sleep(.2); "
        'sys.stdout.buffer.write(b"o"*100000); sys.stdout.flush(); '
        'sys.stderr.buffer.write(b"e"*100000); sys.stderr.flush(); '
        "data=sys.stdin.buffer.read(); print(len(data))"
    )
    result = SubprocessRunner().run(
        ProcessSpec(
            (sys.executable, "-c", code),
            tmp_path,
            dict(os.environ),
            stdin=InputMode.BYTES,
            input=payload,
            output=OutputMode.CAPTURE,
            timeout=5,
        )
    )
    assert result.returncode == 0
    assert result.stdout == b"o" * 100_000 + b"300000\n"
    assert result.stderr == b"e" * 100_000
