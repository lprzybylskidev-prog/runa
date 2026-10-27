"""Recovery retains exact process/workspace ownership without running Docker."""

import fcntl
import json
import os
import signal
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
def test_recovery_checks_current_workspace_process_identity(tmp_path, module, allowed):
    service = HostExecutor(WorkspaceContext(tmp_path), ApplicationProcesses(RecordingRunner(), {}))
    directory = tmp_path / ".runtime/host"
    directory.mkdir(parents=True)
    credentials = directory / "client.json"
    credentials.write_text(json.dumps({"pid": os.getpid()}))
    with (directory / "daemon.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        argv = os.fsencode("/usr/bin/python3") + b"\0-m\0" + module.encode() + b"\0--serve\0"
        with (
            patch.object(Path, "read_bytes", return_value=argv),
            patch(
                "tools.application.host_transport.os.kill",
                side_effect=lambda *_: credentials.unlink(),
            ) as kill,
        ):
            if allowed:
                service.stop()
                kill.assert_called_once_with(os.getpid(), signal.SIGTERM)
            else:
                with pytest.raises(ValueError, match="ownership is uncertain"):
                    service.stop()
                kill.assert_not_called()
