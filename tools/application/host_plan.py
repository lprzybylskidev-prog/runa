"""Immutable request-bound host plan; executable operations are source-owned."""

import json
import re
from dataclasses import dataclass
from typing import NotRequired, TypedDict, cast

DEVELOPMENT_ACTIONS = (
    "initialize",
    "prepare",
    "up",
    "down",
    "config",
    "shell",
    "profile",
    "embeddings",
    "build",
    "recreate",
    "restart",
    "logs",
    "status",
    "setup",
    "knowledge-init",
    "knowledge-status",
)
TOOLS = (
    "doctor",
    "quality",
    "knowledge",
    "security",
    "python",
    "xdebug",
    "check",
    "hook",
    "format",
    "lint",
    "typecheck",
    "analyse",
    "test",
    "production",
    "deploy",
    "host",
)
OPERATIONS = {
    **{"development." + action: action for action in DEVELOPMENT_ACTIONS},
    **{"tooling." + tool: tool for tool in TOOLS},
}


class HostParameters(TypedDict):
    arguments: NotRequired[list[str]]


class HostPlanPayload(TypedDict):
    schema_version: int
    request_id: str
    operation: str
    parameters: HostParameters


@dataclass(frozen=True)
class HostPlan:
    request_id: str
    operation: str
    arguments: tuple[str, ...]
    arguments_present: bool = True

    def as_dict(self) -> HostPlanPayload:
        return {
            "schema_version": 1,
            "request_id": self.request_id,
            "operation": self.operation,
            "parameters": {"arguments": list(self.arguments)} if self.arguments_present else {},
        }

    @classmethod
    def parse(cls, raw: str, request: str) -> "HostPlan":
        def unique_object(pairs: list[tuple[str, object]]) -> dict[str, object]:
            result: dict[str, object] = {}
            for key, value in pairs:
                if key in result:
                    raise ValueError("Duplicate host-plan field.")
                result[key] = value
            return result

        try:
            decoded: object = json.loads(raw, object_pairs_hook=unique_object)
        except (json.JSONDecodeError, UnicodeError) as error:
            raise ValueError("Management did not return a valid JSON host plan.") from error
        if not isinstance(decoded, dict):
            raise ValueError("Unsupported, unbound or unsafe host plan.")
        plan = cast(dict[str, object], decoded)
        operation = plan.get("operation")
        if (
            set(plan) != {"schema_version", "request_id", "operation", "parameters"}
            or type(plan["schema_version"]) is not int
            or plan["schema_version"] != 1
            or plan["request_id"] != request
            or not re.fullmatch(r"[a-f0-9]{32}", request)
            or not isinstance(operation, str)
            or operation not in OPERATIONS
            or type(plan["parameters"]) is not dict
        ):
            raise ValueError("Unsupported, unbound or unsafe host plan.")
        parameters = cast(dict[str, object], plan["parameters"])
        if set(parameters) - {"arguments"} or (
            "arguments" not in parameters and not operation.startswith("development.")
        ):
            raise ValueError("Unsupported host-plan parameters.")
        arguments = parameters.get("arguments", [])
        if not isinstance(arguments, list):
            raise ValueError("Invalid bounded host-plan arguments.")
        items = cast(list[object], arguments)
        if len(items) > 128 or any(
            not isinstance(value, str) or len(value.encode()) > 4096 or "\x00" in value
            for value in items
        ):
            raise ValueError("Invalid bounded host-plan arguments.")
        return cls(request, operation, tuple(cast(list[str], items)), "arguments" in parameters)
