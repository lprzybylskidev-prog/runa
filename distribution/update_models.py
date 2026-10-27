"""Consumed external runtime fields, validated without importing installed code."""

import json
from typing import NotRequired, TypedDict, cast

from distribution.update_contracts import Blocker
from tools.core.state import JsonValue, StateFailure, json_value


class ComposeMount(TypedDict):
    type: str
    source: NotRequired[str]
    target: str
    read_only: NotRequired[bool]


class ComposeService(TypedDict, total=False):
    image: str
    environment: dict[str, JsonValue]
    volumes: list[ComposeMount]
    privileged: bool
    network_mode: str
    cap_add: list[str]


class ComposeVolume(TypedDict):
    name: str


class ComposeConfiguration(TypedDict):
    name: NotRequired[str]
    services: dict[str, ComposeService]
    volumes: NotRequired[dict[str, ComposeVolume]]


class ServiceStatus(TypedDict):
    Service: str
    ID: str
    State: NotRequired[str]
    Health: NotRequired[str]


class ContainerConfiguration(TypedDict, total=False):
    Labels: dict[str, str] | None
    Env: list[str] | None


class ContainerHost(TypedDict, total=False):
    Privileged: bool
    CapAdd: list[str] | None
    NetworkMode: str


class ContainerMount(TypedDict):
    Type: str
    Source: str
    Destination: str
    RW: bool
    Name: NotRequired[str]


class ContainerInspection(TypedDict):
    Config: ContainerConfiguration
    Name: NotRequired[str]
    Image: NotRequired[str]
    HostConfig: NotRequired[ContainerHost]
    Mounts: NotRequired[list[ContainerMount]]


class SeedRun(TypedDict):
    release_sha: str
    seeder: str


class ModuleRecord(TypedDict):
    installed: str | None
    version: str
    registered: bool
    required: dict[str, str] | None


class ModuleStep(TypedDict):
    module: str
    step: str
    fingerprint: str
    status: str
    operation: str


class DatabaseMetadata(TypedDict):
    migrations: list[str]
    seed_runs: list[SeedRun]
    modules: dict[str, ModuleRecord]
    module_steps: list[ModuleStep]
    database_mounts: NotRequired[list[tuple[str, str, str]]]


class RuntimeSnapshot(DatabaseMetadata):
    project: str
    services: list[str]


class AvailableMetadata(TypedDict):
    modules: bool
    steps: bool


def response_json(payload: str) -> JsonValue:
    try:
        return json_value(json.loads(payload))
    except (ValueError, RecursionError, StateFailure):
        raise Blocker(
            "invalid-runtime-response", "Invalid updater process JSON response."
        ) from None


def string_list(value: JsonValue) -> bool:
    return isinstance(value, list) and all(isinstance(item, str) for item in value)


def string_mapping(value: JsonValue) -> bool:
    return isinstance(value, dict) and all(isinstance(item, str) for item in value.values())


def compose_configuration(payload: str) -> ComposeConfiguration:
    value = response_json(payload)
    services = value.get("services") if isinstance(value, dict) else None
    if not isinstance(value, dict) or not isinstance(services, dict):
        raise Blocker("invalid-runtime-response", "Invalid updater Compose services response.")
    if "name" in value and not isinstance(value["name"], str):
        raise Blocker("runtime-ownership", "Invalid updater Compose project identity.")
    for service in services.values():
        if not isinstance(service, dict) or (
            any(
                key in service and not isinstance(service[key], str)
                for key in ("image", "network_mode")
            )
            or ("privileged" in service and type(service["privileged"]) is not bool)
            or ("cap_add" in service and not string_list(service["cap_add"]))
            or ("environment" in service and not isinstance(service["environment"], dict))
        ):
            raise Blocker("invalid-runtime-response", "Invalid updater Compose service fields.")
        mounts = service.get("volumes", [])
        if not isinstance(mounts, list) or any(
            not isinstance(mount, dict)
            or any(not isinstance(mount.get(key), str) for key in ("type", "target"))
            or ("source" in mount and not isinstance(mount["source"], str))
            or ("read_only" in mount and type(mount["read_only"]) is not bool)
            for mount in mounts
        ):
            raise Blocker("invalid-runtime-response", "Invalid updater Compose mounts response.")
    volumes = value.get("volumes", {})
    if not isinstance(volumes, dict) or any(
        not isinstance(volume, dict) or not isinstance(volume.get("name"), str)
        for volume in volumes.values()
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater Compose volumes response.")
    return cast(ComposeConfiguration, value)


def project_identity(config: ComposeConfiguration) -> str:
    project = config.get("name")
    if project is None:
        raise Blocker("runtime-ownership", "Updater Compose project identity is missing.")
    return project


def service_status(payload: str) -> list[ServiceStatus]:
    payload = payload.strip()
    value = (
        response_json(payload)
        if payload.startswith("[")
        else [response_json(line) for line in payload.splitlines() if line]
    )
    if not isinstance(value, list) or any(
        not isinstance(row, dict)
        or any(not isinstance(row.get(key), str) for key in ("Service", "ID"))
        or any(key in row and not isinstance(row[key], str) for key in ("State", "Health"))
        for row in value
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater service status response.")
    return cast(list[ServiceStatus], value)


def container_inspection(payload: str) -> ContainerInspection:
    value = response_json(payload)
    if not isinstance(value, list) or len(value) != 1 or not isinstance(value[0], dict):
        raise Blocker("invalid-runtime-response", "Invalid updater container inspection response.")
    row = value[0]
    config, host, mounts = row.get("Config"), row.get("HostConfig", {}), row.get("Mounts", [])
    if (
        not isinstance(config, dict)
        or (config.get("Env") is not None and not string_list(config["Env"]))
        or (config.get("Labels") is not None and not string_mapping(config["Labels"]))
        or any(key in row and not isinstance(row[key], str) for key in ("Name", "Image"))
        or not isinstance(host, dict)
        or ("Privileged" in host and type(host["Privileged"]) is not bool)
        or (host.get("CapAdd") is not None and not string_list(host["CapAdd"]))
        or ("NetworkMode" in host and not isinstance(host["NetworkMode"], str))
        or not isinstance(mounts, list)
        or any(
            not isinstance(mount, dict)
            or any(not isinstance(mount.get(key), str) for key in ("Type", "Source", "Destination"))
            or type(mount.get("RW")) is not bool
            or ("Name" in mount and not isinstance(mount["Name"], str))
            for mount in mounts
        )
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater container inspection fields.")
    return cast(ContainerInspection, row)


def database_metadata(payload: str) -> DatabaseMetadata:
    value = response_json(payload)
    if not isinstance(value, dict) or not string_list(value.get("migrations")):
        raise Blocker("invalid-runtime-response", "Invalid updater migration metadata.")
    seeds = value.get("seed_runs")
    if not isinstance(seeds, list) or any(
        not isinstance(row, dict)
        or any(not isinstance(row.get(key), str) for key in ("release_sha", "seeder"))
        for row in seeds
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater seed metadata.")
    # These optional tables were absent in old releases; probes fill them separately.
    value.update(modules={}, module_steps=[])
    return cast(DatabaseMetadata, value)


def available_metadata(payload: str) -> AvailableMetadata:
    value = response_json(payload)
    if not isinstance(value, dict) or any(
        type(value.get(key)) is not bool for key in ("modules", "steps")
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater metadata availability response.")
    return cast(AvailableMetadata, value)


def module_records(payload: str) -> dict[str, ModuleRecord]:
    value = response_json(payload)
    if not isinstance(value, dict) or any(
        not isinstance(row, dict)
        or "installed" not in row
        or (row["installed"] is not None and not isinstance(row["installed"], str))
        or not isinstance(row.get("version"), str)
        or type(row.get("registered")) is not bool
        or "required" not in row
        or (row["required"] is not None and not string_mapping(row["required"]))
        for row in value.values()
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater module metadata.")
    return cast(dict[str, ModuleRecord], value)


def module_steps(payload: str) -> list[ModuleStep]:
    value = response_json(payload)
    if not isinstance(value, list) or any(
        not isinstance(row, dict)
        or any(
            not isinstance(row.get(key), str)
            for key in ("module", "step", "fingerprint", "status", "operation")
        )
        for row in value
    ):
        raise Blocker("invalid-runtime-response", "Invalid updater module step metadata.")
    return cast(list[ModuleStep], value)
