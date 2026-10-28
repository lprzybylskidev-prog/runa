"""External planning contracts; probes never import installed Python packages."""

from collections.abc import Mapping
from pathlib import Path
from typing import Literal, NotRequired, Protocol, TypedDict

from distribution.bootstrap import GitHub
from distribution.update_contracts import SourceChange
from distribution.update_file_models import CustomizationReview
from distribution.update_models import PlannedModuleResult, RuntimeSnapshot


class ReleaseIdentity(TypedDict):
    tag: str
    commit: str
    tag_object: str


class ReleaseDownload(Protocol):
    def __call__(
        self,
        client: GitHub,
        tag: str,
        destination: Path,
        mode: str,
        expected_commit: str | None = None,
    ) -> Mapping[str, object]: ...


class RuntimeProbe(Protocol):
    def __call__(self, root: Path, mode: str, operator: Path, /) -> RuntimeSnapshot: ...


class LockProbe(Protocol):
    def __call__(self, candidate: Path, image: str) -> str: ...


class ModulePlanner(Protocol):
    def __call__(
        self, root: Path, target: Path, runtime: RuntimeSnapshot, image: str
    ) -> list[PlannedModuleResult]: ...


class PreflightOptions(TypedDict, total=False):
    toolchain: str
    download: ReleaseDownload
    runtime_probe: RuntimeProbe
    lock_probe: LockProbe
    expected_commit: str | None
    lock_held: bool
    module_plan: ModulePlanner


# The external JSON/PHP contract uses a Python keyword.
_SeederClass = TypedDict("_SeederClass", {"class": str})


class PlatformSeeder(_SeederClass):
    release: str
    already_completed: bool


class UpdatePlan(TypedDict):
    no_op: bool
    source_changes: list[SourceChange]
    platform_migrations: list[str]
    platform_seeder: PlatformSeeder
    customer_inputs: str
    steps: list[str]
    preserve: list[str]
    uncertainties: list[str]
    modules: NotRequired[list[PlannedModuleResult]]


class PreflightCheck(TypedDict):
    id: str
    status: Literal["passed", "blocked"]
    message: NotRequired[str]


class RuntimeSummary(TypedDict):
    project: str
    services: list[str]


class PreflightDetails(TypedDict):
    installed_version: NotRequired[str]
    target_version: NotRequired[str]
    installed_release: NotRequired[ReleaseIdentity]
    target_release: NotRequired[ReleaseIdentity]
    free_bytes: NotRequired[int]
    dependency_checks: NotRequired[list[str]]
    toolchain_image: NotRequired[str]
    runtime: NotRequired[RuntimeSummary]
    plan: NotRequired[UpdatePlan]
    customization_review: NotRequired[CustomizationReview]


class PreflightReport(PreflightDetails):
    execution_available: bool
    mismatches: list[SourceChange]
    temporary_downloads: list[str]
    temporary_downloads_cleaned: bool
    schema_version: int
    mode: Literal["preflight"]
    dry_run: bool
    status: Literal["blocked", "ready"]
    checks: list[PreflightCheck]


def release_identity(value: Mapping[str, object]) -> ReleaseIdentity:
    """Select consumed receipt fields; acquisition owns cryptographic/source checks."""
    tag, commit, tag_object = (value.get(key) for key in ("tag", "commit", "tag_object"))
    if not isinstance(tag, str) or not isinstance(commit, str) or not isinstance(tag_object, str):
        # Preflight retains its established fixed invalid-input diagnostic.
        raise ValueError("Invalid acquired release identity.")
    return {"tag": tag, "commit": commit, "tag_object": tag_object}
