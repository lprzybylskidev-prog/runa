"""Native report provenance for the existing test service; no recovery execution."""

from __future__ import annotations

import hashlib
import json
import os
import re
import uuid
from collections.abc import Callable, Mapping
from datetime import UTC, datetime
from pathlib import Path

from tools.core.process import InputMode, OutputMode, ProcessResult, ProcessRunner, ProcessSpec
from tools.core.state import JsonValue, StateDirectory

LIMIT = 64 * 1024 * 1024


def backend_anchor(scope: Mapping[str, JsonValue]) -> dict[str, JsonValue]:
    evidence = scope.get("evidence")
    if not isinstance(evidence, dict) or not isinstance(evidence.get("run_id"), str):
        return {"unrecorded": True}
    return {
        "run_id": evidence["run_id"],
        "digest": hashlib.sha256(
            json.dumps(scope, sort_keys=True, separators=(",", ":")).encode()
        ).hexdigest(),
    }


def read_artifact(root: Path, name: str) -> bytes:
    path = Path(name)
    if path.is_absolute() or ".." in path.parts or path.parts[:1] != (".runtime",):
        raise ValueError("Evidence artifacts must belong to workspace .runtime.")
    target = root / path
    if not target.parent.is_dir():
        raise ValueError("Missing native evidence artifact.")
    directory = StateDirectory(target.parent, private=False, anchor=root)
    with directory.open() as parent, directory.file(parent, target.name, private=False) as fd:
        if os.fstat(fd).st_size > LIMIT:
            raise ValueError("Native evidence artifact exceeds the supported bound.")
        with os.fdopen(os.dup(fd), "rb") as stream:
            result = stream.read(LIMIT + 1)
    if len(result) > LIMIT:
        raise ValueError("Native evidence artifact exceeds the supported bound.")
    return result


def configuration_fingerprint(root: Path) -> str:
    digest = hashlib.sha256()
    for name in (".env", ".env.dev.local", ".env.testing", "src/.env", "src/.env.testing"):
        path = root / name
        if path.is_symlink():
            raise ValueError("Evidence configuration cannot follow symlinks.")
        digest.update(name.encode() + b"\0")
        digest.update(path.read_bytes() if path.is_file() else b"<absent>")
        digest.update(b"\0")
    return digest.hexdigest()


def full_selection(arguments: tuple[str, ...]) -> bool:
    """Only explicit non-selecting native flags establish an unfiltered inventory."""
    return all(
        value in ("--parallel", "-p", "--compact", "--no-ansi", "--colors=never", "--no-progress")
        or re.fullmatch(r"--processes=[1-9][0-9]*", value)
        for value in arguments
    )


def validate_selection(arguments: tuple[str, ...]) -> None:
    for value in arguments:
        if (
            value.startswith("-")
            and not full_selection((value,))
            and not value.startswith("--filter=")
        ):
            raise ValueError(
                "Native evidence permits file/filter selections and fixed mode/output flags only."
            )


class BackendEvidence:
    def __init__(
        self,
        root: Path,
        runner: ProcessRunner,
        environment: Mapping[str, str],
        report: Callable[[str], None],
        owner: str,
        selection: tuple[str, ...],
        previous: JsonValue = None,
    ) -> None:
        self.root = root
        self.runner = runner
        self.environment = dict(environment, APP_ENV="testing", TEST_TOKEN="")
        self.report = report
        self.native_exit_code: int | None = None
        self.directory = root / ".runtime/tests/native" / uuid.uuid4().hex
        with StateDirectory(self.directory, anchor=root).open():
            pass
        self.started_at = datetime.now(UTC).isoformat()
        self.candidate, self.clean = self.source()
        self.configuration = configuration_fingerprint(root)
        runtime = self.run(
            (
                "php",
                "-r",
                "echo json_encode([PHP_VERSION, PHP_BINARY, php_ini_loaded_file(), php_ini_scanned_files(), ini_get_all(null, false), array_map(fn ($name) => [$name, phpversion($name)], get_loaded_extensions())]);",
            ),
            source=True,
        ).checked()
        relevant = {
            key: value
            for key, value in self.environment.items()
            if (
                key.startswith(
                    (
                        "PHP_",
                        "DB_",
                        "APP_",
                        "RUNA_",
                        "CACHE_",
                        "QUEUE_",
                        "SESSION_",
                        "MAIL_",
                        "LOG_",
                        "REDIS_",
                        "BROADCAST_",
                        "FILESYSTEM_",
                    )
                )
                and not key.startswith(("RUNA_QUALITY_", "RUNA_HOST_", "RUNA_MANAGEMENT_"))
            )
            or key == "XDEBUG_MODE"
        }
        self.record: dict[str, JsonValue] = {
            "run_id": self.directory.name,
            "workspace": str(root),
            "owner": owner,
            "candidate": self.candidate,
            "clean": self.clean,
            "started_at": self.started_at,
            "configuration_fingerprint": self.configuration,
            "runtime_fingerprint": hashlib.sha256(
                runtime.stdout + json.dumps(relevant, sort_keys=True).encode()
            ).hexdigest(),
            "quality_run_id": environment.get("RUNA_QUALITY_RUN_ID", ""),
            "quality_step": environment.get("RUNA_QUALITY_STEP", ""),
            "selection": list(selection),
            "full_suite": full_selection(selection),
            "previous": previous,
        }

    def run(self, command: tuple[str, ...], *, source: bool) -> ProcessResult:
        return self.runner.run(
            ProcessSpec(
                command,
                self.root / "src" if source else self.root,
                self.environment,
                stdin=InputMode.INHERIT,
                output=OutputMode.CAPTURE,
                output_limit=LIMIT,
            )
        )

    def source(self) -> tuple[str, bool]:
        candidate = (
            self.run(("git", "rev-parse", "HEAD"), source=False).checked().stdout.decode().strip()
        )
        changes = (
            self.run(("git", "status", "--porcelain", "--untracked-files=normal"), source=False)
            .checked()
            .stdout
        )
        return candidate, not changes

    def write(self, name: str, content: bytes) -> None:
        directory = StateDirectory(self.directory, anchor=self.root)
        with directory.open() as parent, directory.file(parent, name, create=True) as fd:
            with os.fdopen(os.dup(fd), "wb") as stream:
                stream.write(content)

    def execute(self, command: tuple[str, ...]) -> int:
        reserved = (
            "--log-",
            "--list-",
            "--tmp-dir",
            "--no-test-tokens",
            "--runner",
            "--functional",
            "--repeat",
        )
        if any(value.startswith(reserved) or value == "--processes" for value in command):
            raise ValueError(
                "Evidence owns native logging/discovery and requires the standard native runner."
            )
        parallel = "--parallel" in command or "-p" in command
        self.record["mode"] = "parallel" if parallel else "serial"
        self.record["workers"] = next(
            (value.split("=", 1)[1] for value in command if value.startswith("--processes=")), "1"
        )
        discovery = tuple(
            value
            for value in command
            if value not in ("--parallel", "-p") and not value.startswith("--processes=")
        ) + ("--list-tests-xml=" + str(self.directory / "inventory.xml"),)
        self.record["discovery_command"] = list(discovery)
        listed = self.run(discovery, source=True)
        self.write("discovery-stdout.log", listed.stdout)
        self.write("discovery-stderr.log", listed.stderr)
        self.record["discovery_exit_code"] = listed.returncode
        if listed.returncode:
            self.report(listed.stdout.decode(errors="replace"))
            self.report(listed.stderr.decode(errors="replace"))
            return listed.returncode
        actual = command + (
            "--log-junit=" + str(self.directory / "junit.xml"),
            "--log-teamcity=" + str(self.directory / "teamcity.log"),
        )
        if parallel:
            actual += ("--tmp-dir=" + str(self.directory),)
        self.record["command"] = list(actual)
        native = self.run(actual, source=True)
        self.native_exit_code = native.returncode
        self.write("stdout.log", native.stdout)
        self.write("stderr.log", native.stderr)
        if native.stdout:
            self.report(native.stdout.decode(errors="replace"))
        if native.stderr:
            self.report(native.stderr.decode(errors="replace"))
        return native.returncode

    def finish(self) -> dict[str, JsonValue]:
        candidate, clean = self.source()
        self.record["clean"] = (
            self.clean
            and clean
            and candidate == self.candidate
            and configuration_fingerprint(self.root) == self.configuration
        )
        self.record["finished_at"] = datetime.now(UTC).isoformat()
        self.record["artifacts"] = {
            str(path.relative_to(self.root)): hashlib.sha256(
                read_artifact(self.root, str(path.relative_to(self.root)))
            ).hexdigest()
            for path in sorted(self.directory.rglob("*"))
            if path.is_file()
            and (
                path.suffix in (".xml", ".log")
                or path.name.endswith(("_teamcity", "_junit", "_unexpected_output", "_test_result"))
            )
        }
        return self.record
