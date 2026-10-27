"""Consumed source-owned command and Compose health records for management."""

import json
from typing import TypedDict, cast

from tools.core.state import StateFailure, json_value, unique_object


class CommandDefinition(TypedDict):
    name: str
    tool: str
    prefix: list[str]


def command_definitions(payload: str) -> list[CommandDefinition]:
    try:
        value = json_value(json.loads(payload, object_pairs_hook=unique_object))
    except (ValueError, RecursionError, StateFailure):
        raise ValueError("Invalid management command definitions.") from None
    if not isinstance(value, list):
        raise ValueError("Invalid management command definitions.")
    for row in value:
        if (
            not isinstance(row, dict)
            or any(not isinstance(row.get(key), str) for key in ("name", "tool"))
            or not isinstance(row.get("prefix"), list)
        ):
            raise ValueError("Invalid management command definition fields.")
        prefix = row["prefix"]
        if not isinstance(prefix, list) or any(not isinstance(item, str) for item in prefix):
            raise ValueError("Invalid management command prefix.")
    # Preserve source metadata; the catalog cannot register executable families.
    return cast(list[CommandDefinition], value)
