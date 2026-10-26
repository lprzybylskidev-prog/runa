import os
import sys

from tools.application.runtime import app_command, inside


def main():
    if sys.argv[1:] == ["--help"]:
        print("Usage: ./bin/boost-mcp\nStart development-only Laravel Boost stdio MCP server.")
        sys.exit(0)
    if sys.argv[1:]:
        sys.exit("Usage: ./bin/boost-mcp (no arguments)")
    if inside():
        if os.environ.get("APP_ENV") == "production":
            sys.exit("Boost is development-only.")
        os.execvpe("php", ["php", "artisan", "boost:mcp"], dict(os.environ))
    command = app_command(["php", "artisan", "boost:mcp"])
    os.execvp(command[0], command)


if __name__ == "__main__":
    main()
