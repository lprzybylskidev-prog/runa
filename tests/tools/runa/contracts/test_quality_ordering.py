"""The final backend boundary covers the entire selected graph, including final tests."""

import shlex
from pathlib import Path

import pytest

from tests.tools.runa.support.quality import node
from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_execution import QualityExecutor
from tools.application.quality_models import QualityDefinition, QualityNode, quality_definition
from tools.application.quality_planning import resolve, select
from tools.application.quality_state import run_record
from tools.core.context import ExecutionLocation, WorkspaceContext

ROOT = Path(__file__).resolve().parents[4]


def boundary(key: str, command: str = "true", deps: list[str] | None = None) -> QualityNode:
    result = node(key, command, deps)
    result["terminal"] = True
    return result


@pytest.mark.parametrize("all_groups", [False, True])
def test_backend_waits_for_all_selected_validation_and_final_tests(all_groups: bool) -> None:
    definition: QualityDefinition = {
        "base": [node("config"), boundary("backend", deps=["config"])],
        "impact_areas": {"frontend": [node("browser"), node("installation")]},
        "final": [node("workflow-tests")],
        "mandatory": ["config", "workflow-tests"],
        "groups": {"all-work": ["backend", "browser", "installation"]},
    }
    graph, _ = resolve(
        definition, [], groups=None if all_groups else ["all-work"], all_groups=all_groups
    )
    assert list(graph)[-1] == "backend"
    assert set(graph["backend"]["depends_on"]) == set(graph) - {"backend"}
    assert "backend" not in graph["workflow-tests"]["depends_on"]


def test_real_owner_and_customer_graphs_have_no_backend_successor() -> None:
    reader = ConfigurationReader.for_workspace(ROOT)
    for name, key in (
        ("docs/runa/maintainer/quality/gates.yml", "backend-tests"),
        ("tools/application/quality.yml", "application-tests"),
    ):
        definition = quality_definition(reader.yaml(ROOT / name))
        for graph in (
            resolve(definition, [], all_groups=True)[0],
            select(definition, list(definition["impact_areas"]), True),
        ):
            assert graph[key]["terminal"] is True
            assert set(graph[key]["depends_on"]) == set(graph) - {key}
            assert all(
                key not in value["depends_on"] for other, value in graph.items() if other != key
            )


@pytest.mark.parametrize("code", [0, 7])
def test_executor_never_launches_backend_before_pending_validation(
    tmp_path: Path, code: int
) -> None:
    marker = tmp_path / "validated"
    backend = tmp_path / "backend"
    graph = {
        "backend": boundary(
            "backend",
            shlex.join(
                [
                    "python3",
                    "-c",
                    f"from pathlib import Path; assert Path({str(marker)!r}).exists(); Path({str(backend)!r}).touch()",
                ]
            ),
        ),
        "pending": node(
            "pending",
            shlex.join(
                ["python3", "-c", f"from pathlib import Path; Path({str(marker)!r}).touch()"]
            )
            if code == 0
            else "false",
        ),
    }
    # Even a malformed injected plan omitting terminal dependencies cannot bypass the executor.
    state = run_record(
        {
            "status": "running",
            "steps": {key: dict(value, status="pending") for key, value in graph.items()},
        }
    )
    executor = QualityExecutor(
        WorkspaceContext(tmp_path), ExecutionLocation.CONTAINER, lambda args: args
    )
    assert executor.execute(tmp_path, state) == (0 if code == 0 else 1)
    assert backend.exists() is (code == 0)
    assert state["steps"]["backend"]["status"] == ("passed" if code == 0 else "blocked")


def test_terminal_dependency_cycle_and_multiple_boundaries_are_rejected() -> None:
    for base in (
        [boundary("backend"), node("frontend", deps=["backend"])],
        [boundary("one"), boundary("two")],
    ):
        with pytest.raises(ValueError):
            select({"base": base, "impact_areas": {}, "final": []}, [])
