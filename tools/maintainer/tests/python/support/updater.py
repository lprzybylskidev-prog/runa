#!/usr/bin/env python3
"""External updater failure-first source/plan contracts, real Git reference downloads."""

import json
import shutil
import subprocess
import tempfile
from pathlib import Path
from unittest.mock import patch

import distribution.bootstrap as bootstrap
import distribution.update_customization as update_customization
import distribution.update_planning as update

ROOT = Path(__file__).resolve().parents[5]


class UpdaterWorkspace:
    def git(self, root, *args):
        return (
            subprocess.check_output(["git", "-C", str(root), *args], stderr=subprocess.DEVNULL)
            .decode()
            .strip()
        )

    def commit(self, root):
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

    def __init__(self, cleanup):
        self.addCleanup = cleanup
        # Pure review contracts use JSON Compose data; native host acceptance exercises Docker's parser.
        self.native_configuration = update_customization.configuration
        parser = patch.object(
            update_customization,
            "configuration",
            side_effect=lambda root, name: json.loads((root / name).read_text())["services"],
        )
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
            "tools/application/operations.py": 'SPARSE = ["/VERSION", "/src/", "/tools/application/"]\n',
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
                    "projection": {"version": 1, "excluded": list(bootstrap.OWNER_PATHS)},
                }
            )
        )
        (self.installed / ".runtime/production").mkdir(parents=True)
        (self.installed / ".runtime/production/install-dev.json").write_text('{"state":"complete"}')
        (self.installed / ".env").write_text("APP_ENV=local\n")
        (self.installed / ".env").chmod(0o600)
        self.client = bootstrap.GitHub("fixture/runa")
        self.runtime = {
            "project": "fixture",
            "services": ["app", "postgres"],
            "migrations": ["2026_01_01_000001_initial"],
            "seed_runs": [],
        }

    def manifest(self, root, version):
        (root / "src/runa/platform.json").write_text(
            json.dumps({"schema_version": 1, "id": "runa", "name": "Runa", "version": version})
        )

    def download(self, client, tag, destination, mode, expected_commit=None):
        source = self.original if tag == "v0.2.1" else self.target
        shutil.copytree(source, destination)
        return {
            "tag": tag,
            "commit": self.before if tag == "v0.2.1" else self.after,
            "tag_object": "f" * 40,
        }

    def run_plan(self, **kwargs):
        return update.preflight(
            self.installed,
            self.client,
            "v0.3.0",
            download=self.download,
            runtime_probe=lambda *a: self.runtime,
            lock_probe=lambda *a: "sha256:" + "f" * 64,
            **kwargs,
        )

    def snapshot(self):
        return {
            p.relative_to(self.installed).as_posix(): (p.stat().st_mode, p.read_bytes())
            for p in self.installed.rglob("*")
            if p.is_file() and not p.is_symlink()
        }
