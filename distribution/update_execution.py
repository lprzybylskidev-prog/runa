"""External development-only source replacement, fixed forward steps and recovery."""

import json
import os
import re
import shutil
import stat
import tempfile
import uuid
from collections.abc import Callable, Iterator, Sequence
from contextlib import AbstractContextManager, contextmanager
from datetime import UTC, datetime
from pathlib import Path
from shutil import copytree
from typing import Unpack

from distribution import bootstrap, update_customization, update_files, update_runtime
from distribution import update_planning as update
from distribution.update_contracts import (
    SEMVER,
    Blocker,
    compare,
    compose,
    document,
    platform,
    supports,
)
from distribution.update_docker import DockerRuntime
from distribution.update_execution_models import (
    CompletedModuleStep,
    ExecutionJournal,
    ExecutionOptions,
    ExecutionReport,
    Preflight,
    RuntimeFactory,
    UpdateRuntime,
    baseline_metadata,
    execution_journal,
    journal_review,
)
from distribution.update_file_models import CustomizationReview
from distribution.update_models import DatabaseMetadata, SeedRun
from distribution.update_plan_models import LockProbe, PreflightReport, ReleaseDownload
from tools.core.reporting import SafeFailure
from tools.core.state import json_value

SEEDER = "Runa\\Database\\Seeders\\DatabaseSeeder"


def stamp() -> str:
    return datetime.now(UTC).isoformat()


def private_directory(path: Path) -> None:
    if any(item.is_symlink() for item in [path, *path.parents]):
        raise Blocker("unsafe-operation-state", "Update state must not traverse symlinks.")
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    info = path.stat()
    if not stat.S_ISDIR(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077:
        raise Blocker(
            "unsafe-operation-state", "Update state must be a private operator-owned directory."
        )


def atomic(path: Path, value: object) -> None:
    from tools.core.state import JsonStore, StateDirectory

    JsonStore(StateDirectory(path.parent, anchor=path.parent)).write(path.name, json_value(value))


@contextmanager
def lock(root: Path) -> Iterator[Path]:
    from tools.core.state import FileLock, StateDirectory

    state = root / ".runtime/production"
    try:
        with FileLock(
            StateDirectory(state, anchor=root),
            "operation.lock",
            "Another installation/deployment/update operation is running.",
        ).acquire():
            yield state
    except BlockingIOError:
        raise Blocker(
            "operation-running", "Another installation/deployment/update operation is running."
        ) from None


def read_journal(root: Path, client: bootstrap.GitHub, tag: str) -> ExecutionJournal:
    name = ".runtime/production/in-progress.json"
    path = root / name
    value = document(root, name, 2 * 1024**2)
    if path.stat().st_uid != os.getuid() or path.stat().st_mode & 0o077:
        raise Blocker(
            "unsafe-operation-state", "Update journal must be private and operator-owned."
        )
    return execution_journal(value, root, client.repository, tag)


def target_inputs(root: Path, target: Path) -> None:
    application = document(root, "src/app/application.json")
    version = application.get("version")
    if (
        type(application.get("schema_version")) is not int
        or application.get("schema_version") != 1
        or not isinstance(version, str)
        or not re.fullmatch(SEMVER, version)
        or not supports(application.get("runa"), platform(target))
    ):
        raise Blocker(
            "application-incompatible",
            "Application manifest does not support the selected platform release.",
        )
    for kind in ("composer", "package"):
        expected = compose(
            document(target, "src/runa/" + kind + ".json"),
            document(root, "src/app/" + kind + ".json"),
            kind,
        )
        if document(root, "src/" + kind + ".json") != expected:
            raise Blocker(
                "manual-scaffold-reconciliation",
                "Manually reconcile the target composed "
                + kind
                + " manifest and locks before execution; customer scaffold is never overwritten.",
            )


def move(source: Path, destination: Path) -> None:
    os.rename(source, destination)
    for parent in (source.parent, destination.parent):
        descriptor = os.open(parent, os.O_RDONLY | os.O_DIRECTORY)
        try:
            os.fsync(descriptor)
        finally:
            os.close(descriptor)


def stage_shape(operation: Path) -> None:
    for child in operation.iterdir():
        if (
            child.name not in ("incoming", "previous", "project-files")
            or child.is_symlink()
            or not child.is_dir()
        ):
            raise Blocker(
                "source-staging-changed",
                "Unexpected operation staging content is preserved for inspection.",
            )
        if child.name == "project-files":
            continue  # Independently checked against journaled file identities.
        for part in child.iterdir():
            if part.name != "src" or part.is_symlink() or not part.is_dir():
                raise Blocker(
                    "source-staging-changed",
                    "Unexpected operation staging content is preserved for inspection.",
                )
            if any(p.name != "runa" for p in part.iterdir()):
                raise Blocker(
                    "source-staging-changed",
                    "Unexpected operation staging content is preserved for inspection.",
                )


def references(
    client: bootstrap.GitHub,
    journal: ExecutionJournal,
    directory: Path,
    download: ReleaseDownload,
) -> tuple[Path, Path]:
    result = []
    for key in ("original", "target"):
        root = directory / key
        expected = journal[key]
        identity = download(
            client, expected["tag"], root, "dev", expected_commit=expected["commit"]
        )
        if (
            any(identity.get(field) != expected[field] for field in ("tag", "commit", "tag_object"))
            or platform(root) != expected["tag"][1:]
        ):
            raise Blocker(
                "release-changed",
                "Independently acquired release identity changed; preserve the update journal.",
            )
        result.append(root)
    return result[0], result[1]


def source_state(
    root: Path, operation: Path, original: Path, target: Path, journal: ExecutionJournal
) -> str:
    incoming, previous = operation / "incoming", operation / "previous"
    for name, expected in ((incoming, target), (previous, original)):
        path = name / "src/runa"
        if path.exists() or path.is_symlink():
            differences = compare(name, expected)
            partial = (
                name == incoming
                and journal["phase"] == "maintenance"
                and (root / "src/runa").is_dir()
                and not compare(root, original)
                and all(row["kind"] == "missing" for row in differences)
            )
            if differences and not partial:
                raise Blocker(
                    "protected-source-mismatch",
                    "Journaled source staging differs from its independently downloaded release.",
                )
    actual = root / "src/runa"
    if actual.exists() or actual.is_symlink():
        if not compare(root, target):
            return "target"
        if not compare(root, original):
            return "original"
        raise Blocker(
            "protected-source-mismatch",
            "Installed platform differs from both verified operation releases; no force/repair override exists.",
        )
    if (
        not journal.get("source_transition")
        or not (previous / "src/runa").is_dir()
        or not (incoming / "src/runa").is_dir()
    ):
        raise Blocker(
            "source-transition",
            "Missing platform source is not a verified interrupted directory transition.",
        )
    return "gap"


def execute(
    value: str | Path,
    client: bootstrap.GitHub,
    tag: str,
    *,
    resume: bool = False,
    choice: str | None = None,
    fingerprint: str | None = None,
    toolchain: str = "runa-development:dev",
    expected_commit: str | None = None,
    download: ReleaseDownload = bootstrap.acquire,
    runtime_factory: RuntimeFactory = DockerRuntime,
    lock_probe: LockProbe = update_runtime.validate_locks,
    choose: Callable[[CustomizationReview], str | None] | None = None,
    planner: Preflight = update.preflight,
) -> ExecutionReport:
    report: ExecutionReport = {
        "schema_version": 1,
        "mode": "resume" if resume else "apply",
        "dry_run": False,
        "status": "blocked",
        "checks": [],
    }
    journal: ExecutionJournal | None = None
    runtime: UpdateRuntime | None = None
    root: Path | None = None
    locked: AbstractContextManager[Path] | None = None
    snapshot: PreflightReport | None = None
    approved: str | None = None
    review: CustomizationReview | None = None
    mutation_started = False
    try:
        root = update.installation(value, allow_pending=resume)
        if Path(__file__).resolve().is_relative_to(root):
            raise Blocker(
                "owner-tool-location", "Run the owner updater outside the customer installation."
            )
        runtime = runtime_factory(root)
        if not resume:
            snapshot = planner(
                root,
                client,
                tag,
                toolchain=toolchain,
                download=download,
                runtime_probe=runtime.probe,
                lock_probe=lock_probe,
                expected_commit=expected_commit,
            )
            if snapshot["status"] != "ready":
                return {**snapshot, "mode": "apply", "dry_run": False}
            review, plan = snapshot.get("customization_review"), snapshot.get("plan")
            if review is None or plan is None:
                raise ValueError("Incomplete ready update preflight.")
            report["customization_review"] = review
            if plan["no_op"]:
                return {**report, "status": "completed", "no_op": True}
            if fingerprint is not None and fingerprint != review["fingerprint"]:
                raise Blocker(
                    "customization-changed",
                    "The supplied review fingerprint differs from current delivered files.",
                )
            if review["required"] and choice is None and choose is not None:
                choice = choose(review)
            if (
                review["required"]
                and choice == "continue"
                and choose is None
                and fingerprint != review["fingerprint"]
            ):
                raise Blocker(
                    "customization-decision-required",
                    "Unattended continue requires the exact reviewed plan fingerprint.",
                )
            approved = update_customization.decision(review, choice)
            runtime.manual()
        locked = lock(root)
        state = locked.__enter__()
        if (root / "src").stat().st_dev != state.stat().st_dev:
            raise Blocker(
                "source-filesystem",
                "Atomic platform replacement requires source and operation staging on the same filesystem.",
            )
        pending = state / "in-progress.json"
        if resume:
            journal = read_journal(root, client, tag)
        else:
            if pending.exists() or pending.is_symlink():
                raise Blocker("unresolved-operation", "Another pending operation blocks execution.")
            # Revalidate the complete snapshot under the exclusive operation lock.
            fresh = planner(
                root,
                client,
                tag,
                toolchain=toolchain,
                download=download,
                runtime_probe=runtime.probe,
                lock_probe=lock_probe,
                expected_commit=expected_commit,
                lock_held=True,
            )
            if fresh["status"] != "ready":
                return {**fresh, "mode": "apply", "dry_run": False}
            assert snapshot is not None and review is not None
            installed_release = fresh.get("installed_release")
            target_release = fresh.get("target_release")
            runtime_summary = fresh.get("runtime")
            fresh_plan = fresh.get("plan")
            if (
                installed_release is None
                or target_release is None
                or runtime_summary is None
                or fresh_plan is None
            ):
                raise ValueError("Incomplete ready update preflight.")
            if (
                installed_release != snapshot.get("installed_release")
                or target_release != snapshot.get("target_release")
                or fresh.get("customization_review") != review
            ):
                raise Blocker(
                    "preflight-changed",
                    "Source/review changed before the exclusive lock; inspect a fresh plan.",
                )
            runtime.manual()
            runtime.bind(runtime_summary["project"])
            baseline = runtime.metadata()
            journal = {
                "schema_version": 1,
                "kind": "development-update",
                "id": uuid.uuid4().hex,
                "root": str(root),
                "repository": client.repository.lower(),
                "project": runtime_summary["project"],
                "baseline": baseline_metadata(baseline),
                "original": installed_release,
                "target": target_release,
                "module_plan": fresh_plan.get("modules", []),
                "phase": "planned",
                "completed": ["planned", "preflight-passed", "customization-reviewed"],
                "review": journal_review(review),
                "decision": approved,
                "file_actions": update_files.actions(review),
                "file_action_history": [],
                "state": "running",
                "started_at": stamp(),
            }
            atomic(pending, journal)
        runtime.bind(journal["project"])
        operation = state / "update-staging" / journal["id"]
        private_directory(operation)
        temporary_parent = Path(os.environ.get("TMPDIR", "/tmp")).resolve(strict=True)
        if temporary_parent.is_relative_to(root):
            raise Blocker(
                "unsafe-staging", "Release downloads must remain outside the installation."
            )
        with tempfile.TemporaryDirectory(
            prefix="runa-update-execute-", dir=temporary_parent
        ) as temporary:
            original, target = references(client, journal, Path(temporary), download)
            stage_shape(operation)
            position = source_state(root, operation, original, target, journal)
            overrides = update_files.validate_staging(root, operation, original, target, journal)
            reviewed = update_customization.review(
                root,
                original,
                target,
                {"original": journal["original"], "target": journal["target"]},
                overrides,
            )
            report["customization_review"] = reviewed
            if reviewed["fingerprint"] != journal["review"]["fingerprint"]:
                if choose is not None:
                    choice = choose(reviewed)
                    fingerprint = reviewed["fingerprint"]
                if choice != "continue" or fingerprint != reviewed["fingerprint"]:
                    raise Blocker(
                        "customization-changed",
                        "Delivered files changed since review; inspect the reported names and explicitly continue with the new review fingerprint.",
                    )
                if any(row["state"] == "replacing" for row in journal.get("file_actions", [])):
                    raise Blocker(
                        "project-transition-changed",
                        "Finish or inspect interrupted file transitions before accepting a different plan.",
                    )
                update_files.renew_actions(reviewed, journal)
                journal["decision"] = update_customization.decision(reviewed, choice)
                journal["review"] = journal_review(reviewed)
                atomic(pending, journal)
            elif choice == "abort":
                raise Blocker(
                    "customization-aborted",
                    "Operator aborted resume; source/data and recovery evidence preserved.",
                )
            if expected_commit and expected_commit != journal["target"]["commit"]:
                raise Blocker(
                    "release-changed", "Resume target differs from the recorded exact release."
                )
            target_inputs(root, target)
            lock_probe(
                update.candidate(
                    root, target, Path(temporary) / "dependencies", current_platform=original
                ),
                toolchain,
            )
            metadata = runtime.metadata()
            baseline = journal["baseline"]

            def pairs(rows: Sequence[SeedRun]) -> set[tuple[str, str]]:
                return {(row["release_sha"], row["seeder"]) for row in rows}

            if json.dumps(baseline.get("database_mounts"), sort_keys=True) != json.dumps(
                metadata.get("database_mounts"), sort_keys=True
            ):
                raise Blocker(
                    "database-identity",
                    "Journaled database mounts changed; preserve and inspect the original data before resuming.",
                )
            if not set(baseline["migrations"]) <= set(metadata["migrations"]) or not pairs(
                baseline["seed_runs"]
            ) <= pairs(metadata["seed_runs"]):
                raise Blocker(
                    "database-identity",
                    "Previously recorded platform data/history is missing; do not adopt a different database.",
                )

            def module_records(
                rows: Sequence[CompletedModuleStep],
            ) -> set[tuple[str, str, str, str]]:
                return {
                    (row["module"], row["step"], row["fingerprint"], row["status"]) for row in rows
                }

            if not module_records(baseline.get("module_steps", [])) <= module_records(
                metadata.get("module_steps", [])
            ):
                raise Blocker(
                    "module-history-missing",
                    "Recorded baseline module completion evidence is missing or changed.",
                )
            if "platform-modules" in journal["completed"]:
                if not module_records(journal.get("module_steps", [])) <= module_records(
                    metadata.get("module_steps", [])
                ):
                    raise Blocker(
                        "module-history-missing",
                        "Recorded completed module steps are missing or changed.",
                    )
                for module in journal.get("module_plan", []):
                    actual = metadata.get("modules", {}).get(module["module"])
                    if (
                        actual is None
                        or actual["installed"] != module["to"]
                        or not actual["registered"]
                    ):
                        raise Blocker(
                            "module-version-mismatch",
                            "Recorded completed module installed version is missing or changed.",
                        )
            migration_names = {
                p.stem for p in (target / "src/runa/database/migrations").glob("*.php")
            }
            if set(metadata["migrations"]) - migration_names:
                raise Blocker(
                    "migration-state-mismatch",
                    "Database contains platform migration history absent from the verified target.",
                )

            target_commit = journal["target"]["commit"]

            def seed_done(value: DatabaseMetadata) -> bool:
                return any(
                    row.get("release_sha") == target_commit and row.get("seeder") == SEEDER
                    for row in value["seed_runs"]
                )

            if "platform-migrations" in journal["completed"] and not migration_names <= set(
                metadata["migrations"]
            ):
                raise Blocker(
                    "migration-state-mismatch",
                    "Recorded completed migrations are missing from the database.",
                )
            if "platform-seeders/upgrades" in journal["completed"] and not seed_done(metadata):
                raise Blocker(
                    "seed-state-mismatch",
                    "Recorded required seed completion is missing from the database.",
                )

            def begin(step: str) -> None:
                assert journal is not None
                journal.update(phase=step, state="running", updated_at=stamp())
                atomic(pending, journal)

            def done(step: str) -> None:
                assert journal is not None
                if step not in journal["completed"]:
                    journal["completed"].append(step)
                journal.update(updated_at=stamp())
                atomic(pending, journal)

            # Always reestablish fencing before any resumed source/data work.
            mutation_started = True
            begin("maintenance")
            runtime.fence(journal["id"])
            done("maintenance")
            if position == "original":
                incoming = operation / "incoming/src/runa"
                incoming.parent.mkdir(parents=True, exist_ok=True)
                copytree(target / "src/runa", incoming, symlinks=True, dirs_exist_ok=True)
                if compare(operation / "incoming", target):
                    raise Blocker(
                        "protected-source-mismatch",
                        "Staged target source does not match the verified release.",
                    )
            if position != "target":
                journal["source_transition"] = True
                begin("source-replacing")
                previous = operation / "previous/src/runa"
                previous.parent.mkdir(parents=True, exist_ok=True)
                if position == "original":
                    move(root / "src/runa", previous)
                move(operation / "incoming/src/runa", root / "src/runa")
            if compare(root, target):
                raise Blocker(
                    "protected-source-mismatch",
                    "Replaced source does not match the verified target release.",
                )
            update_files.apply_actions(
                root, operation, target, journal, lambda: atomic(pending, journal), move
            )
            done("source-replaced")
            # Rebuild on resume against the current explicitly reconciled scaffold;
            # only transactional data completions are skipped from durable evidence.
            begin("staged/built")
            runtime.build()
            done("staged/built")
            if "platform-migrations" not in journal["completed"]:
                begin("platform-migrations")
                if not migration_names <= set(runtime.metadata()["migrations"]):
                    runtime.data("migrations", journal["target"]["commit"])
                if not migration_names <= set(runtime.metadata()["migrations"]):
                    raise Blocker(
                        "migration-state-mismatch",
                        "Forward migration completion could not be verified.",
                    )
                done("platform-migrations")
            if "platform-seeders/upgrades" not in journal["completed"]:
                begin("platform-seeders/upgrades")
                if not seed_done(runtime.metadata()):
                    runtime.data("required-seeds", journal["target"]["commit"])
                if not seed_done(runtime.metadata()):
                    raise Blocker(
                        "seed-state-mismatch", "Required seed completion could not be verified."
                    )
                done("platform-seeders/upgrades")
            if "platform-modules" not in journal["completed"]:
                begin("platform-modules")
                if any(
                    (target / path).is_file()
                    for path in (
                        "src/runa/ModuleFramework/Updates/ModuleUpdates.php",
                        "src/runa/Support/Modules/Updates/ModuleUpdates.php",
                    )
                ):
                    runtime.data("modules", journal["target"]["commit"])
                journal["module_steps"] = runtime.metadata().get("module_steps", [])
                done("platform-modules")
            begin("switch")
            runtime.activate(journal["id"])
            done("switch")
            begin("health")
            runtime.health()
            done("health")
            if compare(root, target):
                raise Blocker(
                    "protected-source-mismatch", "Runtime/build modified protected target source."
                )
            source_state(root, operation, original, target, journal)
            stage_shape(operation)
            begin("completed")
            done("completed")
            journal.update(state="complete", completed_at=stamp())
            atomic(state / "updates" / (journal["id"] + ".json"), journal)
            # Only this operation's independently verified source staging.
            report["source_staging_removed"] = str(operation)
            pending.unlink()
            report.update(
                status="completed",
                operation_id=journal["id"],
                target_release=journal["target"],
                manual_reconciliation=journal["review"].get("manual_reconciliation", []),
                infrastructure_follow_up={
                    "services": journal["review"]["services"],
                    "devcontainer": journal["review"]["devcontainer"],
                    "instruction": "Review image rebuild/recreation and devcontainer reopen requirements before committing/deploying the customer project.",
                },
            )
            # Completion is durable before best-effort disposal: a cleanup interruption
            # must not resurrect a pending data operation or fence a healthy release.
            try:
                shutil.rmtree(operation)
            except OSError:
                report["source_staging_requires_inspection"] = True
    except (Blocker, bootstrap.BootstrapError) as error:
        report["checks"].append(
            {
                "id": getattr(error, "code", "release-acquisition"),
                "status": "blocked",
                "message": str(error),
            }
        )
    except (SafeFailure, OSError, ValueError, KeyError, TypeError, RuntimeError):
        report["checks"].append(
            {
                "id": "execution-failed",
                "status": "blocked",
                "message": "Update step failed; inspect the private operation journal and runtime diagnostics before explicit resume.",
            }
        )
    except BaseException:
        report["status"] = "interrupted"
        raise
    finally:
        try:
            if report["status"] != "completed" and journal is not None:
                if mutation_started:
                    assert root is not None and runtime is not None
                    journal.update(
                        state="interrupted" if report["status"] == "interrupted" else "failed",
                        updated_at=stamp(),
                    )
                    atomic(root / ".runtime/production/in-progress.json", journal)
                    # Preserve foreign/manual maintenance markers on fencing failure.
                    try:
                        runtime.fence(journal["id"])
                    except Exception:
                        report["fencing_requires_inspection"] = True
                report.update(
                    operation_id=journal["id"],
                    phase=journal["phase"],
                    journal=".runtime/production/in-progress.json",
                )
        finally:
            if locked is not None:
                locked.__exit__(None, None, None)
    return report


class UpdateExecutor:
    def __init__(
        self, planner: update.UpdatePlanner, *, runtime_factory: RuntimeFactory = DockerRuntime
    ) -> None:
        self.planner = planner
        self.runtime_factory = runtime_factory

    def execute(
        self,
        workspace: str | Path,
        client: bootstrap.GitHub,
        tag: str,
        **options: Unpack[ExecutionOptions],
    ) -> ExecutionReport:
        return execute(
            workspace,
            client,
            tag,
            download=self.planner.download,
            lock_probe=self.planner.lock_probe,
            runtime_factory=self.runtime_factory,
            planner=self.planner.preflight,
            **options,
        )
