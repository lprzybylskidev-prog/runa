"""Migration diagnostics select the native disposable-worker runner without starting it."""

import sys
from collections import deque
from unittest.mock import patch

from tools.application.testing.support.process import RecordingRunner
from tools.core.process import ProcessResult
from tools.maintainer import owner_cli


def test_migration_diagnostic_uses_one_native_worker() -> None:
    runner = RecordingRunner(deque([ProcessResult(0)]))
    with (
        patch.object(sys, "argv", ["owner_cli", "check-database-migrations"]),
        patch.object(owner_cli, "inside", return_value=True),
        patch.object(owner_cli, "container"),
        patch("tools.core.process.SubprocessRunner", return_value=runner),
    ):
        owner_cli.main()
    assert len(runner.calls) == 1
    call = runner.calls[0]
    assert call.argv == (
        "python3",
        "tools/maintainer/test-backend",
        "--parallel",
        "--processes=1",
        "--filter=DatabaseTest",
    )
    assert call.cwd == owner_cli.ROOT
