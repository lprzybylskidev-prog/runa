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
from pathlib import Path, PurePosixPath
from urllib.request import urlopen

ROOT = Path(__file__).resolve().parents[2]
STATE = ROOT / ".runtime/python"
PYTHON = STATE / "venv/bin/python"


def environment() -> dict[str, str]:
    return dict(
        {
            key: value
            for key, value in os.environ.items()
            if key not in ("PYTHONPATH", "PYTHONHOME")
        },
        PYTHONPYCACHEPREFIX=str(STATE / "pycache"),
        PIP_CACHE_DIR=str(STATE / "cache/pip"),
        PIP_DISABLE_PIP_VERSION_CHECK="1",
        PYTEST_DISABLE_PLUGIN_AUTOLOAD="1",
        PYTEST_ADDOPTS="",
        PYTEST_PLUGINS="",
        PYRIGHT_TMPDIR=str(STATE / "cache/pyright"),
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


def artifact_record(name: str) -> tuple[str, str]:
    value: object = json.loads((ROOT / "tools/maintainer" / name).read_text())
    if not isinstance(value, dict):
        raise ValueError("Invalid tool artifact lock.")
    url: object = value.get("url")
    integrity: object = value.get("integrity")
    if not isinstance(url, str) or not isinstance(integrity, str):
        raise ValueError("Invalid tool artifact lock fields.")
    return url, integrity


def setup() -> int:
    """Install locked wheels and the official Pyright tarball without npm scripts."""
    if not Path("/.dockerenv").is_file():
        raise ValueError("Python development tools must be installed in the app container.")
    if any(path.is_symlink() for path in (ROOT / ".runtime", STATE, STATE / "venv")):
        raise ValueError("Python runtime directories must not be symlinks.")
    STATE.mkdir(parents=True, exist_ok=True)
    for directory in ("cache/pyright", "build", "reports"):
        (STATE / directory).mkdir(parents=True, exist_ok=True)
    if not PYTHON.is_file():
        subprocess.run(
            [sys.executable, "-m", "venv", "--without-pip", str(STATE / "venv")],
            check=True,
            env=environment(),
        )
    # A verified pip wheel bootstraps Debian's stdlib venv without ensurepip or
    # a mutable get-pip script. Third-party source stays in the runtime tree.
    url, integrity = artifact_record("pip-bootstrap.lock.json")
    payload = download(url, integrity)
    bootstrap = STATE / "build/pip-bootstrap"
    with zipfile.ZipFile(io.BytesIO(payload)) as archive:
        if sum(member.file_size for member in archive.infolist()) > 64 * 1024 * 1024:
            raise ValueError("Oversized pip archive.")
        for member in archive.infolist():
            if member.is_dir():
                continue
            target = artifact_path(bootstrap, member.filename)
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(archive.read(member))
    subprocess.run(
        [
            str(PYTHON),
            str(bootstrap / "pip"),
            "--isolated",
            "install",
            "--ignore-installed",
            "--require-hashes",
            "--only-binary=:all:",
            "-r",
            str(ROOT / "tools/maintainer/requirements.lock"),
        ],
        cwd=ROOT,
        check=True,
        env=environment(),
    )
    url, integrity = artifact_record("pyright.lock.json")
    payload = download(url, integrity)
    destination = STATE / "node/pyright"
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


if __name__ == "__main__":
    if len(sys.argv) != 1:
        raise SystemExit("Use the fixed python-tool setup transport.")
    try:
        raise SystemExit(setup())
    except ValueError as error:
        raise SystemExit(str(error)) from None
    except subprocess.CalledProcessError as error:
        raise SystemExit(error.returncode) from None
