"""Literal unquoted environment parsing; no interpolation or secret normalization."""

from __future__ import annotations

import re


def raw_environment(content: str) -> dict[str, str]:
    values: dict[str, str] = {}
    for line in content.splitlines():
        if not line or line.startswith("#"):
            continue
        key, separator, value = line.partition("=")
        if (
            not separator
            or not re.fullmatch("[A-Z][A-Z0-9_]*", key)
            or key in values
            or "\0" in value
        ):
            raise ValueError("Invalid raw environment file; do not quote values.")
        values[key] = value
    return values
