"""Validated installer settings, recovery identities and consumed runtime responses."""

import json
from typing import NotRequired, TypedDict, cast

from tools.core.state import JsonValue, StateFailure, json_value, unique_object


class InstallationError(ValueError):
    """Fixed, safe installer diagnostics; external exceptions remain redacted."""


class TlsSettings(TypedDict):
    mode: str
    certificate: NotRequired[str]
    private_key: NotRequired[str]
    ca_file: NotRequired[str]
    https_port: NotRequired[int]
    bind: NotRequired[str]


SmtpSettings = TypedDict(
    "SmtpSettings",
    {
        "host": str,
        "port": int,
        "scheme": str,
        "username": NotRequired[str],
        "password": NotRequired[str],
        "from": str,
        "recipient": str,
    },
)


class AdministratorSettings(TypedDict):
    email: str
    name: str
    login: NotRequired[str]


class InstallationSettings(TypedDict):
    app_url: str
    tls: TlsSettings
    smtp: SmtpSettings
    http_port: NotRequired[int]
    simulation_project: NotRequired[str]
    administrator: NotRequired[AdministratorSettings | None]


class InstallationIdentity(TypedDict):
    sha: str


class InstallationJournal(InstallationIdentity):
    schema_version: NotRequired[int]
    mode: NotRequired[str]
    simulation: bool
    state: str
    profile: NotRequired[str | None]
    started_at: NotRequired[str]
    completed_at: NotRequired[str]
    failed_at: NotRequired[str]
    administrator_invited: NotRequired[bool]


class InstallationResources(InstallationIdentity):
    project: str


class InstallationVolume(TypedDict):
    name: str


class InstallationCompose(TypedDict):
    name: str
    volumes: NotRequired[dict[str, InstallationVolume]]


def json_object(payload: str | bytes) -> dict[str, JsonValue]:
    try:
        value = json_value(json.loads(payload, object_pairs_hook=unique_object))
    except (ValueError, RecursionError, StateFailure):
        raise InstallationError("Invalid installer JSON object.") from None
    if not isinstance(value, dict):
        raise InstallationError("Installer settings/state must be a JSON object.")
    return value


def settings_fields(value: object) -> InstallationSettings:
    """Check field types before semantic TLS/SMTP validation or environment use."""
    try:
        value = json_value(value)
    except (RecursionError, StateFailure):
        raise InstallationError("Invalid installer settings fields.") from None
    if not isinstance(value, dict) or not isinstance(value.get("app_url"), str):
        raise InstallationError("Application URL must be a credential-free HTTPS origin.")
    tls, smtp, administrator = value.get("tls"), value.get("smtp"), value.get("administrator")
    if not isinstance(tls, dict) or not isinstance(smtp, dict):
        raise InstallationError("TLS and SMTP settings objects are required.")
    if (
        not isinstance(tls.get("mode"), str)
        or any(
            key in tls and not isinstance(tls[key], str)
            for key in ("certificate", "private_key", "ca_file", "bind")
        )
        or ("https_port" in tls and type(tls["https_port"]) is not int)
        or any(
            not isinstance(smtp.get(key), str) for key in ("host", "scheme", "from", "recipient")
        )
        or type(smtp.get("port")) is not int
        or any(key in smtp and not isinstance(smtp[key], str) for key in ("username", "password"))
        or ("http_port" in value and type(value["http_port"]) is not int)
        or ("simulation_project" in value and not isinstance(value["simulation_project"], str))
    ):
        raise InstallationError("Invalid installer TLS/SMTP setting types.")
    if administrator is not None and (
        not isinstance(administrator, dict)
        or any(not isinstance(administrator.get(key), str) for key in ("email", "name"))
        or ("login" in administrator and not isinstance(administrator["login"], str))
    ):
        raise InstallationError("Invalid administrator setting types.")
    return cast(InstallationSettings, value)


def installation_journal(value: dict[str, JsonValue]) -> InstallationJournal:
    if (
        not isinstance(value.get("sha"), str)
        or not isinstance(value.get("state"), str)
        or type(value.get("simulation")) is not bool
        or (
            "profile" in value
            and value["profile"] is not None
            and not isinstance(value["profile"], str)
        )
        or ("administrator_invited" in value and type(value["administrator_invited"]) is not bool)
        or ("schema_version" in value and type(value["schema_version"]) is not int)
        or any(
            key in value and not isinstance(value[key], str)
            for key in ("mode", "started_at", "completed_at", "failed_at")
        )
    ):
        raise InstallationError("Invalid installation journal; preserve recovery evidence.")
    return cast(InstallationJournal, value)


def installation_resources(payload: str) -> InstallationResources:
    value = json_object(payload)
    if any(not isinstance(value.get(key), str) for key in ("sha", "project")):
        raise InstallationError(
            "Invalid installation resource identity; preserve recovery evidence."
        )
    return cast(InstallationResources, value)


def installation_compose(payload: str) -> InstallationCompose:
    value = json_object(payload)
    volumes = value.get("volumes", {})
    if (
        not isinstance(value.get("name"), str)
        or not isinstance(volumes, dict)
        or any(
            not isinstance(row, dict) or not isinstance(row.get("name"), str)
            for row in volumes.values()
        )
    ):
        raise InstallationError("Invalid installation Compose resource identity.")
    return cast(InstallationCompose, value)


def container_labels(payload: str) -> dict[str, str]:
    try:
        value = json_value(json.loads(payload, object_pairs_hook=unique_object))
    except (ValueError, RecursionError, StateFailure):
        raise InstallationError("Invalid installation container identity.") from None
    if not isinstance(value, list) or len(value) != 1 or not isinstance(value[0], dict):
        raise InstallationError("Invalid installation container identity.")
    config = value[0].get("Config")
    labels = config.get("Labels") if isinstance(config, dict) else None
    if not isinstance(config, dict) or (
        labels is not None
        and (
            not isinstance(labels, dict)
            or any(not isinstance(item, str) for item in labels.values())
        )
    ):
        raise InstallationError("Invalid installation container labels.")
    return cast(dict[str, str], labels) if labels is not None else {}
