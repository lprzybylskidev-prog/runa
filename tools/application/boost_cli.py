import os
import sys
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.core.context import ExecutionLocation

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    runtime = DevelopmentRuntime.for_workspace(ROOT)
    if sys.argv[1:] == ["--help"]:
        print("Usage: ./bin/boost-mcp\nStart development-only Laravel Boost stdio MCP server.")
        sys.exit(0)
    if sys.argv[1:]:
        sys.exit("Usage: ./bin/boost-mcp (no arguments)")
    if execution_location() is ExecutionLocation.CONTAINER:
        if os.environ.get("APP_ENV") == "production":
            sys.exit("Boost is development-only.")
        os.execvpe("php", ["php", "artisan", "boost:mcp"], dict(os.environ))
    command = runtime.app_command(["php", "artisan", "boost:mcp"])
    os.execvp(command[0], command)


if __name__ == "__main__":
    main()
