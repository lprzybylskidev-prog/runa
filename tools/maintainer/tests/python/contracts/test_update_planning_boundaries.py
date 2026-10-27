"""External planning composition preserves dry-run identity and cleanup boundaries."""

import json
from collections.abc import Iterator, Mapping
from pathlib import Path
from unittest import TestCase

import pytest

from distribution import bootstrap
from distribution.update_contracts import Blocker
from distribution.update_models import PlannedModuleResult, RuntimeSnapshot
from distribution.update_plan_models import release_identity
from distribution.update_planning import UpdatePlanner
from tools.maintainer.tests.python.support.updater import UpdaterWorkspace


@pytest.fixture
def workspace() -> Iterator[UpdaterWorkspace]:
    cleanup = TestCase()
    try:
        yield UpdaterWorkspace(cleanup.addCleanup)
    finally:
        cleanup.doCleanups()


def test_preflight_projects_release_identity_and_propagates_selected_dependencies(
    workspace: UpdaterWorkspace,
) -> None:
    before = workspace.snapshot()
    downloads: list[tuple[str, str | None]] = []
    probes: list[tuple[Path, str]] = []

    def download(
        client: bootstrap.GitHub,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
    ) -> Mapping[str, object]:
        assert client is workspace.client and mode == "dev"
        downloads.append((tag, expected_commit))
        return {**workspace.download(client, tag, destination, mode), "token": "private-sentinel"}

    def lock_probe(candidate: Path, image: str) -> str:
        assert not candidate.is_relative_to(workspace.installed)
        probes.append((candidate, image))
        return "sha256:" + "a" * 64

    planner = UpdatePlanner(
        download=download,
        runtime_probe=lambda root, mode, operator: workspace.runtime,
        lock_probe=lock_probe,
    )
    result = planner.preflight(
        workspace.installed,
        workspace.client,
        "v0.3.0",
        toolchain="selected:toolchain",
        expected_commit=workspace.after,
    )
    assert result["status"] == "ready", result
    assert downloads == [("v0.2.1", None), ("v0.3.0", workspace.after)]
    assert [image for _, image in probes] == ["selected:toolchain"]
    assert result.get("toolchain_image") == "sha256:" + "a" * 64
    assert result.get("dependency_checks") == ["development"]
    assert result.get("target_release") == {
        "tag": "v0.3.0",
        "commit": workspace.after,
        "tag_object": "f" * 40,
    }
    assert "private-sentinel" not in json.dumps(result)
    assert result["temporary_downloads_cleaned"] is True
    assert all(not candidate.exists() for candidate, _ in probes)
    assert workspace.snapshot() == before


def test_preflight_invalid_acquired_identity_blocks_and_cleans_before_probes(
    workspace: UpdaterWorkspace,
) -> None:
    before = workspace.snapshot()

    def download(
        client: bootstrap.GitHub,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
    ) -> Mapping[str, object]:
        identity = workspace.download(client, tag, destination, mode)
        return {**identity, "commit": ["private-sentinel"]}

    def unexpected(candidate: Path, image: str) -> str:
        pytest.fail("Malformed release identity must block before dependency probes.")

    result = UpdatePlanner(download=download, lock_probe=unexpected).preflight(
        workspace.installed, workspace.client, "v0.3.0"
    )
    assert result["status"] == "blocked"
    assert result["checks"][-1]["id"] == "invalid-preflight-input"
    assert "private-sentinel" not in json.dumps(result)
    assert result["temporary_downloads_cleaned"] is True
    assert workspace.snapshot() == before
    for invalid in ({}, {"tag": "v0.3.0", "commit": "a" * 40, "tag_object": False}):
        with pytest.raises(ValueError):
            release_identity(invalid)


def test_preflight_override_retains_pending_module_work_in_same_release_plan(
    workspace: UpdaterWorkspace,
) -> None:
    workspace.runtime["seed_runs"] = [
        {"release_sha": workspace.before, "seeder": "Runa\\Database\\Seeders\\DatabaseSeeder"}
    ]
    module: PlannedModuleResult = {
        "module": "runa.fixture",
        "from": "1.0.0",
        "to": "1.0.0",
        "migrations": [],
        "upgrades": [],
        "fingerprint": "a" * 64,
        "completed_steps": [],
        "pending_migrations": [],
        "pending_upgrades": ["repair"],
    }
    planner = UpdatePlanner(
        download=workspace.download,
        runtime_probe=lambda root, mode, operator: workspace.runtime,
        lock_probe=lambda candidate, image: "sha256:" + "a" * 64,
        module_plan=lambda root, target, runtime, image: [module],
    )
    pending = planner.preflight(workspace.installed, workspace.client, "v0.2.1")
    assert pending["status"] == "ready", pending
    pending_plan = pending.get("plan")
    assert pending_plan is not None
    assert pending_plan["platform_migrations"] == []
    assert pending_plan["platform_seeder"]["already_completed"] is True
    assert pending_plan["no_op"] is False
    completed = planner.preflight(
        workspace.installed,
        workspace.client,
        "v0.2.1",
        module_plan=lambda root, target, runtime, image: [],
    )
    assert completed["status"] == "ready", completed
    completed_plan = completed.get("plan")
    assert completed_plan is not None and completed_plan["no_op"] is True
    # Request-local overrides must not replace the planner's composed dependency.
    repeated = planner.preflight(workspace.installed, workspace.client, "v0.2.1").get("plan")
    assert repeated is not None and repeated["no_op"] is False


def test_preflight_module_refusal_retains_partial_plan_and_cleans_downloads(
    workspace: UpdaterWorkspace,
) -> None:
    before = workspace.snapshot()

    def refuse(
        root: Path, target: Path, runtime: RuntimeSnapshot, image: str
    ) -> list[PlannedModuleResult]:
        raise Blocker("module-preflight", "Module plan is unavailable.")

    result = workspace.run_plan(module_plan=refuse)
    assert result["status"] == "blocked"
    assert result["checks"][-1]["id"] == "module-preflight"
    plan = result.get("plan")
    assert plan is not None
    assert plan["platform_migrations"] == ["2026_01_02_000001_upgrade"]
    assert "modules" not in plan
    assert result["temporary_downloads_cleaned"] is True
    assert workspace.snapshot() == before
