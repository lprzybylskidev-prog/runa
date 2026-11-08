"""Candidate-bound closure evidence; historical one-time exceptions are retired."""

import hashlib
import json
import re
import shlex
import subprocess
import xml.etree.ElementTree as ET
from collections.abc import Mapping
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from typing import Protocol

from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_models import quality_definition
from tools.application.quality_planning import graph_hash, resolve, validate_declared
from tools.application.quality_reporting import read_state
from tools.application.quality_state import RunRecord, timing
from tools.application.testing.evidence import (
    backend_anchor,
    configuration_fingerprint,
    full_selection,
    read_artifact,
    validate_selection,
)
from tools.core.state import JsonValue, json_value, unique_object


@dataclass(frozen=True)
class ClosurePolicy:
    documentation: str = "docs/app"
    allowed_metadata: frozenset[str] = frozenset(
        {"src/app/application.json", "docs/app/CHANGELOG.md"}
    )
    recovered_backend: bool = False


def mapping(value: JsonValue) -> dict[str, JsonValue]:
    if not isinstance(value, dict):
        raise ValueError("Invalid native evidence mapping.")
    return value


def strings(value: JsonValue) -> list[str]:
    if not isinstance(value, list) or any(not isinstance(item, str) for item in value):
        raise ValueError("Invalid native evidence string list.")
    return [item for item in value if isinstance(item, str)]


def timestamp(value: JsonValue) -> datetime:
    if not isinstance(value, str):
        raise ValueError("Missing native evidence timestamp.")
    timing({"started_at": value})
    return datetime.fromisoformat(value)


def inventory(content: bytes) -> set[str]:
    if b"<!DOCTYPE" in content or b"<!ENTITY" in content:
        raise ValueError("Unsafe native inventory XML.")
    try:
        tree = ET.fromstring(content.decode("utf-8"))
    except (ET.ParseError, UnicodeDecodeError):
        raise ValueError("Incomplete native inventory XML.") from None
    identities = [
        element.get("id", "").lstrip("\\")
        for element in tree.iter()
        if element.tag.rsplit("}", 1)[-1] == "testMethod"
    ]
    if (
        not identities
        or any("::" not in name for name in identities)
        or len(identities) != len(set(identities))
    ):
        raise ValueError("Missing or duplicate native inventory identities.")
    return set(identities)


def teamcity(content: bytes) -> tuple[set[str], set[str]]:
    """Use native class/method/dataset identities, never progress/printer positions."""
    started: dict[tuple[str, str], str] = {}
    completed: set[str] = set()
    for line in content.decode("utf-8", errors="strict").splitlines():
        event = re.fullmatch(r"##teamcity\[(\w+)(.*?)\]", line)
        if not event:
            if line.startswith("##teamcity["):
                raise ValueError("Incomplete native TeamCity event.")
            continue
        attributes = {
            key: re.sub(
                r"\|(.)", lambda match: {"n": "\n", "r": "\r"}.get(match[1], match[1]), value
            )
            for key, value in re.findall(r"(\w+)='((?:\|.|[^'])*)'", event[2])
        }
        if event[1] in ("testFailed", "testIgnored"):
            raise ValueError("Ordinary native failure or ignored coverage is blocking.")
        key = (attributes.get("flowId", ""), attributes.get("name", ""))
        if event[1] == "testStarted":
            hint = re.search(
                r"::\\?([A-Za-z_][A-Za-z0-9_\\]*)::", attributes.get("locationHint", "")
            )
            if hint is None or not key[1] or key in started:
                raise ValueError("Unreliable native test identity.")
            name = re.sub(r' with data set "(.*)"$', r"#\1", key[1], flags=re.DOTALL)
            name = re.sub(r" with data set #(\d+)$", r"#\1", name)
            started[key] = hint[1] + "::" + name
        elif event[1] == "testFinished":
            if key not in started:
                raise ValueError("Native finish has no matching start.")
            completed.add(started.pop(key))
    return completed, set(started.values())


def clean_worker_result(content: bytes) -> int:
    """Recognize only the pinned PHPUnit zero-issue receipt, without deserialization.

    TeamCity omits warning/deprecation events. A crashed worker without its final
    result cannot prove that its earlier finished tests were issue-free.
    """
    classname = b"PHPUnit\\TestRunner\\TestResult\\TestResult"

    def string(value: bytes) -> bytes:
        return b"s:" + str(len(value)).encode() + b':"' + value + b'";'

    def field(name: str) -> bytes:
        return re.escape(string(b"\0" + classname + b"\0" + name.encode()))

    parts = [re.escape(b"O:" + str(len(classname)).encode() + b':"' + classname + b'":34:{')]
    for name in ("numberOfTests", "numberOfTestsRun", "numberOfAssertions"):
        parts.append(field(name) + rb"i:([0-9]+);")
    issues = (
        "testErroredEvents",
        "testFailedEvents",
        "testMarkedIncompleteEvents",
        "testSuiteSkippedEvents",
        "testSkippedEvents",
        "testConsideredRiskyEvents",
        "testTriggeredPhpunitDeprecationEvents",
        "testTriggeredPhpunitErrorEvents",
        "testTriggeredPhpunitNoticeEvents",
        "testTriggeredPhpunitWarningEvents",
        "testRunnerTriggeredDeprecationEvents",
        "testRunnerTriggeredNoticeEvents",
        "testRunnerTriggeredWarningEvents",
        "testRunnerTriggeredIssueDeprecationEvents",
        "testRunnerTriggeredIssueErrorEvents",
        "testRunnerTriggeredIssueNoticeEvents",
        "testRunnerTriggeredIssuePhpDeprecationEvents",
        "testRunnerTriggeredIssuePhpNoticeEvents",
        "testRunnerTriggeredIssuePhpWarningEvents",
        "testRunnerTriggeredIssueWarningEvents",
        "errors",
        "deprecations",
        "notices",
        "warnings",
        "phpDeprecations",
        "phpNotices",
        "phpWarnings",
    )
    parts.extend(field(name) + re.escape(b"a:0:{}") for name in issues)
    parts.append(field("numberOfIssuesIgnoredByBaseline") + re.escape(b"i:0;"))
    counts = (
        b"a:4:{"
        + b"".join(string(name) + b"i:0;" for name in (b"self", b"direct", b"indirect", b"unknown"))
        + b"}"
    )
    parts.extend(
        (
            field("numberOfDeprecationsByTrigger") + re.escape(counts),
            field("retriedTests") + re.escape(b"a:0:{}"),
            field("timeLimitExceededEvent") + re.escape(b"N;}"),
        )
    )
    match = re.fullmatch(b"".join(parts), content)
    if match is None:
        raise ValueError("Native worker result has issues or unsupported/incomplete format.")
    return int(match[2])


def recovered_backend(root: Path, paths: JsonValue, state: RunRecord) -> None:
    references = strings(paths)
    if len(references) < 2 or len(references) != len(set(references)):
        raise ValueError("Recovery requires distinct original and continuation evidence.")
    expected: set[str] = set()
    verified: set[str] = set()
    unresolved: dict[str, int] = {}
    recovered: set[str] = set()
    run_ids: set[str] = set()
    previous_end: datetime | None = None
    environment: tuple[JsonValue, JsonValue, JsonValue, JsonValue] | None = None
    configuration = configuration_fingerprint(root)
    predecessor: JsonValue = None
    required = {
        "run_id",
        "workspace",
        "owner",
        "candidate",
        "clean",
        "started_at",
        "finished_at",
        "configuration_fingerprint",
        "runtime_fingerprint",
        "quality_run_id",
        "quality_step",
        "selection",
        "full_suite",
        "mode",
        "workers",
        "discovery_command",
        "discovery_exit_code",
        "command",
        "artifacts",
        "previous",
    }
    for index, reference in enumerate(references):
        document = mapping(
            json_value(json.loads(read_artifact(root, reference), object_pairs_hook=unique_object))
        )
        if (
            type(document.get("schema_version")) is not int
            or document.get("schema_version") != 2
            or document.get("owner") != "runa"
        ):
            raise ValueError("Recovery needs current owner-produced native evidence.")
        scopes = document.get("scopes")
        if not isinstance(scopes, list) or len(scopes) != 1:
            raise ValueError("Recovery needs one explicit backend scope.")
        scope = mapping(scopes[0])
        if (
            set(scope) != {"scope", "status", "exit_code", "cleanup_exit_code", "evidence"}
            or scope.get("scope") != "backend"
        ):
            raise ValueError("Invalid native backend acceptance classification.")
        code = scope.get("exit_code")
        if (
            type(code) is not int
            or type(scope.get("cleanup_exit_code")) is not int
            or scope.get("cleanup_exit_code") != 0
            or scope.get("status") != ("passed" if code == 0 else "failed")
        ):
            raise ValueError("Native failure/cleanup evidence is invalid.")
        evidence = mapping(scope.get("evidence"))
        if (
            set(evidence) != required
            or evidence.get("clean") is not True
            or evidence.get("candidate") != state.get("commit")
            or evidence.get("owner") != "runa"
            or type(evidence.get("discovery_exit_code")) is not int
            or evidence.get("discovery_exit_code") != 0
        ):
            raise ValueError("Unbound, dirty or incompatible native evidence.")
        if index and evidence.get("previous") != predecessor:
            raise ValueError("Missing or altered intervening native execution evidence.")
        predecessor = backend_anchor(scope)
        run_id = evidence.get("run_id")
        if (
            not isinstance(run_id, str)
            or not re.fullmatch(r"[a-f0-9]{32}", run_id)
            or run_id in run_ids
        ):
            raise ValueError("Invalid or duplicate native execution identity.")
        run_ids.add(run_id)
        began, ended = timestamp(evidence.get("started_at")), timestamp(evidence.get("finished_at"))
        if ended < began or (previous_end is not None and began < previous_end):
            raise ValueError("Recovery executions must retain chronological provenance.")
        previous_end = ended
        identity = (
            evidence.get("configuration_fingerprint"),
            evidence.get("runtime_fingerprint"),
            evidence.get("mode"),
            evidence.get("workers"),
        )
        if (
            identity[0] != configuration
            or not isinstance(identity[1], str)
            or not re.fullmatch(r"[a-f0-9]{64}", identity[1])
            or identity[2] not in ("parallel", "serial")
            or not isinstance(identity[3], str)
            or not re.fullmatch(r"[1-9][0-9]*", identity[3])
        ):
            raise ValueError("Native environment/mode evidence is invalid.")
        if environment is not None and identity != environment:
            raise ValueError("Incompatible native execution environments or modes.")
        environment = identity
        workspace = evidence.get("workspace")
        if (
            not isinstance(workspace, str)
            or not Path(workspace).is_absolute()
            or ".." in Path(workspace).parts
        ):
            raise ValueError("Invalid native workspace identity.")
        base = ".runtime/tests/native/" + run_id + "/"
        native_directory = str(Path(workspace) / base) + "/"
        selection = strings(evidence.get("selection"))
        validate_selection(tuple(selection))
        if evidence.get("full_suite") is not full_selection(tuple(selection)):
            raise ValueError("Invalid full backend selection evidence.")
        command = [
            "vendor/bin/pest",
            "--configuration=../tests/backend/runa/phpunit.xml",
            "--test-directory=../tests/backend/runa",
            *selection,
        ]
        parallel = any(value in selection for value in ("--parallel", "-p"))
        if evidence.get("mode") != ("parallel" if parallel else "serial"):
            raise ValueError("Native mode does not match its command.")
        if parallel and not any(value.startswith("--processes") for value in selection):
            command.append("--processes=" + str(identity[3]))
        discovery = [
            value
            for value in command
            if value not in ("--parallel", "-p") and not value.startswith("--processes=")
        ] + ["--list-tests-xml=" + native_directory + "inventory.xml"]
        command += [
            "--log-junit=" + native_directory + "junit.xml",
            "--log-teamcity=" + native_directory + "teamcity.log",
        ]
        if parallel:
            command.append("--tmp-dir=" + native_directory.rstrip("/"))
        if evidence.get("command") != command or evidence.get("discovery_command") != discovery:
            raise ValueError("Native report commands differ from their producer selection.")
        artifacts: dict[str, bytes] = {}
        for name, digest in mapping(evidence.get("artifacts")).items():
            if (
                not name.startswith(base)
                or not isinstance(digest, str)
                or not re.fullmatch(r"[a-f0-9]{64}", digest)
            ):
                raise ValueError("Invalid native artifact binding.")
            content = read_artifact(root, name)
            if hashlib.sha256(content).hexdigest() != digest:
                raise ValueError("Native artifact hash mismatch.")
            artifacts[name[len(base) :]] = content
        if not {"inventory.xml", "stdout.log", "stderr.log"} <= artifacts.keys():
            raise ValueError("Missing native discovery/output evidence.")
        cases = inventory(artifacts["inventory.xml"])
        if index == 0:
            if (
                evidence.get("full_suite") is not True
                or evidence.get("quality_run_id") != state.get("run_id")
                or evidence.get("quality_step") != "backend-tests"
                or not state.get("run_id")
                or code == 0
                or code % 256 != state["steps"]["backend-tests"].get("exit_code")
            ):
                raise ValueError("Original backend interruption is not bound to the failed graph.")
            expected = cases
            launcher = shlex.split(str(state["steps"]["backend-tests"].get("command", "")))
            if (
                launcher[:2] != ["python3", "tools/maintainer/test-backend"]
                or "--evidence" not in launcher
                or [value for value in launcher[2:] if value != "--evidence"] != selection
            ):
                raise ValueError("Original native selection differs from its quality command.")
            for key, step in state["steps"].items():
                if key != "backend-tests" and timestamp(step.get("finished_at")) > began:
                    raise ValueError("Backend started before all required validation completed.")
        elif not cases <= expected or cases & recovered:
            raise ValueError("Unknown coverage or a repeated successfully recovered case.")
        for case in cases & unresolved.keys():
            unresolved[case] += 1
            if unresolved[case] > 2:
                raise ValueError("Native recovery retry budget exceeded.")
        completed: set[str] = set()
        pending: set[str] = set()
        worker_pending: dict[str, set[str]] = {}
        worker_completed: dict[str, set[str]] = {}
        for name, content in artifacts.items():
            if (
                name.endswith(("_junit", ".xml"))
                and name != "inventory.xml"
                and re.search(
                    rb'(?:errors|failures|skipped|warnings)=["\x27][1-9]|<(?:failure|error|skipped)(?:\s|>)',
                    content,
                )
            ):
                raise ValueError("Ordinary native XML failure is blocking.")
            if name == "teamcity.log" or name.endswith("_teamcity"):
                finished, unfinished = teamcity(content)
                completed |= finished
                pending |= unfinished
                match = re.search(r"worker_(\d+)_stdout_", name)
                if match:
                    worker_pending.setdefault(str(int(match[1])), set()).update(unfinished)
                    worker_completed[name.removesuffix("_teamcity")] = finished
        if not completed <= cases or not pending <= cases:
            raise ValueError("Native results do not match the discovered selection.")
        proven = set(completed) if code == 0 else set()
        if code != 0:
            for name, content in artifacts.items():
                if not name.endswith("_test_result") or not content:
                    continue
                prefix = name.removesuffix("_test_result")
                finished = worker_completed.get(prefix)
                if finished is None or clean_worker_result(content) != len(finished):
                    raise ValueError("Native worker result and completed coverage disagree.")
                proven |= finished
        if code == 0:
            if completed != cases or (pending - completed) or "junit.xml" not in artifacts:
                raise ValueError("Successful native execution has incomplete coverage evidence.")
            content = artifacts["junit.xml"]
            if b"<!DOCTYPE" in content or b"<!ENTITY" in content:
                raise ValueError("Unsafe native result XML.")
            try:
                junit = ET.fromstring(content.decode("utf-8"))
            except (ET.ParseError, UnicodeDecodeError):
                raise ValueError("Incomplete native result XML.") from None
            junit_ids = [
                (element.get("classname", element.get("class", "")), element.get("name", ""))
                for element in junit.iter("testcase")
            ]
            if (
                len(junit_ids) != len(cases)
                or len(set(junit_ids)) != len(junit_ids)
                or any(not classname or not name for classname, name in junit_ids)
            ):
                raise ValueError("Native XML and TeamCity coverage disagree.")
        else:
            console = artifacts["stdout.log"] + artifacts["stderr.log"]
            diagnostics = (
                console
                + b"\n"
                + b"\n".join(
                    content
                    for name, content in artifacts.items()
                    if name.endswith("_unexpected_output")
                )
            )
            if re.search(
                rb"(?m)^\s*(?:\[[^\n]*\]\s*)?PHP (?:Warning|Fatal error|Parse error):", diagnostics
            ):
                raise ValueError("Ordinary native PHP diagnostics remain blocking.")
            if code in (139, -11) and not parallel:
                interrupted = pending - completed
            elif (
                code == 1
                and b"WorkerCrashedException.php" in console
                and re.search(rb"Exit Code:\s*139\(Segmentation violation\)", console)
            ):
                tokens = set(re.findall(rb"TEST_TOKEN='([1-9][0-9]*)'", console))
                if len(tokens) != 1:
                    raise ValueError("Ambiguous native crashed worker identity.")
                interrupted = worker_pending.get(next(iter(tokens)).decode(), set()) - completed
            else:
                raise ValueError("Ordinary or unconfirmed native failure is not SIGSEGV recovery.")
            if len(interrupted) != 1:
                raise ValueError("Interrupted native test/dataset is not reliably identified.")
            case = next(iter(interrupted))
            unresolved.setdefault(case, 0)
        for case in proven & unresolved.keys():
            recovered.add(case)
            del unresolved[case]
        verified |= proven
    if unresolved:
        raise ValueError("Unresolved confirmed SIGSEGV requires the existing owner disposition.")
    if verified != expected:
        raise ValueError("Recovered backend evidence has missing mandatory coverage.")


class EvidenceReader(Protocol):
    def yaml(self, path: Path) -> dict[str, JsonValue]: ...


def closure_link_update(root: Path, name: str, candidate: str, identifier: str, docs: str) -> bool:
    if not re.fullmatch(re.escape(docs) + r"/roadmaps/draft/[^/]+\.md", name):
        return False
    path = root / name
    if not path.is_file() or path.is_symlink():
        return False
    original = subprocess.run(
        ["git", "show", candidate + ":" + name], cwd=root, capture_output=True
    )
    if original.returncode:
        return False
    pattern = (
        rb"(?<=\]\()\.\./active/(" + re.escape(identifier.encode()) + rb"-[a-z0-9-]+\.md)(?=\))"
    )
    expected, count = re.subn(pattern, rb"../closed/\1", original.stdout)
    return count > 0 and path.read_bytes() == expected


def check_evidence(
    root: Path,
    data: Mapping[str, JsonValue],
    definition_path: Path,
    directory: Path,
    *,
    policy: ClosurePolicy | None = None,
    reader: EvidenceReader | None = None,
) -> None:
    """Bind selected-scope closure to actual complete results on the verified candidate."""
    if "quality_groups" not in data:
        return  # Immutable older releases predate explicit selection evidence.
    identifier = data.get("id")
    branch = data.get("branch")
    if not isinstance(identifier, str) or not isinstance(branch, str):
        raise ValueError("Invalid closure roadmap identity.")
    path = directory / "state.json"
    if not path.is_file():
        raise ValueError("Missing final quality evidence.")
    state = read_state(directory, anchor=root)
    policy = policy or ClosurePolicy()
    recovery = data.get("quality_recovery")
    if recovery is not None and not policy.recovered_backend:
        raise ValueError("Owner SIGSEGV recovery is unavailable in customer closure.")
    scope = state.get("selection", {"mode": "legacy"})
    validate_declared(data, scope)
    if (
        (
            state.get("status") != "passed"
            and not (recovery is not None and state.get("status") == "failed")
        )
        or state.get("mode") not in ("roadmap", "final")
        or state.get("roadmap") != identifier
        or state.get("branch") != branch
        or state.get("rationale") != data.get("quality_rationale")
    ):
        raise ValueError("Final quality evidence does not match the closed roadmap.")
    definition = quality_definition(
        (reader or ConfigurationReader.for_workspace(root)).yaml(definition_path)
    )
    nodes, resolved = resolve(
        definition,
        [],
        groups=scope["requested_groups"] if scope["mode"] == "groups" else None,
        all_groups=scope.get("mode") == "all",
    )
    steps = state["steps"]
    if (
        scope != resolved
        or set(steps) != set(nodes)
        or any(
            (steps[key].get("status") != "passed" or steps[key].get("exit_code") != 0)
            for key in nodes
            if not (recovery is not None and key == "backend-tests")
        )
    ):
        raise ValueError("Incomplete or stale selected quality graph/results.")
    fields = ("status", "pid", "started_at", "finished_at", "duration_seconds", "exit_code")
    recorded = {
        key: {field: value for field, value in step.items() if field not in fields}
        for key, step in steps.items()
    }
    if resolved["mode"] == "legacy" or graph_hash(recorded) != resolved["graph_hash"]:
        raise ValueError("Quality result commands differ from the agreed graph.")
    candidate = state.get("commit", "")
    if (
        not re.fullmatch("[a-f0-9]{40}", candidate)
        or subprocess.run(
            ["git", "merge-base", "--is-ancestor", candidate, "HEAD"], cwd=root, capture_output=True
        ).returncode
    ):
        raise ValueError("Quality candidate is not an ancestor of this release.")
    docs = policy.documentation
    allowed = policy.allowed_metadata
    prefixes = [
        docs + "/roadmaps/" + status + "/" + identifier + "-" for status in ("active", "closed")
    ]
    changed = subprocess.check_output(
        ["git", "diff", "--no-renames", "--name-only", candidate], cwd=root, text=True
    ).splitlines()
    changed += subprocess.check_output(
        ["git", "ls-files", "--others", "--exclude-standard"], cwd=root, text=True
    ).splitlines()
    if any(
        name not in allowed
        and not any(name.startswith(prefix) and name.endswith(".md") for prefix in prefixes)
        and not closure_link_update(root, name, candidate, identifier, docs)
        for name in changed
    ):
        raise ValueError(
            "Substantive changes after the final quality candidate require a complete rerun."
        )
    if recovery is not None:
        if (
            state.get("status") != "failed"
            or "backend-tests" not in nodes
            or not nodes["backend-tests"].get("terminal")
            or steps["backend-tests"].get("status") != "failed"
        ):
            raise ValueError("Recovery requires the failed terminal native backend graph.")
        recovered_backend(root, recovery, state)
