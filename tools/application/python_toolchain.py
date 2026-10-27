"""Frozen, container-owned Python development tools; never needed for cold operations."""

from __future__ import annotations

import base64
import hashlib
import io
import json
import os
import subprocess
import sys
import tarfile
import zipfile
from dataclasses import dataclass
from pathlib import Path, PurePosixPath
from urllib.request import urlopen

from tools.application.development_runtime import execution_location
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]
STATE = ROOT / ".runtime/python"
PYTHON = STATE / "venv/bin/python"


def environment(root: Path = ROOT) -> dict[str, str]:
    state = root / ".runtime/python"
    return dict(
        {
            key: value
            for key, value in os.environ.items()
            if key not in ("PYTHONPATH", "PYTHONHOME")
        },
        PYTHONPYCACHEPREFIX=str(state / "pycache"),
        PIP_CACHE_DIR=str(state / "cache/pip"),
        PIP_DISABLE_PIP_VERSION_CHECK="1",
        PYTEST_DISABLE_PLUGIN_AUTOLOAD="1",
        PYTEST_ADDOPTS="",
        PYTEST_PLUGINS="",
        PYRIGHT_TMPDIR=str(state / "cache/pyright"),
    )


def download(url: str, integrity: str) -> bytes:
    if not url.startswith(("https://files.pythonhosted.org/", "https://registry.npmjs.org/")):
        raise ValueError("Tool artifact must use its reviewed HTTPS registry.")
    algorithm, expected = integrity.split("-", 1)
    with urlopen(url, timeout=60) as response:
        payload = response.read(64 * 1024 * 1024 + 1)
    if len(payload) > 64 * 1024 * 1024 or algorithm not in ("sha256", "sha512"):
        raise ValueError("Invalid development tool artifact.")
    actual = base64.b64encode(hashlib.new(algorithm, payload).digest()).decode("ascii")
    if actual != expected:
        raise ValueError("Development tool integrity mismatch.")
    return payload


def artifact_path(destination: Path, name: str) -> Path:
    path = PurePosixPath(name)
    if path.is_absolute() or ".." in path.parts or "\\" in name:
        raise ValueError("Unsafe tool archive member.")
    target = destination.joinpath(*path.parts)
    if any(parent.is_symlink() for parent in (target, *target.parents) if parent != ROOT.parent):
        raise ValueError("Tool archive target must not traverse symlinks.")
    if not target.resolve().is_relative_to(destination.resolve()):
        raise ValueError("Tool archive escapes its destination.")
    return target


@dataclass(frozen=True)
class PythonToolchain:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation

    @property
    def state(self) -> Path:
        return self.workspace.runtime("python")

    @property
    def python(self) -> Path:
        return self.state / "venv/bin/python"

    def artifact_record(self, name: str) -> tuple[str, str]:
        value: object = json.loads(
            (self.workspace.root / "tools/application/python" / name).read_text()
        )
        if not isinstance(value, dict):
            raise ValueError("Invalid tool artifact lock.")
        url: object = value.get("url")
        integrity: object = value.get("integrity")
        if not isinstance(url, str) or not isinstance(integrity, str):
            raise ValueError("Invalid tool artifact lock fields.")
        return url, integrity

    def setup(self) -> int:
        """Install locked wheels and the official Pyright tarball without npm scripts."""
        from tools.application.repository_boundary_service import RepositoryPolicy

        RepositoryPolicy.for_workspace(self.workspace.root).assert_development_context(
            self.workspace.root
        )
        if self.location is not ExecutionLocation.CONTAINER:
            raise ValueError("Python development tools must be installed in the app container.")
        if any(
            path.is_symlink()
            for path in (self.workspace.root / ".runtime", self.state, self.state / "venv")
        ):
            raise ValueError("Python runtime directories must not be symlinks.")
        self.state.mkdir(parents=True, exist_ok=True)
        for directory in ("cache/pyright", "build", "reports"):
            (self.state / directory).mkdir(parents=True, exist_ok=True)
        if not self.python.is_file():
            self.processes.run(
                [sys.executable, "-m", "venv", "--without-pip", str(self.state / "venv")],
                check=True,
                cwd=self.workspace.root,
                env=environment(self.workspace.root),
            )
        # A verified pip wheel bootstraps Debian's stdlib venv without ensurepip or
        # a mutable get-pip script. Third-party source stays in the runtime tree.
        url, integrity = self.artifact_record("pip-bootstrap.lock.json")
        payload = download(url, integrity)
        bootstrap = self.state / "build/pip-bootstrap"
        with zipfile.ZipFile(io.BytesIO(payload)) as archive:
            if sum(member.file_size for member in archive.infolist()) > 64 * 1024 * 1024:
                raise ValueError("Oversized pip archive.")
            for member in archive.infolist():
                if member.is_dir():
                    continue
                target = artifact_path(bootstrap, member.filename)
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(archive.read(member))
        self.processes.run(
            [
                str(self.python),
                str(bootstrap / "pip"),
                "--isolated",
                "install",
                "--ignore-installed",
                "--require-hashes",
                "--only-binary=:all:",
                "-r",
                str(self.workspace.root / "tools/application/python/requirements.lock"),
            ],
            cwd=self.workspace.root,
            check=True,
            env=environment(self.workspace.root),
        )
        url, integrity = self.artifact_record("pyright.lock.json")
        payload = download(url, integrity)
        destination = self.state / "node/pyright"
        with tarfile.open(fileobj=io.BytesIO(payload), mode="r:gz") as archive:
            members = archive.getmembers()
            if sum(member.size for member in members) > 64 * 1024 * 1024:
                raise ValueError("Oversized Pyright archive.")
            for member in members:
                if member.isdir():
                    continue
                if not member.isfile() or not member.name.startswith("package/"):
                    raise ValueError("Unsupported Pyright archive member.")
                target = artifact_path(destination, member.name.removeprefix("package/"))
                stream = archive.extractfile(member)
                if stream is None:
                    raise ValueError("Missing Pyright archive member.")
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(stream.read())
        print("Installed frozen Python development tools under .runtime/python.")
        return 0


def setup() -> int:
    return PythonToolchain(
        WorkspaceContext(ROOT),
        ApplicationProcesses(SubprocessRunner(), environment()),
        execution_location(),
    ).setup()


if __name__ == "__main__":
    try:
        raise SystemExit(setup())
    except (ValueError, subprocess.CalledProcessError) as error:
        raise SystemExit(str(error)) from None
