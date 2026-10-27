"""Runtime ownership, operation fencing and bounded disposable Docker workers."""

import re
import sys
import uuid
from collections.abc import Sequence
from pathlib import Path

from distribution import update_runtime
from distribution.update_contracts import Blocker
from distribution.update_models import (
    DatabaseMetadata,
    RuntimeSnapshot,
    compose_configuration,
    container_inspection,
    project_identity,
)


class DockerRuntime:
    def __init__(self, root: Path, *, command: update_runtime.RuntimeCommand | None = None) -> None:
        self.transport = command or update_runtime.command
        self.root = root
        self.args = update_runtime.compose(root, "dev", root)
        self.project = project_identity(
            compose_configuration(self.transport([*self.args, "config", "--format", "json"], root))
        )
        if not re.fullmatch("[a-z0-9][a-z0-9_-]{0,127}", self.project):
            raise Blocker("runtime-ownership", "Unsupported Compose project identity.")
        self.args[2:2] = ["--project-name", self.project]

    def bind(self, project: str) -> None:
        if project != self.project:
            raise Blocker(
                "runtime-ownership",
                "The journaled Compose project changed; preserve and reconcile its environment before resuming.",
            )

    def command(self, arguments: Sequence[str], timeout: float = 1800) -> str:
        if arguments[0] != "run":
            return self.transport([*self.args, *arguments], self.root, timeout=timeout)
        # Killing a Compose client must not leave a detached data/build writer.
        name = "runa-update-step-" + uuid.uuid4().hex
        try:
            return self.transport(
                [*self.args, "run", "--name", name, *arguments[1:]], self.root, timeout=timeout
            )
        finally:
            ids = self.transport(
                ["docker", "ps", "-aq", "--filter", "name=^/" + name + "$"]
            ).split()
            for identity in ids:
                actual = container_inspection(self.transport(["docker", "inspect", identity]))
                labels = actual["Config"].get("Labels") or {}
                if (
                    actual.get("Name") != "/" + name
                    or labels.get("com.docker.compose.project") != self.project
                    or labels.get("com.docker.compose.project.working_dir") != str(self.root)
                    or labels.get("com.docker.compose.oneoff", "").lower() != "true"
                ):
                    raise Blocker(
                        "runtime-ownership",
                        "Unexpected one-off identity; preserve it for scoped inspection.",
                    )
                print(
                    "Scoped updater one-off cleanup: " + name + " (" + identity + ")",
                    file=sys.stderr,
                )
                self.transport(["docker", "rm", "--force", identity])

    def probe(self, *args: object) -> RuntimeSnapshot:
        return update_runtime.probe(self.root, "dev", self.root, transport=self.transport)

    def metadata(self) -> DatabaseMetadata:
        container = self.command(["ps", "--quiet", "postgres"]).strip()
        actual = container_inspection(self.transport(["docker", "inspect", container]))
        labels = actual["Config"].get("Labels") or {}
        if (
            labels.get("com.docker.compose.project.working_dir") != str(self.root)
            or labels.get("com.docker.compose.project") != self.project
        ):
            raise Blocker(
                "runtime-ownership", "Database container does not belong to this checkout/project."
            )
        mounts = sorted(
            (item["Type"], item.get("Name") or item["Source"], item["Destination"])
            for item in actual.get("Mounts", [])
            if item["Type"] in ("bind", "volume")
        )
        return {
            **update_runtime.metadata(self.root, self.args, transport=self.transport),
            "database_mounts": mounts,
        }

    def marker(self, action: str, identity: str) -> str:
        # Fixed PHP program, no installed code or environment output. One-off
        # Compose mounts observe the actual selected storage even with custom volumes.
        code = r"""$directory="/workspace/src/storage/framework/maintenance";
$path=$directory."/enabled"; $down="/workspace/src/storage/framework/down";
$expected="runa-update:".$argv[2]."\n";
if (is_link($directory)||is_link($path)||is_link($down)) { exit(1); }
if ($argv[1]==="check") { echo (is_file($path)||is_file($down)) ? "manual" : "clear"; exit(0); }
if ($argv[1]==="write") {
 if (is_file($path)&&file_get_contents($path)!==$expected) { exit(1); }
 if (!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory)) { exit(1); }
 exit(file_put_contents($path,$expected,LOCK_EX)===false ? 1 : 0);
}
if ($argv[1]==="clear") {
 if (is_file($down)||!is_file($path)||file_get_contents($path)!==$expected) { exit(1); }
 exit(unlink($path) ? 0 : 1);
}
exit(1);"""
        return self.command(
            [
                "run",
                "--rm",
                "--no-deps",
                "-T",
                "--entrypoint",
                "php",
                "app",
                "-r",
                code,
                "--",
                action,
                identity,
            ]
        ).strip()

    def manual(self) -> None:
        if self.marker("check", "0" * 32) != "clear":
            raise Blocker(
                "manual-maintenance",
                "Manual HTTP/Laravel maintenance is active; preserve it before updating.",
            )

    def fence(self, identity: str) -> None:
        try:
            self.marker("write", identity)
        finally:
            self.command(["stop", "nginx", "queue", "scheduler", "app"])

    def build(self) -> None:
        commands = [
            ["composer", "--working-dir=src", "install", "--no-interaction", "--prefer-dist"],
            ["php", "artisan", "config:clear", "--no-interaction"],
            ["pnpm", "--dir", "src", "install", "--frozen-lockfile"],
            ["pnpm", "--dir", "src", "run", "build"],
            ["php", "src/runa/resources/errors/build.php"],
            ["php", "artisan", "runa:config:validate"],
            ["php", "artisan", "runa:config:templates"],
        ]
        for arguments in commands:
            self.command(["run", "--rm", "--no-deps", "-T", "app", *arguments])

    def data(self, phase: str, release: str) -> None:
        self.command(
            [
                "run",
                "--rm",
                "--no-deps",
                "-T",
                "app",
                "php",
                "src/runa/database/prepare.php",
                release,
                phase,
            ]
        )

    def activate(self, identity: str) -> None:
        self.marker("clear", identity)
        self.command(
            [
                "up",
                "-d",
                "--wait",
                "--wait-timeout",
                "180",
                "--no-build",
                "--no-deps",
                "app",
                "queue",
                "scheduler",
                "nginx",
            ],
            timeout=240,
        )

    def health(self) -> None:
        self.probe()
        self.command(
            ["exec", "-T", "nginx", "wget", "-qO", "/dev/null", "http://127.0.0.1:8080/api/ready"]
        )
