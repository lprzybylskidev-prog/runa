"""Isolated registry discovery and workspace-bound allowlisted plan execution."""

import hashlib
import json
import os
import re
import stat
import sys
import uuid
from collections.abc import Callable
from contextlib import AbstractContextManager
from dataclasses import dataclass
from functools import partial

from tools.application.development_runtime import DevelopmentRuntime, development_compose
from tools.application.host_plan import DEVELOPMENT_ACTIONS, OPERATIONS, TOOLS, HostPlan
from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.process import InputMode, OutputMode, ProcessSpec
from tools.core.state import FileLock, StateDirectory

MAX_OUTPUT = 65536
TIMEOUT = 30


@dataclass(frozen=True)
class ManagementService:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation
    browser_endpoint: Callable[[], AbstractContextManager] | None = None

    @property
    def policy(self):
        return RepositoryPolicy(
            partial(self.processes.run, cwd=self.workspace.root), self.processes.environment
        )

    @property
    def runtime(self):
        return DevelopmentRuntime(ExecutionContext(self.workspace, self.workspace, self.location))

    def operation_lock(self):
        return FileLock(
            StateDirectory(
                self.workspace.runtime("production"), private=False, anchor=self.workspace.root
            ),
            "operation.lock",
        ).acquire()

    def host_stack_health(self):
        result = self.processes.run(
            development_compose(self.workspace).command("ps", "--all", "--format", "json"),
            cwd=self.workspace.root,
            capture_output=True,
            text=True,
            check=True,
        ).stdout.strip()
        rows = (
            json.loads(result)
            if result.startswith("[")
            else [json.loads(line) for line in result.splitlines() if line]
        )
        services = {row["Service"]: row for row in rows}
        for service in ("app", "nginx", "postgres", "queue", "scheduler", "mailpit"):
            row = services.get(service, {})
            if row.get("State") != "running" or row.get("Health") != "healthy":
                raise ValueError("Development container is missing/not healthy: " + service)
        if (
            services.get("setup", {}).get("State") != "exited"
            or services.get("setup", {}).get("ExitCode") != 0
        ):
            raise ValueError("Development setup did not complete successfully.")
        print("PASS: host Compose service/container health and setup exit status.")

    def image_identity(self):
        digest = hashlib.sha256()
        paths = [
            self.workspace.root / name
            for name in (
                "src/artisan",
                "src/composer.json",
                "src/composer.lock",
                "docker/php/Dockerfile",
                ".dockerignore",
            )
        ]
        if (
            not (self.workspace.root / "src/runa/management").is_dir()
            or (self.workspace.root / "src/runa/management").is_symlink()
        ):
            raise ValueError("Management build inputs must not traverse symlinks.")
        paths += sorted(
            path
            for path in (self.workspace.root / "src/runa/management").rglob("*")
            if not path.is_dir() or path.is_symlink()
        )
        for path in paths:
            if path.is_symlink() or not path.is_file():
                raise ValueError("Missing regular management build input.")
            digest.update(
                str(path.relative_to(self.workspace.root)).encode()
                + b"\0"
                + path.read_bytes()
                + b"\0"
            )
        return digest.hexdigest()

    def ensure_image(self):
        identity = self.image_identity()
        tag = "runa-management:" + identity[:24]
        inspect = [
            "docker",
            "image",
            "inspect",
            "--format",
            '{{.Id}} {{index .Config.Labels "runa.management.inputs"}}',
            tag,
        ]
        result = self.processes.run(
            inspect, cwd=self.workspace.root, capture_output=True, text=True, timeout=TIMEOUT
        )
        if result.returncode:
            print(
                "Building the isolated management image (no app services are started).",
                file=sys.stderr,
            )
            target = (
                "development-management"
                if (self.workspace.root / "src/runa/management/development.json").is_file()
                else "management"
            )
            self.processes.run(
                [
                    "docker",
                    "build",
                    "--target",
                    target,
                    "--file",
                    "docker/php/Dockerfile",
                    "--label",
                    "runa.management.inputs=" + identity,
                    "--tag",
                    tag,
                    ".",
                ],
                cwd=self.workspace.root,
                check=True,
                stdout=sys.stderr,
                stderr=sys.stderr,
            )
            result = self.processes.run(
                inspect,
                cwd=self.workspace.root,
                capture_output=True,
                text=True,
                timeout=TIMEOUT,
                check=True,
            )
        fields = result.stdout.strip().split()
        if (
            len(fields) != 2
            or not re.fullmatch(r"sha256:[a-f0-9]{64}", fields[0])
            or fields[1] != identity
        ):
            raise ValueError(
                "Management image identity/inputs mismatch; inspect the local image before retrying."
            )
        return fields[0]

    def bounded_output(self, command):
        result = self.processes.runner.run(
            ProcessSpec(
                tuple(command),
                self.workspace.root,
                self.processes.environment,
                stdin=InputMode.CLOSED,
                output=OutputMode.CAPTURE,
                timeout=TIMEOUT,
                output_limit=MAX_OUTPUT,
            )
        )
        return result.returncode, result.stdout.decode("utf-8"), result.stderr.decode("utf-8")

    def validate_plan(self, raw, request):
        return HostPlan.parse(raw, request).as_dict()

    def private_directory(self, path):
        for parent in reversed([path, *path.parents]):
            if parent.is_symlink():
                raise ValueError("Management state must not traverse symlinks.")
        path.mkdir(mode=0o700, parents=True, exist_ok=True)
        info = path.stat()
        if (
            not stat.S_ISDIR(info.st_mode)
            or info.st_uid != os.getuid()
            or stat.S_IMODE(info.st_mode) & 0o077
        ):
            raise ValueError(
                "Management state directory must be private and owned by the current operator."
            )

    def execute_plan(self, plan, request):
        # Revalidate at the execution boundary; no caller can supply shell/argv/env/path fields.
        plan = self.validate_plan(json.dumps(plan), request)
        self.assert_operation_context(plan)
        directory = self.workspace.root / ".runtime/management"
        self.private_directory(directory)
        for path in (
            self.workspace.runtime("production").parent,
            self.workspace.runtime("production"),
            self.workspace.runtime("production") / "operation.lock",
        ):
            if path.is_symlink():
                raise ValueError("Operation state/lock must not be a symlink.")
        from contextlib import nullcontext

        with (
            self.operation_lock() if plan["operation"].startswith("development.") else nullcontext()
        ):
            if (
                plan["operation"].startswith("development.")
                and plan["operation"] != "development.status"
                and (self.workspace.runtime("production") / "in-progress.json").exists()
            ):
                raise ValueError(
                    "An unresolved production operation requires explicit recovery before host mutation."
                )
            path = directory / (request + ".json")
            descriptor = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
            try:
                with os.fdopen(descriptor, "w") as stream:
                    json.dump(plan, stream)
                    stream.write("\n")
                # Fixed trusted executable and action, never instructions from release/retrieval text.
                browser = plan["operation"] == "tooling.test" and plan["parameters"].get(
                    "arguments", []
                )[:1] == ["e2e"]
                if browser and self.browser_endpoint is None:
                    raise ValueError("Browser endpoint lifecycle is unavailable.")
                with self.browser_endpoint() if browser else nullcontext():
                    result = self.processes.run(
                        self.execution_command(plan), cwd=self.workspace.root
                    )
                return result.returncode
            finally:
                path.unlink()

    def public_command(self, tool, arguments):
        """Resolve only fixed public definitions; the destination reparses its own options."""
        definitions = json.loads(
            (self.workspace.root / "src/runa/management/operations.json").read_text()
        )
        development = self.workspace.root / "src/runa/management/development.json"
        if development.exists():
            definitions += json.loads(development.read_text())
        candidates = [
            entry
            for entry in definitions
            if entry["tool"] == tool and arguments[: len(entry["prefix"])] == entry["prefix"]
        ]
        candidates.sort(
            key=lambda entry: (
                len(entry["prefix"]),
                entry["name"] == "runa:dev:profile:set" and len(arguments) > 1,
            ),
            reverse=True,
        )
        if not candidates:
            raise ValueError("No public command matches this internal operation.")
        entry = candidates[0]
        return [entry["name"], *arguments[len(entry["prefix"]) :]]

    def assert_operation_context(self, plan):
        if plan["operation"] not in ("tooling.production", "tooling.deploy", "tooling.host"):
            self.policy.assert_development_context(self.workspace.root)

    def execution_command(self, plan):
        """Fixed executable families only; plan data never supplies an executable or shell."""
        self.assert_operation_context(plan)
        operation = plan["operation"]
        arguments = plan["parameters"].get("arguments", [])
        if operation.startswith("development."):
            action = OPERATIONS[operation]
            if action in ("setup", "knowledge-init", "knowledge-status"):
                return self.runtime.app_command(
                    ["php", "artisan", *self.public_command("dev", [action, *arguments])]
                )
            return ["python3", "-m", "tools.application.development", action, *arguments]
        tool = OPERATIONS[operation]
        if tool == "host":
            return ["python3", "-m", "tools.application.host_transport", "--control", *arguments]
        if tool in ("production", "quality", "security"):
            return [
                "python3",
                str(self.workspace.root / ("tools/application/executors/" + tool + ".py")),
                *arguments,
            ]
        if tool == "deploy":
            return [
                "python3",
                str(self.workspace.root / "tools/application/executors/operations.py"),
                tool,
                *arguments,
            ]
        if tool == "hook":
            return [
                str(self.workspace.root / "tools/application/bin" / arguments[0]),
                *arguments[1:],
            ]
        if tool == "check":
            return [
                "python3",
                str(self.workspace.root / "tools/application/executors/quality_check.py"),
                *arguments,
            ]
        if tool == "doctor":
            if arguments == ["production"]:
                return [
                    "python3",
                    str(self.workspace.root / "tools/application/executors/production.py"),
                    "self-test",
                ]
            if not arguments or arguments == ["development"]:
                self.host_stack_health()
        return self.runtime.app_command(["php", "artisan", *self.public_command(tool, arguments)])

    def is_management_command(self, name):
        # Reuse the same bounded, named-container lifecycle as every other discovery.
        import contextlib
        import io

        with contextlib.redirect_stdout(io.StringIO()) as output:
            status = self.launch(["list", "--raw"])
        if status:
            raise ValueError("Management command discovery failed.")
        return name in {line.split()[0] for line in output.getvalue().splitlines() if line.strip()}

    def launch(self, args):
        if any(arg == "--host-request" or arg.startswith("--host-request=") for arg in args):
            raise ValueError("--host-request is reserved for the host transport.")
        request = uuid.uuid4().hex
        planning = bool(
            args and args[0].startswith("runa:") and "--help" not in args and "-h" not in args
        )
        image = self.ensure_image()
        name = "runa-management-" + request
        command = [
            "docker",
            "run",
            "--rm",
            "--name",
            name,
            "--network",
            "none",
            "--read-only",
            "--cap-drop",
            "ALL",
            "--security-opt",
            "no-new-privileges",
            "--pids-limit",
            "128",
            "--memory",
            "128m",
            "--cpus",
            "1",
            "--tmpfs",
            "/tmp:rw,nosuid,nodev,noexec,size=16m",
            image,
            "--management",
            *args,
        ]
        if planning:
            command += ["--host-request=" + request]
        try:
            status, output, _ = self.bounded_output(command)
        except BaseException:
            # Killing the Docker client alone does not reliably remove its daemon-side process.
            self.processes.run(
                ["docker", "rm", "--force", name], capture_output=True, timeout=TIMEOUT
            )
            raise
        if status:
            print(
                "Management command rejected or failed; use --management help <command> for accepted arguments.",
                file=sys.stderr,
            )
            return status
        if not planning:
            print(output, end="")
            return 0
        plan = self.validate_plan(output, request)
        if "--plan" in args:
            print(json.dumps(plan, indent=2))
            return 0
        return self.execute_plan(plan, request)
