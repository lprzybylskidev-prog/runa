"""Producer-bound native recovery; fixtures simulate outcomes, never PHP faults."""

from __future__ import annotations

import json
import subprocess
from dataclasses import dataclass, field
from pathlib import Path
from xml.sax.saxutils import quoteattr

import pytest

from tests.tools.runa.support.quality import FixtureReader, node
from tools.application.quality_evidence import ClosurePolicy, check_evidence, clean_worker_result
from tools.application.quality_models import QualityDefinition
from tools.application.quality_planning import resolve
from tools.application.quality_reporting import save
from tools.application.quality_state import run_record
from tools.application.testing.runner import Selection
from tools.application.testing.runner import TestService as SuiteService
from tools.core.context import WorkspaceContext
from tools.core.process import ProcessResult, ProcessSpec, SubprocessRunner
from tools.core.state import JsonValue
from tools.maintainer.test_cli import OWNER

CASES = ("testRecovered#(false)", "testBefore", "testRemaining")
# Generated zero-issue PHPUnit 13.4.5 / ParaTest 7.27.4 receipt: counters only; NULs escaped.
NATIVE_RESULT = Path(__file__).parents[1] / "fixtures/native-clean-worker-result.txt"


def worker_result(count: int) -> bytes:
    content = NATIVE_RESULT.read_bytes().removesuffix(b"\n").replace(b"\\0", b"\0")
    for name, old, new in (
        ("numberOfTests", 36, count),
        ("numberOfTestsRun", 148, count),
        ("numberOfAssertions", 1598, count * 2),
    ):
        content = content.replace(
            (name + '";i:' + str(old) + ";").encode(), (name + '";i:' + str(new) + ";").encode()
        )
    return content


def escape(value: str) -> str:
    for original, replacement in (
        ("|", "||"),
        ("'", "|'"),
        ("\n", "|n"),
        ("\r", "|r"),
        ("[", "|["),
        ("]", "|]"),
    ):
        value = value.replace(original, replacement)
    return value


def events(
    cases: tuple[str, ...], interrupted: str | None = None, assertion: bool = False
) -> bytes:
    lines: list[str] = []
    for case in cases:
        method, separator, dataset = case.partition("#")
        name = method + (' with data set "' + dataset + '"' if separator else "")
        attrs = "name='" + escape(name) + "' flowId='42'"
        lines.append(
            "##teamcity[testStarted "
            + attrs
            + " locationHint='php_qn:///fixture.php::\\Example::"
            + escape(name)
            + "']"
        )
        if case != interrupted:
            if assertion:
                lines.append("##teamcity[testFailed " + attrs + " message='assertion']")
            lines.append("##teamcity[testFinished " + attrs + "]")
    return ("\n".join(lines) + "\n").encode()


@dataclass
class NativeFixture:
    cases: tuple[str, ...]
    crash: str | None = None
    assertion: bool = False
    cleanup: int = 0
    not_started: tuple[str, ...] = ()
    fail_finish: bool = False
    native_completed: bool = False
    unsealed_completed: bool = False
    warning: bool = False
    calls: list[ProcessSpec] = field(default_factory=list)
    cleanups: int = 0

    def run(self, spec: ProcessSpec) -> ProcessResult:
        self.calls.append(spec)
        if spec.argv[0] == "git":
            if self.fail_finish and self.native_completed:
                return ProcessResult(7)
            return SubprocessRunner().run(spec)
        if spec.argv[0] == "php":
            if "-r" in spec.argv:
                return ProcessResult(0, b'["fixture PHP", "fixture ini"]')
            self.cleanups += 1
            return ProcessResult(self.cleanup if self.cleanups == 2 else 0)
        assert spec.argv[0] == "vendor/bin/pest"
        target = next(
            (
                value.split("=", 1)[1]
                for value in spec.argv
                if value.startswith("--list-tests-xml=")
            ),
            None,
        )
        if target:
            Path(target).write_text(
                '<testSuite xmlns="https://xml.phpunit.de/testSuite"><tests><testClass name="Example">'
                + "".join(
                    "<testMethod name="
                    + quoteattr(case.partition("#")[0])
                    + " id="
                    + quoteattr("Example::" + case)
                    + "/>"
                    for case in self.cases
                )
                + "</testClass></tests></testSuite>"
            )
            return ProcessResult(0)
        output = next(
            (value.split("=", 1)[1] for value in spec.argv if value.startswith("--log-teamcity=")),
            None,
        )
        if output is None:
            return ProcessResult(0)
        directory = Path(output).parent
        executed = tuple(
            case for case in self.cases if case not in self.not_started and case != self.crash
        ) + ((self.crash,) if self.crash else ())
        teamcity = events(executed, self.crash, self.assertion)
        (directory / "teamcity.log").write_bytes(teamcity if not self.crash else b"")
        completed = tuple(case for case in executed if case != self.crash)
        if self.crash and not self.unsealed_completed:
            (directory / "worker_01_stdout_fixture_teamcity").write_bytes(
                events((self.crash,), self.crash)
            )
            (directory / "worker_02_stdout_fixture_teamcity").write_bytes(
                events(completed, assertion=self.assertion)
            )
            summary = worker_result(len(completed))
            if self.warning:
                summary = summary.replace(
                    b'phpWarnings";a:0:{}', b'phpWarnings";a:1:{i:0;s:7:"warning";}'
                )
            (directory / "worker_02_stdout_fixture_test_result").write_bytes(summary)
        else:
            (directory / "worker_01_stdout_fixture_teamcity").write_bytes(teamcity)
        if not self.crash:
            (directory / "junit.xml").write_text(
                '<testsuites><testsuite errors="0" failures="0">'
                + "".join(
                    '<testcase classname="Example" name=' + quoteattr(case) + "/>"
                    for case in executed
                )
                + "</testsuite></testsuites>"
            )
        console = (
            b"In WorkerCrashedException.php line 41:\nTEST_TOKEN='1'\nExit Code: 139(Segmentation violation)\n"
            if self.crash
            else b"Native completed.\n"
        )
        self.native_completed = True
        return ProcessResult(1 if self.crash or self.assertion else 0, console)


@dataclass
class RecoveryFixture:
    root: Path
    candidate: str
    references: list[str] = field(default_factory=list)

    def execute(
        self,
        cases: tuple[str, ...],
        *,
        crash: str | None = None,
        assertion: bool = False,
        cleanup: int = 0,
        original: bool = False,
        not_started: tuple[str, ...] = (),
    ) -> NativeFixture:
        runner = NativeFixture(cases, crash, assertion, cleanup, not_started)
        environment = (
            {"RUNA_QUALITY_RUN_ID": "a" * 32, "RUNA_QUALITY_STEP": "backend-tests"}
            if original
            else {}
        )
        service = SuiteService(
            WorkspaceContext(self.root), OWNER, runner, environment, lambda _: None
        )
        selection = Selection(
            ("backend",),
            ("--parallel",),
            filter="" if original else "native-selection",
            evidence=True,
        )
        service.execute(selection)
        latest = self.root / ".runtime/tests/runa-results.json"
        target = (
            self.root / ".runtime/verification/retained" / (str(len(self.references)) + ".json")
        )
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(latest.read_bytes())
        self.references.append(str(target.relative_to(self.root)))
        return runner

    def check(
        self,
        *,
        policy: ClosurePolicy | None = None,
        missing_gate: bool = False,
        normal: bool = False,
    ) -> None:
        backend = node(
            "backend-tests", "python3 tools/maintainer/test-backend --parallel --evidence"
        )
        backend["terminal"] = True
        definition: QualityDefinition = {
            "base": [node("other-check"), backend],
            "impact_areas": {},
            "final": [node("workflow-tests")],
            "groups": {"backend": ["backend-tests"]},
            "mandatory": ["other-check", "workflow-tests"],
        }
        graph, selection = resolve(definition, [], groups=["backend"])
        state = run_record(
            {
                "run_id": "a" * 32,
                "status": "passed" if normal else "failed",
                "mode": "final",
                "roadmap": "fixture",
                "branch": "roadmap/fixture",
                "commit": self.candidate,
                "rationale": "fixture scope",
                "selection": selection,
                "steps": {
                    key: dict(
                        value, status="passed", exit_code=0, finished_at="2020-01-01T00:00:00+00:00"
                    )
                    for key, value in graph.items()
                },
            }
        )
        if not normal:
            state["steps"]["backend-tests"].update(status="failed", exit_code=1)
        if missing_gate:
            state["steps"]["other-check"].update(status="pending", exit_code=None)
        directory = self.root / ".runtime/quality"
        directory.mkdir(exist_ok=True)
        save(directory, state, anchor=self.root)
        data: dict[str, JsonValue] = {
            "id": "fixture",
            "branch": "roadmap/fixture",
            "quality_groups": "backend",
            "quality_rationale": "fixture scope",
        }
        if not normal:
            data["quality_recovery"] = list(self.references)
        check_evidence(
            self.root,
            data,
            self.root / "definition",
            directory,
            policy=policy or ClosurePolicy(recovered_backend=True),
            reader=FixtureReader(definition),
        )

    def alter(self, index: int, action: str) -> None:
        path = self.root / self.references[index]
        value = json.loads(path.read_text())
        scope = value["scopes"][0]
        evidence = scope["evidence"]
        if action == "candidate":
            evidence["candidate"] = "b" * 40
        elif action == "runtime":
            evidence["runtime_fingerprint"] = "b" * 64
        elif action == "approval":
            scope["owner_approval_required"] = True
        elif action == "unconfirmed":
            scope["exit_code"] = 7
        elif action == "hash":
            evidence["artifacts"][next(iter(evidence["artifacts"]))] = "b" * 64
        elif action == "duplicate-run":
            other = json.loads((self.root / self.references[0]).read_text())
            evidence["run_id"] = other["scopes"][0]["evidence"]["run_id"]
        elif action == "dirty":
            evidence["clean"] = False
        else:
            raise AssertionError(action)
        path.write_text(json.dumps(value))


@pytest.fixture
def recovery(tmp_path: Path) -> RecoveryFixture:
    subprocess.run(["git", "init", "-q", "-b", "roadmap/fixture", str(tmp_path)], check=True)
    (tmp_path / ".gitignore").write_text(".runtime/\n")
    example = tmp_path / "tests/backend/runa/ExampleTest.php"
    example.parent.mkdir(parents=True)
    example.write_text("<?php\n")
    (tmp_path / "src").mkdir()
    subprocess.run(["git", "add", "."], cwd=tmp_path, check=True)
    subprocess.run(
        [
            "git",
            "-c",
            "user.name=Fixture",
            "-c",
            "user.email=fixture@example.test",
            "commit",
            "-qm",
            "fixture",
        ],
        cwd=tmp_path,
        check=True,
    )
    candidate = subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=tmp_path, text=True
    ).strip()
    return RecoveryFixture(tmp_path, candidate)


@pytest.mark.parametrize("second_retry", [False, True])
def test_complete_recovery_preserves_original_failure_without_approval(
    recovery: RecoveryFixture, second_retry: bool
) -> None:
    runner = recovery.execute(CASES, crash=CASES[0], original=True, not_started=(CASES[2],))
    if second_retry:
        recovery.execute((CASES[0],), crash=CASES[0])
    recovery.execute((CASES[0],))
    recovery.execute((CASES[2],))
    original = (recovery.root / recovery.references[0]).read_bytes()
    recovery.check()
    assert (recovery.root / recovery.references[0]).read_bytes() == original
    assert json.loads(original)["scopes"][0]["status"] == "failed"
    assert runner.cleanups == 2
    assert (
        sum(
            call.argv[0] == "vendor/bin/pest"
            and not any(value.startswith("--list-tests-xml") for value in call.argv)
            for call in runner.calls
        )
        == 1
    )


def test_recovered_case_does_not_hide_incomplete_remaining_coverage(
    recovery: RecoveryFixture,
) -> None:
    recovery.execute(CASES, crash=CASES[0], original=True, not_started=(CASES[2],))
    recovery.execute((CASES[0],))
    with pytest.raises(ValueError, match="missing mandatory coverage"):
        recovery.check()


@pytest.mark.parametrize(
    "action", ["candidate", "runtime", "approval", "unconfirmed", "hash", "duplicate-run", "dirty"]
)
def test_incompatible_or_unsupported_recovery_is_rejected(
    recovery: RecoveryFixture, action: str
) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    recovery.execute((CASES[0],))
    recovery.alter(1, action)
    with pytest.raises(ValueError):
        recovery.check()


@pytest.mark.parametrize("attempts", [2, 3])
def test_exhausted_or_exceeded_retries_never_auto_accept(
    recovery: RecoveryFixture, attempts: int
) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    for _ in range(attempts):
        recovery.execute((CASES[0],), crash=CASES[0])
    with pytest.raises(ValueError, match="disposition|budget"):
        recovery.check()


@pytest.mark.parametrize(
    "kind", ["assertion", "cleanup", "missing-gate", "repeat-success", "duplicate", "customer"]
)
def test_recovery_cannot_waive_other_acceptance_requirements(
    recovery: RecoveryFixture, kind: str
) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    recovery.execute(
        (CASES[0],), assertion=kind == "assertion", cleanup=9 if kind == "cleanup" else 0
    )
    if kind == "repeat-success":
        recovery.execute((CASES[0],))
    if kind == "duplicate":
        recovery.references.append(recovery.references[1])
    with pytest.raises(ValueError):
        recovery.check(
            missing_gate=kind == "missing-gate",
            policy=ClosurePolicy() if kind == "customer" else None,
        )


def test_complete_successful_graph_keeps_normal_acceptance_path(recovery: RecoveryFixture) -> None:
    recovery.check(normal=True)


def test_omitted_retry_cannot_reset_the_budget(recovery: RecoveryFixture) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    recovery.execute((CASES[0],), crash=CASES[0])
    recovery.execute((CASES[0],))
    recovery.references.pop(1)
    with pytest.raises(ValueError, match="intervening"):
        recovery.check()


def test_nonbackend_execution_preserves_native_predecessor(recovery: RecoveryFixture) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    service = SuiteService(
        WorkspaceContext(recovery.root), OWNER, NativeFixture(()), {}, lambda _: None
    )
    # An empty owner frontend scope fails without starting a child, but must not erase backend provenance.
    assert service.execute(Selection(("frontend",))) == 1
    recovery.execute((CASES[0],))
    recovery.check()


def test_unrecorded_backend_invocation_breaks_recovery_continuity(
    recovery: RecoveryFixture,
) -> None:
    recovery.execute(CASES[:2], crash=CASES[0], original=True)
    service = SuiteService(
        WorkspaceContext(recovery.root), OWNER, NativeFixture((CASES[0],)), {}, lambda _: None
    )
    assert service.execute(Selection(("backend",), ("--parallel",))) == 0
    recovery.execute((CASES[0],))
    with pytest.raises(ValueError, match="intervening"):
        recovery.check()


def test_native_artifacts_refuse_symlink_redirection(recovery: RecoveryFixture) -> None:
    from tools.application.testing.evidence import read_artifact

    evidence = recovery.root / ".runtime/verification/link"
    evidence.parent.mkdir(parents=True)
    evidence.symlink_to(recovery.root / ".gitignore")
    with pytest.raises(OSError):
        read_artifact(recovery.root, str(evidence.relative_to(recovery.root)))


def test_pinned_native_worker_result_preserves_strict_issue_detection() -> None:
    content = NATIVE_RESULT.read_bytes().removesuffix(b"\n").replace(b"\\0", b"\0")
    assert clean_worker_result(content) == 148
    with pytest.raises(ValueError, match="issues"):
        clean_worker_result(
            content.replace(b'phpWarnings";a:0:{}', b'phpWarnings";a:1:{i:0;s:7:"warning";}')
        )


@pytest.mark.parametrize("warning", [False, True])
def test_partial_worker_markers_or_hidden_warnings_cannot_prove_pass(
    recovery: RecoveryFixture, warning: bool
) -> None:
    runner = NativeFixture(
        CASES[:2], crash=CASES[0], unsealed_completed=not warning, warning=warning
    )
    service = SuiteService(
        WorkspaceContext(recovery.root),
        OWNER,
        runner,
        {"RUNA_QUALITY_RUN_ID": "a" * 32, "RUNA_QUALITY_STEP": "backend-tests"},
        lambda _: None,
    )
    assert service.execute(Selection(("backend",), ("--parallel",), evidence=True)) == 1
    target = recovery.root / ".runtime/verification/retained/0.json"
    target.parent.mkdir(parents=True)
    target.write_bytes((recovery.root / ".runtime/tests/runa-results.json").read_bytes())
    recovery.references.append(str(target.relative_to(recovery.root)))
    recovery.execute((CASES[0],))
    with pytest.raises(ValueError, match="issues|missing mandatory coverage"):
        recovery.check()


@pytest.mark.parametrize("assertion", [False, True])
def test_failed_provenance_collection_preserves_native_exit_and_cleanup(
    recovery: RecoveryFixture, assertion: bool
) -> None:
    runner = NativeFixture((CASES[0],), assertion=assertion, fail_finish=True)
    service = SuiteService(WorkspaceContext(recovery.root), OWNER, runner, {}, lambda _: None)
    assert service.execute(Selection(("backend",), ("--parallel",), evidence=True)) == 1
    scope = json.loads((recovery.root / ".runtime/tests/runa-results.json").read_text())["scopes"][
        0
    ]
    assert scope["status"] == "blocked"
    assert scope["exit_code"] == (1 if assertion else 0)
    assert scope["cleanup_exit_code"] == 0
    assert scope["evidence"]["incomplete"] is True
    assert runner.cleanups == 2


@pytest.mark.parametrize(
    "option",
    [
        "--do-not-fail-on-warning",
        "--no-configuration",
        "--bootstrap=foreign.php",
        "--log-junit=foreign.xml",
        "--processes",
    ],
)
def test_evidence_refuses_acceptance_weakening_flags_before_execution(option: str) -> None:
    with pytest.raises(ValueError, match="file/filter"):
        Selection(("backend",), ("--parallel", option), evidence=True)
