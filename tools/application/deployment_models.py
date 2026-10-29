"""Validated deployment journals and the consumed Docker/Compose response fields."""

import json
import re
from pathlib import Path
from typing import NotRequired, TypedDict, cast

from tools.core.state import JsonValue, StateFailure, json_value


class ReleaseRecord(TypedDict):
    sha: str
    version: str
    path: str
    application_version: str
    database_tree: str
    rollback_safe: NotRequired[bool]
    deployed_at: str
    simulation: bool
    images: dict[str, str]
    phase_costs: NotRequired[dict[str, float]]


class DeploymentState(TypedDict):
    current: ReleaseRecord | None
    previous: ReleaseRecord | None


class DeploymentProgress(TypedDict):
    target: str
    previous: ReleaseRecord | None
    started_at: str
    phase: str
    data_steps_started: bool
    phase_costs: NotRequired[dict[str, float]]


class ComposeService(TypedDict):
    image: NotRequired[str]
    profiles: NotRequired[list[str]]
    healthcheck: NotRequired[dict[str, JsonValue]]
    privileged: NotRequired[bool]
    network_mode: NotRequired[str]
    cap_add: NotRequired[list[str]]
    volumes: NotRequired[list[JsonValue]]
    environment: NotRequired[dict[str, JsonValue]]


class ComposeConfiguration(TypedDict):
    services: dict[str, ComposeService]


class ServiceStatus(TypedDict):
    Service: str
    State: NotRequired[str]
    Health: NotRequired[str]
    ExitCode: NotRequired[int]


class ContainerHost(TypedDict, total=False):
    Privileged: bool
    CapAdd: list[str] | None
    NetworkMode: str


class ContainerMount(TypedDict, total=False):
    Source: str


class ContainerConfiguration(TypedDict):
    Env: list[str] | None


class ContainerInspection(TypedDict):
    Image: str
    HostConfig: ContainerHost
    Config: ContainerConfiguration
    Mounts: NotRequired[list[ContainerMount]]


def response_json(payload: str) -> JsonValue:
    try:
        return json_value(json.loads(payload))
    except (ValueError, RecursionError, StateFailure):
        raise ValueError("Invalid production process JSON response.") from None


def string_mapping(value: JsonValue) -> bool:
    return isinstance(value, dict) and all(isinstance(item, str) for item in value.values())


def string_list(value: JsonValue) -> bool:
    return isinstance(value, list) and all(isinstance(item, str) for item in value)


def deployment_state(value: JsonValue, directory: Path) -> DeploymentState:
    if not isinstance(value, dict) or set(value) != {"current", "previous"}:
        raise ValueError("Invalid deployment journal; preserve operation evidence.")
    for row in value.values():
        if row is None:
            continue
        if not isinstance(row, dict):
            raise ValueError("Invalid deployment journal; preserve operation evidence.")
        sha = row.get("sha")
        if (
            not isinstance(sha, str)
            or not re.fullmatch("[a-f0-9]{40}", sha)
            or row.get("path") != str(directory / "releases" / sha)
        ):
            raise ValueError("Deployment release identity differs from its operator workspace.")
        if (
            any(
                not isinstance(row.get(key), str)
                for key in ("version", "application_version", "database_tree", "deployed_at")
            )
            or type(row.get("simulation")) is not bool
            or ("rollback_safe" in row and type(row["rollback_safe"]) is not bool)
            or not string_mapping(row.get("images"))
        ):
            raise ValueError("Invalid deployment release record; preserve operation evidence.")
    # All consumed fields are checked; preserve additional historical evidence unchanged.
    return cast(DeploymentState, value)


def compose_configuration(payload: str) -> ComposeConfiguration:
    value = response_json(payload)
    services = value.get("services") if isinstance(value, dict) else None
    if not isinstance(services, dict):
        raise ValueError("Invalid production Compose services response.")
    for service in services.values():
        if not isinstance(service, dict):
            raise ValueError("Invalid production Compose service response.")
        if (
            any(
                key in service and not isinstance(service[key], str)
                for key in ("image", "network_mode")
            )
            or any(
                key in service and not string_list(service[key]) for key in ("profiles", "cap_add")
            )
            or any(
                key in service and not isinstance(service[key], dict)
                for key in ("healthcheck", "environment")
            )
            or ("volumes" in service and not isinstance(service["volumes"], list))
            or ("privileged" in service and type(service["privileged"]) is not bool)
        ):
            raise ValueError("Invalid production Compose service field.")
    return cast(ComposeConfiguration, value)


def service_image(service: ComposeService) -> str:
    image = service.get("image")
    if image is None:
        raise ValueError("Selected production service has no image identity.")
    return image


def service_status(payload: str) -> list[ServiceStatus]:
    value = (
        response_json(payload)
        if payload.startswith("[")
        else [response_json(line) for line in payload.splitlines() if line]
    )
    if not isinstance(value, list):
        raise ValueError("Invalid production service status response.")
    for row in value:
        if (
            not isinstance(row, dict)
            or not isinstance(row.get("Service"), str)
            or any(key in row and not isinstance(row[key], str) for key in ("State", "Health"))
            or ("ExitCode" in row and type(row["ExitCode"]) is not int)
        ):
            raise ValueError("Invalid production service status row.")
    return cast(list[ServiceStatus], value)


def container_inspection(payload: str) -> ContainerInspection:
    value = response_json(payload)
    if not isinstance(value, list) or len(value) != 1 or not isinstance(value[0], dict):
        raise ValueError("Invalid production container inspection response.")
    row = value[0]
    host, config, mounts = row.get("HostConfig"), row.get("Config"), row.get("Mounts", [])
    if (
        not isinstance(row.get("Image"), str)
        or not isinstance(host, dict)
        or not isinstance(config, dict)
        or "Env" not in config
        or (config["Env"] is not None and not string_list(config["Env"]))
        or not isinstance(mounts, list)
        or any(
            not isinstance(mount, dict)
            or ("Source" in mount and not isinstance(mount["Source"], str))
            for mount in mounts
        )
    ):
        raise ValueError("Invalid production container inspection fields.")
    if (
        ("Privileged" in host and type(host["Privileged"]) is not bool)
        or ("NetworkMode" in host and not isinstance(host["NetworkMode"], str))
        or (host.get("CapAdd") is not None and not string_list(host["CapAdd"]))
    ):
        raise ValueError("Invalid production container host configuration.")
    return cast(ContainerInspection, row)


def manifest_version(path: Path) -> str:
    value = response_json(path.read_text())
    version = value.get("version") if isinstance(value, dict) else None
    if not isinstance(version, str):
        raise ValueError("Invalid deployment manifest version.")
    return version
