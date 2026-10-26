import os
import subprocess
import sys

from tools.application import management
from tools.application.runtime import ROOT, app_command, inside, run
from tools.core.lifecycle import interrupt_signals
from tools.core.reporting import SafeFailure


def main():
    try:
        args = sys.argv[1:]
        if args == ["--recover-host"]:
            from tools.application import host_executor

            if (host_executor.directory() / "client.json").exists():
                host_executor.stop()
            host_executor.ensure()
            print("Host executor ready. Use php artisan for normal operation.")
        elif args == ["--stop-host"]:
            from tools.application import host_executor

            host_executor.stop()
        elif args == ["--operator"]:
            from tools.application import host_executor

            host_executor.ensure()
            image = management.ensure_image()
            command = [
                "docker",
                "run",
                "--rm",
                "--network",
                "none",
                "--read-only",
                "--tmpfs",
                "/tmp",
                "--cap-drop=ALL",
                "--security-opt=no-new-privileges:true",
                "--user",
                str(os.getuid()) + ":" + str(os.getgid()),
                "--mount",
                "type=bind,src=" + str(host_executor.directory()) + ",dst=/run/runa-host",
                "--entrypoint",
                "/bin/sh",
                "-i",
            ]
            if sys.stdin.isatty():
                command.append("-t")
            command += [image]
            raise SystemExit(subprocess.run(command, cwd=ROOT).returncode)
        elif not args or args == ["--help"]:
            print(
                "Usage: ./bin/artisan [--management] <Artisan arguments>\n"
                "Ordinary commands use the app container. --management discovers cold management commands.\n"
                "Setup/recovery only. --recover-host prepares the private host executor.\n"
                "Use php artisan for normal configured-environment operation; --plan is nonexecuting."
            )
        elif args[0] == "--management" or (
            args[0].startswith("runa:") and (inside() or management.is_management_command(args[0]))
        ):
            explicit_management = args[0] == "--management"
            if explicit_management:
                args = args[1:]
            if inside():
                run(["php", "artisan", *(["--management"] if explicit_management else []), *args])
            else:
                raise SystemExit(management.launch(args))
        else:
            run(app_command(["php", "artisan", *args]))
    except (ValueError, OSError, subprocess.SubprocessError, SafeFailure) as error:
        message = (
            f"Child command failed (exit {error.returncode})."
            if isinstance(error, subprocess.CalledProcessError)
            else str(error)
        )
        print("Artisan transport failed: " + message, file=sys.stderr)
        sys.exit(getattr(error, "returncode", getattr(error, "exit_code", 1)))


if __name__ == "__main__":
    try:
        with interrupt_signals():
            main()
    except KeyboardInterrupt:
        raise SystemExit(130)
