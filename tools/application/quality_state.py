"""Validated run evidence, including minimal historical status records."""

import math
import re
from collections.abc import Mapping
from datetime import datetime
from typing import Literal, NotRequired, TypedDict, cast

from tools.application.quality_models import QualityScope, quality_scope, string_list
from tools.core.state import json_value

RunStatus = Literal["running", "passed", "failed", "interrupted"]
StepStatus = Literal["pending", "running", "passed", "failed", "blocked", "interrupted"]


class StepRecord(TypedDict):
    status: StepStatus
    id: NotRequired[str]
    command: NotRequired[str]
    depends_on: NotRequired[list[str]]
    severity: NotRequired[Literal["hard_fail", "warning"]]
    execution_context: NotRequired[Literal["application", "host"]]
    exclusive_group: NotRequired[str | None]
    depends_on_if_selected: NotRequired[list[str]]
    command_if_selected: NotRequired[dict[str, str]]
    pid: NotRequired[int | None]
    exit_code: NotRequired[int | None]
    started_at: NotRequired[str | None]
    finished_at: NotRequired[str | None]
    duration_seconds: NotRequired[float | None]


class RunRecord(TypedDict):
    status: RunStatus
    steps: dict[str, StepRecord]
    mode: NotRequired[str]
    roadmap: NotRequired[str]
    branch: NotRequired[str]
    commit: NotRequired[str]
    run_id: NotRequired[str]
    last_step: NotRequired[str]
    impact_areas: NotRequired[list[str]]
    selection: NotRequired[QualityScope]
    rationale: NotRequired[str | None]
    started_at: NotRequired[str | None]
    finished_at: NotRequired[str | None]
    duration_seconds: NotRequired[float | None]


def timing(record: Mapping[str, object]) -> None:
    duration = record.get("duration_seconds")
    if duration is not None:
        if type(duration) not in (int, float):
            raise ValueError("Invalid quality duration.")
        try:
            numeric = float(cast(float, duration))
        except OverflowError:
            raise ValueError("Invalid quality duration.") from None
        if not math.isfinite(numeric) or numeric < 0:
            raise ValueError("Invalid quality duration.")
    for key in ("started_at", "finished_at"):
        value = record.get(key)
        if value is None:
            continue
        if not isinstance(value, str):
            raise ValueError("Invalid quality timestamp.")
        try:
            parsed = datetime.fromisoformat(value)
        except ValueError:
            raise ValueError("Invalid quality timestamp.") from None
        if parsed.utcoffset() is None:
            raise ValueError("Invalid quality timestamp.")


def run_record(value: object) -> RunRecord:
    """Check consumed fields, preserving unknown evidence and optional legacy fields."""
    decoded = json_value(value)
    if not isinstance(decoded, dict) or not isinstance(decoded.get("steps"), dict):
        raise ValueError("Invalid quality state.")
    if decoded.get("status") not in ("running", "passed", "failed", "interrupted"):
        raise ValueError("Invalid quality run status.")
    timing(decoded)
    for key in ("mode", "roadmap", "branch", "commit", "last_step", "run_id"):
        if key in decoded and not isinstance(decoded[key], str):
            raise ValueError("Invalid quality run identity.")
    if decoded.get("rationale") is not None and not isinstance(decoded["rationale"], str):
        raise ValueError("Invalid quality rationale.")
    if "impact_areas" in decoded:
        string_list(decoded["impact_areas"])
    if "selection" in decoded:
        selection = decoded["selection"]
        if not isinstance(selection, dict):
            raise ValueError("Invalid quality selection.")
        quality_scope(selection)
    steps = decoded["steps"]
    assert isinstance(steps, dict)
    for key, step in steps.items():
        if not re.fullmatch(r"[a-z0-9-]+", key) or not isinstance(step, dict):
            raise ValueError("Invalid quality step state.")
        if step.get("status") not in (
            "pending",
            "running",
            "passed",
            "failed",
            "blocked",
            "interrupted",
        ):
            raise ValueError("Invalid quality step status.")
        pid = step.get("pid")
        if pid is not None and (type(pid) is not int or pid <= 0):
            raise ValueError("Invalid quality child identity.")
        code = step.get("exit_code")
        if code is not None and type(code) is not int:
            raise ValueError("Invalid quality child exit code.")
        timing(step)
    return cast(RunRecord, decoded)
