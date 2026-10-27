import os
import subprocess
import sys
from functools import partial
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.host_transport import default_service as host_service
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation
from tools.core.lifecycle import interrupt_signals
from tools.core.process import SubprocessRunner
from tools.core.reporting import SafeFailure

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    management = ManagementService.for_workspace(ROOT)
    host_executor = host_service()
    runtime = DevelopmentRuntime.for_workspace(ROOT)
    processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    run = partial(processes.run, cwd=ROOT, check=True)
    try:
        args = sys.argv[1:]
        if args == ["--recover-host"]:
            if (host_executor.directory() / "client.json").exists():
                host_executor.stop()
            host_executor.ensure()
            print("Host executor ready. Use php artisan for normal operation.")
        elif args == ["--stop-host"]:
            host_executor.stop()
        elif args == ["--operator"]:
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
            raise SystemExit(processes.run(command, cwd=ROOT).returncode)
        elif not args or args == ["--help"]:
            print(
                "Usage: ./bin/artisan [--management] <Artisan arguments>\n"
                "Ordinary commands use the app container. --management discovers cold management commands.\n"
                "Setup/recovery only. --recover-host prepares the private host executor.\n"
                "Use php artisan for normal configured-environment operation; --plan is nonexecuting."
            )
        elif args[0] == "--management" or (
            args[0].startswith("runa:")
            and (
                execution_location() is ExecutionLocation.CONTAINER
                or management.is_management_command(args[0])
            )
        ):
            explicit_management = args[0] == "--management"
            if explicit_management:
                args = args[1:]
            if execution_location() is ExecutionLocation.CONTAINER:
                run(["php", "artisan", *(["--management"] if explicit_management else []), *args])
            else:
                raise SystemExit(management.launch(args))
        else:
            run(runtime.app_command(["php", "artisan", *args]))
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
        raise SystemExit(130) from None
