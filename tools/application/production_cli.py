"""Production command composition; no import-time dispatch or global release state."""

import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.deployment_context import read_env
from tools.application.deployment_service import DeploymentService
from tools.application.production_runtime import SERVICES
from tools.core.lifecycle import interrupt_signals
from tools.core.reporting import SafeFailure


def dispatch(service: DeploymentService, args: list[str]) -> None:
    if args == ["--help"] or not args:
        print(
            "Usage: ./bin/artisan runa:production:validate|build|up|down|restart|status|logs [service]|self-test|verify-ai-isolation\nHost terminal only; requires .env.prod.compose, .env.prod and .env.prod.database.\nBuild creates exact-tag PHP/Nginx images. Up expects migrations/production seeding already complete. Down preserves volumes. Use the external installer or runa:deploy for migrations and managed releases."
        )
        return
    if service.context.inside():
        raise ValueError(
            "Run production Docker operations in a host terminal outside the devcontainer; no Docker socket is mounted here."
        )
    action, *services = args
    service.context.policy.assert_production_installation(service.context.workspace.root)
    if action in ("self-test", "verify-ai-isolation"):
        if services:
            raise ValueError("This operation takes no service arguments.")
        release = service.active_release()
        if action == "self-test":
            service.runtime.health(release, verify_source=True)
        else:
            service.runtime.isolation(release, verify_source=True)
        return
    if action not in ("validate", "build", "up", "down", "restart", "status", "logs"):
        raise ValueError(
            "Unknown or not yet implemented production operation. Use php artisan list runa:production and help <exact-command>."
        )
    if services and (
        action not in ("restart", "logs") or len(services) != 1 or services[0] not in SERVICES
    ):
        raise ValueError("Only restart/logs accept one known production service.")
    command = service.runtime.production.compose()
    environment = service.context.environment()
    for key in ("RUNA_IMAGE_TAG", "RUNA_HTTP_PORT"):
        environment.pop(key, None)
    # Quiet validation never prints expanded application or database secrets.
    service.context.run(command + ["config", "--quiet"], env=environment)
    commands = {
        "validate": None,
        "build": ["build", "app", "nginx"],
        "up": ["up", "-d", "--wait", "--no-build"],
        "down": ["down"],
        "restart": ["restart", *services],
        "status": ["ps", "--all"],
        "logs": ["logs", "--tail", "100", *services],
    }
    if action == "build":
        target = read_env(service.context.workspace.root / ".env.prod.compose")["RUNA_IMAGE_TAG"]
        sha = service.source.resolve(target)
        head = service.context.run(
            ["git", "rev-parse", "HEAD"], capture_output=True, text=True
        ).stdout.strip()
        dirty = service.context.run(
            ["git", "status", "--porcelain", "--untracked-files=all"],
            capture_output=True,
            text=True,
        ).stdout.strip()
        if sha != head or dirty:
            raise ValueError(
                "Production build requires a clean checkout whose HEAD matches the exact image tag."
            )
        service.runtime.build_images()
        print("PASS: production build.")
        return
    if commands[action]:
        service.context.run(command + commands[action], env=environment)
    print("PASS: production " + action + ".")


def main(args: Sequence[str], service: DeploymentService | None = None) -> None:
    args = list(args)
    service = service or DeploymentService.for_workspace(Path(__file__).resolve().parents[2])
    if not args or args == ["--help"] or service.context.inside():
        return dispatch(service, args)
    service.context.policy.assert_production_installation(service.context.workspace.root)
    with service.context.lock():
        return dispatch(service, args)


def boundary() -> int:
    try:
        with interrupt_signals():
            main(sys.argv[1:])
        return 0
    except KeyboardInterrupt:
        return 130
    except (
        SafeFailure,
        ValueError,
        OSError,
        EOFError,
        KeyError,
        subprocess.CalledProcessError,
    ) as error:
        print(
            "Operation failed: "
            + (
                f"child command exited {error.returncode}."
                if isinstance(error, subprocess.CalledProcessError)
                else str(error)
            ),
            file=sys.stderr,
        )
        return (
            error.returncode
            if isinstance(error, subprocess.CalledProcessError)
            else (error.exit_code if isinstance(error, SafeFailure) else 1)
        )


if __name__ == "__main__":
    raise SystemExit(boundary())
