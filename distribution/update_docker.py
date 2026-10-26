"""Runtime ownership, operation fencing and bounded disposable Docker workers."""

import json
import re
import sys
import uuid

from distribution import update_runtime
from distribution.update_contracts import Blocker


class DockerRuntime:
    def __init__(self, root, *, command=update_runtime.command):
        self.transport = command
        self.root = root
        self.args = update_runtime.compose(root, "dev", root)
        self.project = json.loads(self.transport([*self.args, "config", "--format", "json"], root))[
            "name"
        ]
        if not re.fullmatch("[a-z0-9][a-z0-9_-]{0,127}", self.project):
            raise Blocker("runtime-ownership", "Unsupported Compose project identity.")
        self.args[2:2] = ["--project-name", self.project]

    def bind(self, project):
        if project != self.project:
            raise Blocker(
                "runtime-ownership",
                "The journaled Compose project changed; preserve and reconcile its environment before resuming.",
            )

    def command(self, arguments, timeout=1800):
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
                actual = json.loads(self.transport(["docker", "inspect", identity]))[0]
                labels = actual["Config"].get("Labels", {})
                if (
                    actual["Name"] != "/" + name
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

    def probe(self, *args):
        return update_runtime.probe(self.root, "dev", self.root)

    def metadata(self):
        container = self.command(["ps", "--quiet", "postgres"]).strip()
        actual = json.loads(self.transport(["docker", "inspect", container]))[0]
        labels = actual["Config"].get("Labels", {})
        if (
            labels.get("com.docker.compose.project.working_dir") != str(self.root)
            or labels.get("com.docker.compose.project") != self.project
        ):
            raise Blocker(
                "runtime-ownership", "Database container does not belong to this checkout/project."
            )
        mounts = sorted(
            (item["Type"], item.get("Name") or item.get("Source"), item["Destination"])
            for item in actual.get("Mounts", [])
            if item["Type"] in ("bind", "volume")
        )
        return {**update_runtime.metadata(self.root, self.args), "database_mounts": mounts}

    def marker(self, action, identity):
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

    def manual(self):
        if self.marker("check", "0" * 32) != "clear":
            raise Blocker(
                "manual-maintenance",
                "Manual HTTP/Laravel maintenance is active; preserve it before updating.",
            )

    def fence(self, identity):
        try:
            self.marker("write", identity)
        finally:
            self.command(["stop", "nginx", "queue", "scheduler", "app"])

    def build(self):
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

    def data(self, phase, release):
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

    def activate(self, identity):
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

    def health(self):
        self.probe()
        self.command(
            ["exec", "-T", "nginx", "wget", "-qO", "/dev/null", "http://127.0.0.1:8080/api/ready"]
        )
