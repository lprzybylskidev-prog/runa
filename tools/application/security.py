"""Compatibility functions over explicit security services."""

import os
from pathlib import Path

from tools.application.development_runtime import execution_location
from tools.application.processes import ApplicationProcesses
from tools.application.security_service import SecurityService
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]


def service(root=ROOT, *, audit_root=None):
    return SecurityService(
        WorkspaceContext(root),
        ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
        execution_location(),
        audit_root,
    )


def surface(reference="main"):
    return service().surface(reference)


def review():
    return service().review()


def production_images(root):
    return service().production_images(root)


def trivy(production_root=None, *, additional_images=()):
    return service().trivy(production_root, additional_images=additional_images)


def dependencies(*, audit_root=None):
    return service(audit_root=audit_root).dependencies()


def secrets_scan():
    return service().secrets_scan()
