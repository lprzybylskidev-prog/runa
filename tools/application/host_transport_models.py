"""Consumed host protocol fields and legacy-compatible recovery records."""

import base64
import binascii
import hmac
import json
import re
from collections.abc import Mapping
from typing import Literal, NotRequired, Protocol, TypedDict, cast

from tools.core.state import JsonValue, StateFailure, json_value

LIMIT = 65536


class FrameReader(Protocol):
    def readline(self, size: int = -1, /) -> bytes: ...


class Credentials(TypedDict):
    workspace: str
    token: str
    version: NotRequired[int]
    pid: NotRequired[int]
    identity: NotRequired[str]


class RegistryRequest(TypedDict):
    id: str
    command: str
    arguments: list[str]


class HostRequest(RegistryRequest):
    version: int
    workspace: str
    token: str
    challenge: str
    interactive: bool


class OperationRecord(TypedDict):
    id: str
    command: str
    state: Literal["validating", "running", "completed", "failed"]
    started_at: float
    connected: bool
    finished_at: NotRequired[float]
    exit_code: NotRequired[int]
    cancel_requested: NotRequired[bool]
    inspect_recovery: NotRequired[bool]


class InputFrame(TypedDict):
    type: Literal["stdin", "cancel", "eof"]
    data: NotRequired[str]


def decode_object(raw: str | bytes) -> dict[str, JsonValue]:
    def unique(pairs: list[tuple[str, object]]) -> dict[str, object]:
        result: dict[str, object] = {}
        for key, value in pairs:
            if key in result:
                raise ValueError("Duplicate transport field.")
            result[key] = value
        return result

    try:
        value = json_value(json.loads(raw, object_pairs_hook=unique))
    except (ValueError, UnicodeError, RecursionError, StateFailure):
        raise ValueError("Invalid transport JSON object.") from None
    if not isinstance(value, dict):
        raise ValueError("Invalid transport JSON object.")
    return value


def read_json(stream: FrameReader) -> dict[str, JsonValue]:
    raw = stream.readline(LIMIT + 1)
    if not raw or len(raw) > LIMIT or not raw.endswith(b"\n"):
        raise ValueError("Missing or oversized transport frame.")
    return decode_object(raw)


def credentials(value: object) -> Credentials:
    if not isinstance(value, dict):
        raise ValueError("Invalid host credentials; recover the executor explicitly.")
    row = cast(dict[str, object], value)
    if (
        not isinstance(row.get("workspace"), str)
        or not isinstance(row.get("token"), str)
        or ("identity" in row and not isinstance(row["identity"], str))
        or ("version" in row and (type(row["version"]) is not int or row["version"] != 1))
    ):
        raise ValueError("Invalid host credentials; recover the executor explicitly.")
    if "pid" in row:
        recovery_pid(row)
    return cast(Credentials, row)


def recovery_pid(value: object) -> int:
    # Old recovery records need only the PID; process ownership is checked by stop().
    pid = cast(dict[str, object], value).get("pid") if isinstance(value, dict) else None
    if type(pid) is not int or pid <= 0:
        raise ValueError("Invalid host recovery PID; inspect the private executor record.")
    return pid


def token_matches(token: str, expected: str) -> bool:
    try:
        return hmac.compare_digest(token.encode(), expected.encode())
    except UnicodeError:
        return False


def validate_request(
    value: object, credentials: Mapping[str, object], challenge: str
) -> HostRequest:
    if not isinstance(value, dict):
        raise ValueError("Unauthorized or invalid workspace request.")
    row = cast(dict[str, object], value)
    token = row.get("token")
    expected_token = credentials.get("token")
    arguments = row.get("arguments")
    command = row.get("command")
    request_id = row.get("id")
    if (
        set(row)
        != {
            "version",
            "workspace",
            "token",
            "challenge",
            "id",
            "command",
            "arguments",
            "interactive",
        }
        or type(row["version"]) is not int
        or row["version"] != 1
        or not isinstance(token, str)
        or not isinstance(expected_token, str)
        or not token_matches(token, expected_token)
        or row["workspace"] != credentials["workspace"]
        or row["challenge"] != challenge
        or not isinstance(request_id, str)
        or not re.fullmatch("[a-f0-9]{32}", request_id)
        or not isinstance(command, str)
        or not re.fullmatch("runa:[a-z][a-z0-9-]*(?::[a-z][a-z0-9-]*)*", command)
        or type(row["interactive"]) is not bool
        or not isinstance(arguments, list)
    ):
        raise ValueError("Unauthorized or invalid workspace request.")
    items = cast(list[object], arguments)
    try:
        valid_arguments = len(items) <= 128 and all(
            isinstance(arg, str) and len(arg.encode()) <= 4096 and "\0" not in arg for arg in items
        )
    except UnicodeError:
        valid_arguments = False
    if not valid_arguments:
        raise ValueError("Unauthorized or invalid workspace request.")
    request = cast(HostRequest, row)
    if any(
        arg == "--host-request"
        or arg.startswith("--host-request=")
        or arg in ("--plan", "--help", "-h", "--")
        for arg in request["arguments"]
    ):
        raise ValueError("Transport-only options are forbidden in execution requests.")
    return request


def input_frame(raw: bytes, interactive: bool) -> InputFrame:
    row = decode_object(raw)
    if row in ({"type": "cancel"}, {"type": "eof"}):
        return cast(InputFrame, row)
    if (
        set(row) != {"type", "data"}
        or row["type"] != "stdin"
        or not interactive
        or not isinstance(row["data"], str)
    ):
        raise ValueError("Invalid input frame.")
    return cast(InputFrame, row)


def stdin_content(frame: InputFrame) -> bytes:
    data = frame.get("data")
    if not isinstance(data, str):
        raise ValueError("Invalid interactive input.")
    try:
        content = base64.b64decode(data, validate=True)
    except (ValueError, binascii.Error):
        raise ValueError("Invalid interactive input.") from None
    if len(content) > 4096:
        raise ValueError("Invalid interactive input.")
    return content


def output_content(frame: dict[str, JsonValue]) -> bytes:
    data = frame.get("data")
    if not isinstance(data, str):
        raise ValueError("Invalid host output frame.")
    try:
        return base64.b64decode(data, validate=True)
    except (ValueError, binascii.Error):
        raise ValueError("Invalid host output frame.") from None


def challenge_value(frame: dict[str, JsonValue]) -> str:
    value = frame.get("value")
    if frame.get("type") != "challenge" or not isinstance(value, str):
        raise ValueError("Invalid host challenge frame.")
    return value


def exit_code(frame: dict[str, JsonValue]) -> int:
    value = frame.get("code")
    if type(value) is not int:
        raise ValueError("Invalid host exit frame.")
    return value
