"""Shapes of checked-in Compose fixtures; topology assertions validate their contents."""

from pathlib import Path
from typing import TypedDict, cast

from tools.core.state import JsonValue
from tools.maintainer.checks import yaml


class MountOptions(TypedDict):
    subpath: str


class SourceMount(TypedDict):
    type: str
    source: str
    target: str
    read_only: bool
    volume: MountOptions


class SourceLogging(TypedDict):
    options: dict[str, str]


class SourceService(TypedDict):
    image: str
    environment: dict[str, str]
    volumes: list[SourceMount]
    logging: SourceLogging
    privileged: bool
    read_only: bool
    cap_drop: list[str]
    tmpfs: list[str]


class ComposeSource(TypedDict):
    services: dict[str, SourceService]
    volumes: dict[str, JsonValue]


def compose_source(path: Path) -> ComposeSource:
    # This private fixture describes the fields asserted by each topology case,
    # not an external configuration decoder. Missing fields still fail the case.
    return cast(ComposeSource, yaml(path))
