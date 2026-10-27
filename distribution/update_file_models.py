"""Delivered-file review and recovery records; no customer source is imported."""

import re
from typing import Literal, NotRequired, TypedDict, cast

from distribution.update_contracts import Blocker
from tools.core.state import JsonValue

Ownership = Literal[
    "owner-excluded",
    "protected-platform",
    "application",
    "private-or-generated",
    "instructions",
    "delivered",
]
Difference = Literal["unchanged", "added", "removed", "modified"]
Action = Literal["preserve", "remove", "add", "update", "manual-reconciliation"]


class FileIdentity(TypedDict):
    mode: str
    sha256: str | None


class FileReview(TypedDict):
    path: str
    ownership: Ownership
    action: Action
    local: Difference | Literal["customer-owned"]
    upstream: Difference | Literal["customer-owned"]
    conflict: str | None
    original: NotRequired[FileIdentity | None]
    current: NotRequired[FileIdentity | None]
    target: NotRequired[FileIdentity | None]


class FileAction(TypedDict):
    path: str
    ownership: Ownership
    action: Literal["add", "update", "remove"]
    local: Difference
    upstream: Difference
    conflict: str | None
    original: FileIdentity | None
    current: FileIdentity | None
    target: FileIdentity | None
    id: str
    state: Literal["planned", "replacing", "done"]


class FileJournal(TypedDict, total=False):
    file_actions: list[FileAction]
    file_action_history: list[FileAction]


class DeliveredReview(TypedDict):
    files: list[FileReview]
    fingerprint: str
    required: bool
    manual_reconciliation: list[str]


class InfrastructureReview(TypedDict):
    required: bool
    services: list[str]
    devcontainer: bool
    fingerprint: str
    inputs: list[str]


class CustomizationReview(DeliveredReview):
    services: list[str]
    devcontainer: bool


def valid_identity(value: JsonValue) -> bool:
    if value is None:
        return True
    if not isinstance(value, dict):
        return False
    mode, digest = value.get("mode"), value.get("sha256")
    return (mode == "unsupported" and "sha256" in value and digest is None) or (
        mode in ("100644", "100755", "120000")
        and isinstance(digest, str)
        and re.fullmatch("[a-f0-9]{64}", digest) is not None
    )


def file_journal(value: dict[str, JsonValue]) -> FileJournal:
    """Validate consumed action fields, preserving other journal/review evidence."""
    for key in ("file_actions", "file_action_history"):
        rows = value.get(key, [])
        if not isinstance(rows, list):
            raise Blocker("project-journal-changed", "Invalid delivered-file action journal.")
        for row in rows:
            if (
                not isinstance(row, dict)
                or any(not isinstance(row.get(field), str) for field in ("path", "id"))
                or row.get("ownership") not in ("delivered", "instructions")
                or row.get("action") not in ("add", "update", "remove")
                or row.get("state") not in ("planned", "replacing", "done")
                or any(
                    row.get(field) not in ("unchanged", "added", "removed", "modified")
                    for field in ("local", "upstream")
                )
                or "conflict" not in row
                or (row["conflict"] is not None and not isinstance(row["conflict"], str))
                or any(
                    field not in row or not valid_identity(row[field])
                    for field in ("original", "current", "target")
                )
            ):
                raise Blocker("project-journal-changed", "Invalid delivered-file action journal.")
    return cast(FileJournal, value)
