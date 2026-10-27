"""Host frames fail safely before process control or interactive writes."""

import base64
import io
import json
from pathlib import Path

import pytest

from tools.application.host_transport import HostExecutor
from tools.application.host_transport_models import (
    challenge_value,
    credentials,
    decode_object,
    exit_code,
    input_frame,
    output_content,
    read_json,
    recovery_pid,
    stdin_content,
    validate_request,
)
from tools.application.processes import ApplicationProcesses
from tools.application.testing.support.process import RecordingRunner
from tools.core.context import WorkspaceContext


def test_transport_decoding_rejects_ambiguous_or_nonfinite_objects() -> None:
    for raw in (
        b"[]\n",
        b'{"data":"first","data":"second"}\n',
        b'{"data":{"x":NaN}}\n',
        b'{"data":Infinity}\n',
        b'{"data":-Infinity}\n',
        b'{"data":"\xff"}\n',
        b"{}",
        b"x" * 65537,
    ):
        with pytest.raises(ValueError):
            read_json(io.BytesIO(raw))
    assert read_json(io.BytesIO(b'{"extra":{"values":[1,null,true]}}\n')) == {
        "extra": {"values": [1, None, True]}
    }


def test_input_frames_validate_before_interactive_decode() -> None:
    for payload in (
        {"type": "stdin", "data": 1},
        {"type": "stdin", "data": "", "command": "override"},
        {"type": "cancel", "data": ""},
        {"type": "eof", "extra": True},
        {"type": "unknown"},
    ):
        with pytest.raises(ValueError, match="Invalid input frame"):
            input_frame(json.dumps(payload).encode(), True)
    with pytest.raises(ValueError, match="Invalid input frame"):
        input_frame(b'{"type":"stdin","data":""}', False)
    assert input_frame(b'{"type":"cancel"}', False) == {"type": "cancel"}
    assert input_frame(b'{"type":"eof"}', False) == {"type": "eof"}
    content = b"literal $()\x00\xff\n"
    frame = input_frame(
        json.dumps({"type": "stdin", "data": base64.b64encode(content).decode()}).encode(), True
    )
    assert stdin_content(frame) == content
    for data in ("!", "\u0105", base64.b64encode(b"x" * 4097).decode()):
        with pytest.raises(ValueError, match="Invalid interactive input"):
            stdin_content({"type": "stdin", "data": data})


def test_control_response_decoders_keep_bytes_and_native_status() -> None:
    assert challenge_value({"type": "challenge", "value": "previous-release-challenge"}) == (
        "previous-release-challenge"
    )
    assert exit_code({"type": "exit", "code": 130}) == 130
    assert output_content({"type": "stdout", "data": "/w=="}) == b"\xff"
    with pytest.raises(ValueError, match="challenge"):
        challenge_value({"type": "stdout", "value": ""})
    with pytest.raises(ValueError, match="exit"):
        exit_code({"type": "exit", "code": True})
    for data in (None, 1, "!"):
        with pytest.raises(ValueError, match="output"):
            output_content({"type": "stderr", "data": data})


def test_recovery_records_retain_legacy_fields_and_reject_unsafe_pids() -> None:
    old = decode_object('{"workspace":"old-workspace","token":"old-token","extra":1}')
    assert credentials(old) == old
    assert recovery_pid({"pid": 42, "extra": "preserved"}) == 42
    for pid in (True, 0, -1, "42", 1.5, None):
        with pytest.raises(ValueError, match="recovery PID"):
            recovery_pid({"pid": pid})
    with pytest.raises(ValueError, match="credentials"):
        credentials({"workspace": [], "token": "token"})


def test_invalid_request_token_and_unicode_arguments_are_safe_refusals() -> None:
    value: dict[str, object] = {
        "version": 1,
        "workspace": "workspace",
        "token": "token",
        "challenge": "challenge",
        "id": "a" * 32,
        "command": "runa:dev:up",
        "arguments": ["literal $()", "\u0105"],
        "interactive": False,
    }
    auth = {"workspace": "workspace", "token": "token"}
    assert validate_request(value, auth, "challenge")["arguments"] == ["literal $()", "\u0105"]
    for change in (
        {"token": "\u0105"},
        {"token": "\ud800"},
        {"arguments": ["\ud800"]},
        {"version": True},
        {"interactive": 1},
    ):
        with pytest.raises(ValueError, match="Unauthorized"):
            validate_request(value | change, auth, "challenge")


def test_status_preserves_receipts_without_process_execution(tmp_path: Path) -> None:
    runner = RecordingRunner()
    host = HostExecutor(WorkspaceContext(tmp_path), ApplicationProcesses(runner, {}))
    directory = host.directory() / "operations"
    directory.mkdir(parents=True)
    request = "a" * 32
    receipt = {"id": request, "state": "completed", "exit_code": 9, "extra": ["old"]}
    (directory / (request + ".json")).write_text(json.dumps(receipt))
    auth = credentials({"workspace": "old-workspace", "token": "old-token"})
    assert host.status(auth, request) == receipt
    assert host.status(auth)["operations"] == [receipt]
    with pytest.raises(ValueError, match="Invalid operation ID"):
        host.status(auth, "../client")
    assert not runner.calls
