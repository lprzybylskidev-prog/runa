"""Installer settings and preserved private configuration across explicit contexts."""

from types import SimpleNamespace

import pytest

from tools.application.installation_service import (
    InstallationError,
    InstallationService,
    validate_settings,
)
from tools.application.operations import service
from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation
from tools.maintainer.tests.python.support.process import RecordingRunner


def settings():
    return dict(
        app_url="https://customer.example",
        http_port=18081,
        tls={"mode": "external"},
        smtp=dict(
            host="smtp.customer.example",
            port=587,
            scheme="smtp",
            username="fixture",
            password="literal$raw=fixture",
            **{"from": "from@example.invalid"},
            recipient="to@example.invalid",
        ),
    )


@pytest.mark.parametrize("change", ["smtp-transport", "smtp-injection", "tls-symlink"])
def test_settings_refuse_unsafe_smtp_or_tls_inputs(tmp_path, change):
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


def test_existing_production_secrets_are_preserved_and_settings_use_raw_values(tmp_path):
    from tools.maintainer.python_toolchain import ROOT

    (tmp_path / ".env.prod.example").write_bytes((ROOT / ".env.prod.example").read_bytes())
    deployment = service(
        tmp_path, ApplicationProcesses(RecordingRunner(), {}), ExecutionLocation.HOST
    )
    installation = InstallationService(deployment, SimpleNamespace())
    value = validate_settings(settings(), False)
    installation.provision(value, "a" * 40, False)
    before = {name: (tmp_path / name).read_bytes() for name in (".env.prod", ".env.prod.database")}
    installation.provision(value, "a" * 40, False)
    assert before == {name: (tmp_path / name).read_bytes() for name in before}
    assert b"MAIL_PASSWORD=literal$raw=fixture" in before[".env.prod"]


def test_https_verification_redirects_remain_refused():
    from tools.application.installation_service import NoRedirect

    with pytest.raises(InstallationError, match="redirect"):
        NoRedirect().redirect_request(None)
