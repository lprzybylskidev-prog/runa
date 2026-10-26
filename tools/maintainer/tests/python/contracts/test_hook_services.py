"""Staged Python formatting/lint and private history never overwrite dirty drafts."""

import hashlib
import json
import subprocess
from pathlib import Path

import pytest

from tools.application.hooks import pre_commit
from tools.application.processes import ApplicationProcesses
from tools.core.process import ProcessResult, SubprocessRunner
from tools.maintainer.hook_policy import immutable_history


def fixture(tmp_path):
    subprocess.run(["git", "init", "-q", str(tmp_path)], check=True)
    python = tmp_path / ".runtime/python/venv/bin/python"
    python.parent.mkdir(parents=True)
    python.touch()
    scanner = tmp_path / ".runtime/tools/gitleaks"
    scanner.parent.mkdir(parents=True)
    scanner.touch()
    target = tmp_path / "fixture.py"
    target.write_text("x=1\n")
    subprocess.run(["git", "-C", str(tmp_path), "add", "fixture.py"], check=True)
    return target


class HookRunner:
    def __init__(self, fail_lint=False):
        self.fail_lint = fail_lint
        self.scanned = None

    def run(self, spec):
        if spec.argv[0] == "git":
            return SubprocessRunner().run(spec)
        if "ruff" in spec.argv:
            if "format" in spec.argv:
                return ProcessResult(0, b"x = 1\n")
            return ProcessResult(1 if self.fail_lint else 0)
        if spec.argv[0].endswith("gitleaks"):
            self.scanned = (Path(spec.argv[2]) / "fixture.py").read_bytes()
            return ProcessResult(0)
        raise AssertionError(spec.argv)


def test_python_partial_staging_refused_without_index_or_worktree_mutation(tmp_path):
    target = fixture(tmp_path)
    target.write_text("x=2\n")
    runner = HookRunner()
    with pytest.raises(ValueError, match="partially staged"):
        pre_commit(tmp_path, check_knowledge=False, processes=ApplicationProcesses(runner, {}))
    assert target.read_bytes() == b"x=2\n"
    assert subprocess.check_output(["git", "-C", str(tmp_path), "show", ":fixture.py"]) == b"x=1\n"
    assert runner.scanned is None


def test_python_lint_failure_prevents_any_publication(tmp_path):
    target = fixture(tmp_path)
    with pytest.raises(subprocess.CalledProcessError):
        pre_commit(
            tmp_path, check_knowledge=False, processes=ApplicationProcesses(HookRunner(True), {})
        )
    assert target.read_bytes() == b"x=1\n"
    assert subprocess.check_output(["git", "-C", str(tmp_path), "show", ":fixture.py"]) == b"x=1\n"


def test_python_full_staging_formats_only_after_scan(tmp_path):
    target = fixture(tmp_path)
    runner = HookRunner()
    pre_commit(tmp_path, check_knowledge=False, processes=ApplicationProcesses(runner, {}))
    assert target.read_bytes() == b"x = 1\n"
    assert (
        subprocess.check_output(["git", "-C", str(tmp_path), "show", ":fixture.py"]) == b"x = 1\n"
    )
    assert runner.scanned == b"x=1\n"


def test_private_history_is_exact_and_receipt_always_removes_owner_scope(tmp_path):
    (tmp_path / "tools/maintainer").mkdir(parents=True)
    contract = tmp_path / "docs/runa/maintainer/agent-contract.md"
    contract.parent.mkdir(parents=True)
    contract.touch()
    name = "docs/runa/maintainer/evidence/history.md"
    mapping = dict(immutable_sha256={name: hashlib.sha256(b"history").hexdigest()})
    (contract.parent / "documentation-map.json").write_text(json.dumps(mapping))
    assert immutable_history(tmp_path, name, b"history")
    with pytest.raises(ValueError, match="Immutable"):
        immutable_history(tmp_path, name, b"changed")
    (tmp_path / ".git").mkdir()
    (tmp_path / ".git/runa-bootstrap.json").write_text("{}")
    assert not immutable_history(tmp_path, name, b"changed")


def test_closed_application_history_cannot_be_deleted_or_renamed(tmp_path):
    target = tmp_path / "docs/app/roadmaps/closed/RM-2026-999-fixture.md"
    target.parent.mkdir(parents=True)
    target.write_text("Immutable fixture history.\n")
    subprocess.run(["git", "init", "-q", str(tmp_path)], check=True)
    subprocess.run(["git", "-C", str(tmp_path), "add", str(target)], check=True)
    subprocess.run(
        [
            "git",
            "-C",
            str(tmp_path),
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.test",
            "commit",
            "-qm",
            "fixture",
        ],
        check=True,
    )
    target.rename(target.with_name("renamed.md"))
    subprocess.run(["git", "-C", str(tmp_path), "add", "-A"], check=True)
    with pytest.raises(ValueError, match="Immutable historical source deleted"):
        pre_commit(
            tmp_path, check_knowledge=False, processes=ApplicationProcesses(HookRunner(), {})
        )
