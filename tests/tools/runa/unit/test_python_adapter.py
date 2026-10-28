from collections import deque
from pathlib import Path

import pytest

from tools.application.python_toolchain import artifact_path
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import InputMode, ProcessResult, ProcessSpec
from tools.maintainer.python_cli import parser
from tools.maintainer.python_cli import test_command as build_test_command


def test_literal_process_inputs_and_scripted_failure(tmp_path: Path) -> None:
    runner = RecordingRunner(deque([ProcessResult(7), OSError("injected failure")]))
    result = runner.run(
        ProcessSpec(
            ("git", "a b", "$(literal)"),
            tmp_path,
            {"A": "$raw"},
            stdin=InputMode.BYTES,
            input=b"input",
        )
    )
    assert result.returncode == 7
    assert runner.calls[0].argv == ("git", "a b", "$(literal)")
    assert runner.calls[0].input == b"input"
    assert runner.calls[0].env == {"A": "$raw"}
    with pytest.raises(OSError, match="injected"):
        runner.run(ProcessSpec(("git",), tmp_path, {}))


def test_scope_refuses_escape_and_unknown_options() -> None:
    with pytest.raises(ValueError, match="selected scope"):
        build_test_command(parser().parse_args(["test", "--path=../../python_cli.py"]))
    with pytest.raises(SystemExit) as error:
        parser().parse_args(["test", "--scope=unknown"])
    assert error.value.code == 2


@pytest.mark.parametrize("name", ["../escape", "/absolute", "a/../../escape", "a\\escape"])
def test_archive_paths_cannot_escape(tmp_path: Path, name: str) -> None:
    with pytest.raises(ValueError, match="Unsafe"):
        artifact_path(tmp_path, name)
