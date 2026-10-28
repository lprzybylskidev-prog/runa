"""Aggregate failure, ownership, filtering and resource lifecycle contracts."""

from __future__ import annotations

import json
from collections import deque
from collections.abc import Sequence
from pathlib import Path
from typing import Never

import pytest

from tools.application.testing.cli import parse
from tools.application.testing.runner import APPLICATION, Selection
from tools.application.testing.runner import TestService as SuiteService
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext
from tools.core.process import ProcessResult, ProcessSpec
from tools.core.state import JsonValue
from tools.maintainer.test_cli import OWNER


def fixture(
    root: Path, outcomes: Sequence[int] = (0, 0, 0, 0, 0)
) -> tuple[SuiteService, RecordingRunner]:
    for name in (
        "tests/backend/app/Feature/ExampleTest.php",
        "tests/frontend/app/unit/example.test.ts",
        "tests/tools/app/test_example.py",
        ".runtime/python/venv/bin/python",
    ):
        target = root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.touch()
    runner = RecordingRunner(deque(ProcessResult(code) for code in outcomes))
    return SuiteService(WorkspaceContext(root), APPLICATION, runner, {}, lambda _: None), runner


def results(root: Path) -> list[dict[str, JsonValue]]:
    return json.loads((root / ".runtime/tests/application-results.json").read_text())["scopes"]


def test_defaults_run_all_independent_scopes_and_keep_native_failures(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path, (0, 7, 0, 3, 5))
    assert service.execute(Selection()) == 1
    assert [row["exit_code"] for row in results(tmp_path)] == [7, 3, 5]
    assert len(runner.calls) == 5
    assert (
        runner.calls[0].argv
        == runner.calls[2].argv
        == ("php", "runa/testing/backend/worker-databases.php")
    )
    assert runner.calls[4].cwd == tmp_path


def test_backend_cleanup_failure_is_recorded_and_does_not_skip_frontend(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path, (0, 0, 9, 0, 0))
    assert service.execute(Selection()) == 1
    assert results(tmp_path)[0]["cleanup_exit_code"] == 9
    assert results(tmp_path)[1]["status"] == "passed"
    assert len(runner.calls) == 5


def test_missing_tooling_prerequisite_blocks_without_skipping_other_scopes(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path, (0, 0, 0, 0))
    (tmp_path / ".runtime/python/venv/bin/python").unlink()
    assert service.execute(Selection()) == 1
    assert results(tmp_path)[2]["status"] == "blocked"
    assert results(tmp_path)[2]["exit_code"] == 127
    assert len(runner.calls) == 4


def test_empty_customer_tooling_never_discovers_copied_private_tests(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path)
    (tmp_path / "tests/tools/app/test_example.py").unlink()
    private = tmp_path / "tests/tools/runa/unit/test_private.py"
    private.parent.mkdir(parents=True)
    private.write_text("raise AssertionError('private suite must never import')")
    assert service.execute(Selection(("tooling",))) == 0
    assert results(tmp_path)[0]["status"] == "empty"
    assert not runner.calls
    with pytest.raises(ValueError, match="suite roots"):
        service.command("tooling", Selection(("tooling",), python_paths=(str(private),)))


def test_owner_empty_scope_is_a_failure(tmp_path: Path) -> None:
    service = SuiteService(WorkspaceContext(tmp_path), OWNER, RecordingRunner(), {}, lambda _: None)
    assert service.execute(Selection(("tooling",))) == 1


def test_interruption_stops_scheduling_and_records_pending_scopes(tmp_path: Path) -> None:
    service, _ = fixture(tmp_path)

    # A native runner reaps its process group before raising this interruption.
    class Interrupted:
        calls: list[ProcessSpec] = []

        def run(self, spec: ProcessSpec) -> Never:
            self.calls.append(spec)
            raise KeyboardInterrupt

    interrupted = Interrupted()
    service = SuiteService(service.workspace, APPLICATION, interrupted, {}, lambda _: None)
    assert service.execute(Selection(("frontend", "tooling"))) == 130
    assert [row["status"] for row in results(tmp_path)] == ["interrupted", "blocked"]
    assert len(interrupted.calls) == 1


def test_filters_are_native_and_raw_arguments_require_an_explicit_scope(tmp_path: Path) -> None:
    service, _ = fixture(tmp_path)
    assert parse([]).scopes == ("backend", "frontend", "tooling")
    assert parse(["--scope=tooling", "--filter=invoice"]).filter == "invoice"
    assert "--testNamePattern" in service.command("frontend", Selection(filter="invoice"))
    assert "--filter=invoice" in service.command("backend", Selection(filter="invoice"))
    assert "-k" in service.command("tooling", Selection(filter="invoice"))
    with pytest.raises(ValueError, match="one explicit scope"):
        parse(["--parallel"])
    with pytest.raises(ValueError, match="unique"):
        parse(["--scope=frontend", "--scope=frontend"])


def test_scoped_native_exit_and_discovery_do_not_cleanup_workers(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path, (7,))
    assert service.execute(Selection(("backend",), ("--list-tests",))) == 7
    assert len(runner.calls) == 1
    assert "vendor/bin/pest" == runner.calls[0].argv[0]
    assert not (tmp_path / ".runtime/tests/suite.lock").exists()


def test_missing_process_continues_independent_scopes(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path)
    runner.outcomes = deque([FileNotFoundError(), ProcessResult(0)])
    assert service.execute(Selection(("frontend", "tooling"))) == 1
    assert [row["status"] for row in results(tmp_path)] == ["blocked", "passed"]


def test_failed_orphan_cleanup_never_starts_backend_but_other_scopes_run(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path, (9, 0, 0))
    assert service.execute(Selection()) == 1
    assert [row["status"] for row in results(tmp_path)] == ["blocked", "passed", "passed"]
    assert not any(call.argv[0] == "vendor/bin/pest" for call in runner.calls)


def test_nested_suite_lock_refuses_without_starting_any_child(tmp_path: Path) -> None:
    from tools.core.reporting import SafeFailure
    from tools.core.state import FileLock, StateDirectory

    service, runner = fixture(tmp_path)
    directory = StateDirectory(tmp_path / ".runtime/tests", private=False, anchor=tmp_path)
    with FileLock(directory, "suite.lock", "busy", private=False).acquire():
        with pytest.raises(SafeFailure, match="Another test suite"):
            service.execute(Selection())
    assert not runner.calls


def test_symlink_cannot_redirect_customer_discovery_to_private_suite(tmp_path: Path) -> None:
    service, runner = fixture(tmp_path)
    (tmp_path / "tests/tools/app/test_example.py").unlink()
    (tmp_path / "tests/tools/app").rmdir()
    private = tmp_path / "tests/tools/runa"
    private.mkdir(parents=True)
    (tmp_path / "tests/tools/app").symlink_to(private, target_is_directory=True)
    with pytest.raises(ValueError, match="owner's source"):
        service.execute(Selection())
    assert not runner.calls


def test_cached_php_configuration_does_not_skip_independent_frontend_or_tooling(
    tmp_path: Path,
) -> None:
    service, runner = fixture(tmp_path, (0, 0))
    cache = tmp_path / "src/bootstrap/cache/config.php"
    cache.parent.mkdir(parents=True)
    cache.touch()
    assert service.execute(Selection()) == 1
    assert [row["status"] for row in results(tmp_path)] == ["blocked", "passed", "passed"]
    assert len(runner.calls) == 2
