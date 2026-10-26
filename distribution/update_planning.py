#!/usr/bin/env python3
"""External development-only owner updater: read-only plans, execution and explicit recovery."""

import fcntl
import json
import os
import re
import shutil
import stat
import tempfile
from contextlib import contextmanager, nullcontext
from pathlib import Path

from distribution import bootstrap, update_customization, update_modules, update_runtime
from distribution.update_contracts import (
    OWNER_PATHS,
    SEMVER,
    Blocker,
    changes,
    compare,
    compose,
    document,
    platform,
    regular,
    stable,
    supports,
)


@contextmanager
def operation_snapshot(root):
    """Observe existing locks without creating a lock/state directory in dry-run."""
    path = root / ".runtime/production/operation.lock"
    stream = None
    try:
        if path.exists() or path.is_symlink():
            if any(p.is_symlink() for p in [path, *path.parents]):
                raise Blocker("unsafe-lock", "Operation lock must not traverse symlinks.")
            descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
            stream = os.fdopen(descriptor, "rb")
            info = os.fstat(stream.fileno())
            if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_mode & 0o077:
                raise Blocker(
                    "unsafe-lock", "Operation lock must be a private operator-owned regular file."
                )
            try:
                fcntl.flock(stream, fcntl.LOCK_SH | fcntl.LOCK_NB)
            except BlockingIOError:
                raise Blocker(
                    "operation-running",
                    "Another installation/deployment/update operation is running.",
                ) from None
        yield
    finally:
        if stream:
            stream.close()


def installation(value, *, allow_pending=False):
    root = Path(value).absolute()
    if (
        root.resolve() != root
        or not root.is_dir()
        or root.stat().st_uid != os.getuid()
        or root.stat().st_mode & 0o022
    ):
        raise Blocker(
            "installation-ownership",
            "Select an existing operator-owned installation without symlinks or group/other write access.",
        )
    if not (root / ".git").is_dir() or (root / ".git").is_symlink():
        raise Blocker(
            "installation-identity", "Select a customer Git checkout with real local metadata."
        )
    for name in OWNER_PATHS:
        if (root / name).exists() or (root / name).is_symlink():
            raise Blocker(
                "owner-source-present",
                "Owner-only source is materialized in the installation: " + name,
            )
    marker = root / ".git/runa-bootstrap.json"
    if marker.exists() or marker.is_symlink():
        receipt = document(root, ".git/runa-bootstrap.json", 32768)
        if receipt.get("mode") != "dev":
            raise Blocker(
                "development-only",
                "The Runa updater operates exclusively on development checkouts; deploy customer commits to production.",
            )
    if (root / ".env.prod.compose").exists() or (root / ".env.prod.compose").is_symlink():
        raise Blocker("development-only", "Production configuration cannot be an updater target.")
    values = update_runtime.env_file(root, ".env")
    if values.get("APP_ENV", "").strip("\"'") != "local":
        raise Blocker(
            "development-only", "Configure the customer development environment before updating."
        )
    if not allow_pending and (
        (root / ".runtime/production/in-progress.json").exists()
        or (root / ".runtime/production/in-progress.json").is_symlink()
    ):
        raise Blocker(
            "unresolved-operation",
            "Unresolved operation state blocks planning; preserve recovery evidence.",
        )
    return root


def candidate(root, target, directory, *, current_platform=None):
    destination = directory / "candidate"
    (destination / "src").mkdir(parents=True)
    for kind in ("composer", "package"):
        app = document(root, "src/app/" + kind + ".json")
        result = compose(document(target, "src/runa/" + kind + ".json"), app, kind)
        current = compose(
            document(current_platform or root, "src/runa/" + kind + ".json"), app, kind
        )
        if document(root, "src/" + kind + ".json") not in (current, result):
            raise Blocker(
                "dependency-composition", "Installed composed dependency manifest is stale: " + kind
            )
        (destination / "src" / (kind + ".json")).write_text(json.dumps(result, indent=2) + "\n")
    for name in ("composer.lock", "pnpm-lock.yaml"):
        (destination / "src" / name).write_bytes(regular(root, "src/" + name, 32 * 1024**2))
    (destination / "src/pnpm-workspace.yaml").write_bytes(regular(root, "src/pnpm-workspace.yaml"))
    return destination


def plan(root, target, original, target_identity, installed_identity, runtime):
    delta = changes(original, target)
    migrations = sorted(p.stem for p in (target / "src/runa/database/migrations").glob("*.php"))
    applied = set(runtime["migrations"])
    changed_applied = [
        row["path"]
        for row in delta
        if row["path"].startswith("src/runa/database/migrations/")
        and Path(row["path"]).stem in applied
    ]
    if changed_applied:
        raise Blocker(
            "applied-migration-changed",
            "Target changes/removes applied platform migrations: "
            + ", ".join(changed_applied[:10]),
        )
    seeder = "Runa\\Database\\Seeders\\DatabaseSeeder"
    seed_done = any(
        row.get("release_sha") == target_identity["commit"] and row.get("seeder") == seeder
        for row in runtime["seed_runs"]
    )
    return {
        "no_op": target_identity["commit"] == installed_identity["commit"]
        and not (set(migrations) - applied)
        and seed_done,
        "source_changes": delta,
        "platform_migrations": [name for name in migrations if name not in applied],
        "platform_seeder": {
            "class": seeder,
            "release": target_identity["commit"],
            "already_completed": seed_done,
        },
        "customer_inputs": "application/private inputs preserved; exact reviewed delivered-file actions require consent",
        "steps": [
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
        ],
        "preserve": [
            "src/app",
            "docs/app",
            "application identity/version",
            "application dependency declarations/locks",
            "private environment inputs",
            "both durable storage trees",
            "customer Git remotes/history",
        ],
        "uncertainties": [
            "Migration/seed bodies are not executed to predict effects.",
            "Image build disk use is an estimate.",
            "Offline lock consistency is not a supply-chain audit; real build/install policies must still pass.",
            "Preflight is a snapshot, not a reservation; execution must revalidate.",
            "Code rollback cannot reverse schema/data.",
        ],
    }


def preflight(
    value,
    client,
    tag,
    *,
    toolchain="runa-development:dev",
    download=bootstrap.acquire,
    runtime_probe=update_runtime.probe,
    lock_probe=update_runtime.validate_locks,
    expected_commit=None,
    lock_held=False,
    module_plan=update_modules.plan,
):
    report = {
        "schema_version": 1,
        "mode": "preflight",
        "dry_run": True,
        "status": "blocked",
        "checks": [],
        "execution_available": True,
        "mismatches": [],
        "temporary_downloads": [],
        "temporary_downloads_cleaned": False,
    }

    def passed(name):
        report["checks"].append({"id": name, "status": "passed"})

    try:
        root = installation(value)
        if Path(__file__).resolve().is_relative_to(root):
            raise Blocker(
                "owner-tool-location",
                "Run the separately supplied owner updater outside the customer installation.",
            )
        version = platform(root)
        stable(tag.removeprefix("v"))
        if not re.fullmatch(bootstrap.TAG, tag):
            raise Blocker("target-release", "Select an exact stable published target release.")
        report["installed_version"], report["target_version"] = version, tag[1:]
        if stable(tag[1:]) < stable(version):
            raise Blocker(
                "downgrade-unsupported",
                "A downgrade has unproven schema/data compatibility; use reviewed recovery rather than an update plan.",
            )
        passed("installation-identity")
        with nullcontext() if lock_held else operation_snapshot(root):
            temporary_parent = Path(os.environ.get("TMPDIR", "/tmp")).resolve(strict=True)
            if temporary_parent.is_relative_to(root):
                raise Blocker(
                    "unsafe-staging",
                    "Temporary release downloads must stay outside the installation.",
                )
            with tempfile.TemporaryDirectory(
                prefix="runa-update-plan-", dir=temporary_parent
            ) as temporary:
                staging = Path(temporary).resolve()
                if staging.is_relative_to(root):
                    raise Blocker(
                        "unsafe-staging",
                        "Temporary release downloads must stay outside the installation.",
                    )
                report["temporary_downloads"] = [str(staging / "original"), str(staging / "target")]
                original, target = staging / "original", staging / "target"
                installed_identity = download(client, "v" + version, original, "dev")
                if platform(original) != version:
                    raise Blocker(
                        "original-release",
                        "Original release platform identity does not match the installed version.",
                    )
                report["installed_release"] = {
                    key: installed_identity[key] for key in ("tag", "commit", "tag_object")
                }
                passed("original-release")
                report["mismatches"] = compare(root, original)
                if report["mismatches"]:
                    raise Blocker(
                        "protected-source-mismatch",
                        "Protected source differs from the independently downloaded installed release; no repair or consent override exists.",
                    )
                passed("protected-source")
                target_identity = download(
                    client, tag, target, "dev", expected_commit=expected_commit
                )
                if platform(target) != tag[1:]:
                    raise Blocker(
                        "target-release", "Target release platform identity does not match its tag."
                    )
                report["target_release"] = {
                    key: target_identity[key] for key in ("tag", "commit", "tag_object")
                }
                passed("target-release")
                applications = [("development", root)]
                for location, source in applications:
                    application = document(source, "src/app/application.json", 16384)
                    if (
                        type(application.get("schema_version")) is not int
                        or application.get("schema_version") != 1
                        or not isinstance(application.get("version"), str)
                        or not re.fullmatch(SEMVER, application["version"])
                        or not supports(application.get("runa"), tag[1:])
                    ):
                        raise Blocker(
                            "application-incompatible",
                            "Application manifest schema/range does not support the selected platform release: "
                            + location,
                        )
                passed("application-compatibility")
                free = shutil.disk_usage(root).free
                if free < bootstrap.MIN_FREE or not os.access(root, os.W_OK | os.X_OK):
                    raise Blocker(
                        "disk-permissions",
                        "Installation needs an owned writable directory and at least 5 GiB free space; builds may require more.",
                    )
                report["free_bytes"] = free
                passed("disk-permissions")
                report["dependency_checks"] = []
                for location, source in applications:
                    report["toolchain_image"] = lock_probe(
                        candidate(source, target, staging / location), toolchain
                    )
                    report["dependency_checks"].append(location)
                passed("preserved-dependency-locks")
                runtime = runtime_probe(root, "dev", root)
                report["runtime"] = {"project": runtime["project"], "services": runtime["services"]}
                passed("runtime-and-database")
                report["plan"] = plan(
                    root, target, original, target_identity, installed_identity, runtime
                )
                report["plan"]["modules"] = module_plan(
                    root, target, runtime, report["toolchain_image"]
                )
                report["plan"]["no_op"] = report["plan"]["no_op"] and all(
                    row["from"] == row["to"]
                    and not row["pending_migrations"]
                    and not row["pending_upgrades"]
                    for row in report["plan"]["modules"]
                )
                report["customization_review"] = update_customization.review(
                    root,
                    original,
                    target,
                    {"original": report["installed_release"], "target": report["target_release"]},
                )
                passed("delivered-file-review")
                report["plan"]["no_op"] = (
                    report["plan"]["no_op"] and not report["customization_review"]["required"]
                )
                report["status"] = "ready"
    except Blocker as error:
        report["checks"].append({"id": error.code, "status": "blocked", "message": str(error)})
    except bootstrap.BootstrapError as error:
        report["checks"].append(
            {"id": "release-acquisition", "status": "blocked", "message": str(error)}
        )
    except (
        OSError,
        ValueError,
        KeyError,
        IndexError,
        AttributeError,
        TypeError,
        SyntaxError,
        RuntimeError,
    ):
        report["checks"].append(
            {
                "id": "invalid-preflight-input",
                "status": "blocked",
                "message": "Preflight input/probe is unavailable or unsupported; no update was executed.",
            }
        )
    finally:
        report["temporary_downloads_cleaned"] = all(
            not Path(p).parent.exists() for p in report["temporary_downloads"]
        )
    return report


class UpdatePlanner:
    def __init__(
        self,
        *,
        download=bootstrap.acquire,
        runtime_probe=update_runtime.probe,
        lock_probe=update_runtime.validate_locks,
        module_plan=update_modules.plan,
    ):
        self.download = download
        self.runtime_probe = runtime_probe
        self.lock_probe = lock_probe
        self.module_plan = module_plan

    def preflight(self, workspace, client, tag, **options):
        options.setdefault("download", self.download)
        options.setdefault("runtime_probe", self.runtime_probe)
        options.setdefault("lock_probe", self.lock_probe)
        options.setdefault("module_plan", self.module_plan)
        return preflight(workspace, client, tag, **options)
