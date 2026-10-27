"""Central discovery, legacy receipts and guarded generator paths without live services."""

from __future__ import annotations

import json
import re
import subprocess
from functools import partial
from pathlib import Path

import pytest

from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.application.testing.runner import APPLICATION, Selection
from tools.application.testing.runner import TestService as SuiteService
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext
from tools.core.delivery import LEGACY_OWNER_PATHS, OWNER_PATHS
from tools.maintainer.test_cli import OWNER

ROOT = Path(__file__).resolve().parents[4]


def test_central_layout_commands_keep_technology_and_owner_separate(tmp_path: Path) -> None:
    for layout, owner in ((APPLICATION, "app"), (OWNER, "runa")):
        path = tmp_path / "tests/tools" / owner / "test_example.py"
        path.parent.mkdir(parents=True)
        path.write_text("def test_example(): pass\n")
        service = SuiteService(WorkspaceContext(tmp_path), layout, RecordingRunner(), {})
        assert "--test-directory=../tests/backend/" + owner in service.command(
            "backend", Selection()
        )
        command = service.command("tooling", Selection(("tooling",)))
        assert str(path.parent) in command
        assert not any(
            "src/app/tests" in part or "tools/maintainer/tests" in part for part in command
        )
        with pytest.raises(ValueError, match="selected suite roots"):
            service.command(
                "tooling", Selection(("tooling",), python_paths=(str(tmp_path / "foreign.py"),))
            )


@pytest.mark.parametrize("version", [1, 2])
def test_exact_legacy_and_current_receipts_refuse_central_private_source(
    tmp_path: Path, version: int
) -> None:
    (tmp_path / ".git").mkdir()
    path = tmp_path / ".git/runa-bootstrap.json"
    excluded = LEGACY_OWNER_PATHS if version == 1 else OWNER_PATHS
    path.write_text(
        json.dumps(
            {"schema_version": 1, "projection": {"version": version, "excluded": list(excluded)}}
        )
    )
    original = path.read_bytes()
    processes = ApplicationProcesses(RecordingRunner(), {})
    policy = RepositoryPolicy(partial(processes.run, cwd=tmp_path), {})
    assert policy.receipt(tmp_path)["projection"] == {
        "version": version,
        "excluded": list(excluded),
    }
    private = tmp_path / "tests/tools/runa"
    private.mkdir(parents=True)
    with pytest.raises(ValueError, match="Owner distribution source"):
        policy.receipt(tmp_path)
    assert path.read_bytes() == original


def test_module_generator_owns_central_cases_and_runtime_needs_no_test_tree(tmp_path: Path) -> None:
    (tmp_path / "src/runa").mkdir(parents=True)
    (tmp_path / "src/runa/platform.json").write_text('{"version":"0.5.0"}')
    code = r"""
require $argv[1].'/src/vendor/autoload.php';
$root = $argv[2];
$generator = new Runa\Support\Modules\Development\ModuleGenerator($root, true);
$plan = $generator->plan('app.central_probe');
$expected = 'tests/backend/app/modules/central_probe/ModuleContractTest.php';
if (! isset($plan[$expected]) || isset($plan['src/app/modules/central_probe/tests/ModuleContractTest.php'])) { exit(11); }
$generator->write($plan);
$source = (new Runa\Support\Modules\ModuleDiscovery($root.'/src', true))->discover('app')[0];
if ($source->manifest->definition['tests'] !== ['tests/backend/app/modules/central_probe']) { exit(12); }
unlink($root.'/'.$expected);
rmdir($root.'/tests/backend/app/modules/central_probe');
if (count((new Runa\Support\Modules\ModuleDiscovery($root.'/src', true))->discover('app')) !== 1) { exit(13); }
try { $generator->write(['tests/backend/runa/modules/central_probe/Foreign.php' => '<?php']); exit(14); }
catch (InvalidArgumentException) {}
if (file_exists($root.'/tests/backend/runa')) { exit(15); }
"""
    result = subprocess.run(
        ["php", "-r", code, str(ROOT), str(tmp_path)], cwd=ROOT, capture_output=True, text=True
    )
    assert result.returncode == 0, result.stderr


def test_central_frontend_configurations_resolve_existing_src_dependencies() -> None:
    code = """
const app = (await import('./src/vitest.config.mjs')).default;
const owner = (await import('./src/runa/frontend/vitest.config.mjs')).default;
const browser = (await import('./src/runa/frontend/playwright.config.mjs')).default;
await import('./src/runa/Testing/playwright.ts');
if (!app.test.include[0].includes('tests/frontend/app/unit')) process.exit(11);
if (!owner.test.include[0].includes('tests/frontend/runa/unit')) process.exit(12);
if (!browser.testDir.endsWith('tests/frontend/runa/e2e')) process.exit(13);
"""
    result = subprocess.run(
        ["node", "--input-type=module", "-e", code], cwd=ROOT, capture_output=True, text=True
    )
    assert result.returncode == 0, result.stderr


def test_central_frontend_dynamic_imports_are_scannable_without_warm_cache() -> None:
    # Vitest scans every suite entry, including files excluded by a name filter.
    # A broken lazy import elsewhere must not abort that scan and reload the
    # browser with a second version of the stateful Vitest runtime.
    imports = re.compile(r"\bimport\s*\(\s*['\"]([^'\"]+)['\"]\s*\)")
    sources = ROOT / "tests/frontend"
    for path in sorted(sources.rglob("*")):
        if path.suffix not in {".ts", ".vue"}:
            continue
        for specifier in imports.findall(path.read_text()):
            assert specifier not in {"@vue/test-utils", "vitest"}, (
                f"{path.relative_to(ROOT)}: use the shared @runa/testing facade "
                f"for lazy {specifier} imports outside src/node_modules"
            )
            if specifier.startswith("."):
                target = path.parent / specifier
                candidates = (target, target.with_suffix(".ts"), target / "index.ts")
                assert any(candidate.is_file() for candidate in candidates), (
                    f"{path.relative_to(ROOT)}: unresolved lazy import {specifier}"
                )


def test_central_application_cases_are_customer_owned_and_preserved() -> None:
    from distribution.update_files import ownership
    from tools.application.customer_source_service import CustomerSource

    for root in ("tests/backend/app", "tests/frontend/app", "tests/tools/app"):
        path = root + "/modules/example/test_customer.py"
        assert ownership(path) == "application"
        assert CustomerSource.application_path(path)
    for root in ("tests/backend/runa", "tests/frontend/runa", "tests/tools/runa"):
        assert ownership(root + "/test_private.py") == "owner-excluded"


def test_unreconciled_legacy_cases_never_become_successful_empty_scope(tmp_path: Path) -> None:
    legacy = tmp_path / "src/app/tests/python/test_customer.py"
    legacy.parent.mkdir(parents=True)
    legacy.write_text("def test_customer(): pass\n")
    messages: list[str] = []
    runner = RecordingRunner()
    service = SuiteService(WorkspaceContext(tmp_path), APPLICATION, runner, {}, messages.append)
    result = service.run_scope("tooling", Selection(("tooling",)))
    assert result.status == "blocked" and result.exit_code == 1
    assert "explicit reconciliation" in messages[0]
    assert legacy.is_file() and not runner.calls
