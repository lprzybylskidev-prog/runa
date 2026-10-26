"""Cold package adapters; full physical projection coverage remains a final gate."""

import os
import subprocess
import sys
from pathlib import Path

import pytest

from tools.maintainer.python_toolchain import ROOT


@pytest.mark.parametrize("alias", ["artisan", "pnpm", "composer", "boost-mcp"])
def test_help_from_unrelated_working_directory(alias: str, tmp_path: Path) -> None:
    result = subprocess.run(
        [str(ROOT / "bin" / alias), "--help"],
        cwd=tmp_path,
        env=dict(os.environ, PYTHONPYCACHEPREFIX=str(ROOT / ".runtime/python/pycache")),
        capture_output=True,
        text=True,
    )
    assert result.returncode == 0
    assert "Usage:" in result.stdout
    assert not result.stderr


def test_migrated_service_imports_do_not_dispatch_or_change_cwd(tmp_path: Path) -> None:
    code = """
import os, subprocess, sys

def refuse(*args, **kwargs):
    raise AssertionError('import performed an operation')

subprocess.Popen = refuse
os.chdir = refuse
sys.argv = ['untrusted', '--serve']
from tools.application import (artisan_cli, boost_cli, command_cli, passthrough_cli,
    hook_cli, git_hook, development, host_transport, management_service,
    repository_boundary_service, customer_source_service, dependencies, module_versions)
"""
    result = subprocess.run(
        [sys.executable, "-c", code],
        cwd=ROOT,
        env=dict(os.environ, PYTHONPYCACHEPREFIX=str(ROOT / ".runtime/python/pycache")),
        capture_output=True,
    )
    assert result.returncode == 0, result.stderr
