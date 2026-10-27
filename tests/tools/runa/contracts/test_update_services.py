"""Version-separated upgrade and durable resume using fake runtime/data effects."""

from __future__ import annotations

import json
import shutil
import subprocess
from pathlib import Path
from types import SimpleNamespace

import pytest

from distribution import bootstrap, update_execution
from distribution.application_gateway import GatewayError, installation_command
from distribution.update_execution import SEEDER, UpdateExecutor
from distribution.update_models import DatabaseMetadata, RuntimeSnapshot
from distribution.update_plan_models import ReleaseIdentity
from distribution.update_planning import UpdatePlanner
from tools.core.delivery import OWNER_PATHS


def write(root: Path, name: str, content: str) -> Path:
    path = root / name
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content)
    return path


class Runtime:
    def __init__(self) -> None:
        self.data_state: DatabaseMetadata = {
            "migrations": [],
            "seed_runs": [],
            "modules": {},
            "module_steps": [],
            "database_mounts": [],
        }
        self.seed_effects = 0
        self.events: list[str] = []
        self.failure: str | None = None

    def bind(self, project: str) -> None:
        assert project == "fixture"

    def probe(self, *args: object) -> RuntimeSnapshot:
        return dict(self.data_state, project="fixture", services=["app", "postgres"])

    def metadata(self) -> DatabaseMetadata:
        return json.loads(json.dumps(self.data_state))

    def manual(self) -> None:
        pass

    def fence(self, identity: str) -> None:
        self.events.append("fence")

    def build(self) -> None:
        self.events.append("build")

    def activate(self, identity: str) -> None:
        self.events.append("activate")

    def health(self) -> None:
        self.events.append("health")

    def data(self, phase: str, release: str) -> None:
        self.events.append(phase)
        if phase == "required-seeds":
            self.seed_effects += 1
            self.data_state["seed_runs"].append({"release_sha": release, "seeder": SEEDER})
            if self.failure == "committed-seed":
                self.failure = None
                raise RuntimeError("Injected process loss after committed effects")


def upgrade(tmp_path: Path) -> tuple[Path, Runtime, UpdateExecutor]:
    original, target, installed = [tmp_path / name for name in ("original", "target", "installed")]
    for version, root in [("0.5.0", original), ("0.6.0", target)]:
        files = {
            "VERSION": version,
            "src/runa/platform.json": json.dumps(
                dict(schema_version=1, id="runa", name="Runa", version=version)
            ),
            "src/app/application.json": json.dumps(
                dict(schema_version=1, version="2.3.4", runa=">=0.5.0 <0.7.0")
            ),
            "src/app/composer.json": "{}",
            "src/app/package.json": "{}",
            "src/runa/composer.json": "{}",
            "src/runa/package.json": "{}",
            "src/composer.json": "{}",
            "src/package.json": "{}",
            "src/composer.lock": "{}",
            "src/pnpm-lock.yaml": "fixture",
            "src/pnpm-workspace.yaml": "{}",
            "tools/application/python/requirements.lock": "frozen-" + version,
        }
        for name, content in files.items():
            write(root, name, content)
        subprocess.run(["git", "init", "-q", str(root)], check=True)
        subprocess.run(["git", "-C", str(root), "add", "--all"], check=True)
    shutil.copytree(original, installed)
    installed.chmod(0o700)
    write(installed, ".env", "APP_ENV=local\n").chmod(0o600)
    write(installed, ".git/runa-bootstrap.json", '{"mode":"dev"}').chmod(0o600)
    write(installed, "src/app/python/requirements.lock", "customer-frozen")
    write(installed, "src/app/python/ruff.toml", "customer-config")
    write(installed, "tests/backend/app/tooling/test_extension.py", "# customer")
    runtime = Runtime()

    def download(
        client: bootstrap.GitHub,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
    ) -> ReleaseIdentity:
        shutil.copytree(original if tag == "v0.5.0" else target, destination)
        commit = "a" * 40 if tag == "v0.5.0" else "b" * 40
        return dict(tag=tag, commit=commit, tag_object="c" * 40)

    planner = UpdatePlanner(
        download=download,
        runtime_probe=runtime.probe,
        lock_probe=lambda *args: "fixture",
        module_plan=lambda *args: [],
    )
    return installed, runtime, UpdateExecutor(planner, runtime_factory=lambda root: runtime)


def test_upgrade_050_to_060_preserves_customer_tooling_and_does_not_replay_committed_seed(
    tmp_path: Path,
) -> None:
    installed, runtime, executor = upgrade(tmp_path)
    runtime.failure = "committed-seed"
    client = SimpleNamespace(repository="fixture/runa")
    failed = executor.execute(installed, client, "v0.6.0", choose=lambda review: "continue")
    assert failed["status"] == "blocked", failed
    assert runtime.seed_effects == 1
    result = executor.execute(installed, client, "v0.6.0", resume=True)
    assert result["status"] == "completed", result
    assert runtime.seed_effects == 1
    assert (installed / "src/app/python/requirements.lock").read_text() == "customer-frozen"
    assert (installed / "tools/application/python/requirements.lock").read_text() == "frozen-0.6.0"
    assert (installed / "tests/backend/app/tooling/test_extension.py").is_file()
    assert not (installed / ".runtime/production/in-progress.json").exists()


@pytest.mark.parametrize("boundary", [1, 2])
def test_rename_interruption_reconciles_both_release_identities(
    tmp_path: Path, monkeypatch: pytest.MonkeyPatch, boundary: int
) -> None:
    installed, runtime, executor = upgrade(tmp_path)
    client = SimpleNamespace(repository="fixture/runa")
    rename = update_execution.os.rename
    calls = []

    def interrupted(source: Path, destination: Path) -> None:
        rename(source, destination)
        calls.append(destination)
        if len(calls) == boundary:
            raise KeyboardInterrupt

    with monkeypatch.context() as patch:
        patch.setattr(update_execution.os, "rename", interrupted)
        with pytest.raises(KeyboardInterrupt):
            executor.execute(installed, client, "v0.6.0", choose=lambda review: "continue")
    result = executor.execute(installed, client, "v0.6.0", resume=True)
    assert result["status"] == "completed", result
    assert runtime.seed_effects == 1


def test_standalone_projection_matches_canonical_policy_and_gateway_is_versioned(
    tmp_path: Path,
) -> None:
    assert bootstrap.OWNER_PATHS == OWNER_PATHS
    write(
        tmp_path, "tools/application/gateway.json", '{"schema_version":1,"installation_protocol":1}'
    )
    command = installation_command(tmp_path, ["dev", "a" * 40])
    assert command[1:3] == ["-m", "tools.application.installation_gateway"]
    assert not any("runpy" in arg or "sys.path" in arg for arg in command)
    write(
        tmp_path,
        "tools/application/gateway.json",
        '{"schema_version":1,"installation_protocol":99}',
    )
    with pytest.raises(GatewayError, match="unsupported"):
        installation_command(tmp_path, ["dev", "a" * 40])


def test_external_bundle_imports_and_commands_do_not_depend_on_customer_or_maintainer(
    tmp_path: Path,
) -> None:
    from tools.maintainer.python_packages import stage

    stage("external", tmp_path)
    result = subprocess.run(
        [
            "python3",
            "-E",
            "-s",
            "-c",
            "import sys; import distribution.update_planning, distribution.update_execution, distribution.installation; "
            'assert not any(name.startswith(("tools.application", "tools.maintainer")) for name in sys.modules)',
        ],
        cwd=tmp_path,
        capture_output=True,
        text=True,
    )
    assert result.returncode == 0, result.stderr
