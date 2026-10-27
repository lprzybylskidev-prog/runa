"""Delivered Python developer commands; host operation remains stdlib-only."""

import argparse
import sys
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.python_execution import PythonToolExecutor
from tools.application.python_toolchain import environment
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.process import InputMode, ProcessSpec, SubprocessRunner
from tools.core.reporting import SafeFailure


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=["setup", "format", "lint", "typecheck"])
    parser.add_argument("--check", action="store_true")
    parser.add_argument(
        "--config", type=Path, help="Application-owned Ruff or Pyright configuration"
    )
    args = parser.parse_args(argv)
    root = Path(__file__).resolve().parents[2]
    RepositoryPolicy.for_workspace(root).assert_development_context(root)
    if args.check and args.action != "format" or args.config and args.action == "setup":
        raise ValueError("Unsupported Python tool options.")
    if args.config and (
        not args.config.is_file() or not args.config.resolve().is_relative_to(root / "src/app")
    ):
        raise ValueError(
            "Custom Python configuration must be an application-owned file under src/app."
        )
    workspace = WorkspaceContext(root)
    location = execution_location()
    runner = SubprocessRunner()
    env = environment(root)
    runtime = DevelopmentRuntime(ExecutionContext(workspace, workspace, location))
    if location is ExecutionLocation.HOST:
        return runner.run(
            ProcessSpec(
                runtime.app_command(
                    (
                        "python3",
                        "-m",
                        "tools.application.python_cli",
                        *(sys.argv[1:] if argv is None else argv),
                    )
                ),
                root,
                env,
                stdin=InputMode.INHERIT,
            )
        ).returncode
    python = workspace.runtime("python") / "venv/bin/python"
    if args.action == "setup":
        command = (sys.executable, "-m", "tools.application.python_toolchain")
    else:
        if not python.is_file():
            raise ValueError("Run ./bin/artisan runa:python:setup first.")
        config = args.config or root / "tools/application/python" / (
            "pyrightconfig.json" if args.action == "typecheck" else "ruff.toml"
        )
        if args.action == "typecheck":
            command = (
                "node",
                str(workspace.runtime("python") / "node/pyright/dist/pyright.js"),
                "--project",
                str(config),
            )
        else:
            command = (
                str(python),
                "-m",
                "ruff",
                "format" if args.action == "format" else "check",
                "--config",
                str(config),
                "--cache-dir",
                str(workspace.runtime("python") / "cache/ruff"),
                *(["--check"] if args.check else []),
                "tools/core",
                "tools/application",
                "src/app",
            )
    return PythonToolExecutor(workspace, runner, env).execute(command)


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        raise SystemExit(130) from None
    except (ValueError, OSError, SafeFailure) as error:
        print(str(error), file=sys.stderr)
        raise SystemExit(1) from None
