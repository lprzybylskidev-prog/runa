"""Real private transports refuse scope overrides before Docker or application work."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

import pytest

from tools.application.testing.runner import Scope, Selection
from tools.application.testing.runner import TestService as SuiteService
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext
from tools.maintainer.test_cli import OWNER

ROOT = Path(__file__).resolve().parents[4]


@pytest.mark.parametrize(
    "name,arguments",
    [
        ("migrate", ["--path=app/database/migrations"]),
        ("seed", ["Database\\Seeders\\DatabaseSeeder"]),
        ("make-migration", ["example", "--path=app/database/migrations"]),
        ("quality-status", ["--all"]),
        ("test", ["--scope=unknown"]),
    ],
)
def test_owner_adapters_reject_scope_overrides_without_docker(
    tmp_path: Path, name: str, arguments: list[str]
) -> None:
    result = subprocess.run(
        [sys.executable, str(ROOT / "tools/maintainer" / name), *arguments],
        cwd=tmp_path,
        capture_output=True,
    )
    assert result.returncode != 0
    assert b"Traceback" not in result.stderr


def test_owner_native_test_configurations_are_explicit_and_separate(tmp_path: Path) -> None:
    service = SuiteService(WorkspaceContext(tmp_path), OWNER, RecordingRunner(), {})
    configurations: tuple[tuple[Scope, str], ...] = (
        ("backend", "../tests/backend/runa/phpunit.xml"),
        ("frontend", "../tools/maintainer/frontend/vitest.config.mjs"),
        ("e2e", "../tools/maintainer/frontend/playwright.config.mjs"),
    )
    for scope, config in configurations:
        assert any(
            argument.endswith("=" + config)
            for argument in service.command(scope, Selection((scope,)))
        )


@pytest.mark.parametrize(
    "name", ["test", "test-backend", "test-frontend", "test-e2e", "test-python"]
)
def test_test_transports_help_is_independent_of_cwd(tmp_path: Path, name: str) -> None:
    result = subprocess.run(
        [sys.executable, str(ROOT / "tools/maintainer" / name), "--help"],
        cwd=tmp_path,
        capture_output=True,
    )
    assert result.returncode == 0
    assert b"--scope" in result.stdout
