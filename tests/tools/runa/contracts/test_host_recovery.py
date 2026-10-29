"""Recovery retains exact process/workspace ownership without running Docker."""

import fcntl
import json
import os
import signal
import unittest
from pathlib import Path
from unittest.mock import patch

import pytest

from tools.application.host_transport import HostExecutor
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext


@pytest.mark.parametrize(
    "module,allowed",
    [("tools.application.host_transport", True), ("foreign.host_transport", False)],
)
def test_recovery_checks_current_workspace_process_identity(
    tmp_path: Path, module: str, allowed: bool
) -> None:
    service = HostExecutor(WorkspaceContext(tmp_path), ApplicationProcesses(RecordingRunner(), {}))
    directory = tmp_path / ".runtime/host"
    directory.mkdir(parents=True)
    credentials = directory / "client.json"
    credentials.write_text(json.dumps({"pid": os.getpid()}))
    with (directory / "daemon.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        argv = os.fsencode("/usr/bin/python3") + b"\0-m\0" + module.encode() + b"\0--serve\0"

        def remove_credentials(pid: int, signum: int) -> None:
            credentials.unlink()

        with (
            patch.object(Path, "read_bytes", return_value=argv),
            patch(
                "tools.application.host_transport.os.kill",
                side_effect=remove_credentials,
            ) as kill,
        ):
            if allowed:
                service.stop()
                kill.assert_called_once_with(os.getpid(), signal.SIGTERM)
            else:
                with pytest.raises(ValueError, match="ownership is uncertain"):
                    service.stop()
                kill.assert_not_called()


class QueueLifetimePreservationContracts(unittest.TestCase):
    def test_only_proven_single_healthy_queue_lifetime_exit_is_accepted(self) -> None:
        from tests.tools.runa.acceptance.host_executor_host import natural_queue_rollover

        log = "2026-10-29 10:09:50 Worker STOPPED Maximum run time exceeded"
        self.assertTrue(natural_queue_rollover("queue", 1, True, True, log))
        for service, delta, running, healthy, evidence in (
            ("app", 1, True, True, log),
            ("postgres", 1, True, True, log),
            ("queue", 2, True, True, log),
            ("queue", 1, False, True, log),
            ("queue", 1, True, False, log),
            ("queue", 1, True, True, ""),
            ("queue", 1, True, True, "Worker STOPPED Memory limit exceeded"),
            ("queue", 1, True, True, log + "\n" + log),
        ):
            with self.subTest(service=service, delta=delta, running=running, healthy=healthy):
                self.assertFalse(natural_queue_rollover(service, delta, running, healthy, evidence))
