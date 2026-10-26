"""Application dependency/bootstrap setup with a content-bound freshness marker."""

import hashlib
from dataclasses import dataclass
from pathlib import Path

from tools.application.development_config import DevelopmentConfig
from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext


@dataclass(frozen=True)
class DevelopmentSetup:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    configuration: DevelopmentConfig

    def setup_fingerprint(self):
        paths = [
            self.workspace.root / p
            for p in (
                "src/composer.json",
                "src/runa/composer.json",
                "src/app/composer.json",
                "src/package.json",
                "src/runa/package.json",
                "src/app/package.json",
                "src/app/application.json",
                "src/runa/platform.json",
                "src/composer.lock",
                "src/pnpm-lock.yaml",
                "src/vite.config.mjs",
                "src/tsconfig.json",
                ".env",
                "tools/application/development.py",
                "tools/application/development_config.py",
                "tools/application/development_setup.py",
                "tools/application/development_runtime.py",
                "tools/application/python_toolchain.py",
                "tools/application/python/requirements.lock",
                "tools/application/python/pip-bootstrap.lock.json",
                "tools/application/python/pyright.lock.json",
                "tools/application/processes.py",
                "tools/application/dependencies.py",
            )
        ]
        for directory in (
            "src/runa/config",
            "src/app/config",
            "src/app/resources",
            "src/runa/database/migrations",
            "src/app/database/migrations",
            "resources",
        ):
            paths.extend(p for p in (self.workspace.root / directory).rglob("*") if p.is_file())
        digest = hashlib.sha256()
        for path in sorted(paths):
            digest.update(str(path.relative_to(self.workspace.root)).encode())
            digest.update(path.read_bytes())
        return digest.hexdigest()

    def register_boost(self):
        # Register in the container user's configuration before project trust is established.
        self.processes.run(
            [
                "codex",
                "mcp",
                "add",
                "laravel-boost",
                "--",
                str(self.workspace.root / "bin/boost-mcp"),
            ],
            cwd=self.workspace.root,
            check=True,
        )

    def register_hooks(self):
        # Owner hooks are configured deliberately by the maintainer workflow.
        if (self.workspace.root / "tools/maintainer").exists() or not (
            self.workspace.root / ".git"
        ).is_dir():
            return
        if any(key.startswith("GIT_") and key != "GIT_PAGER" for key in self.processes.environment):
            raise ValueError("Hook setup refuses inherited Git target overrides.")
        top = self.processes.run(
            ["git", "rev-parse", "--show-toplevel"],
            cwd=self.workspace.root,
            check=True,
            capture_output=True,
            text=True,
        ).stdout.strip()
        if Path(top).resolve() != self.workspace.root.resolve():
            raise ValueError("Hook setup requires the exact application checkout.")
        if not all(
            (self.workspace.root / ".githooks" / name).is_file()
            for name in ("pre-commit", "commit-msg")
        ):
            raise ValueError("Customer hook source is missing.")
        current = self.processes.run(
            ["git", "config", "--local", "--get", "core.hooksPath"],
            cwd=self.workspace.root,
            capture_output=True,
            text=True,
        )
        if current.returncode not in (0, 1):
            raise ValueError("Cannot inspect application hook configuration.")
        if current.returncode == 1:
            self.processes.run(
                ["git", "config", "--local", "core.hooksPath", ".githooks"],
                cwd=self.workspace.root,
                check=True,
            )

    def application_setup(self, args):
        self.configuration.environment_files()
        self.register_hooks()
        self.processes.run(
            ["python3", "tools/application/install-scanners.py"],
            cwd=self.workspace.root,
            check=True,
        )
        env = self.workspace.root / ".env"
        marker = self.workspace.root / ".runtime/dev/setup.sha256"
        required = [
            ".runtime/python/venv/bin/python",
            ".runtime/python/node/pyright/dist/pyright.js",
            "src/vendor/autoload.php",
            "src/node_modules/.modules.yaml",
            "src/public/build/index.html",
            "src/public/runa-error/styles.css",
            "src/public/runa-error/pages/503-pl-system.html",
        ]
        if (
            args == ["--if-needed"]
            and marker.exists()
            and marker.read_text().strip() == self.setup_fingerprint()
            and all((self.workspace.root / p).exists() for p in required)
        ):
            self.processes.run(
                ["php", "artisan", "runa:config:validate"], cwd=self.workspace.root, check=True
            )
            self.processes.run(
                ["php", "artisan", "runa:config:templates"], cwd=self.workspace.root, check=True
            )
            self.register_boost()
            print(
                "Development setup is current; dependency installation and frontend build skipped."
            )
            return 0
        self.processes.run(
            ["python3", "-m", "tools.application.python_toolchain"],
            cwd=self.workspace.root,
            check=True,
        )
        self.processes.run(
            ["python3", "-m", "tools.application.dependencies", "--check"],
            cwd=self.workspace.root,
            check=True,
        )
        self.processes.run(
            ["composer", "--working-dir=src", "install", "--no-interaction", "--prefer-dist"],
            cwd=self.workspace.root,
            check=True,
        )
        if any(line == "APP_KEY=" for line in env.read_text().splitlines()):
            self.processes.run(
                ["php", "artisan", "key:generate", "--force"], cwd=self.workspace.root, check=True
            )
        testing = self.workspace.root / ".env.testing"
        if "APP_KEY=\n" in testing.read_text():
            key = next(line for line in env.read_text().splitlines() if line.startswith("APP_KEY="))
            testing.write_text(testing.read_text().replace("APP_KEY=\n", key + "\n"))
        self.processes.run(
            ["php", "artisan", "runa:config:validate"], cwd=self.workspace.root, check=True
        )
        self.processes.run(
            ["php", "artisan", "runa:config:templates"], cwd=self.workspace.root, check=True
        )
        self.processes.run(
            ["pnpm", "--dir", "src", "install", "--frozen-lockfile"],
            cwd=self.workspace.root,
            check=True,
        )
        self.processes.run(
            ["pnpm", "--dir", "src", "run", "build"], cwd=self.workspace.root, check=True
        )
        self.processes.run(
            ["php", "src/runa/resources/errors/build.php"], cwd=self.workspace.root, check=True
        )
        self.register_boost()
        marker.parent.mkdir(parents=True, exist_ok=True)
        marker.write_text(self.setup_fingerprint() + "\n")
        return 0
