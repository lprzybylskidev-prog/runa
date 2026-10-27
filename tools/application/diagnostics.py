"""Read-only application diagnostics over injected workspace and process policies."""

from __future__ import annotations

import json
import os
import re
import time
import urllib.request
from dataclasses import dataclass
from functools import partial
from pathlib import Path

from tools.application.processes import ApplicationProcesses, WorkspaceProcess
from tools.core.context import WorkspaceContext


@dataclass(frozen=True)
class Diagnostics:
    workspace: WorkspaceContext
    processes: ApplicationProcesses

    @classmethod
    def for_workspace(cls, root: Path) -> Diagnostics:
        from tools.core.process import SubprocessRunner

        return cls(
            WorkspaceContext(root), ApplicationProcesses(SubprocessRunner(), dict(os.environ))
        )

    @property
    def command(self) -> WorkspaceProcess:
        return partial(self.processes.run, cwd=self.workspace.root, check=True)

    def repository(self) -> None:
        active = list((self.workspace.root / "docs/app/roadmaps/active").glob("*.md"))
        if len(active) > 1:
            raise ValueError("Multiple active application roadmaps.")
        if active:
            content = active[0].read_text()
            branch = re.search(r"^branch: (.+)$", content, re.M)
            actual = self.command(
                ["git", "branch", "--show-current"], capture_output=True, text=True
            ).stdout.strip()
            if not branch or actual != branch[1] or "\nstatus: active\n" not in content:
                raise ValueError("Active application roadmap status/branch mismatch.")
        print("PASS: application repository/roadmap state.")

    def maintenance(self) -> None:
        self.repository()
        print("PASS: application maintenance context; platform release maintenance is owner-only.")

    def docs(self) -> None:
        from tools.application.docs_checks import validate

        validate(self.workspace.root)

    def tooling(self) -> None:
        for path in sorted((self.workspace.root / "bin").iterdir()):
            if path.is_file() and os.access(path, os.X_OK) and path.name != "check":
                result = self.command([str(path), "--help"], capture_output=True, text=True)
                if not result.stdout.strip():
                    raise ValueError("Missing help output: " + path.name)
        scanner = self.workspace.root / ".runtime/tools/gitleaks"
        if (
            self.command([str(scanner), "version"], capture_output=True, text=True).stdout.strip()
            != "8.30.1"
        ):
            raise ValueError("Pinned Gitleaks version mismatch.")
        php = self.command(
            ["php", "-r", "echo json_encode([PHP_VERSION, get_loaded_extensions()]);"],
            capture_output=True,
            text=True,
        )
        version, extensions = json.loads(php.stdout)
        required = {
            "pdo_pgsql",
            "mbstring",
            "intl",
            "bcmath",
            "Zend OPcache",
            "pcntl",
            "zip",
            "curl",
            "xml",
            "xdebug",
        }
        if version != "8.4.27" or not required.issubset(extensions):
            raise ValueError("PHP version/extensions differ from the development toolchain.")
        for command, expected in [
            (["node", "--version"], "v24.22.0"),
            (["pnpm", "--version"], "12.14.0"),
            (["composer", "--version"], "2.10.3"),
            (["codex", "--version"], "0.163.0"),
        ]:
            if expected not in self.command(command, capture_output=True, text=True).stdout:
                raise ValueError("Pinned tool version mismatch: " + command[0])
        print(
            "PASS: command help/exit contracts, PHP extensions and pinned PHP/Node/pnpm/Composer/Codex/Gitleaks."
        )

    def development(self) -> None:
        self.command(["php", "artisan", "runa:config:validate"])
        self.command(["php", "artisan", "runa:config:templates"])
        if any((self.workspace.root / "src/app/database/migrations").glob("*.php")):
            self.command(["php", "artisan", "migrate:status", "--no-ansi"])
        for service, limit in (("queue", 120), ("scheduler", 150)):
            heartbeat = json.loads(
                (
                    self.workspace.root / "src/storage/framework/health" / (service + ".json")
                ).read_text()
            )
            age = time.time() - heartbeat.get("recorded_at", 0)
            if not heartbeat.get("instance") or not 0 <= age <= limit:
                raise ValueError(service + " shared heartbeat is missing/stale.")
        for endpoint in ("http://nginx:8080/api/health", "http://mailpit:8025/readyz"):
            with urllib.request.build_opener(urllib.request.ProxyHandler({})).open(
                endpoint, timeout=10
            ) as response:
                if response.status != 200:
                    raise ValueError("Development HTTP health failed.")
        self.command(["php", "artisan", "runa:dev:knowledge-status"])
        self.command(["php", "artisan", "runa:dev:profile"])
        print(
            "PASS: config, database/migrations, queue/scheduler, Nginx, Mailpit and effective resources; optional AI availability reported separately."
        )

    def boost(self) -> None:
        commands = self.command(
            ["php", "artisan", "list", "--raw"], capture_output=True, text=True
        ).stdout
        if "boost:mcp" not in commands:
            raise ValueError("Boost MCP Artisan command missing.")
        result = self.command(
            ["codex", "mcp", "get", "laravel-boost", "--json"], capture_output=True, text=True
        )
        registration = json.loads(result.stdout)
        if "boost-mcp" not in json.dumps(registration):
            raise ValueError("Boost MCP registration missing canonical launcher.")
        if any(
            (self.workspace.root / ".agents/skills" / name).exists()
            for name in ("laravel-boost", "pest-testing", "tailwindcss-development")
        ):
            raise ValueError("Boost-generated skills must be removed.")
        print(
            "PASS: Boost command/MCP registration and generated-skill cleanup; live version-aware documentation access is verified separately."
        )
