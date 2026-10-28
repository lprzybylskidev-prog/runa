#!/usr/bin/env python3
"""Native-host cold Artisan/Compose recovery acceptance with isolated disposable process fixtures."""

from __future__ import annotations

import json
import shutil
import subprocess
import sys
import tempfile
import uuid
from collections.abc import Sequence
from pathlib import Path
from typing import Unpack

from tests.tools.runa.acceptance.acceptance import AcceptanceContext, OutputOptions
from tools.application.management_service import ManagementService
from tools.core.context import WorkspaceContext
from tools.core.state import JsonValue, json_value
from tools.maintainer.python_packages import stage

ROOT = Path(__file__).resolve().parents[4]
ACCEPTANCE = AcceptanceContext(WorkspaceContext(ROOT))


def output(command: Sequence[str], **kwargs: Unpack[OutputOptions]) -> str:
    return ACCEPTANCE.output(command, **kwargs)


def main() -> None:
    if len(sys.argv) != 1 or Path("/.dockerenv").exists():
        raise SystemExit(
            "Run python3 -m tests.tools.runa.acceptance.management_host on the native Docker host."
        )
    report_path = ROOT / ".runtime/ownership/RM-2026-005-management-host-latest.json"
    report_path.parent.mkdir(parents=True, exist_ok=True)
    checks: list[str] = []
    report: dict[str, JsonValue] = {
        "result": "RUNNING",
        "checks": list(checks),
        "scope": "Transport recovery using fixed development operations and isolated PHP process fixtures; not full Laravel installation acceptance.",
    }
    report_path.write_text(json.dumps(report, indent=2) + "\n")
    image = ManagementService.for_workspace(ROOT).ensure_image()
    report["management_image"] = image
    report["management_tag"] = (
        "runa-management:" + ManagementService.for_workspace(ROOT).image_identity()[:24]
    )
    project = "runa-rm005-management-" + uuid.uuid4().hex[:10]
    report["project"] = project
    protected = ACCEPTANCE.protected_containers()
    before = {
        name: output(["docker", "inspect", "--format", "{{.Id}} {{.State.StartedAt}}", name])
        for name in protected
    }

    def check(condition: bool, label: str) -> None:
        if not condition:
            raise AssertionError(label)
        checks.append(label)
        print("PASS: " + label, flush=True)

    with tempfile.TemporaryDirectory(prefix=project + "-", dir=ROOT / ".runtime") as folder:
        root = Path(folder)
        for name in (
            "artisan",
            "src/artisan",
            "bin/artisan",
            "tools/application/development_runtime.py",
            "tools/application/repository_boundary_service.py",
            "tools/application/management_service.py",
            "tools/application/test_resources.py",
            "tools/application/host_transport.py",
            "tools/application/deployment_service.py",
            "tools/application/production_runtime.py",
            "src/composer.json",
            "src/composer.lock",
            "docker/php/Dockerfile",
            ".dockerignore",
        ):
            target = root / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / name, target)
        shutil.copytree(ROOT / "src/runa/Tooling/Management", root / "src/runa/Tooling/Management")
        stage("application", root)
        (root / ".devcontainer").mkdir()
        (root / ".devcontainer/compose.resources.yaml").write_text('{"services":{}}\n')
        (root / ".env.dev.local").write_text(
            "DEV_PROFILE=low\nEMBEDDINGS_DEVICE=cpu\nEMBEDDINGS_CUDA=0\n"
        )
        common: dict[str, JsonValue] = {
            "image": report["management_tag"],
            "pull_policy": "never",
            "entrypoint": ["php", "-r"],
            "command": ["sleep(300);"],
            "read_only": True,
            "cap_drop": ["ALL"],
            "healthcheck": {
                "test": ["CMD", "php", "-r", "exit(0);"],
                "interval": "1s",
                "timeout": "1s",
                "retries": 5,
            },
        }
        services = {
            name: dict(common)
            for name in (
                "app",
                "postgres",
                "nginx",
                "queue",
                "scheduler",
                "mailpit",
                "qdrant",
                "embeddings",
            )
        }
        for name in ("setup", "knowledge-init"):
            services[name] = dict(common, command=["exit(0);"], healthcheck={"disable": True})
        services["app"]["depends_on"] = {"postgres": {"condition": "service_healthy"}}
        services["setup"]["depends_on"] = {"app": {"condition": "service_healthy"}}
        services["nginx"]["depends_on"] = {"setup": {"condition": "service_completed_successfully"}}
        (root / "compose.yaml").write_text(
            json.dumps(
                {"name": project, "services": services, "networks": {"default": {"internal": True}}}
            )
        )
        compose = [
            "docker",
            "compose",
            "--project-directory",
            str(root),
            "--env-file",
            str(root / ".env.dev.local"),
            "-f",
            str(root / "compose.yaml"),
            "-f",
            str(root / ".devcontainer/compose.resources.yaml"),
        ]
        launcher = str(root / "bin/artisan")
        try:
            check(
                not any(
                    (root / name).exists()
                    for name in (".env", "vendor", "node_modules", "bootstrap/app.php")
                ),
                "fixture starts without application kernel, vendor, DB credentials or key",
            )
            discovery = ACCEPTANCE.call(
                [launcher, "--management", "list", "--raw"], cwd=root, capture=True, check=False
            )
            if discovery.returncode:
                raise RuntimeError("Cold fixture discovery failed: " + discovery.stderr)
            listing = discovery.stdout.strip()
            check(
                "runa:dev" in listing and "migrate" not in listing,
                "cold discovery uses the packaged production dependencies and explicit management registry",
            )
            check(
                "runa:dev" in output([launcher, "--management"], cwd=root),
                "default management invocation lists commands",
            )
            check(
                not output(compose + ["ps", "-aq"]),
                "cold discovery started no application or database services",
            )
            plan = json.loads(output([launcher, "runa:dev:up", "--plan"], cwd=root))
            check(
                plan["operation"] == "development.up" and not output(compose + ["ps", "-aq"]),
                "plan-only does not start services",
            )
            for action in ("plan", "roadmap", "final"):
                selected = json.loads(
                    output(
                        [
                            launcher,
                            "runa:quality:" + action,
                            "--group=docs",
                            "--group=backend",
                            "--plan",
                        ],
                        cwd=root,
                    )
                )
                expected = [
                    "show" if action == "plan" else action,
                    "--group=docs",
                    "--group=backend",
                ]
                check(
                    selected["parameters"]["arguments"] == expected,
                    "quality "
                    + action
                    + " preserves repeated groups through cold native transport",
                )
            for arguments in (
                ["--all", "--group=docs"],
                ["--group=installation"],
                ["--group="],
                ["--group=../exec"],
            ):
                refused = subprocess.run(
                    [launcher, "runa:quality:final", *arguments, "--plan"],
                    cwd=root,
                    capture_output=True,
                    text=True,
                )
                check(refused.returncode != 0, "cold quality selection rejects " + repr(arguments))
            check(
                not output(compose + ["ps", "-aq"]),
                "quality selection previews/rejections start no application services",
            )
            result = subprocess.run(
                [launcher, "runa:dev:up"], cwd=root, capture_output=True, text=True
            )
            report["cold_recovery"] = {
                "exit_code": result.returncode,
                "stdout": result.stdout,
                "stderr": result.stderr,
            }
            check(
                result.returncode == 0,
                "bound Artisan plan runs the existing host dev lifecycle on an empty isolated project",
            )
            app = output(compose + ["ps", "-q", "app"])
            check(
                bool(app)
                and output(["docker", "inspect", "--format", "{{.State.Health.Status}}", app])
                == "healthy",
                "isolated target is running and healthy after cold recovery",
            )
            output(compose + ["stop", "app"])
            check(
                output(["docker", "inspect", "--format", "{{.State.Running}}", app]) == "false",
                "isolated application target is stopped before recovery check",
            )
            check(
                "runa:dev" in output([launcher, "--management", "list", "--raw"], cwd=root),
                "discovery still works with the application stopped",
            )
            result = subprocess.run(
                [launcher, "runa:dev:up"], cwd=root, capture_output=True, text=True
            )
            check(
                result.returncode == 0
                and output(["docker", "inspect", "--format", "{{.State.Running}}", app]) == "true",
                "real host lifecycle recovers the stopped target through the management plan",
            )
            for arguments in (
                ["runa:dev", "destroy"],
                ["runa:dev:status", "--unknown"],
                ["runa:dev:status", "--host-request=spoof"],
            ):
                result = subprocess.run(
                    [launcher, *arguments], cwd=root, capture_output=True, text=True
                )
                check(
                    result.returncode != 0,
                    "invalid action/option/request rejected: " + arguments[-1],
                )
            check(
                not (root / ".env").exists()
                and not list((root / ".runtime/management").glob("*.json")),
                "no application secrets or reusable executable plan files were created",
            )
            check(
                all(
                    output(["docker", "inspect", "--format", "{{.Id}} {{.State.StartedAt}}", name])
                    == value
                    for name, value in before.items()
                ),
                "active development application identity/start time unchanged",
            )
            report["result"] = "PASS"
        finally:
            if (root / ".runtime/host/client.json").exists():
                output([launcher, "--stop-host"], cwd=root)
            containers = output(compose + ["ps", "--all", "-q"]).splitlines()
            networks = output(
                [
                    "docker",
                    "network",
                    "ls",
                    "-q",
                    "--filter",
                    "label=com.docker.compose.project=" + project,
                ]
            ).splitlines()
            volumes = output(
                [
                    "docker",
                    "volume",
                    "ls",
                    "-q",
                    "--filter",
                    "label=com.docker.compose.project=" + project,
                ]
            ).splitlines()
            report["cleanup"] = json_value(
                {
                    "containers": containers,
                    "networks": networks,
                    "volumes": volumes,
                    "preserved": [
                        "development/test data",
                        "knowledge/model caches",
                        "backups",
                        "verification reports",
                        "image/build caches",
                    ],
                }
            )
            print("Scoped disposable cleanup: " + json.dumps(report["cleanup"]), flush=True)
            output(compose + ["down", "--remove-orphans"])
            check(
                not output(compose + ["ps", "--all", "-q"]),
                "isolated acceptance containers removed",
            )
            report_path.write_text(json.dumps(report, indent=2) + "\n")
    print("Report: " + str(report_path))


if __name__ == "__main__":
    main()
