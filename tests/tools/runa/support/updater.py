#!/usr/bin/env python3
"""External updater failure-first source/plan contracts, real Git reference downloads."""

import json
import shutil
import subprocess
import tempfile
from collections.abc import Callable
from pathlib import Path
from typing import Unpack, cast
from unittest.mock import patch

import distribution.bootstrap as bootstrap
import distribution.update_customization as update_customization
import distribution.update_planning as update
from distribution.update_file_models import CustomizationReview
from distribution.update_models import RuntimeSnapshot, response_json
from distribution.update_plan_models import PreflightOptions, PreflightReport, ReleaseIdentity
from tools.core.state import JsonValue

ROOT = Path(__file__).resolve().parents[4]


class UpdaterWorkspace:
    def git(self, root: Path, *args: str) -> str:
        return (
            subprocess.check_output(["git", "-C", str(root), *args], stderr=subprocess.DEVNULL)
            .decode()
            .strip()
        )

    def commit(self, root: Path) -> str:
        self.git(root, "add", "--all")
        self.git(
            root,
            "-c",
            "core.hooksPath=/dev/null",
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.invalid",
            "commit",
            "-qm",
            "fixture",
        )
        return self.git(root, "rev-parse", "HEAD")

    def __init__(self, cleanup: Callable[[Callable[[], object]], object]) -> None:
        self.addCleanup = cleanup
        # Pure review contracts use JSON Compose data; native host acceptance exercises Docker's parser.
        self.native_configuration = update_customization.configuration

        def configuration(root: Path, name: str) -> dict[str, dict[str, JsonValue]]:
            value = response_json((root / name).read_text())
            assert isinstance(value, dict)
            services = value["services"]
            assert isinstance(services, dict)
            assert all(isinstance(service, dict) for service in services.values())
            return cast(dict[str, dict[str, JsonValue]], services)

        parser = patch.object(update_customization, "configuration", side_effect=configuration)
        parser.start()
        self.addCleanup(parser.stop)
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.original, self.target, self.installed = [
            self.base / name for name in ("original", "target", "installed")
        ]
        self.original.mkdir()
        files = {
            "VERSION": "0.2.1\n",
            "src/runa/platform.json": json.dumps(
                {"schema_version": 1, "id": "runa", "name": "Runa", "version": "0.2.1"}
            ),
            "src/app/application.json": json.dumps(
                {"schema_version": 1, "version": "7.4.2", "runa": ">=0.2.1 <0.4.0"}
            ),
            "src/app/composer.json": "{}",
            "src/app/package.json": "{}",
            "src/runa/composer.json": "{}",
            "src/runa/package.json": "{}",
            "src/composer.json": "{}",
            "src/package.json": "{}",
            "src/composer.lock": "{}",
            "src/pnpm-lock.yaml": "lock",
            "src/pnpm-workspace.yaml": "{}",
            "src/runa/Support/example.php": "<?php // protected",
            "compose.yaml": '{"services": {"app": {"image": "fixture"}}}',
            "src/runa/database/migrations/2026_01_01_000001_initial.php": "<?php // initial",
            "src/runa/database/seeders/DatabaseSeeder.php": "<?php // default",
            "tools/application/deployment_cli.py": '"""Fixture operation adapter."""\n',
            "docs/app/README.md": "customer",
        }
        for name, content in files.items():
            path = self.original / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
        self.git(self.original, "init", "-q")
        self.before = self.commit(self.original)
        self.git(
            self.original,
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.invalid",
            "tag",
            "-a",
            "v0.2.1",
            "-m",
            "stable",
        )
        shutil.copytree(self.original, self.target)
        (self.target / "VERSION").write_text("0.3.0\n")
        self.manifest(self.target, "0.3.0")
        (self.target / "src/runa/database/migrations/2026_01_02_000001_upgrade.php").write_text(
            "<?php // never execute"
        )
        self.after = self.commit(self.target)
        self.git(
            self.target,
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.invalid",
            "tag",
            "-a",
            "v0.3.0",
            "-m",
            "stable",
        )
        shutil.copytree(self.original, self.installed)
        self.installed.chmod(0o700)
        (self.installed / ".git/runa-bootstrap.json").write_text(
            json.dumps(
                {
                    "schema_version": 1,
                    "repository": "fixture/runa",
                    "mode": "dev",
                    "projection": {"version": 2, "excluded": list(bootstrap.OWNER_PATHS)},
                }
            )
        )
        (self.installed / ".runtime/production").mkdir(parents=True)
        (self.installed / ".runtime/production/install-dev.json").write_text('{"state":"complete"}')
        (self.installed / ".env").write_text("APP_ENV=local\n")
        (self.installed / ".env").chmod(0o600)
        self.client = bootstrap.GitHub("fixture/runa")
        self.runtime: RuntimeSnapshot = {
            "project": "fixture",
            "services": ["app", "postgres"],
            "migrations": ["2026_01_01_000001_initial"],
            "seed_runs": [],
            "modules": {},
            "module_steps": [],
        }

    def manifest(self, root: Path, version: str) -> None:
        (root / "src/runa/platform.json").write_text(
            json.dumps({"schema_version": 1, "id": "runa", "name": "Runa", "version": version})
        )

    def download(
        self,
        client: bootstrap.GitHub,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
    ) -> ReleaseIdentity:
        source = self.original if tag == "v0.2.1" else self.target
        shutil.copytree(source, destination)
        return {
            "tag": tag,
            "commit": self.before if tag == "v0.2.1" else self.after,
            "tag_object": "f" * 40,
        }

    def review(self) -> CustomizationReview:
        report = self.run_plan()
        assert "customization_review" in report
        return report["customization_review"]

    def run_plan(self, **kwargs: Unpack[PreflightOptions]) -> PreflightReport:
        planner = update.UpdatePlanner(
            download=self.download,
            runtime_probe=lambda root, mode, operator: self.runtime,
            lock_probe=lambda candidate, image: "sha256:" + "f" * 64,
        )
        return planner.preflight(
            self.installed,
            self.client,
            "v0.3.0",
            **kwargs,
        )

    def snapshot(self) -> dict[str, tuple[int, bytes]]:
        return {
            p.relative_to(self.installed).as_posix(): (p.stat().st_mode, p.read_bytes())
            for p in self.installed.rglob("*")
            if p.is_file() and not p.is_symlink()
        }
