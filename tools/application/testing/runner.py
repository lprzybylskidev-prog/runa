"""Shared native test lifecycle; ownership is supplied by the composition root."""

from __future__ import annotations

import json
from collections.abc import Callable, Mapping
from dataclasses import dataclass, replace
from pathlib import Path
from typing import Literal

from tools.core.context import WorkspaceContext
from tools.core.lifecycle import interrupt_signals
from tools.core.process import InputMode, ProcessRunner, ProcessSpec
from tools.core.reporting import SafeFailure
from tools.core.state import FileLock, JsonStore, StateDirectory

Scope = Literal["backend", "frontend", "tooling", "e2e"]
SCOPES: tuple[Scope, ...] = ("backend", "frontend", "tooling", "e2e")
DEFAULT_SCOPES: tuple[Scope, ...] = ("backend", "frontend", "tooling")


@dataclass(frozen=True)
class TestLayout:
    """Explicit suite roots. Customer composition never reads private configuration."""

    owner: Literal["application", "runa"]
    backend: str
    phpunit: str
    vitest: str
    playwright: str
    python_roots: tuple[str, ...]


APPLICATION = TestLayout(
    "application",
    "app/tests",
    "phpunit.xml",
    "vitest.config.mjs",
    "playwright.config.mjs",
    ("src/app/tests/python",),
)


def application_layout(root: Path) -> TestLayout:
    """Optional source-owned discovery data, never executable registration."""
    config = root / "src/app/testing.json"
    if not config.exists() and not config.is_symlink():
        return APPLICATION
    if config.is_symlink() or not config.resolve().is_relative_to(root / "src/app"):
        raise ValueError("Application test configuration must be an owned regular file.")
    data = json.loads(config.read_text())
    if (
        not isinstance(data, dict)
        or set(data) != {"schema_version", "python_roots"}
        or type(data["schema_version"]) is not int
        or data["schema_version"] != 1
    ):
        raise ValueError("Unsupported application testing configuration.")
    paths = data["python_roots"]
    if (
        not isinstance(paths, list)
        or not paths
        or any(
            not isinstance(p, str) or not p or Path(p).is_absolute() or ".." in Path(p).parts
            for p in paths
        )
    ):
        raise ValueError("Python roots must be explicit paths relative to src/app.")
    roots = tuple("src/app/" + path for path in paths)
    if len(set(roots)) != len(roots):
        raise ValueError("Python roots must be unique.")
    return replace(APPLICATION, python_roots=roots)


@dataclass(frozen=True)
class Selection:
    scopes: tuple[Scope, ...] = DEFAULT_SCOPES
    arguments: tuple[str, ...] = ()
    filter: str = ""
    python_paths: tuple[str, ...] = ()
    marker: str = "not docker and not network and not knowledge and not database and not packaging"
    collect_only: bool = False

    def __post_init__(self) -> None:
        if (
            not self.scopes
            or len(set(self.scopes)) != len(self.scopes)
            or any(scope not in SCOPES for scope in self.scopes)
        ):
            raise ValueError("Select unique backend/frontend/tooling/e2e scopes.")
        if self.arguments and len(self.scopes) != 1:
            raise ValueError("Native runner arguments require one explicit scope.")
        if self.python_paths and self.scopes != ("tooling",):
            raise ValueError("Python paths require the tooling scope.")
        if self.collect_only and self.scopes != ("tooling",):
            raise ValueError("Collection requires the tooling scope.")


@dataclass(frozen=True)
class ScopeResult:
    scope: Scope
    status: Literal["passed", "failed", "empty", "blocked", "interrupted"]
    exit_code: int
    cleanup_exit_code: int = 0


@dataclass(frozen=True)
class TestService:
    workspace: WorkspaceContext
    layout: TestLayout
    runner: ProcessRunner
    environment: Mapping[str, str]
    report: Callable[[str], None] = print

    def process(self, command: tuple[str, ...], *, source: bool = True) -> int:
        return self.runner.run(
            ProcessSpec(
                command,
                self.workspace.root / "src" if source else self.workspace.root,
                dict(self.environment, APP_ENV="testing", TEST_TOKEN=""),
                stdin=InputMode.INHERIT,
            )
        ).returncode

    def command(self, scope: Scope, selection: Selection) -> tuple[str, ...]:
        args = list(selection.arguments)
        if scope == "backend":
            if selection.filter:
                args.append("--filter=" + selection.filter)
            if "--parallel" in args and not any(a.startswith("--processes") for a in args):
                args.append("--processes=" + self.environment.get("TEST_WORKERS", "4"))
            return (
                "vendor/bin/pest",
                "--configuration=" + self.layout.phpunit,
                "--test-directory=" + self.layout.backend,
                *args,
            )
        if scope == "frontend":
            if selection.filter:
                args += ["--testNamePattern", selection.filter]
            return ("pnpm", "exec", "vitest", "run", "--config=" + self.layout.vitest, *args)
        if scope == "e2e":
            if selection.filter:
                args += ["--grep", selection.filter]
            return (
                "pnpm",
                "exec",
                "playwright",
                "test",
                "--config=" + self.layout.playwright,
                *(a for a in args if a != "--critical"),
            )
        base = self.workspace.root
        roots = tuple(base / path for path in self.layout.python_roots)
        paths = selection.python_paths or tuple(str(path) for path in roots if path.is_dir())
        for value in paths:
            filename = value.partition("::")[0]
            target = Path(filename)
            if not target.is_absolute():
                target = base / target
            if not target.exists() or not any(
                target.resolve().is_relative_to(root) for root in roots
            ):
                raise ValueError("Python test path must belong to the selected suite roots.")
        state = self.workspace.runtime("python")
        return (
            str(state / "venv/bin/python"),
            "-m",
            "pytest",
            "-c",
            "tools/application/python/pytest.ini",
            "--rootdir",
            str(base),
            *paths,
            *(["-k", selection.filter] if selection.filter else []),
            "-m",
            selection.marker,
            *(["--collect-only"] if selection.collect_only else []),
            "--junitxml=" + str(state / f"reports/{self.layout.owner}-pytest.xml"),
            "--basetemp=" + str(state / "cache/pytest-temp"),
            "-o",
            "cache_dir=" + str(state / "cache/pytest"),
            *args,
        )

    def empty(self, scope: Scope, selection: Selection) -> bool:
        root = self.workspace.root
        if scope == "tooling":
            return not selection.python_paths and not any(
                path.is_file()
                for name in self.layout.python_roots
                for path in (root / name).rglob("test_*.py")
            )
        directory = (
            root
            / "src"
            / (
                self.layout.backend
                if scope == "backend"
                else self.layout.backend + ("/frontend" if scope == "frontend" else "/e2e")
            )
        )
        patterns = (
            ("*Test.php",)
            if scope == "backend"
            else ("*.test.ts", "*.spec.ts", "*.test.js", "*.spec.js")
        )
        return not any(path.is_file() for pattern in patterns for path in directory.rglob(pattern))

    def run_scope(self, scope: Scope, selection: Selection) -> ScopeResult:
        if self.empty(scope, selection):
            if self.layout.owner == "runa":
                return ScopeResult(scope, "blocked", 1)
            return ScopeResult(scope, "empty", 0)
        command = self.command(scope, selection)
        if scope == "tooling" and not Path(command[0]).is_file():
            self.report("Run ./bin/artisan runa:python:setup first.")
            return ScopeResult(scope, "blocked", 127)
        if (
            scope == "backend"
            and (self.workspace.root / "src/bootstrap/cache/config.php").is_file()
        ):
            self.report("Clear cached development config before testing: php artisan config:clear")
            return ScopeResult(scope, "blocked", 1)
        cleanup_command = ("php", "runa/Testing/worker-databases.php")
        if scope == "backend":
            before = self.process(cleanup_command)
            if before:
                return ScopeResult(scope, "blocked", before)
        cleanup = 0
        try:
            if scope == "e2e":
                built = self.process(("php", "runa/resources/errors/build.php"))
                if built:
                    return ScopeResult(scope, "blocked", built)
            status = self.process(command, source=scope != "tooling")
        finally:
            if scope == "backend":
                try:
                    cleanup = self.process(cleanup_command)
                except (OSError, SafeFailure):
                    cleanup = 127
        # pytest 5 is a collection/filter failure, never a successful empty scope.
        return ScopeResult(scope, "passed" if status == cleanup == 0 else "failed", status, cleanup)

    def execute(self, selection: Selection) -> int:
        ownership_root = self.workspace.root / (
            "src/app" if self.layout.owner == "application" else "tools/maintainer/tests/python"
        )
        for name in self.layout.python_roots:
            root = self.workspace.root / name
            if not root.resolve().is_relative_to(ownership_root):
                raise ValueError("Python discovery must remain within the selected owner's source.")
        for scope in selection.scopes:
            if not self.empty(scope, selection):
                self.command(scope, selection)
        if selection.scopes == ("backend",) and set(selection.arguments).intersection(
            {"--list-tests", "--list-tests-xml", "--list-suites", "--list-groups"}
        ):
            return self.process(self.command("backend", selection))
        directory = StateDirectory(
            self.workspace.runtime("tests"), private=False, anchor=self.workspace.root
        )
        lock = FileLock(
            directory,
            "suite.lock",
            "Another test suite is running in this checkout.",
            private=False,
        )
        results: list[ScopeResult] = []
        code = 0
        with lock.acquire(), interrupt_signals():
            try:
                for scope in selection.scopes:
                    try:
                        result = self.run_scope(scope, selection)
                    except (OSError, SafeFailure):
                        result = ScopeResult(scope, "blocked", 127)
                    results.append(result)
                    self.report(
                        f"{scope}: {result.status} (exit {result.exit_code}, cleanup {result.cleanup_exit_code})"
                    )
            except KeyboardInterrupt:
                results.append(ScopeResult(selection.scopes[len(results)], "interrupted", 130))
                code = 130
            finally:
                completed = {result.scope for result in results}
                results.extend(
                    ScopeResult(scope, "blocked", 130)
                    for scope in selection.scopes
                    if scope not in completed
                )
                JsonStore(directory).write(
                    f"{self.layout.owner}-results.json",
                    {
                        "schema_version": 1,
                        "owner": self.layout.owner,
                        "scopes": [
                            {
                                "scope": r.scope,
                                "status": r.status,
                                "exit_code": r.exit_code,
                                "cleanup_exit_code": r.cleanup_exit_code,
                            }
                            for r in results
                        ],
                    },
                )
        failed = [r for r in results if r.exit_code or r.cleanup_exit_code]
        if code:
            return code
        if len(results) == 1:
            return results[0].exit_code or results[0].cleanup_exit_code
        return 1 if failed else 0
