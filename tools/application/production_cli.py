"""Production command composition; no import-time dispatch or global release state."""

from tools.application.deployment_context import read_env
from tools.application.operations import service as deployment
from tools.application.production_runtime import SERVICES


def dispatch(service, args):
    if args == ["--help"] or not args:
        print(
            "Usage: ./bin/artisan runa:production:validate|build|up|down|restart|status|logs [service]|self-test|verify-ai-isolation\nHost terminal only; requires .env.prod.compose, .env.prod and .env.prod.database.\nBuild creates exact-tag PHP/Nginx images. Up expects migrations/production seeding already complete. Down preserves volumes. Use install-prod/deploy for migrations and managed releases."
        )
        return
    if service.context.inside():
        raise ValueError(
            "Run production Docker operations in a host terminal outside the devcontainer; no Docker socket is mounted here."
        )
    action, *services = args
    from tools.application.repository_boundary import assert_production_installation

    assert_production_installation(service.context.workspace.root)
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
    command = service.production.compose()
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


def main(args, service=None):
    service = service or deployment()
    if not args or args == ["--help"] or service.context.inside():
        return dispatch(service, args)
    from tools.application.repository_boundary import assert_production_installation

    assert_production_installation(service.context.workspace.root)
    with service.context.lock():
        return dispatch(service, args)
