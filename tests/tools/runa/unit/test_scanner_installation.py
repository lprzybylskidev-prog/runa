"""Current scanner reuse stays bound to each workspace and needs no download."""

from collections import deque
from pathlib import Path
from unittest.mock import patch

from tools.application.processes import ApplicationProcesses
from tools.application.scanner_installation import install
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext
from tools.core.process import ProcessResult


def test_current_scanners_in_two_workspaces_do_not_share_process_identity(tmp_path: Path) -> None:
    runner = RecordingRunner(deque([ProcessResult(0, b"8.30.1\n")] * 2))
    processes = ApplicationProcesses(runner, {})
    with (
        patch("tools.application.scanner_installation.platform.machine", return_value="x86_64"),
        patch("tools.application.scanner_installation.urlopen") as download,
    ):
        for name in ("one", "two"):
            workspace = WorkspaceContext(tmp_path / name)
            scanner = workspace.runtime("tools") / "gitleaks"
            scanner.parent.mkdir(parents=True)
            scanner.touch()
            install(workspace, processes)
        download.assert_not_called()
    assert [call.cwd for call in runner.calls] == [tmp_path / "one", tmp_path / "two"]
    for call in runner.calls:
        assert call.argv == (str(call.cwd / ".runtime/tools/gitleaks"), "version")
