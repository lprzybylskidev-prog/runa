"""Physical customer tooling composition without private source or test imports."""

import json
import os
from collections import deque

import pytest

from tools.application.testing.runner import APPLICATION, Selection, application_layout
from tools.application.testing.runner import TestService as SuiteService
from tools.application.testing.support.process import RecordingRunner
from tools.application.testing.support.workspaces import workspace
from tools.core.context import WorkspaceContext
from tools.core.process import ProcessResult, SubprocessRunner
from tools.maintainer.python_packages import stage
from tools.maintainer.python_toolchain import PYTHON


def test_application_owned_roots_can_extend_tooling_without_private_discovery(tmp_path):
    fixture = workspace(tmp_path)
    fixture.write(
        "src/app/testing.json",
        json.dumps(
            {"schema_version": 1, "python_roots": ["tests/python", "modules/example/tests/python"]}
        ),
    )
    fixture.write(
        "src/app/modules/example/tests/python/test_extension.py", "def test_extension(): pass\n"
    )
    fixture.write(
        "tools/maintainer/tests/python/test_private.py", "raise AssertionError('private')\n"
    )
    layout = application_layout(tmp_path)
    service = SuiteService(WorkspaceContext(tmp_path), layout, RecordingRunner(), {})
    command = service.command("tooling", Selection(("tooling",), filter="extension"))
    assert str(tmp_path / "src/app/modules/example/tests/python") in command
    assert not any("tools/maintainer" in argument for argument in command)
    fixture.write(
        "src/app/testing.json",
        json.dumps({"schema_version": 1, "python_roots": ["../../tools/maintainer/tests/python"]}),
    )
    with pytest.raises(ValueError):
        application_layout(tmp_path)


def test_projected_customer_runs_real_python_extension_and_reports_collection_failure(tmp_path):
    stage("application", tmp_path)
    fixture = workspace(tmp_path)
    fixture.write("src/app/tooling/calculation.py", "def total(values):\n    return sum(values)\n")
    fixture.write(
        "src/app/tests/python/test_extension.py",
        """from collections import deque
import sys
from src.app.tooling.calculation import total
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import ProcessResult, ProcessSpec
from pathlib import Path
def test_extension():
    assert total([2, 3]) == 5
    runner = RecordingRunner(deque([ProcessResult(7)]))
    assert runner.run(ProcessSpec(("literal $(false)",), Path.cwd(), {})).returncode == 7
    assert not any(name.startswith("tools.maintainer") for name in sys.modules)
""",
    )
    python = tmp_path / ".runtime/python/venv/bin/python"
    python.parent.mkdir(parents=True)
    python.symlink_to(PYTHON)
    service = SuiteService(
        WorkspaceContext(tmp_path),
        APPLICATION,
        SubprocessRunner(),
        {key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
    )
    assert not (tmp_path / "tools/maintainer").exists()
    assert not (tmp_path / "distribution").exists()
    assert service.execute(Selection(("tooling",))) == 0
    assert service.execute(Selection(("tooling",), filter="missing_selection")) == 5
    evidence = json.loads((tmp_path / ".runtime/tests/application-results.json").read_text())
    assert evidence["scopes"][0]["status"] == "failed"


def test_projected_customer_aggregate_native_scopes_keep_ownership_and_all_results(tmp_path):
    stage("application", tmp_path)
    fixture = workspace(tmp_path)
    for name in (
        "src/app/tests/Feature/CustomerTest.php",
        "src/app/tests/frontend/customer.test.ts",
        "src/app/tests/python/test_customer.py",
        ".runtime/python/venv/bin/python",
    ):
        fixture.write(name, "")
    runner = RecordingRunner(deque(ProcessResult(code) for code in (0, 7, 0, 3, 0)))
    service = SuiteService(WorkspaceContext(tmp_path), APPLICATION, runner, {})
    assert service.execute(Selection()) == 1
    assert not any(
        "runa/tests" in argument or "maintainer" in argument
        for call in runner.calls
        for argument in call.argv
    )
    assert (
        len(
            json.loads((tmp_path / ".runtime/tests/application-results.json").read_text())["scopes"]
        )
        == 3
    )
