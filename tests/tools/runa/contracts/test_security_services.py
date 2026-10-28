"""Strict scans, all-result collection and actual content-bound review evidence."""

from __future__ import annotations

import hashlib
import json
from collections import deque
from pathlib import Path
from typing import TypedDict

import pytest

from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.application.secret_scanner import SecretScanner
from tools.application.security_service import SecurityService
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import ProcessResult, ProcessRunner, ProcessSpec


class ImageSelection(TypedDict):
    images: list[str]
    locks: dict[str, str]


def security(
    root: Path,
    runner: ProcessRunner,
    *,
    location: ExecutionLocation = ExecutionLocation.CONTAINER,
    audit_root: Path | None = None,
) -> SecurityService:
    return SecurityService(
        WorkspaceContext(root), ApplicationProcesses(runner, {}), location, audit_root
    )


def test_audits_collect_both_failures_and_keep_checkout_identities(tmp_path: Path) -> None:
    other = tmp_path / "other"
    other.mkdir()
    runner = RecordingRunner(
        deque([ProcessResult(1), ProcessResult(2), ProcessResult(0), ProcessResult(0)])
    )
    first, second = security(tmp_path, runner), security(other, runner)
    with pytest.raises(ValueError, match="Composer, pnpm"):
        first.dependencies()
    second.dependencies()
    assert [c.cwd for c in runner.calls] == [tmp_path, tmp_path, other, other]


def test_isolated_audit_refuses_different_locks_before_processes(tmp_path: Path) -> None:
    dev = tmp_path / "dev"
    (dev / "src").mkdir(parents=True)
    (tmp_path / "src").mkdir()
    (dev / "src/composer.lock").write_text("different")
    (tmp_path / "src/composer.lock").write_text("original")
    runner = RecordingRunner()
    with pytest.raises(ValueError, match="lockfiles differ"):
        security(tmp_path, runner, location=ExecutionLocation.HOST, audit_root=dev).dependencies()
    assert not runner.calls


def test_scan_collects_repository_all_images_and_both_audits(tmp_path: Path) -> None:
    binary = tmp_path / ".runtime/tools/trivy"
    binary.parent.mkdir(parents=True)
    binary.touch()
    calls: list[ProcessSpec] = []

    class Runner:
        def run(self, spec: ProcessSpec) -> ProcessResult:
            calls.append(spec)
            if "--version" in spec.argv:
                return ProcessResult(0, b"Version: 0.76.0")
            return ProcessResult(1 if spec.argv[0] == str(binary) or "audit" in spec.argv else 0)

    class Selected(SecurityService):
        def production_images(self, root: Path) -> list[str]:
            return ["image:one", "image:two"]

    target = Selected(
        WorkspaceContext(tmp_path), ApplicationProcesses(Runner(), {}), ExecutionLocation.HOST
    )
    with pytest.raises(ValueError, match="Security checks failed"):
        target.trivy(additional_images=["image:three"])
    scans = [c.argv for c in calls if c.argv[0] == str(binary) and "--version" not in c.argv]
    assert len(scans) == 4
    assert "--include-dev-deps" in scans[0]
    assert "src/vendor" in scans[0][scans[0].index("--skip-dirs") + 1]
    assert len([c for c in calls if "audit" in c.argv]) == 2
    assert not (tmp_path / ".runtime/trivy-image.tar").exists()
    assert len(list((tmp_path / ".runtime/security").glob("*.json"))) == 4


def test_missing_stale_blocking_and_changed_review_evidence_fail(tmp_path: Path) -> None:
    class Reviewed(SecurityService):
        def surface(self, reference: str = "main") -> dict[str, str]:
            return {"src/file.php": "current"}

    target = Reviewed(
        WorkspaceContext(tmp_path),
        ApplicationProcesses(RecordingRunner(), {}),
        ExecutionLocation.CONTAINER,
    )
    (tmp_path / ".runtime").mkdir()
    with pytest.raises(ValueError, match="Missing actual"):
        target.review()
    report = tmp_path / "report.md"
    report.write_text("Synthetic review fixture, not an attestation.")
    data = dict(
        reviewer="Fixture",
        reviewed_at="2026-10-26",
        report="report.md",
        report_sha256=hashlib.sha256(report.read_bytes()).hexdigest(),
        blocking_findings=[],
        surface={"src/file.php": "old"},
    )
    path = tmp_path / ".runtime/security-review.json"
    for value in [data, data | dict(surface=target.surface(), blocking_findings=["open"])]:
        path.write_text(json.dumps(value))
        with pytest.raises(ValueError, match="stale or has blocking"):
            target.review()
    path.write_text(json.dumps(data | dict(surface=target.surface())))
    target.review()
    report.write_text("changed")
    with pytest.raises(ValueError, match="report is missing or changed"):
        target.review()


def test_gitleaks_ignores_ambient_suppression_and_receives_only_snapshot(tmp_path: Path) -> None:
    binary = tmp_path / "gitleaks"
    binary.touch()
    runner = RecordingRunner(deque([ProcessResult(0)]))
    SecretScanner(
        binary, ApplicationProcesses(runner, {"GITLEAKS_CONFIG": "unsafe", "SAFE": "value"})
    ).scan(tmp_path)
    spec = runner.calls[0]
    assert "--ignore-gitleaks-allow" in spec.argv and "--redact" in spec.argv
    assert spec.env == {"SAFE": "value"}
    assert spec.argv[1:3] == ("dir", str(tmp_path))


def test_production_images_refuse_owner_context_before_processes(tmp_path: Path) -> None:
    runner = RecordingRunner()
    with pytest.raises(ValueError, match="receipt"):
        security(tmp_path, runner).production_images(tmp_path)
    assert not runner.calls


def test_production_fixture_image_identity_and_locks_are_bound_to_scanned_source(
    tmp_path: Path,
) -> None:
    from unittest.mock import patch

    fixture = tmp_path / "customer"
    fixture.mkdir()
    locks: dict[str, str] = {}
    for name in ["src/composer.lock", "src/pnpm-lock.yaml"]:
        path = tmp_path / name
        path.parent.mkdir(exist_ok=True)
        path.write_text(name)
        locks[name] = hashlib.sha256(path.read_bytes()).hexdigest()
    selected: ImageSelection = {"images": ["production:exact"], "locks": locks}
    runner = RecordingRunner(deque([ProcessResult(0, json.dumps(selected).encode())]))
    with patch.object(RepositoryPolicy, "assert_production_installation") as guard:
        assert security(tmp_path, runner).production_images(fixture) == ["production:exact"]
        guard.assert_called_once_with(fixture)
    assert runner.calls[0].cwd == fixture
    assert "sys.path" not in runner.calls[0].argv[-1]
    assert "DeploymentService.for_workspace(root)" in runner.calls[0].argv[-1]


def test_custom_service_fixture_pulls_exact_digest_and_rejects_wrong_identity() -> None:
    from types import SimpleNamespace
    from unittest.mock import patch

    from tools.maintainer import service_fixtures

    reference = service_fixtures.REDIS_IMAGE
    metadata = json.dumps([{"RepoDigests": ["redis@" + reference.split("@")[1]]}])
    with patch.object(
        service_fixtures.subprocess,
        "run",
        side_effect=[
            SimpleNamespace(returncode=1, stdout=""),
            SimpleNamespace(returncode=0),
            SimpleNamespace(returncode=0, stdout=metadata),
        ],
    ) as run:
        assert service_fixtures.redis_image() == reference
        assert run.call_args_list[1].args[0] == ["docker", "pull", reference]
    with patch.object(
        service_fixtures.subprocess,
        "run",
        return_value=SimpleNamespace(
            returncode=0, stdout='[{"RepoDigests":["redis@sha256:other"]}]'
        ),
    ):
        with pytest.raises(ValueError, match="pinned Redis artifact"):
            service_fixtures.redis_image()


@pytest.mark.parametrize("invalid", ["locks", "images"])
def test_production_image_mismatch_or_empty_selection_fails(tmp_path: Path, invalid: str) -> None:
    from unittest.mock import patch

    selected: ImageSelection = {"images": ["production:exact"], "locks": {}}
    for name in ["src/composer.lock", "src/pnpm-lock.yaml"]:
        path = tmp_path / name
        path.parent.mkdir(exist_ok=True)
        path.write_text(name)
        selected["locks"][name] = hashlib.sha256(path.read_bytes()).hexdigest()
    if invalid == "locks":
        selected["locks"]["src/composer.lock"] = "other"
    else:
        selected["images"] = []
    runner = RecordingRunner(deque([ProcessResult(0, json.dumps(selected).encode())]))
    with patch.object(RepositoryPolicy, "assert_production_installation"):
        with pytest.raises(ValueError, match="dependency locks differ|no runtime images"):
            security(tmp_path, runner).production_images(tmp_path)
