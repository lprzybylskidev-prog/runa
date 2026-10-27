"""Application test composition. The owner composes the same service separately."""

from __future__ import annotations

import argparse
import sys
from collections.abc import Sequence
from pathlib import Path
from typing import cast

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.python_toolchain import environment
from tools.application.repository_boundary import assert_development_context
from tools.application.testing.runner import (
    APPLICATION,
    DEFAULT_SCOPES,
    SCOPES,
    Scope,
    Selection,
    TestLayout,
    TestService,
    application_layout,
)
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.process import InputMode, ProcessSpec, SubprocessRunner
from tools.core.reporting import SafeFailure


def parse(argv: Sequence[str]) -> Selection:
    parser = argparse.ArgumentParser(
        description="Run backend, frontend and tooling tests by default."
    )
    parser.add_argument("--scope", action="append", choices=SCOPES)
    parser.add_argument(
        "--filter", default="", help="PHP filter / Vitest name / pytest -k / Playwright grep"
    )
    parser.add_argument(
        "--path",
        action="append",
        default=[],
        help="Python file or node within selected suite roots",
    )
    parser.add_argument("--marker", default=Selection().marker)
    parser.add_argument("--collect-only", action="store_true")
    args = list(argv)
    # Retain the established focused positional transport spellings.
    scope = args.pop(0) if args and args[0] in SCOPES else None
    options, native = parser.parse_known_args(args)
    if scope and options.scope:
        parser.error("Use a positional scope or --scope, not both.")
    scopes = tuple(
        cast(Scope, value) for value in (options.scope or ([scope] if scope else DEFAULT_SCOPES))
    )
    if native[:1] == ["--"]:
        native.pop(0)
    return Selection(
        scopes,
        tuple(native),
        options.filter,
        tuple(options.path),
        options.marker,
        options.collect_only,
    )


def main(
    argv: Sequence[str] | None = None,
    *,
    layout: TestLayout = APPLICATION,
    module: str = "tools.application.testing.cli",
) -> int:
    arguments = list(sys.argv[1:] if argv is None else argv)
    selection = parse(arguments)
    root = Path(__file__).resolve().parents[3]
    assert_development_context(root)
    workspace = WorkspaceContext(root)
    location = execution_location()
    runner = SubprocessRunner()
    env = environment(root)
    if location is ExecutionLocation.HOST:
        runtime = DevelopmentRuntime(ExecutionContext(workspace, workspace, location))

        def delegate() -> int:
            return runner.run(
                ProcessSpec(
                    runtime.app_command(("python3", "-m", module, *arguments)),
                    root,
                    env,
                    stdin=InputMode.INHERIT,
                )
            ).returncode

        if "e2e" in selection.scopes:
            from tools.application.test_resources import endpoint

            with endpoint():
                return delegate()
        return delegate()
    selected_layout = application_layout(root) if layout is APPLICATION else layout
    return TestService(workspace, selected_layout, runner, env).execute(selection)


def boundary(action) -> int:
    try:
        return action()
    except KeyboardInterrupt:
        return 130
    except (ValueError, SafeFailure) as error:
        print(str(error), file=sys.stderr)
        return 1
    except OSError:
        print(
            "Test runner unavailable; check development services and tool setup.", file=sys.stderr
        )
        return 127


if __name__ == "__main__":
    raise SystemExit(boundary(main))
