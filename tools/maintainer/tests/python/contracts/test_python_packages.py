"""Physical projections and wheel isolation, scheduled in final verification."""

from __future__ import annotations

import ast
import os
import subprocess
import sys
import tarfile
import tomllib
import zipfile
from pathlib import Path

import pytest

from tools.application.python_toolchain import ROOT
from tools.maintainer.python_packages import build, external_bundle, stage


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


def test_project_imports_use_qualified_packages_including_nested_imports() -> None:
    for directory in ("tools", "distribution", "bin"):
        for path in (ROOT / directory).rglob("*"):
            if path.is_symlink() or not path.is_file():
                continue
            if path.suffix != ".py" and not path.read_bytes().startswith(
                b"#!/usr/bin/env python3\n"
            ):
                continue
            for node in ast.walk(ast.parse(path.read_text())):
                if isinstance(node, ast.Import):
                    names = [alias.name for alias in node.names]
                elif isinstance(node, ast.ImportFrom):
                    assert node.level == 0, path
                    names = [node.module or ""]
                else:
                    continue
                for name in names:
                    top = name.split(".")[0]
                    assert (
                        top in sys.stdlib_module_names
                        or top in ("tools", "distribution")
                        or top == "pytest"
                        and path.is_relative_to(ROOT / "tools/maintainer/tests")
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
            "tools.application.development_runtime, tools.application.python_execution",
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
            "import tools.application.doctor_cli, tools.application.source_checks; "
            'assert not any(name.startswith("tools.maintainer") for name in sys.modules)',
        ],
        cwd=tmp_path,
        check=True,
        env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
    )


def test_delivered_deployment_and_installer_import_without_external_or_owner(
    tmp_path: Path,
) -> None:
    stage("application", tmp_path)
    subprocess.run(
        [
            sys.executable,
            "-E",
            "-s",
            "-c",
            "import sys; import tools.application.deployment_service, tools.application.installation_service, "
            "tools.application.installation_gateway, tools.application.production_cli; "
            'assert not any(name.startswith(("distribution", "tools.maintainer")) for name in sys.modules)',
        ],
        cwd=tmp_path,
        check=True,
        env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
    )


def test_application_metadata_declares_every_authored_package() -> None:
    configuration = tomllib.loads((ROOT / "pyproject.toml").read_text())
    declared = set(configuration["tool"]["setuptools"]["packages"])
    authored = {"tools"}
    for base in ("core", "application"):
        authored.update(
            ".".join(path.parent.relative_to(ROOT).parts)
            for path in (ROOT / "tools" / base).rglob("__init__.py")
        )
    assert declared == authored


def test_projected_application_modules_are_inert_and_physically_independent(tmp_path: Path) -> None:
    stage("application", tmp_path)
    subprocess.run(
        [
            sys.executable,
            "-E",
            "-s",
            "-c",
            "import importlib,pkgutil,sys,tools.application,tools.core; "
            "[importlib.import_module(item.name) for package in (tools.core,tools.application) "
            "for item in pkgutil.walk_packages(package.__path__,package.__name__+'.')]; "
            "assert not any(name.startswith(('distribution','tools.maintainer')) for name in sys.modules)",
        ],
        cwd=tmp_path,
        check=True,
        env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
    )


@pytest.mark.packaging
def test_external_source_bundle_needs_no_install_or_application_tree(tmp_path: Path) -> None:
    source, output, acquired = [tmp_path / name for name in ("source", "output", "acquired")]
    stage("external", source)
    output.mkdir()
    target = external_bundle(source, output)
    with tarfile.open(target) as archive:
        for member in archive.getmembers():
            assert member.isfile()
            assert not Path(member.name).is_absolute() and ".." not in Path(member.name).parts
            content = archive.extractfile(member)
            assert content is not None
            destination = acquired / member.name
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_bytes(content.read())
    assert not (acquired / "tools/application").exists()
    assert not (acquired / "tools/maintainer").exists()
    for launcher in (acquired / "distribution/update.py", output / "bootstrap.py"):
        result = subprocess.run(
            [sys.executable, "-E", "-s", str(launcher), "--help"],
            cwd=tmp_path,
            capture_output=True,
            env={key: value for key, value in os.environ.items() if key != "PYTHONPATH"},
        )
        assert result.returncode == 0, result.stderr
        assert b"usage:" in result.stdout.lower()


def test_migration_reconciliation_covers_every_baseline_identity() -> None:
    import json

    directory = ROOT / "docs/runa/maintainer/tooling/python-rebuild"
    baseline = json.loads((directory / "inventory.json").read_text())
    current = json.loads((directory / "reconciliation.json").read_text())
    assert current["baseline_commit"] == baseline["baseline_commit"]
    for group in (
        "components",
        "aliases",
        "resources",
        "non_python_references",
        "inline_integrations",
    ):
        rows = current[group]
        assert len(rows) == len({row["path"] for row in rows})
        assert {row["path"] for row in rows} == {row["path"] for row in baseline[group]}
        for row in rows:
            path = ROOT / row["path"]
            if row["disposition"] == "retired":
                assert not path.exists() and not path.is_symlink(), row["path"]
            else:
                assert path.is_file(), row["path"]
            for target in row.get("targets", []):
                assert (ROOT / target).is_file(), (row["path"], target)
            if group == "aliases":
                assert path.is_symlink() and os.readlink(path) == row["target"]
                assert path.resolve() == ROOT / row["resolved_target"]
