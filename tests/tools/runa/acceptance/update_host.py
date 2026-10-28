"""Exercise real updater planning, execution and recovery in disposable customer development/production fixtures."""

from __future__ import annotations

import hashlib
import json
from collections.abc import Callable
from pathlib import Path
from typing import Unpack
from unittest.mock import patch

import distribution.bootstrap as bootstrap
import distribution.update_execution as update_execution
import distribution.update_planning as update
import distribution.update_runtime as update_runtime
from distribution.bootstrap import ReleaseIdentity
from tests.tools.runa.acceptance.acceptance import (
    AcceptanceCall,
    AcceptanceOutput,
    Check,
    GitOptions,
    Report,
    Snapshot,
)
from tools.core.state import JsonValue
from tools.maintainer.service_fixtures import redis_image

ROOT = Path(__file__).resolve().parents[4]


def release_tags(installed_version: str) -> tuple[str, str]:
    major, minor, patch = map(int, installed_version.split("."))
    return "v" + installed_version, f"v{major}.{minor}.{patch + 1}"


def reconcile_fixture_configuration(root: Path, services: dict[str, JsonValue]) -> None:
    """Explicit customer action after the updater applies its approved target."""
    path = root / "compose.yaml"
    value = json.loads(path.read_text())
    if set(services) & value["services"].keys():
        raise RuntimeError("Fixture reconciliation refuses to replace an existing release service.")
    templates = {
        root / name: (root / name).read_text() for name in (".env.dev.example", ".env.prod.example")
    }
    if any(
        any(line.strip().startswith("TEAM_RELEASE_MARKER=") for line in content.splitlines())
        for content in templates.values()
    ):
        raise RuntimeError(
            "Fixture reconciliation refuses to replace an existing environment instruction."
        )
    value["services"].update(services)
    path.write_text(json.dumps(value, indent=2) + "\n")
    for template, content in templates.items():
        template.write_text(content.rstrip("\n") + "\n\nTEAM_RELEASE_MARKER=\n")


def exercise(
    prod: Path,
    fixture: Path,
    project: str,
    check: Check,
    report: Report,
    call: AcceptanceCall,
    output: AcceptanceOutput,
    commit: Callable[[Path], str],
) -> None:
    mode = json.loads((prod / ".git/runa-bootstrap.json").read_text())["mode"]
    installed_version = json.loads((prod / "src/runa/platform.json").read_text())["version"]
    original_tag, target_tag = release_tags(installed_version)
    prod.chmod(0o700)
    if mode == "prod":
        result = update.preflight(prod, bootstrap.GitHub("fixture/runa"), target_tag)
        check(
            result["checks"][-1]["id"] == "development-only" and not result["temporary_downloads"],
            "production updater rejects before release acquisition or runtime mutation",
        )
        return
    repository = fixture / ("reference-releases-" + mode)
    call(["git", "clone", "--quiet", "--no-hardlinks", str(prod), str(repository)])

    def git(*args: str) -> str:
        return output(["git", "-C", str(repository), *args])

    for tag in (original_tag, target_tag):
        call(["git", "-C", str(repository), "tag", "-d", tag], check=False, capture=True)
    for name in ("release-note.txt", "retired-guide.txt"):
        (repository / name).write_text("original release guide\n")
        (prod / name).write_text("original release guide\n")
    commit(repository)
    git(
        "-c",
        "user.name=Fixture",
        "-c",
        "user.email=fixture@example.invalid",
        "tag",
        "-a",
        original_tag,
        "-m",
        "original",
    )
    (repository / "VERSION").write_text(target_tag.removeprefix("v") + "\n")
    manifest = repository / "src/runa/platform.json"
    value = json.loads(manifest.read_text())
    value["version"] = target_tag.removeprefix("v")
    manifest.write_text(json.dumps(value) + "\n")
    migration = repository / "src/runa/database/migrations/2099_01_01_000001_update_preflight.php"
    migration.write_text(
        "<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('CREATE TABLE shared.update_reference (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, code text NOT NULL UNIQUE, label text NOT NULL)'); } };\n"
    )
    scope = repository / "src/runa/Database/DatabaseScope.php"
    scope.write_text(
        scope.read_text().replace(
            "'shared.platform_seed_runs'",
            "'shared.platform_seed_runs', 'shared.update_reference'",
            1,
        )
    )
    seed = repository / "src/runa/database/seeders/DatabaseSeeder.php"
    seed.write_text(
        "<?php namespace Runa\\Database\\Seeders; final class DatabaseSeeder extends \\Illuminate\\Database\\Seeder { public function run(): void { \\Illuminate\\Support\\Facades\\DB::table('shared.update_reference')->upsert([['code'=>'baseline','label'=>'updated-reference']], ['code'], ['label']); } }\n"
    )
    (repository / "release-note.txt").write_text("target release guide\n")
    (repository / "retired-guide.txt").unlink()
    (repository / "new-release-guide.txt").write_text("new target guide\n")
    commit(repository)
    git(
        "-c",
        "user.name=Fixture",
        "-c",
        "user.email=fixture@example.invalid",
        "tag",
        "-a",
        target_tag,
        "-m",
        "target",
    )
    (prod / "release-note.txt").write_text("customer customized guide\n")
    instructions = prod / "AGENTS.md"
    instructions.write_text(instructions.read_text() + "\nCustomer application rule sentinel.\n")
    instruction_before = instructions.read_bytes()
    workspace = prod / ".devcontainer/devcontainer.json"
    value = json.loads(workspace.read_text())
    value["name"] = "Customer customized workspace"
    workspace.write_text(json.dumps(value) + "\n")
    definitions = prod / "compose.yaml"
    configuration = json.loads(definitions.read_text())
    redis = redis_image()
    configuration["services"]["redis"] = {
        "image": redis,
        "pull_policy": "never",
        "user": "1000:1000",
        "env_file": [{"path": ".env", "format": "raw"}],
        "command": ["redis-server", "--save", "", "--appendonly", "no"],
        "read_only": True,
        "tmpfs": ["/data:rw,nosuid,nodev,size=16m,uid=1000,gid=1000"],
        "cap_drop": ["ALL"],
        "security_opt": ["no-new-privileges:true"],
        "cpus": 0.25,
        "mem_limit": "64M",
        "healthcheck": {
            "test": ["CMD", "redis-cli", "ping"],
            "interval": "2s",
            "timeout": "2s",
            "retries": 10,
        },
    }
    definitions.write_text(json.dumps(configuration) + "\n")
    with (prod / ".env").open("a") as stream:
        stream.write("\nTEAM_RELEASE_MARKER=manually-prepared\n")
    for name in (".env.dev.example", ".env.prod.example"):
        with (prod / name).open("a") as stream:
            stream.write("\nTEAM_RELEASE_MARKER=\n")
    infrastructure = {path: path.read_bytes() for path in (workspace, definitions)}
    call([*update_runtime.compose(prod, "dev", prod), "up", "-d", "--wait", "redis"], cwd=prod)
    client = bootstrap.GitHub("fixture/runa")

    def local_git(source: Path, home: Path, *args: str, **kwargs: Unpack[GitOptions]) -> bytes:
        if args[:3] == ("remote", "add", "origin"):
            args = (*args[:3], str(repository))
        env = bootstrap.git_environment(home)
        env["GIT_ALLOW_PROTOCOL"] = "file"
        return bootstrap.process(
            ["git", "-c", "core.hooksPath=/dev/null", "-C", str(source), *args],
            env=env,
            input_data=kwargs.get("input_data"),
        )

    def resolve(tag: str) -> ReleaseIdentity:
        return {
            "release_id": 1,
            "tag_object": git("rev-parse", tag),
            "commit": git("rev-parse", tag + "^{commit}"),
        }

    def sql() -> str:
        return output(
            [
                "docker",
                "exec",
                project + "-postgres-1",
                "psql",
                "-U",
                "runa",
                "-d",
                "runa",
                "-XqAt",
                "-c",
                "SELECT json_build_array((SELECT json_agg(t ORDER BY id) FROM shared.migrations t),(SELECT json_agg(t ORDER BY id) FROM shared.platform_seed_runs t))",
            ]
        )

    def files() -> Snapshot:
        result: Snapshot = {}
        active = (
            prod
            if mode == "dev"
            else Path(
                json.loads((prod / ".runtime/production/deployment.json").read_text())["current"][
                    "path"
                ]
            )
        )
        for root, prefix in ((prod, "operator"), (active, "active")):
            for name in (
                "VERSION",
                "src/runa/platform.json",
                "src/app/application.json",
                "src/composer.json",
                "src/composer.lock",
                "src/package.json",
                "src/pnpm-lock.yaml",
                ".env",
                ".env.dev.local",
                ".env.dev.database",
                ".env.prod",
                ".env.prod.database",
                ".env.prod.compose",
                ".runtime/production/deployment.json",
            ):
                p = root / name
                if p.exists():
                    result[prefix + "/" + name] = (
                        p.stat().st_mode,
                        hashlib.sha256(p.read_bytes()).hexdigest(),
                    )
        return result

    def services() -> dict[str, str]:
        return {
            name: output(
                [
                    "docker",
                    "inspect",
                    "--format",
                    "{{.Id}} {{.State.StartedAt}}",
                    project + "-" + name + "-1",
                ]
            )
            for name in (
                "app",
                "nginx",
                "postgres",
                "queue",
                "scheduler",
                *(["mailpit"] if mode == "dev" else []),
            )
        }

    before = {"files": files(), "database": sql(), "services": services()}
    # Only source transport/API is synthetic. All Git verification, Docker,
    # Composer/pnpm, actual runtime/image and PostgreSQL reads are exercised.
    with (
        patch.object(bootstrap, "git", side_effect=local_git),
        patch.object(client, "resolve", side_effect=resolve),
        patch.object(client, "verify_repository", return_value=1),
    ):
        result = update.preflight(prod, client, target_tag)
        (ROOT / ".runtime/ownership" / ("update-preflight-" + project + ".json")).write_text(
            json.dumps(result, indent=2) + "\n"
        )
        check(
            result["status"] == "ready",
            "external updater verifies independently fetched original/target source and real runtime/dependency/database prerequisites",
        )
        assert "plan" in result
        check(
            result["plan"]["platform_migrations"] == [migration.stem] and result["dry_run"] is True,
            "read-only plan identifies platform-only migration/seeder steps without executing target PHP",
        )
        check(
            before == {"files": files(), "database": sql(), "services": services()},
            "dry-run preserves actual database rows, config/locks/identity/source bytes and runtime container identities/start times",
        )
        check(
            not (prod / ".runtime/production/in-progress.json").exists()
            and result["temporary_downloads_cleaned"]
            and all(not Path(p).exists() for p in result["temporary_downloads"]),
            "planning creates no installation journal/maintenance and removes all external source downloads",
        )
        assert "customization_review" in result
        check(
            result["customization_review"]["required"]
            and "redis" in result["customization_review"]["services"]
            and result["customization_review"]["devcontainer"],
            "real customized Redis and workspace definitions require affected-name review",
        )
        missing = update_execution.execute(prod, client, target_tag)
        aborted = update_execution.execute(prod, client, target_tag, choice="abort")
        check(
            missing["checks"][-1]["id"] == "customization-decision-required"
            and aborted["checks"][-1]["id"] == "customization-aborted"
            and before == {"files": files(), "database": sql(), "services": services()},
            "noninteractive missing decision and explicit abort preserve actual source/data/settings/services",
        )
        original = prod / "src/runa/platform.json"
        content = original.read_bytes()
        value = json.loads(content)
        value["name"] = "tampered"
        original.write_text(json.dumps(value))
        blocked = update.preflight(prod, client, target_tag)
        original.write_bytes(content)
        check(
            blocked["status"] == "blocked",
            "modified platform manifest blocks owner planning without repair or override",
        )
        unknown = prod / "src/runa/unexpected.php"
        unknown.write_text("<?php // unexpected")
        blocked = update.preflight(prod, client, target_tag)
        unknown.unlink()
        check(
            blocked["checks"][-1]["id"] == "protected-source-mismatch",
            "unexpected protected source blocks before target hooks or runtime mutation",
        )
        # Application data work is deliberately pending: the owner update must never execute it.
        app_pending = prod / "src/app/database/migrations/2099_01_01_000099_never_from_updater.php"
        app_pending.write_text(
            "<?php throw new RuntimeException('Updater must not execute application migrations');\n"
        )
        private = prod / "src/storage/app/application/private/update-sentinel"
        private.parent.mkdir(parents=True, exist_ok=True)
        private.write_text("preserved")
        preserved = files()
        git_head = output(["git", "-C", str(prod), "rev-parse", "HEAD"])

        class InterruptedHealth(update_execution.DockerRuntime):
            def health(self) -> None:
                super().health()
                raise RuntimeError("Synthetic lost response after actual healthy switch")

        assert "customization_review" in result
        review = result["customization_review"]
        failed = update_execution.execute(
            prod,
            client,
            target_tag,
            choice="continue",
            fingerprint=review["fingerprint"],
            runtime_factory=InterruptedHealth,
        )
        (
            ROOT / ".runtime/ownership" / ("update-execution-failure-" + project + ".json")
        ).write_text(json.dumps(failed, indent=2) + "\n")
        check(
            failed["status"] == "blocked" and failed.get("phase") == "health",
            "real update reaches schema/seed/source switch and fences a lost health response",
        )
        journal = json.loads((prod / ".runtime/production/in-progress.json").read_text())
        check(
            all(
                output(
                    [
                        "docker",
                        "inspect",
                        "--format",
                        "{{.State.Running}}",
                        project + "-" + service + "-1",
                    ]
                )
                == "false"
                for service in ("app", "nginx", "queue", "scheduler")
            ),
            "failed development update stops HTTP and application writers while retaining PostgreSQL",
        )

        def query(statement: str) -> str:
            return output(
                [
                    "docker",
                    "exec",
                    project + "-postgres-1",
                    "psql",
                    "-U",
                    "runa",
                    "-d",
                    "runa",
                    "-XqAt",
                    "-v",
                    "ON_ERROR_STOP=1",
                    "-c",
                    statement,
                ]
            )

        check(
            query("SELECT count(*) FROM shared.update_reference") == "1",
            "development updater applies actual platform migration and required reference seed",
        )
        seeds_before = query(
            "SELECT count(*) FROM shared.platform_seed_runs WHERE release_sha='"
            + journal["target"]["commit"]
            + "'"
        )
        resumed = update_execution.execute(prod, client, target_tag, resume=True)
        (ROOT / ".runtime/ownership" / ("update-execution-" + project + ".json")).write_text(
            json.dumps(resumed, indent=2) + "\n"
        )
        check(
            resumed["status"] == "completed",
            "independently verified resume recovers the actual development runtime",
        )
        check(
            (prod / "release-note.txt").read_text() == "target release guide\n"
            and not (prod / "retired-guide.txt").exists()
            and (prod / "new-release-guide.txt").read_text() == "new target guide\n",
            "real reviewed update applies non-infrastructure replacement, removal and addition",
        )
        assert "manual_reconciliation" in resumed
        check(
            instructions.read_bytes() == instruction_before
            and "AGENTS.md" in resumed["manual_reconciliation"],
            "real update preserves customer instruction extensions for explicit reconciliation",
        )
        check(
            query(
                "SELECT count(*) FROM shared.platform_seed_runs WHERE release_sha='"
                + journal["target"]["commit"]
                + "'"
            )
            == seeds_before
            == "1",
            "resume never blindly replays already committed required seeds",
        )
        check(
            all(
                path.read_bytes() == (repository / path.relative_to(prod)).read_bytes()
                for path in infrastructure
            )
            and output(["docker", "exec", project + "-redis-1", "redis-cli", "ping"]) == "PONG",
            "explicit continue applies exact reviewed infrastructure while preserving the existing custom service until operator reconciliation",
        )
        check(
            private.read_text() == "preserved"
            and app_pending.is_file()
            and output(["git", "-C", str(prod), "rev-parse", "HEAD"]) == git_head,
            "updater preserves application pending data/source, private storage and customer Git HEAD",
        )
        after = files()
        check(
            all(
                after[name] == content
                for name, content in preserved.items()
                if not name.endswith(("/src/runa/platform.json", "/VERSION"))
            ),
            "customer identity, dependency locks, settings survive reviewed update and recovery",
        )
        check(
            not (prod / ".runtime/production/in-progress.json").exists()
            and not list((prod / ".runtime/production/update-staging").iterdir()),
            "successful resume archives journal and removes only its verified temporary platform source",
        )
        app_pending.unlink()
        # The updater applied the reviewed release, including removal of the local
        # descriptor and public environment instructions. Reconcile both before
        # Team publishes the complete customer project for fresh installation.
        reconcile_fixture_configuration(prod, {"redis": configuration["services"]["redis"]})
        check(
            json.loads(definitions.read_text())["services"]["redis"]
            == configuration["services"]["redis"]
            and all(
                (prod / name).read_text().count("TEAM_RELEASE_MARKER=\n") == 1
                for name in (".env.dev.example", ".env.prod.example")
            ),
            "customer reconciles its additional service and both environment templates before team publication and production deployment",
        )
    assert "target_release" in result
    report.setdefault("update_preflight", {})[mode] = {
        "result": result["status"],
        "target": result["target_release"]["commit"],
    }
