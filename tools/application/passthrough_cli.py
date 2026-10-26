import subprocess
import sys

from tools.application.runtime import ROOT, app_command, run


def main():
    name = sys.argv[1]
    args = sys.argv[2:]
    try:
        if args == ["--help"] or (not args and name != "logs"):
            print(
                {
                    "artisan": "Usage: ./bin/artisan <Artisan arguments>\nRun Laravel CLI inside the app container.",
                    "composer": "Usage: ./bin/composer <Composer arguments>\nRun the pinned Composer inside the app container.",
                    "pnpm": "Usage: ./bin/pnpm <pnpm arguments>\nRun the pinned pnpm inside the app container.",
                    "logs": "Usage: ./bin/logs [service]\nHost only. Show the last 100 development service log lines; logs must never contain secrets.",
                    "passthrough": "Usage: invoke artisan, composer, pnpm or logs wrapper.",
                }[name]
            )
        elif name == "logs":
            run([str(ROOT / "bin/artisan"), "--management", "runa:dev:logs", *args])
        else:
            commands = {
                "artisan": ["php", "artisan"],
                "composer": ["composer", "--working-dir=src"],
                "pnpm": ["pnpm", "--dir", "src"],
            }
            run(app_command(commands[name] + args))
    except (KeyError, ValueError, OSError, subprocess.CalledProcessError) as error:
        print(
            f"Error: child command failed (exit {error.returncode})."
            if isinstance(error, subprocess.CalledProcessError)
            else f"Error: {error}",
            file=sys.stderr,
        )
        sys.exit(getattr(error, "returncode", 1))


if __name__ == "__main__":
    main()
