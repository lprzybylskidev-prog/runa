"""Compatibility facade for production command and image consumers."""

from tools.application.production_cli import main as main
from tools.application.production_runtime import SERVICES as SERVICES


def compose(tls_root=None, *, root=None):
    from tools.application.operations import service

    return service(root).production.compose(tls_root=tls_root)


def verify_images(command, environment, source=None, *, root=None):
    from tools.application.operations import service

    return service(root).production.verify_images(command, environment, source)
