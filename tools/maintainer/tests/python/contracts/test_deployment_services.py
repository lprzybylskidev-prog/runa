"""Release isolation and failure fencing without production Docker/data mutations."""

import json
from collections import deque
from types import SimpleNamespace

import pytest

from tools.application.deployment_service import DeploymentService
from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation
from tools.core.process import ProcessResult


def workspace(root, sha="a" * 40):
    root.mkdir(parents=True, exist_ok=True)
    for name in (".env.prod", ".env.prod.database"):
        path = root / name
        path.write_text("VALUE=literal$with=equals\n")
        path.chmod(0o600)
    path = root / ".env.prod.compose"
    path.write_text("RUNA_IMAGE_TAG=" + sha + "\nRUNA_PRODUCTION_MODE=simulation\n")
    path.chmod(0o600)
    return root


def test_interleaved_workspaces_and_releases_keep_operator_secrets_and_tls(tmp_path):
    first, second = [workspace(tmp_path / name) for name in ("first", "second")]
    runner = RecordingRunner()
    processes = ApplicationProcesses(runner, {})
    one, two = [
        DeploymentService.for_workspace(root, processes=processes, location=ExecutionLocation.HOST)
        for root in (first, second)
    ]
    a = first / ".runtime/production/releases" / ("b" * 40)
    b = second / ".runtime/production/releases" / ("c" * 40)
    for path in (a, b):
        path.mkdir(parents=True)
    x = one.runtime.command(a)
    y = two.runtime.command(b)
    assert one.runtime.command(a) == x
    assert x[x.index("--project-directory") + 1] == str(a)
    assert y[y.index("--project-directory") + 1] == str(b)
    assert "b" * 40 in (a / ".env.prod.compose").read_text()
    assert "c" * 40 in (b / ".env.prod.compose").read_text()
    assert (a / ".env.prod").read_bytes() == (first / ".env.prod").read_bytes()
    assert "a" * 40 in (first / ".env.prod.compose").read_text()
    assert runner.calls == []
    (b / ".env.prod").unlink()
    (b / ".env.prod").symlink_to(first / ".env.prod")
    with pytest.raises(ValueError, match="symlink"):
        two.runtime.command(b)


class Runtime:
    def __init__(self, fail=None):
        self.events = []
        self.fail = fail

    def event(self, name):
        self.events.append(name)
        if self.fail == name:
            raise ValueError("Injected failure")

    def dc(self, args, release=None, **kwargs):
        self.event(args[0])
        return SimpleNamespace(stdout="")

    def build_images(self, release):
        self.event("build")

    def validate(self, release):
        self.event("validate")

    def pause_http(self, release):
        self.event("fence")

    def resume_http(self, release, **kwargs):
        self.event("activate")

    def platform_data(self, sha, phase, release):
        self.event(phase)

    def artisan(self, args, release):
        self.event(args[0])

    def health(self, release, **kwargs):
        self.event("health")

    def maintenance_active(self, release):
        return False

    def images(self, release):
        return {"runtime": "sha256:fixed"}

    def command(self):
        return ["--project-name", "runa-prod-simulation-fixture"]


def deployment(tmp_path, fail=None):
    root = workspace(tmp_path)
    runner = RecordingRunner(deque([ProcessResult(0)]))
    instance = DeploymentService.for_workspace(
        root, processes=ApplicationProcesses(runner, {}), location=ExecutionLocation.HOST
    )
    instance.context.state.mkdir(parents=True, mode=0o700)
    runtime = Runtime(fail)
    sha = "b" * 40
    release = root / ".runtime/production/releases" / sha
    for owner in ("runa", "app"):
        path = release / "src" / owner
        path.mkdir(parents=True)
        (path / ("platform.json" if owner == "runa" else "application.json")).write_text(
            '{"version":"0.6.0"}'
        )
    source = SimpleNamespace(checkout=lambda *args: release, tree_hash=lambda *args: "fixed")
    return DeploymentService(instance.context, source, runtime), runtime, sha


@pytest.mark.parametrize("failure", ["build", "migrations", "required-seeds", "health"])
def test_failure_restores_pre_data_selection_or_retains_fenced_journal(tmp_path, failure):
    deploy, runtime, sha = deployment(tmp_path, failure)
    original = (tmp_path / ".env.prod.compose").read_bytes()
    with pytest.raises(ValueError, match="Injected"):
        deploy.deploy_revision(sha, True)
    if failure == "build":
        assert (tmp_path / ".env.prod.compose").read_bytes() == original
        assert "fence" not in runtime.events
    else:
        state = json.loads((deploy.context.state / "in-progress.json").read_text())
        assert state["data_steps_started"]
        assert runtime.events[-1] == "fence"
        assert state["phase"] in ("platform-migrations", "platform-seeding", "restart-and-health")


def test_successful_repeat_verifies_health_without_replaying_data(tmp_path):
    deploy, runtime, sha = deployment(tmp_path)
    deploy.deploy_revision(sha, True)
    before = list(runtime.events)
    deploy.deploy_revision(sha, True)
    assert runtime.events == before + ["health"]
    assert not (deploy.context.state / "in-progress.json").exists()
    assert deploy.context.read_state()["current"]["rollback_safe"] is False


def test_unsafe_data_rollback_refuses_before_runtime_work(tmp_path, monkeypatch):
    deploy, runtime, sha = deployment(tmp_path)
    deploy.deploy_revision(sha, True)
    state = deploy.context.read_state()
    previous = dict(
        state["current"], sha="a" * 40, path=str(deploy.context.state / "releases" / ("a" * 40))
    )
    state["previous"] = previous
    deploy.context.atomic(deploy.context.state / "deployment.json", state)

    monkeypatch.setattr(RepositoryPolicy, "assert_production_installation", lambda self, root: None)
    before = list(runtime.events)
    with pytest.raises(ValueError, match="unproven"):
        deploy.rollback()
    assert before == runtime.events
