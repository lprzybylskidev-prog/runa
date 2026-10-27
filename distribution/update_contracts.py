"""Pure source/compatibility contracts for the external owner updater."""

import hashlib
import json
import os
import re
from pathlib import Path
from typing import TypedDict

from tools.core.delivery import OWNER_PATHS as OWNER_PATHS
from tools.core.state import JsonValue, StateFailure, json_value

VERSION = r"(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)"
SEMVER = (
    VERSION
    + r"(?:-(?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9][0-9]*|[0-9]*[a-zA-Z-][0-9a-zA-Z-]*))*)?(?:\+[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*)?"
)


class Blocker(ValueError):
    def __init__(self, code: str, message: str) -> None:
        self.code = code
        super().__init__(message)


def regular(root: Path, name: str, limit: int = 2 * 1024**2) -> bytes:
    path = root / name
    if (
        any(p.is_symlink() for p in [path, *path.parents])
        or not path.is_file()
        or path.stat().st_size > limit
    ):
        raise Blocker("unsafe-input", "Required input must be a bounded regular file: " + name)
    return path.read_bytes()


def document(root: Path, name: str, limit: int = 2 * 1024**2) -> dict[str, JsonValue]:
    try:

        def unique(pairs: list[tuple[str, object]]) -> dict[str, object]:
            result: dict[str, object] = {}
            for key, value in pairs:
                if key in result:
                    raise ValueError()
                result[key] = value
            return result

        value = json_value(json.loads(regular(root, name, limit), object_pairs_hook=unique))
        if not isinstance(value, dict):
            raise ValueError()
        return value
    except (ValueError, UnicodeError, RecursionError, StateFailure):
        raise Blocker("invalid-json", "Invalid JSON object: " + name) from None


def stable(value: object) -> tuple[int, int, int]:
    if (
        not isinstance(value, str)
        or not re.fullmatch(VERSION, value)
        or any(len(p) > 9 for p in value.split("."))
    ):
        raise Blocker(
            "invalid-version", "Platform compatibility requires bounded stable X.Y.Z versions."
        )
    major, minor, patch = map(int, value.split("."))
    return major, minor, patch


def supports(constraint: object, version: object) -> bool:
    target = stable(version)
    if not isinstance(constraint, str) or not 0 < len(constraint) <= 256:
        raise Blocker("unsupported-constraint", "Unsupported application compatibility constraint.")
    result = False
    for alternative in constraint.split("||"):
        tokens = alternative.split()
        if not tokens:
            raise Blocker(
                "unsupported-constraint", "Unsupported application compatibility constraint."
            )
        matches = True
        for token in tokens:
            match = re.fullmatch(r"(\^|~|>=|<=|>|<|=)?(" + VERSION + ")", token)
            if not match:
                raise Blocker(
                    "unsupported-constraint", "Unsupported application compatibility constraint."
                )
            op, value = match[1] or "=", stable(match[2])
            upper = (
                (value[0], value[1] + 1, 0)
                if op == "~"
                else (value[0] + 1, 0, 0)
                if value[0]
                else (0, value[1] + 1, 0)
                if value[1]
                else (0, 0, value[2] + 1)
            )
            matches = (
                matches
                and {
                    "=": target == value,
                    ">": target > value,
                    "<": target < value,
                    ">=": target >= value,
                    "<=": target <= value,
                    "^": value <= target < upper,
                    "~": value <= target < upper,
                }[op]
            )
        result = result or matches
    return result


def platform(root: Path) -> str:
    value = document(root, "src/runa/platform.json", 16384)
    version = value.get("version")
    stable(version)
    if (
        type(value.get("schema_version")) is not int
        or set(value) != {"schema_version", "id", "name", "version"}
        or value != {"schema_version": 1, "id": "runa", "name": "Runa", "version": version}
    ):
        raise Blocker("platform-identity", "Platform manifest has unsupported identity/schema.")
    assert isinstance(version, str)
    return version


def inventory(root: Path) -> dict[str, tuple[str, bytes]]:
    """Delivered platform source only, including every unexpected byte/type."""
    base = root / "src/runa"
    if base.is_symlink() or (root / "src").is_symlink() or not base.is_dir():
        raise Blocker("unsafe-platform", "Platform root must be an existing real directory.")
    result: dict[str, tuple[str, bytes]] = {}

    def visit(folder: Path) -> None:
        for path in sorted(folder.iterdir()):
            name = path.relative_to(root).as_posix()
            if any(ord(char) < 32 or ord(char) == 127 for char in name) or "\\" in name:
                raise Blocker("unsafe-platform", "Platform contains an unsafe source path.")
            if path.is_symlink():
                result[name] = ("120000", os.readlink(path).encode())
            elif path.is_dir():
                visit(path)
            elif path.is_file():
                if path.stat().st_size > 512 * 1024**2:
                    raise Blocker("source-size", "Platform file exceeds the supported size.")
                digest = hashlib.sha256()
                with path.open("rb") as stream:
                    for chunk in iter(lambda: stream.read(65536), b""):
                        digest.update(chunk)
                result[name] = (
                    "100755" if path.stat().st_mode & 0o111 else "100644",
                    digest.digest(),
                )
            else:
                result[name] = ("unsupported", b"")
            if len(result) > 50000:
                raise Blocker("source-size", "Platform inventory exceeds 50000 files.")

    visit(base)
    return result


class SourceChange(TypedDict):
    path: str
    kind: str


def compare(root: Path, reference: Path) -> list[SourceChange]:
    expected, actual = inventory(reference), inventory(root)
    differences: list[SourceChange] = []
    for name in sorted(expected.keys() | actual.keys()):
        if name not in expected:
            differences.append({"path": name, "kind": "unexpected"})
        elif name not in actual:
            differences.append({"path": name, "kind": "missing"})
        elif actual[name] != expected[name]:
            differences.append(
                {
                    "path": name,
                    "kind": "type-or-mode" if actual[name][0] != expected[name][0] else "changed",
                }
            )
    return differences


def changes(original: Path, target: Path) -> list[SourceChange]:
    before, after = inventory(original), inventory(target)
    return [
        {
            "path": p,
            "kind": "added" if p not in before else "removed" if p not in after else "changed",
        }
        for p in sorted(before.keys() | after.keys())
        if before.get(p) != after.get(p)
    ]


def compose(
    platform: dict[str, JsonValue], application: dict[str, JsonValue], kind: str
) -> dict[str, JsonValue]:
    from tools.core.dependencies import compose as compose_manifests

    try:
        return compose_manifests(platform, application, kind)
    except (ValueError, KeyError, TypeError):
        raise Blocker(
            "dependency-conflict", "Conflicting or unsupported dependency declaration."
        ) from None


def local_path(root: Path, value: str, base: Path | None = None) -> str:
    path = (base or root) / value
    path = path.absolute()
    try:
        relative = path.resolve().relative_to(root.resolve()).as_posix()
    except (ValueError, OSError):
        raise Blocker(
            "customization-review-unavailable",
            "Infrastructure references must resolve inside the customer project.",
        ) from None
    if path.is_symlink() or any(p.is_symlink() for p in path.parents):
        raise Blocker(
            "customization-review-unavailable",
            "Infrastructure references must not traverse symlinks.",
        )
    return relative
