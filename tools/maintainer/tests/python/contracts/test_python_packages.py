"""Physical projections and wheel isolation, scheduled in final verification."""

import ast
import os
import subprocess
import sys
import zipfile
from pathlib import Path

import pytest

from tools.maintainer.python_packages import build, stage
from tools.maintainer.python_toolchain import ROOT


@pytest.mark.parametrize("kind", ["application", "maintainer", "external"])
def test_physical_projection_ownership(tmp_path: Path, kind: str) -> None:
    stage(kind, tmp_path)
    files = {p.relative_to(tmp_path).as_posix() for p in tmp_path.rglob("*") if p.is_file()}
    if kind == "application":
        assert "tools/core/__init__.py" in files
        assert "tools/application/quality.yml" in files
        assert not any(p.startswith(("tools/maintainer/", "distribution/")) for p in files)
        assert "tools/application/python/requirements.lock" in files
        assert not any("tests/python" in p for p in files)
    elif kind == "maintainer":
        assert "tools/maintainer/__init__.py" in files
        assert "tools/__init__.py" not in files
        assert not any(
            p.startswith(("tools/core/", "tools/application/", "distribution/")) for p in files
        )
    else:
        assert "tools/core/__init__.py" in files
        assert "distribution/module-plan.php" in files
        assert not any(p.startswith(("tools/application/", "tools/maintainer/")) for p in files)


def test_package_initializers_are_inert() -> None:
    for directory in (ROOT / "tools", ROOT / "distribution"):
        for path in directory.rglob("__init__.py"):
            tree = ast.parse(path.read_text())
            assert all(
                isinstance(node, ast.Expr) and isinstance(node.value, ast.Constant)
                for node in tree.body
            ), path


def test_core_depends_only_on_stdlib_and_core() -> None:
    for path in (ROOT / "tools/core").glob("*.py"):
        tree = ast.parse(path.read_text())
        for node in ast.walk(tree):
            if isinstance(node, ast.Import):
                names = [alias.name for alias in node.names]
            elif isinstance(node, ast.ImportFrom):
                assert node.level == 0, path
                names = [node.module or ""]
            else:
                continue
            for name in names:
                assert name.startswith("tools.core.") or name.split(".")[0] in (
                    sys.stdlib_module_names
                ), (path, name)


@pytest.mark.packaging
def test_wheels_layer_without_file_collisions_and_import_outside_checkout(tmp_path: Path) -> None:
    outputs = {
        kind: build(kind, tmp_path / kind) for kind in ("application", "maintainer", "external")
    }
    contents: dict[str, set[str]] = {}
    for kind, output in outputs.items():
        wheels = list(output.glob("*.whl"))
        assert len(wheels) == 1
        with zipfile.ZipFile(wheels[0]) as archive:
            contents[kind] = set(archive.namelist())
            archive.extractall(tmp_path / ("external-site" if kind == "external" else "owner-site"))
    assert not contents["application"] & contents["maintainer"]
    for site, modules in (
        (
            "owner-site",
            "tools.core.context, tools.core.state, tools.core.process, "
            "tools.application.development_runtime, tools.maintainer.python_execution",
        ),
        ("external-site", "tools.core.context, tools.core.state, tools.core.process, distribution"),
    ):
        subprocess.run(
            [sys.executable, "-E", "-s", "-c", "import " + modules],
            cwd=tmp_path / site,
            check=True,
            env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
        )


def test_standalone_owner_launcher_help_from_unrelated_cwd(tmp_path: Path) -> None:
    result = subprocess.run(
        [sys.executable, str(ROOT / "tools/maintainer/test-python"), "--help"],
        cwd=tmp_path,
        capture_output=True,
        text=True,
    )
    assert result.returncode == 0
    assert "--scope" in result.stdout


def test_delivered_quality_security_and_hooks_import_without_private_packages(
    tmp_path: Path,
) -> None:
    stage("application", tmp_path)
    subprocess.run(
        [
            sys.executable,
            "-E",
            "-s",
            "-c",
            "import sys; import tools.application.quality_cli, tools.application.security_cli, "
            "tools.application.hooks, tools.application.python_cli, tools.application.diagnostics; "
            "import tools.application.executors.doctor, tools.application.executors.quality_check; "
            'assert not any(name.startswith("tools.maintainer") for name in sys.modules)',
        ],
        cwd=tmp_path,
        check=True,
        env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
    )
