"""External update execution dependencies and consumed recovery evidence."""

import re
from collections.abc import Callable, Sequence
from pathlib import Path
from typing import Literal, NotRequired, Protocol, TypedDict, cast

from distribution.bootstrap import SHA, TAG, GitHub
from distribution.update_contracts import Blocker, SourceChange
from distribution.update_file_models import CustomizationReview, FileJournal, file_journal
from distribution.update_models import DatabaseMetadata, RuntimeSnapshot, SeedRun
from distribution.update_plan_models import (
    LockProbe,
    ModulePlanner,
    PreflightCheck,
    PreflightDetails,
    PreflightReport,
    ReleaseDownload,
    ReleaseIdentity,
    RuntimeProbe,
)
from tools.core.state import JsonValue, json_value

STEPS = (
    "planned",
    "preflight-passed",
    "customization-reviewed",
    "maintenance",
    "source-replaced",
    "staged/built",
    "platform-migrations",
    "platform-seeders/upgrades",
    "platform-modules",
    "switch",
    "health",
    "completed",
)


class UpdateRuntime(Protocol):
    def bind(self, project: str) -> None: ...
    def probe(self, root: Path, mode: str, operator: Path, /) -> RuntimeSnapshot: ...
    def metadata(self) -> DatabaseMetadata: ...
    def manual(self) -> None: ...
    def fence(self, identity: str) -> None: ...
    def build(self) -> None: ...
    def data(self, phase: str, release: str) -> None: ...
    def activate(self, identity: str) -> None: ...
    def health(self) -> None: ...


class RuntimeFactory(Protocol):
    def __call__(self, root: Path) -> UpdateRuntime: ...


class Preflight(Protocol):
    def __call__(
        self,
        value: str | Path,
        client: GitHub,
        tag: str,
        /,
        *,
        toolchain: str = ...,
        download: ReleaseDownload = ...,
        runtime_probe: RuntimeProbe = ...,
        lock_probe: LockProbe = ...,
        expected_commit: str | None = ...,
        lock_held: bool = ...,
        module_plan: ModulePlanner = ...,
    ) -> PreflightReport: ...


class ExecutionOptions(TypedDict, total=False):
    resume: bool
    choice: str | None
    fingerprint: str | None
    toolchain: str
    expected_commit: str | None
    choose: Callable[[CustomizationReview], str | None] | None


class CompletedModuleStep(TypedDict):
    module: str
    step: str
    fingerprint: str
    status: str


class ModuleTarget(TypedDict):
    module: str
    to: str


class BaselineMetadata(TypedDict):
    migrations: list[str]
    seed_runs: list[SeedRun]
    database_mounts: NotRequired[list[list[str]]]
    module_steps: NotRequired[Sequence[CompletedModuleStep]]


class JournalReview(TypedDict):
    fingerprint: str
    services: list[str]
    devcontainer: bool
    manual_reconciliation: NotRequired[list[str]]


class ExecutionJournal(FileJournal):
    schema_version: int
    kind: str
    id: str
    root: str
    repository: str
    project: str
    baseline: BaselineMetadata
    original: ReleaseIdentity
    target: ReleaseIdentity
    phase: str
    completed: list[str]
    review: JournalReview
    decision: NotRequired[str | None]
    state: NotRequired[str]
    started_at: NotRequired[str]
    source_transition: NotRequired[bool]
    module_plan: NotRequired[Sequence[ModuleTarget]]
    module_steps: NotRequired[Sequence[CompletedModuleStep]]
    updated_at: NotRequired[str]
    completed_at: NotRequired[str]


class InfrastructureFollowUp(TypedDict):
    services: list[str]
    devcontainer: bool
    instruction: str


class ExecutionReport(PreflightDetails):
    execution_available: NotRequired[bool]
    mismatches: NotRequired[list[SourceChange]]
    temporary_downloads: NotRequired[list[str]]
    temporary_downloads_cleaned: NotRequired[bool]
    schema_version: int
    mode: Literal["apply", "resume"]
    dry_run: bool
    status: Literal["blocked", "ready", "completed", "interrupted"]
    checks: list[PreflightCheck]
    no_op: NotRequired[bool]
    operation_id: NotRequired[str]
    phase: NotRequired[str]
    journal: NotRequired[str]
    source_staging_removed: NotRequired[str]
    source_staging_requires_inspection: NotRequired[bool]
    fencing_requires_inspection: NotRequired[bool]
    manual_reconciliation: NotRequired[list[str]]
    infrastructure_follow_up: NotRequired[InfrastructureFollowUp]


def invalid_journal() -> Blocker:
    return Blocker(
        "journal-identity",
        "Pending operation is not this development update; preserve its evidence.",
    )


def string_rows(value: object) -> bool:
    return isinstance(value, list) and all(
        isinstance(item, str) for item in cast(list[object], value)
    )


def completion_rows(value: JsonValue) -> bool:
    return isinstance(value, list) and all(
        isinstance(row, dict)
        and all(
            isinstance(row.get(key), str) for key in ("module", "step", "fingerprint", "status")
        )
        for row in value
    )


def baseline_metadata(value: object) -> BaselineMetadata:
    decoded = json_value(value)
    if not isinstance(decoded, dict) or not string_rows(decoded.get("migrations")):
        raise invalid_journal()
    seeds = decoded.get("seed_runs")
    if not isinstance(seeds, list) or any(
        not isinstance(row, dict)
        or any(not isinstance(row.get(key), str) for key in ("release_sha", "seeder"))
        for row in seeds
    ):
        raise invalid_journal()
    mounts = decoded.get("database_mounts", [])
    if not isinstance(mounts, list) or any(
        not string_rows(row) or len(cast(list[JsonValue], row)) != 3 for row in mounts
    ):
        raise invalid_journal()
    if "module_steps" in decoded and not completion_rows(decoded["module_steps"]):
        raise invalid_journal()
    return cast(BaselineMetadata, decoded)


def journal_review(value: object) -> JournalReview:
    decoded = json_value(value)
    if (
        not isinstance(decoded, dict)
        or not isinstance(decoded.get("fingerprint"), str)
        or not string_rows(decoded.get("services"))
        or type(decoded.get("devcontainer")) is not bool
        or (
            "manual_reconciliation" in decoded and not string_rows(decoded["manual_reconciliation"])
        )
    ):
        raise invalid_journal()
    return cast(JournalReview, decoded)


def execution_journal(
    value: dict[str, JsonValue], root: Path, repository: str, tag: str
) -> ExecutionJournal:
    """Validate consumed fields without dropping unknown or legacy evidence."""
    target = value.get("target")
    if (
        type(value.get("schema_version")) is not int
        or value.get("schema_version") != 1
        or value.get("kind") != "development-update"
        or value.get("root") != str(root)
        or value.get("repository") != repository.lower()
        or not isinstance(target, dict)
        or target.get("tag") != tag
        or not isinstance(value.get("id"), str)
        or not re.fullmatch("[a-f0-9]{32}", cast(str, value["id"]))
        or not isinstance(value.get("project"), str)
        or not re.fullmatch("[a-z0-9][a-z0-9_-]{0,127}", cast(str, value["project"]))
        or value.get("phase") not in (*STEPS, "source-replacing")
        or not string_rows(value.get("completed"))
        or any(step not in STEPS for step in cast(list[str], value["completed"]))
        or ("source_transition" in value and type(value["source_transition"]) is not bool)
    ):
        raise invalid_journal()
    for key in ("original", "target"):
        identity = value.get(key)
        if not isinstance(identity, dict) or any(
            not isinstance(identity.get(field), str)
            or not re.fullmatch(pattern, cast(str, identity[field]))
            for field, pattern in (("tag", TAG), ("commit", SHA), ("tag_object", SHA))
        ):
            raise Blocker("journal-identity", "Update release identities are invalid.")
    baseline_metadata(value.get("baseline"))
    journal_review(value.get("review"))
    if "module_steps" in value and not completion_rows(value["module_steps"]):
        raise invalid_journal()
    modules = value.get("module_plan", [])
    if not isinstance(modules, list) or any(
        not isinstance(row, dict)
        or any(not isinstance(row.get(key), str) for key in ("module", "to"))
        for row in modules
    ):
        raise invalid_journal()
    file_journal(value)
    return cast(ExecutionJournal, value)
