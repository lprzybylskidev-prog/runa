from collections import deque
from pathlib import Path

import pytest

from tools.application.development_runtime import DevelopmentRuntime, development_compose
from tools.application.python_execution import PythonToolExecutor
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import (
    ExecutionContext,
    ExecutionLocation,
    ReleaseContext,
    WorkspaceContext,
)
from tools.core.process import ProcessResult


def test_two_workspace_and_release_identities_do_not_interfere(tmp_path: Path) -> None:
    operator = WorkspaceContext(tmp_path / "operator with spaces")
    first = ReleaseContext(operator, WorkspaceContext(tmp_path / "first"), "first-revision")
    second = ReleaseContext(operator, WorkspaceContext(tmp_path / "second"), "second-revision")
    before = development_compose(first.source).command("config", "--quiet")
    assert str(second.source.root) in development_compose(second.source).command()
    assert development_compose(first.source).command("config", "--quiet") == before
    assert first.operator.runtime("production") == operator.root / ".runtime/production"
    runtime = DevelopmentRuntime(ExecutionContext(operator, first.source, ExecutionLocation.HOST))
    assert runtime.app_command(("python3", "a b", "$(literal)")) == (
        *development_compose(operator).command(),
        "exec",
        "-T",
        "app",
        "python3",
        "a b",
        "$(literal)",
    )
    container = DevelopmentRuntime(
        ExecutionContext(operator, first.source, ExecutionLocation.CONTAINER)
    )
    assert container.app_command(("python3",)) == ("python3",)


def test_explicit_contexts_execute_without_ambient_cwd_or_environment_mutation(
    tmp_path: Path,
) -> None:
    runner = RecordingRunner(deque([ProcessResult(7), ProcessResult(0)]))
    for name, status in (("one", 7), ("two", 0)):
        context = WorkspaceContext(tmp_path / name)
        executor = PythonToolExecutor(context, runner, {"RAW": "$literal"})
        assert executor.execute(("test", name)) == status
        assert runner.calls[-1].cwd == context.root
        assert runner.calls[-1].env == {"RAW": "$literal"}


@pytest.mark.parametrize("path", [Path("relative"), Path("/absolute/../escape")])
def test_context_refuses_ambiguous_roots(path: Path) -> None:
    with pytest.raises(ValueError, match="absolute"):
        WorkspaceContext(path)
