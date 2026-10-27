"""Installer settings and preserved private configuration across explicit contexts."""

from pathlib import Path

import pytest

from tools.application.deployment_service import DeploymentService
from tools.application.installation_models import InstallationSettings
from tools.application.installation_service import (
    InstallationError,
    InstallationService,
    validate_settings,
)
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import ExecutionLocation


def settings() -> InstallationSettings:
    return {
        "app_url": "https://customer.example",
        "http_port": 18081,
        "tls": {"mode": "external"},
        "smtp": {
            "host": "smtp.customer.example",
            "port": 587,
            "scheme": "smtp",
            "username": "fixture",
            "password": "literal$raw=fixture",
            "from": "from@example.invalid",
            "recipient": "to@example.invalid",
        },
    }


@pytest.mark.parametrize("change", ["smtp-transport", "smtp-injection", "tls-symlink"])
def test_settings_refuse_unsafe_smtp_or_tls_inputs(tmp_path: Path, change: str) -> None:
    value = settings()
    if change == "smtp-transport":
        value["smtp"]["scheme"] = "plaintext"
    if change == "smtp-injection":
        value["smtp"]["password"] = "fixture\nOTHER=bad"
    if change == "tls-symlink":
        path = tmp_path / "certificate"
        path.symlink_to(tmp_path / "missing")
        value["tls"] = dict(mode="builtin", certificate=str(path), private_key=str(path))
    with pytest.raises(InstallationError):
        validate_settings(value, False)


def test_existing_production_secrets_are_preserved_and_settings_use_raw_values(
    tmp_path: Path,
) -> None:
    from tools.application.python_toolchain import ROOT

    (tmp_path / ".env.prod.example").write_bytes((ROOT / ".env.prod.example").read_bytes())
    deployment = DeploymentService.for_workspace(
        tmp_path,
        processes=ApplicationProcesses(RecordingRunner(), {}),
        location=ExecutionLocation.HOST,
    )
    installation = InstallationService(
        deployment,
        ManagementService(
            deployment.context.workspace, deployment.context.processes, ExecutionLocation.HOST
        ),
    )
    value = validate_settings(settings(), False)
    installation.provision(value, "a" * 40, False)
    before = {name: (tmp_path / name).read_bytes() for name in (".env.prod", ".env.prod.database")}
    installation.provision(value, "a" * 40, False)
    assert before == {name: (tmp_path / name).read_bytes() for name in before}
    assert b"MAIL_PASSWORD=literal$raw=fixture" in before[".env.prod"]


def test_https_verification_redirects_remain_refused() -> None:
    from tools.application.installation_service import NoRedirect

    with pytest.raises(InstallationError, match="redirect"):
        NoRedirect().redirect_request(None)
