"""Import-safe owner Python adapter; public application registry remains Artisan."""

from __future__ import annotations

import argparse
import fcntl
import hashlib
import os
import signal
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.repository_boundary import assert_development_context
from tools.application.runtime import compose, inside
from tools.maintainer.python_toolchain import PYTHON, ROOT, STATE, environment


def parser() -> argparse.ArgumentParser:
    result = argparse.ArgumentParser(description=__doc__)
    commands = result.add_subparsers(dest="action", required=True)
    test = commands.add_parser("test", help="Run explicit owner Python test scopes")
    test.add_argument(
        "--scope", action="append", choices=("unit", "contracts", "integration", "e2e")
    )
    test.add_argument("--filter", default="", help="pytest -k expression")
    test.add_argument("--marker", default="", help="pytest -m expression")
    test.add_argument("--path", help="One file/node relative to tests/python")
    test.add_argument("--collect-only", action="store_true")
    commands.add_parser("setup", help="Install reviewed frozen development tools")
    formatter = commands.add_parser("format")
    formatter.add_argument("--check", action="store_true")
    commands.add_parser("lint")
    commands.add_parser("typecheck")
    packages = commands.add_parser("packages")
    packages.add_argument(
        "--kind", choices=("application", "maintainer", "external"), required=True
    )
    return result


def test_command(args: argparse.Namespace) -> list[str]:
    base = ROOT / "tools/maintainer/tests/python"
    scopes: list[str] = args.scope or ["unit", "contracts"]
    paths = [str(base / scope) for scope in dict.fromkeys(scopes)]
    if args.path:
        filename, _, _node = args.path.partition("::")
        selected = base / filename
        if not selected.is_file() or not any(
            selected.resolve().is_relative_to(base / scope) for scope in scopes
        ):
            raise ValueError("Test path must belong to an explicitly selected scope.")
        paths = [str(base / args.path)]
    command = [str(PYTHON), "-m", "pytest", "-c", "tools/maintainer/pytest.ini", *paths]
    if args.filter:
        command += ["-k", args.filter]
    command += [
        "-m",
        args.marker
        or "not docker and not network and not knowledge and not database and not packaging",
    ]
    if args.collect_only:
        command.append("--collect-only")
    selection = hashlib.sha256("\0".join(command).encode()).hexdigest()[:12]
    command += [
        "--junitxml=" + str(STATE / f"reports/pytest-{selection}.xml"),
        "--basetemp=" + str(STATE / "cache/pytest-temp"),
    ]
    return command


def interrupted(_signum: int, _frame: object) -> None:
    raise KeyboardInterrupt


def execute(command: list[str]) -> int:
    """Share the existing native-suite lock and reap before releasing it."""
    directory = ROOT / ".runtime/tests"
    directory.mkdir(parents=True, exist_ok=True)
    (STATE / "reports").mkdir(parents=True, exist_ok=True)
    (STATE / "cache/pyright").mkdir(parents=True, exist_ok=True)
    with (directory / "suite.lock").open("a") as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError as error:
            raise ValueError("Another test suite is running in this checkout.") from error
        previous = signal.signal(signal.SIGTERM, interrupted)
        try:
            with subprocess.Popen(
                command, cwd=ROOT, env=environment(), start_new_session=True
            ) as child:
                try:
                    return child.wait()
                except BaseException:
                    try:
                        os.killpg(child.pid, signal.SIGTERM)
                        child.wait(timeout=10)
                    except subprocess.TimeoutExpired:
                        os.killpg(child.pid, signal.SIGKILL)
                        child.wait()
                    except ProcessLookupError:
                        child.wait()
                    raise
        finally:
            signal.signal(signal.SIGTERM, previous)


def main(argv: Sequence[str] | None = None) -> int:
    args = parser().parse_args(argv)
    assert_development_context(ROOT)
    if (ROOT / ".git/runa-bootstrap.json").exists():
        raise ValueError("Owner Python tools require a receipt-free maintenance checkout.")
    if args.action == "test":
        command = test_command(args)  # Validate scope before any Docker/tool execution.
    else:
        command = []
    if not inside():
        return subprocess.call(
            [
                *compose(),
                "exec",
                "-T",
                "app",
                "python3",
                "-m",
                "tools.maintainer.python_cli",
                *(list(argv) if argv is not None else sys.argv[1:]),
            ],
            cwd=ROOT,
        )
    if args.action == "setup":
        return execute([sys.executable, "-m", "tools.maintainer.python_toolchain"])
    if not PYTHON.is_file():
        raise ValueError("Install tools first: python3 tools/maintainer/python-tool setup")
    if args.action == "packages":
        return execute([sys.executable, "-m", "tools.maintainer.python_packages", args.kind])
    if args.action == "format":
        command = [str(PYTHON), "-m", "ruff", "format", *(["--check"] if args.check else []), "."]
    elif args.action == "lint":
        command = [str(PYTHON), "-m", "ruff", "check", "."]
    elif args.action == "typecheck":
        command = [
            "node",
            str(STATE / "node/pyright/dist/pyright.js"),
            "--project",
            "pyproject.toml",
        ]
    return execute(command)


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        raise SystemExit(130) from None
    except ValueError as error:
        print(str(error), file=sys.stderr)
        raise SystemExit(1) from None
    except OSError:
        print(
            "Python tooling unavailable; check the app container and locked tool setup.",
            file=sys.stderr,
        )
        raise SystemExit(1) from None
    except subprocess.CalledProcessError as error:
        raise SystemExit(error.returncode) from None
