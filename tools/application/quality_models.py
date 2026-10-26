"""Validated immutable quality node boundary; serialized evidence stays compatible."""

import re
import shlex
from collections.abc import Mapping
from dataclasses import dataclass


@dataclass(frozen=True)
class QualityStep:
    identifier: str
    command: tuple[str, ...]
    dependencies: tuple[str, ...]
    severity: str
    execution_context: str
    exclusive_group: str | None

    @classmethod
    def parse(cls, value: Mapping[str, object]) -> "QualityStep":
        identifier = value.get("id")
        command = value.get("command")
        dependencies = value.get("depends_on")
        severity = value.get("severity")
        location = value.get("execution_context", "application")
        group = value.get("exclusive_group")
        if not isinstance(identifier, str) or not re.fullmatch(r"[a-z0-9-]+", identifier):
            raise ValueError("Invalid quality step identity.")
        if not isinstance(command, str) or not command or "\0" in command:
            raise ValueError("Invalid quality command.")
        argv = tuple(shlex.split(command))
        if not argv or not argv[0]:
            raise ValueError("Empty quality command.")
        if not isinstance(dependencies, list) or not all(
            isinstance(item, str) and re.fullmatch(r"[a-z0-9-]+", item) for item in dependencies
        ):
            raise ValueError("Invalid quality dependencies.")
        if (
            not isinstance(severity, str)
            or not isinstance(location, str)
            or severity not in ("hard_fail", "warning")
            or location not in ("application", "host")
        ):
            raise ValueError("Invalid quality severity or execution context.")
        if group is not None and (
            not isinstance(group, str) or not re.fullmatch(r"[a-z0-9-]+", group)
        ):
            raise ValueError("Invalid quality exclusive group.")
        return cls(identifier, argv, tuple(dependencies), severity, location, group)
