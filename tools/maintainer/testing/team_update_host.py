"""Real customer commit/push/pull/data/deploy propagation in disposable projects."""

import json
import os
import shutil
import sys
from pathlib import Path


def production_source_matches(active, peer, names):
    from tools.application.repository_boundary_service import OWNER_PATHS
    from tools.core.delivery import DEVELOPMENT_MODULES

    excluded = (*OWNER_PATHS, *DEVELOPMENT_MODULES)
    if any((active / name).exists() or (active / name).is_symlink() for name in excluded):
        return False
    return all(
        (active / name).read_bytes() == (peer / name).read_bytes()
        for name in names
        if name
        and not any(name == root or name.startswith(root + "/") for root in excluded)
        and not (active / name).is_symlink()
    )


class Team:
    def __init__(self, dev, fixture, project, check, report, call, output, commit, snapshot):
        self.dev, self.fixture, self.project = dev, fixture, project
        self.dev_project = json.loads((dev / "compose.yaml").read_text())["name"]
        self.check, self.report, self.call, self.output, self.commit = (
            check,
            report,
            call,
            output,
            commit,
        )
        self.remote = fixture / "customer-origin.git"
        self.peer = fixture / "teammate"
        # Publish only delivered files into new customer history, never owner Git objects.
        source = fixture / "initial-customer"
        snapshot(dev, source)
        migration = source / "src/app/database/migrations/2099_01_01_000010_team_initial.php"
        migration.write_text(
            "<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('CREATE SCHEMA team_probe'); \\Illuminate\\Support\\Facades\\DB::statement('CREATE TABLE team_probe.records (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, label text NOT NULL UNIQUE)'); } };\n"
        )
        seed = source / "src/app/database/seeders/DatabaseSeeder.php"
        seed.write_text(
            "<?php namespace Database\\Seeders; final class DatabaseSeeder extends \\Illuminate\\Database\\Seeder { public function run(): void { \\Illuminate\\Support\\Facades\\DB::table('team_probe.records')->updateOrInsert(['label'=>'original'], []); } }\n"
        )
        self.original = commit(source)
        call(["git", "clone", "--quiet", "--bare", str(source), str(self.remote)])
        (dev / ".git").rename(fixture / "acquisition-git")
        (source / ".git").rename(dev / ".git")
        shutil.copyfile(migration, dev / migration.relative_to(source))
        shutil.copyfile(seed, dev / seed.relative_to(source))
        call(["git", "-C", str(dev), "remote", "add", "origin", str(self.remote)])
        from tools.application.repository_boundary_service import RepositoryPolicy

        RepositoryPolicy.for_workspace(dev).prepare_customer_context(dev, "dev")
        call(["git", "clone", "--quiet", str(self.remote), str(self.peer)])
        RepositoryPolicy.for_workspace(self.peer).prepare_customer_context(self.peer, "dev")
        self.peer_environment = dict(os.environ, COMPOSE_PROJECT_NAME=project, RUNA_HOST_WORKER="1")
        call(
            [sys.executable, "-m", "tools.application.development", "embeddings", "cpu"],
            cwd=self.peer,
            env=self.peer_environment,
        )
        call(
            [sys.executable, "-m", "tools.application.development", "profile", "low"],
            cwd=self.peer,
            env=self.peer_environment,
        )
        call(
            [sys.executable, "-m", "tools.application.development", "prepare"],
            cwd=self.peer,
            env=self.peer_environment,
        )
        self.peer_compose = [
            "docker",
            "compose",
            "--project-name",
            project,
            "--project-directory",
            str(self.peer),
            "--env-file",
            str(self.peer / ".env.dev.local"),
            "-f",
            str(self.peer / "compose.yaml"),
            "-f",
            str(self.peer / ".devcontainer/compose.resources.yaml"),
        ]
        # Establish old teammate data without binding HTTP/Mailpit ports used by dev.
        call([*self.peer_compose, "up", "-d", "--wait", "postgres", "app", "setup"], cwd=self.peer)
        call([*self.peer_compose, "wait", "setup"], cwd=self.peer)
        for command in (
            ["php", "src/runa/database/prepare.php", self.original],
            ["php", "artisan", "migrate", "--force"],
            ["php", "artisan", "db:seed", "--force"],
        ):
            call([*self.peer_compose, "exec", "-T", "app", *command], cwd=self.peer)
        self.peer_secrets = {
            name: (self.peer / name).read_bytes()
            for name in (".env", ".env.testing", ".env.dev.database")
        }
        self.peer_locks = {
            name: (self.peer / "src" / name).read_bytes()
            for name in ("composer.lock", "pnpm-lock.yaml")
        }
        self.peer_history = self.sql(
            project, "SELECT string_agg(migration,',' ORDER BY id) FROM shared.migrations"
        )
        for command in (["migrate", "--force"], ["db:seed", "--force"]):
            call(
                [
                    "docker",
                    "exec",
                    "-w",
                    "/workspace",
                    self.dev_project + "-app-1",
                    "php",
                    "artisan",
                    *command,
                ]
            )
        check(
            self.sql(project, "SELECT label FROM team_probe.records") == "original",
            "teammate has real old application data/history before the development update",
        )

    def sql(self, project, statement):
        return self.output(
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

    def publish(self):
        migration = self.dev / "src/app/database/migrations/2099_01_01_000011_team_upgrade.php"
        migration.write_text(
            "<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { \\Illuminate\\Support\\Facades\\DB::statement('ALTER TABLE team_probe.records ADD COLUMN note text'); } };\n"
        )
        seed = self.dev / "src/app/database/seeders/DatabaseSeeder.php"
        seed.write_text(
            seed.read_text().replace(
                "['label'=>'original'], []", "['label'=>'updated'], ['note'=>'forward-data']"
            )
        )
        self.updated = self.commit(self.dev)
        self.call(["git", "-C", str(self.dev), "push", "origin", "main"])
        for command in (
            ["php", "src/runa/database/prepare.php", self.updated],
            ["php", "artisan", "migrate", "--force"],
            ["php", "artisan", "db:seed", "--force"],
        ):
            self.call(["docker", "exec", "-w", "/workspace", self.dev_project + "-app-1", *command])
        self.check(
            self.sql(
                self.dev_project, "SELECT string_agg(label,',' ORDER BY id) FROM team_probe.records"
            )
            == "original,updated",
            "updated developer explicitly prepares application forward data after owner-only platform update",
        )
        self.check(
            self.output(["git", "--git-dir=" + str(self.remote), "rev-parse", "main"])
            == self.updated,
            "customer explicitly commits and pushes the complete updated project after owner execution",
        )
        self.report["team_update"] = {"original": self.original, "updated": self.updated}

    def pull(self, expected):
        self.call(["git", "-C", str(self.peer), "pull", "--ff-only", "origin", "main"])
        self.check(
            self.output(["git", "-C", str(self.peer), "rev-parse", "HEAD"]) == expected,
            "teammate pulls the exact complete customer revision without upstream Runa access",
        )
        self.check(
            self.sql(self.project, "SELECT to_regclass('shared.update_reference') IS NULL") == "t"
            and self.sql(self.project, "SELECT label FROM team_probe.records") == "original",
            "customer pull alone does not run either owners data steps",
        )
        with (self.peer / ".env").open("a") as stream:
            stream.write("\nTEAM_RELEASE_MARKER=manually-prepared\n")
        self.peer_secrets[".env"] = (self.peer / ".env").read_bytes()
        self.call([*self.peer_compose, "up", "-d", "--wait", "redis"], cwd=self.peer)
        self.call(
            [sys.executable, "-m", "tools.application.development", "initialize"],
            cwd=self.peer,
            env=self.peer_environment,
        )
        self.check(
            self.sql(
                self.project, "SELECT string_agg(label,',' ORDER BY id) FROM team_probe.records"
            )
            == "original,updated"
            and self.sql(self.project, "SELECT count(*) FROM shared.update_reference") == "1",
            "explicit teammate preparation applies both owners forward data and preserves original application rows",
        )
        self.check(
            all(
                name
                in self.sql(
                    self.project,
                    "SELECT string_agg(migration,',' ORDER BY id) FROM shared.migrations",
                ).split(",")
                for name in self.peer_history.split(",")
            )
            and all(
                (self.peer / name).read_bytes() == value
                for name, value in self.peer_secrets.items()
            ),
            "teammate pull/preparation preserves platform history and private credentials",
        )
        self.check(
            self.output(
                ["docker", "exec", self.project + "-redis-1", "printenv", "TEAM_RELEASE_MARKER"]
            )
            == "manually-prepared",
            "teammate manually reconciles private environment for the selected customer service",
        )
        self.check(
            all(
                (self.peer / "src" / name).read_bytes() == value
                for name, value in self.peer_locks.items()
            ),
            "manual reconciliation and teammate preparation preserve selected customer dependency locks",
        )

    def deploy(self, prod, project, expected):
        self.check(
            self.sql(project, "SELECT to_regclass('shared.update_reference') IS NULL") == "t",
            "previous production revision has not received the target platform schema",
        )
        self.check(
            self.sql(project, "SELECT label FROM team_probe.records") == "original",
            "production starts on the previous customer data/schema revision",
        )
        environment = prod / ".env.prod"
        original_environment = environment.read_text()
        if original_environment.count("TEAM_RELEASE_MARKER=\n") != 1:
            raise RuntimeError(
                "Production fixture expects one empty release-instruction environment key."
            )
        environment.write_text(
            original_environment.replace(
                "TEAM_RELEASE_MARKER=\n", "TEAM_RELEASE_MARKER=manually-prepared\n"
            )
        )
        settings = {
            name: (prod / name).read_bytes() for name in (".env.prod", ".env.prod.database")
        }
        storage = "foreach (['application','runa'] as $owner) { file_put_contents('/workspace/src/storage/app/'.$owner.'/private/team-sentinel', $owner); }"
        self.call(["docker", "exec", project + "-app-1", "php", "-r", storage])
        for _ in range(2):
            self.call(
                [
                    sys.executable,
                    "-m",
                    "tools.application.deployment_cli",
                    "deploy",
                    "--simulation",
                ],
                cwd=prod,
            )
        state = json.loads((prod / ".runtime/production/deployment.json").read_text())["current"]
        self.check(
            state["sha"] == expected
            and self.sql(project, "SELECT count(*) FROM shared.update_reference") == "1"
            and self.sql(
                project, "SELECT string_agg(label,',' ORDER BY id) FROM team_probe.records"
            )
            == "original,updated",
            "production deploys the exact pulled customer revision with real platform/application transitions and repeat-safe seeds",
        )
        self.check(
            self.output(["docker", "exec", project + "-redis-1", "printenv", "TEAM_RELEASE_MARKER"])
            == "manually-prepared"
            and self.output(["docker", "exec", project + "-redis-1", "redis-cli", "ping"])
            == "PONG",
            "production starts the manually reconciled customer service and private environment",
        )
        self.check(
            all((prod / name).read_bytes() == value for name, value in settings.items()),
            "production deployment preserves manually supplied settings and database credentials",
        )
        storage_check = "foreach (['application','runa'] as $owner) { if (file_get_contents('/workspace/src/storage/app/'.$owner.'/private/team-sentinel') !== $owner) { exit(1); } }"
        self.call(["docker", "exec", project + "-app-1", "php", "-r", storage_check])
        self.check(True, "production deployment preserves both owners private storage")
        active = Path(state["path"])
        names = self.output(["git", "-C", str(self.peer), "ls-files", "-z"]).split("\0")
        self.check(
            production_source_matches(active, self.peer, names),
            "deployed production matches versioned customer production source and excludes owner/development modules",
        )
        self.check(
            self.sql(
                project,
                "SELECT count(*) FROM shared.platform_seed_runs WHERE release_sha='"
                + expected
                + "'",
            )
            == "1",
            "repeat deployment keeps one exact-customer-revision required platform seed completion",
        )
        self.report["team_update"]["deployed"] = expected
