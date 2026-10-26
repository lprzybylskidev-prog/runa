"""Explicit package projection and private wheel preparation."""

from __future__ import annotations

import shutil
import subprocess
import sys
import tempfile
import tomllib
from pathlib import Path

from tools.maintainer.python_toolchain import PYTHON, ROOT, STATE, environment

METADATA = {
    "application": "pyproject.toml",
    "maintainer": "tools/maintainer/packaging/pyproject.toml",
    "external": "distribution/pyproject.toml",
}


def stage(kind: str, destination: Path, root: Path = ROOT) -> None:
    """Copy only declared packages and resources; never discover across roots."""
    metadata = root / METADATA[kind]
    config = tomllib.loads(metadata.read_text())
    settings = config["tool"]["setuptools"]
    destination.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(metadata, destination / "pyproject.toml")
    shutil.copyfile(root / "VERSION", destination / "VERSION")
    for package in settings["packages"]:
        relative = Path(*package.split("."))
        source = root / relative
        target = destination / relative
        target.mkdir(parents=True, exist_ok=True)
        for path in sorted(source.glob("*.py")):
            if path.is_symlink():
                raise ValueError("Package source must be a regular authored file.")
            shutil.copyfile(path, target / path.name)
        for resource in settings.get("package-data", {}).get(package, []):
            path = source / resource
            if not path.is_file() or not path.resolve().is_relative_to(root.resolve()):
                raise ValueError("Missing or escaping declared package resource.")
            output = target / resource
            output.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(path, output)


def build(kind: str, output: Path | None = None) -> Path:
    directory = STATE / "build"
    directory.mkdir(parents=True, exist_ok=True)
    output = (output or STATE / "build/wheels" / kind).resolve()
    output.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix=kind + "-", dir=directory) as temporary:
        source = Path(temporary)
        stage(kind, source)
        subprocess.run(
            [
                str(PYTHON),
                "-c",
                "from setuptools.build_meta import build_wheel; "
                "import sys; build_wheel(sys.argv[1])",
                str(output),
            ],
            cwd=source,
            check=True,
            env=environment(),
        )
    return output


if __name__ == "__main__":
    if len(sys.argv) != 2 or sys.argv[1] not in METADATA:
        raise SystemExit("Use python-tool packages --kind=application|maintainer|external.")
    print(build(sys.argv[1]))
