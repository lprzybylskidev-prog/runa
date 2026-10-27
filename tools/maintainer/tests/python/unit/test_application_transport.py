"""Request binding, workspace isolation and refusal before development mutations."""

import io
import json
import os
import subprocess
import sys
from collections import deque
from pathlib import Path

import pytest

from tools.application.dependencies import compose
from tools.application.development import DevelopmentService
from tools.application.host_plan import OPERATIONS, HostPlan
from tools.application.host_transport import HostExecutor, read_json, validate_request
from tools.application.management_service import ManagementService
from tools.application.module_versions import assess
from tools.application.processes import ApplicationProcesses
from tools.application.python_toolchain import ROOT
from tools.application.repository_boundary_service import OWNER_PATHS, RepositoryPolicy
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import ProcessResult, SubprocessRunner
from tools.core.reporting import SafeFailure

REQUEST = "a" * 32


def plan(operation="development.status", arguments=None):
    return {
        "schema_version": 1,
        "request_id": REQUEST,
        "operation": operation,
        "parameters": {"arguments": arguments or []},
    }


def management(root, outcomes=()):
    runner = RecordingRunner(deque(outcomes))
    return ManagementService(
        WorkspaceContext(root), ApplicationProcesses(runner, {}), ExecutionLocation.HOST
    ), runner


@pytest.mark.parametrize(
    "change",
    [
        {"schema_version": True},
        {"request_id": "b" * 32},
        {"operation": "sh"},
        {"parameters": {"argv": ["sh"]}},
        {"cwd": "/tmp"},
        {"parameters": {"arguments": ["x\0y"]}},
    ],
)
def test_untrusted_plans_cannot_choose_execution(change):
    with pytest.raises(ValueError):
        HostPlan.parse(json.dumps(plan() | change), REQUEST)


def test_duplicate_plan_fields_and_frozen_arguments():
    with pytest.raises(ValueError, match="Duplicate"):
        HostPlan.parse(
            json.dumps(plan()).replace(
                '"schema_version": 1', '"schema_version": 1, "schema_version": 1'
            ),
            REQUEST,
        )
    value = HostPlan.parse(json.dumps(plan(arguments=["$(false); literal"])), REQUEST)
    assert value.arguments == ("$(false); literal",)
    assert value.as_dict()["parameters"]["arguments"] == ["$(false); literal"]


def test_catalog_schema_and_independent_allowlist_agree():
    schema = json.loads((ROOT / "src/runa/management/host-plan.v1.schema.json").read_text())
    assert set(schema["properties"]["operation"]["enum"]) == set(OPERATIONS)
    definitions = sum(
        (
            json.loads((ROOT / "src/runa/management" / name).read_text())
            for name in ("operations.json", "development.json")
        ),
        [],
    )
    declared = {
        "development." + entry["prefix"][0]
        if entry["tool"] == "dev"
        else "tooling." + entry["tool"]
        for entry in definitions
    }
    assert declared == set(OPERATIONS)


@pytest.mark.parametrize("mode", ["prod", "unknown", None])
def test_role_denial_precedes_state_processes_and_profile_files(tmp_path, mode):
    (tmp_path / ".git").mkdir()
    policy = RepositoryPolicy(subprocess.run, {})
    policy.write_staging_receipt(
        tmp_path,
        {
            "schema_version": 1,
            "mode": mode,
            "projection": {"version": 1, "excluded": list(OWNER_PATHS)},
        },
    )
    service, runner = management(tmp_path)
    with pytest.raises(ValueError):
        service.execute_plan(plan("development.up"), REQUEST)
    dev = DevelopmentService(service.workspace, service.processes, ExecutionLocation.HOST)
    with pytest.raises(ValueError):
        dev.main(["prepare"])
    assert not (tmp_path / ".runtime").exists()
    assert not (tmp_path / ".env.dev.local").exists()
    assert not runner.calls


def test_two_workspaces_do_not_share_request_paths_or_process_cwd(tmp_path):
    for name in ("one", "two"):
        root = tmp_path / name
        root.mkdir()
        service, runner = management(root, [ProcessResult(7)])
        assert service.execute_plan(plan(), REQUEST) == 7
        assert runner.calls[0].cwd == root
        assert runner.calls[0].argv == ("python3", "-m", "tools.application.development", "status")
        assert not list(root.glob(".runtime/management/*.json"))
        assert (root / ".runtime/production/operation.lock").exists()


def test_pending_production_operation_blocks_mutation_but_allows_status(tmp_path):
    state = tmp_path / ".runtime/production"
    state.mkdir(parents=True, mode=0o700)
    (state / "in-progress.json").write_text("{}")
    service, runner = management(tmp_path, [ProcessResult(0)])
    with pytest.raises(ValueError, match="unresolved production"):
        service.execute_plan(plan("development.up"), REQUEST)
    assert not runner.calls
    assert service.execute_plan(plan(), REQUEST) == 0


def test_discovery_limits_streams_and_reaps_child(tmp_path):
    service = ManagementService(
        WorkspaceContext(tmp_path),
        ApplicationProcesses(SubprocessRunner(), {}),
        ExecutionLocation.HOST,
    )
    with pytest.raises(SafeFailure, match="transport limit"):
        service.bounded_output([sys.executable, "-c", 'import os; os.write(2,b"x"*70000)'])
    assert service.bounded_output(
        [sys.executable, "-c", 'import sys; print("ok"); sys.exit(9)']
    ) == (9, "ok\n", "")


def test_host_request_cannot_override_workspace_or_replay_challenge():
    credentials = {"token": "b" * 64, "workspace": "c" * 64}
    value = {
        "version": 1,
        **credentials,
        "challenge": "d" * 64,
        "id": REQUEST,
        "command": "runa:dev:up",
        "arguments": [],
        "interactive": False,
    }
    assert validate_request(value, credentials, "d" * 64) == value
    for change in (
        {"challenge": "e" * 64},
        {"token": "e" * 64},
        {"cwd": "/tmp"},
        {"arguments": ["--plan"]},
    ):
        with pytest.raises(ValueError):
            validate_request(value | change, credentials, "d" * 64)
    for raw in (b'{"id":1,"id":2}\n', b"{}", b"x" * 65537):
        with pytest.raises(ValueError):
            read_json(io.BytesIO(raw))


def test_host_state_has_no_deployment_import_or_ambient_cwd(tmp_path):
    service, _ = management(tmp_path)
    host = HostExecutor(service.workspace, service.processes)
    before = Path.cwd()
    host.management.private_directory(host.directory())
    host.atomic(host.directory() / "client.json", {"state": "ready"})
    assert (host.directory() / "client.json").stat().st_mode & 0o777 == 0o600
    with host.socket_path() as address:
        assert address.startswith("/proc/self/fd/")
        assert Path.cwd() == before


def test_profile_and_secret_parsing_preserve_literal_dollar_values(tmp_path):
    service, _ = management(tmp_path)
    (tmp_path / ".devcontainer").mkdir()
    dev = DevelopmentService(service.workspace, service.processes, ExecutionLocation.HOST)
    values = dev.configuration.load()
    dev.configuration.persist(values)
    assert dev.configuration.load() == values
    secret = tmp_path / "secret"
    secret.write_text("PASSWORD=literal$with#symbols\n")
    secret.chmod(0o600)
    assert dev.configuration.private_environment(secret)["PASSWORD"] == "literal$with#symbols"


def test_manifest_composition_and_versions_preserve_refusals():
    assert compose({"require": {"a": "1"}}, {"require": {"b": "2"}}, "composer")["require"] == {
        "a": "1",
        "b": "2",
    }
    with pytest.raises(ValueError, match="Conflicting"):
        compose({"require": {"a": "1"}}, {"require": {"a": "2"}}, "composer")
    assert assess("1.0.0", "1.0.0", True, "") == [
        "changed_module_requires_version_bump",
        "module_version_requires_changelog_entry",
    ]


def test_plan_preview_never_invokes_executor(tmp_path, capsys):
    class Preview(ManagementService):
        def ensure_image(self):
            return "sha256:" + "b" * 64

        def bounded_output(self, command):
            request = command[-1].removeprefix("--host-request=")
            return 0, json.dumps(plan() | {"request_id": request}), ""

        def execute_plan(self, value, request):
            raise AssertionError("Preview attempted a mutation")

    _, runner = management(tmp_path)
    service = Preview(
        WorkspaceContext(tmp_path), ApplicationProcesses(runner, {}), ExecutionLocation.HOST
    )
    assert service.launch(["runa:dev:status", "--plan"]) == 0
    assert json.loads(capsys.readouterr().out)["operation"] == "development.status"
    assert not runner.calls
    assert not (tmp_path / ".runtime").exists()


def test_container_routing_does_not_invoke_host_compose(tmp_path):
    service, _ = management(tmp_path)
    container = ManagementService(service.workspace, service.processes, ExecutionLocation.CONTAINER)
    assert container.runtime.app_command(("php", "artisan")) == ("php", "artisan")
    assert service.runtime.app_command(("php", "artisan"))[:2] == ("docker", "compose")


def test_request_receipt_is_removed_after_child_failure(tmp_path):
    service, _ = management(tmp_path, [OSError("fixture")])
    with pytest.raises(OSError):
        service.execute_plan(plan(), REQUEST)
    assert not (tmp_path / ".runtime/management" / (REQUEST + ".json")).exists()


def test_disconnect_completes_once_and_replay_is_refused(tmp_path):
    import socket
    import threading

    from tools.application.host_transport import Handler, Server, encode

    finished = threading.Event()
    spawned = []
    credentials = {"token": "b" * 64, "workspace": "c" * 64}

    class Identity:
        def image_identity(self):
            return "fixture"

    class Host(HostExecutor):
        @property
        def management(self):
            return Identity()

        def validated_plan(self, value, image):
            return plan() | {"request_id": value["id"]}

    def spawn(argv, **kwargs):
        spawned.append(argv)
        return subprocess.Popen(
            [
                sys.executable,
                "-c",
                'import sys,time; sys.stdin.buffer.readline(); time.sleep(.05); print("done")',
            ],
            **kwargs,
        )

    class ObservedHandler(Handler):
        def handle(self):
            try:
                super().handle()
            finally:
                finished.set()

    host = Host(WorkspaceContext(tmp_path), ApplicationProcesses(SubprocessRunner(), {}), spawn)
    operations = host.directory() / "operations"
    operations.mkdir(mode=0o700, parents=True)
    with Server(str(tmp_path / "socket"), ObservedHandler) as server:
        server.service = host
        server.credentials, server.identity, server.image, server.environment = (
            credentials,
            "fixture",
            "fixture",
            dict(os.environ),
        )
        thread = threading.Thread(target=server.serve_forever)
        thread.start()
        try:
            value = {
                "version": 1,
                **credentials,
                "id": REQUEST,
                "command": "runa:dev:up",
                "arguments": [],
                "interactive": False,
            }
            with socket.socket(socket.AF_UNIX) as client:
                client.connect(str(tmp_path / "socket"))
                with client.makefile("rwb", buffering=0) as stream:
                    challenge = read_json(stream)
                    stream.write(encode(value | {"challenge": challenge["value"]}))
                    assert read_json(stream)["type"] == "accepted"
            assert finished.wait(3)
            receipt = json.loads((operations / (REQUEST + ".json")).read_text())
            assert receipt["state"] == "completed" and receipt["exit_code"] == 0
            assert receipt["connected"] is False
            assert "token" not in receipt
            with socket.socket(socket.AF_UNIX) as client:
                client.connect(str(tmp_path / "socket"))
                with client.makefile("rwb", buffering=0) as stream:
                    challenge = read_json(stream)
                    stream.write(encode(value | {"challenge": challenge["value"]}))
                    assert read_json(stream)["type"] == "error"
            assert len(spawned) == 1
        finally:
            server.shutdown()
            thread.join(3)


def test_setup_wait_lock_retains_inode_and_content(tmp_path):
    class Setup(DevelopmentService):
        def application_setup(self, arguments):
            return 7

    service, _ = management(tmp_path)
    directory = tmp_path / ".runtime/dev"
    directory.mkdir(parents=True)
    lock = directory / "setup.lock"
    lock.write_text("stable")
    inode = lock.stat().st_ino
    setup = Setup(service.workspace, service.processes, ExecutionLocation.CONTAINER)
    assert setup.main(["setup", "--if-needed"]) == 7
    assert lock.stat().st_ino == inode and lock.read_text() == "stable"


def test_host_registry_validation_cleans_only_its_named_container(tmp_path):
    service, runner = management(
        tmp_path, [ProcessResult(0, json.dumps(plan()).encode()), ProcessResult(0)]
    )
    host = HostExecutor(service.workspace, service.processes)
    assert (
        host.validated_plan(
            {"id": REQUEST, "command": "runa:dev:status", "arguments": []}, "sha256:" + "b" * 64
        )
        == plan()
    )
    assert runner.calls[0].output_limit == 65536
    assert runner.calls[0].timeout == 30
    assert "--mount" not in runner.calls[0].argv
    assert runner.calls[1].cwd == tmp_path
    assert runner.calls[1].argv == ("docker", "rm", "--force", "runa-host-validate-" + REQUEST)


@pytest.mark.parametrize("arguments", [["status"], ["logs", "app"], ["restart", "mailpit"]])
def test_development_service_uses_canonical_compose_context(tmp_path, arguments):
    from tools.application.development_runtime import development_compose

    service, runner = management(tmp_path, [ProcessResult(0)])
    dev = DevelopmentService(service.workspace, service.processes, ExecutionLocation.HOST)
    assert dev.main(arguments) == 0
    prefix = development_compose(service.workspace).command()
    assert runner.calls[0].argv[: len(prefix)] == prefix
    assert runner.calls[0].cwd == tmp_path


def test_commit_message_is_relative_to_requested_repository(tmp_path):
    repository = tmp_path / "target"
    (repository / ".git").mkdir(parents=True)
    (repository / ".git/COMMIT_EDITMSG").write_text("chore: target repository message\n")
    result = subprocess.run(
        [
            sys.executable,
            "-m",
            "tools.application.git_hook",
            str(repository),
            "commit-msg",
            ".git/COMMIT_EDITMSG",
        ],
        cwd=ROOT,
        capture_output=True,
        env=dict(os.environ, PYTHONPYCACHEPREFIX=str(ROOT / ".runtime/python/pycache")),
    )
    assert result.returncode == 0, result.stderr
