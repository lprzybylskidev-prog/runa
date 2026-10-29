"""Private installer phase evidence; shared results belong to one quality run only."""

from __future__ import annotations

import os
import re
import subprocess
import time
from collections.abc import Generator, Mapping
from contextlib import contextmanager
from datetime import UTC, datetime
from pathlib import Path

from tests.tools.runa.acceptance.acceptance import PhaseRecord, Report
from tools.application.quality_planning import graph_hash
from tools.application.quality_reporting import read_state
from tools.application.quality_state import timing
from tools.core.state import JsonStore, JsonValue, StateDirectory, json_value

SHARED_REPORT = "production-fixture.json"
SHARED_PHASES = (
    "preparation",
    "healthy-validation",
    "security-scan",
    "customer-deployment",
    "cleanup",
)


def report_store(root: Path, directory: Path) -> JsonStore:
    return JsonStore(
        StateDirectory(directory, private=False, anchor=root), max_bytes=4 * 1024 * 1024
    )


def quality_binding(root: Path, environment: Mapping[str, str], step: str) -> dict[str, JsonValue]:
    directory = root / ".runtime/quality"
    if environment.get("RUNA_QUALITY_DIRECTORY") != str(directory):
        raise ValueError("Shared production evidence requires the owner quality directory.")
    state = read_state(directory, anchor=root)
    run_id = environment.get("RUNA_QUALITY_RUN_ID", "")
    if (
        not re.fullmatch("[a-f0-9]{32}", run_id)
        or state.get("run_id") != run_id
        or environment.get("RUNA_QUALITY_STEP") != step
        or step not in state["steps"]
        or state["status"] != "running"
    ):
        raise ValueError(
            "Shared production evidence belongs to a different or inactive quality run."
        )
    commit = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=root, text=True).strip()
    branch = subprocess.check_output(
        ["git", "branch", "--show-current"], cwd=root, text=True
    ).strip()
    if state.get("commit") != commit or state.get("branch") != branch:
        raise ValueError("Shared production evidence requires the exact quality candidate.")
    runtime_fields = {"status", "pid", "exit_code", "started_at", "finished_at", "duration_seconds"}
    graph = {
        key: {k: v for k, v in node.items() if k not in runtime_fields}
        for key, node in state["steps"].items()
    }
    return {
        "run_id": run_id,
        "commit": commit,
        "branch": branch,
        "graph_hash": graph_hash(graph),
        "selection": json_value(state.get("selection")),
    }


class FixtureEvidence:
    def __init__(self, root: Path, path: Path, report: Report) -> None:
        self.root, self.path, self.report = root, path, report
        self.phases: dict[str, PhaseRecord] = {}
        self.persist()

    def persist(self) -> None:
        self.report["phases"] = self.phases
        report_store(self.root, self.path.parent).write(self.path.name, json_value(self.report))

    @contextmanager
    def phase(self, name: str) -> Generator[None, None, None]:
        if name in self.phases:
            raise ValueError("Installer phase cannot be replayed: " + name)
        started = time.monotonic()
        record: PhaseRecord = {
            "result": "RUNNING",
            "started_at": datetime.now(UTC).isoformat(),
            "finished_at": None,
            "duration_seconds": None,
        }
        self.phases[name] = record
        self.persist()
        try:
            yield
        except BaseException as failure:
            record["result"] = "INTERRUPTED" if isinstance(failure, KeyboardInterrupt) else "FAIL"
            self.report["result"] = record["result"]
            raise
        else:
            record["result"] = "PASS"
        finally:
            record["finished_at"] = datetime.now(UTC).isoformat()
            record["duration_seconds"] = round(time.monotonic() - started, 3)
            self.persist()


def validate_shared(report: JsonValue, binding: dict[str, JsonValue]) -> None:
    if (
        not isinstance(report, dict)
        or report.get("quality_binding") != binding
        or report.get("result") != "PASS"
    ):
        raise ValueError("Missing, failed or stale shared production evidence.")
    phases, images, sha = report.get("phases"), report.get("images"), report.get("prod_sha")
    if (
        not isinstance(phases, dict)
        or not isinstance(images, dict)
        or not images
        or not all(
            isinstance(v, str) and re.fullmatch("sha256:[a-f0-9]{64}", v) for v in images.values()
        )
        or not isinstance(sha, str)
        or not re.fullmatch("[a-f0-9]{40}", sha)
    ):
        raise ValueError("Incomplete shared production source/image evidence.")
    previous: datetime | None = None
    for name in SHARED_PHASES:
        phase = phases.get(name)
        if not isinstance(phase, dict) or phase.get("result") != "PASS":
            raise ValueError("Missing, failed or unordered shared production phase: " + name)
        timing(phase)
        started, finished = phase.get("started_at"), phase.get("finished_at")
        if (
            not isinstance(started, str)
            or not isinstance(finished, str)
            or phase.get("duration_seconds") is None
        ):
            raise ValueError("Incomplete shared production phase timing: " + name)
        start, finish = datetime.fromisoformat(started), datetime.fromisoformat(finished)
        if (previous is not None and start < previous) or finish < start:
            raise ValueError("Unordered shared production phase: " + name)
        previous = finish
    if not isinstance(report.get("cleanup"), list) or not report["cleanup"]:
        raise ValueError("Missing shared production cleanup evidence.")


def publish_shared(root: Path, report: Report) -> None:
    binding = quality_binding(root, os.environ, "production-acceptance")
    validate_shared(json_value(report), binding)
    report_store(root, root / ".runtime/quality").write(SHARED_REPORT, json_value(report))


def verify_shared(root: Path) -> None:
    binding = quality_binding(root, os.environ, "repository-container-scan")
    state = read_state(root / ".runtime/quality", anchor=root)
    producer = state["steps"].get("production-acceptance")
    if not producer or producer["status"] != "passed" or producer.get("exit_code") != 0:
        raise ValueError("Shared production producer did not complete successfully.")
    report = report_store(root, root / ".runtime/quality").read(SHARED_REPORT)
    validate_shared(report, binding)
    print(
        "PASS: exact-run production preparation, healthy-image scanning, deployment and cleanup; native phase evidence: "
        + str(root / ".runtime/quality" / SHARED_REPORT)
    )
