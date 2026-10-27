"""Typed decoder boundaries preserve literals and refuse invalid data without payload leaks."""

from collections import deque
from functools import partial
from pathlib import Path

import pytest

from tools.application.configuration_reader import ConfigurationReader
from tools.application.development_runtime import DevelopmentRuntime
from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryBoundaryError, RepositoryPolicy
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.delivery import OWNER_PATHS
from tools.core.process import InputMode, OutputMode, ProcessResult
from tools.core.state import JsonValue


def reader(root: Path, payload: bytes) -> tuple[ConfigurationReader, RecordingRunner]:
    workspace = WorkspaceContext(root)
    runtime = DevelopmentRuntime(
        ExecutionContext(workspace, workspace, ExecutionLocation.CONTAINER)
    )
    runner = RecordingRunner(deque([ProcessResult(0, payload)]))
    return ConfigurationReader(workspace, runtime, runner, {}), runner


def test_configuration_keeps_nested_json_types_and_literal_stdin(tmp_path: Path) -> None:
    configuration, runner = reader(
        tmp_path, b'{"enabled":true,"steps":[null,3,{"value":"literal$#"}]}'
    )
    content = "value: literal$#\n"
    assert configuration.parse(content) == {
        "enabled": True,
        "steps": [None, 3, {"value": "literal$#"}],
    }
    call = runner.calls[0]
    assert call.cwd == tmp_path
    assert call.stdin is InputMode.BYTES and call.input == content.encode()
    assert call.output is OutputMode.CAPTURE


@pytest.mark.parametrize("payload", [b'{"private":"sentinel",}', b'{"number":NaN}'])
def test_configuration_invalid_decoder_output_has_a_fixed_diagnostic(
    tmp_path: Path, payload: bytes
) -> None:
    configuration, _ = reader(tmp_path, payload)
    with pytest.raises(ValueError, match="^Configuration must contain valid finite JSON values.$"):
        configuration.parse("private: sentinel")


def test_invalid_frontmatter_does_not_invoke_the_parser(tmp_path: Path) -> None:
    configuration, runner = reader(tmp_path, b"{}")
    roadmap = tmp_path / "roadmap.md"
    roadmap.write_text("# No frontmatter\n")
    with pytest.raises(ValueError, match="Missing roadmap frontmatter"):
        configuration.roadmap(roadmap)
    assert not runner.calls


def test_receipt_preserves_unknown_metadata_and_rejects_a_nonmapping(tmp_path: Path) -> None:
    (tmp_path / ".git").mkdir()
    runner = RecordingRunner()
    policy = RepositoryPolicy(partial(ApplicationProcesses(runner, {}).run, cwd=tmp_path), {})
    receipt: dict[str, JsonValue] = {
        "schema_version": 1,
        "repository": "fixture/runa",
        "projection": {"version": 2, "excluded": list(OWNER_PATHS)},
        "extension": {"retained": [None, True, "literal$#"]},
    }
    policy.write_staging_receipt(tmp_path, receipt)
    assert policy.receipt(tmp_path) == receipt
    (tmp_path / ".git/runa-bootstrap.json").write_text('["sentinel"]')
    with pytest.raises(
        RepositoryBoundaryError, match="^Unsupported installation acquisition receipt.$"
    ):
        policy.receipt(tmp_path)
    assert not runner.calls
