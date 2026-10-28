"""Descriptor-anchored private files, bounded JSON and stable-inode Linux locks."""

from __future__ import annotations

import fcntl
import json
import math
import os
import secrets
import stat
from collections.abc import Generator
from contextlib import contextmanager
from dataclasses import dataclass, field
from pathlib import Path
from typing import TypeAlias, cast

from tools.core.context import absolute_path
from tools.core.reporting import SafeFailure

JsonValue: TypeAlias = "None | bool | int | float | str | list[JsonValue] | dict[str, JsonValue]"


class StateFailure(SafeFailure):
    pass


def filename(name: str) -> str:
    if not name or name in (".", "..") or any(char in name for char in "/\\\0"):
        raise StateFailure("State filename must be a single path component.")
    return name


def regular_file(descriptor: int, *, private: bool = True) -> os.stat_result:
    info = os.fstat(descriptor)
    if (
        not stat.S_ISREG(info.st_mode)
        or info.st_uid != os.getuid()
        or info.st_mode & (0o077 if private else 0o022)
        or info.st_nlink != 1
    ):
        raise StateFailure(
            "State file must be private, regular and operator-owned without hardlinks."
        )
    return info


@dataclass(frozen=True)
class StateDirectory:
    path: Path
    private: bool = True
    anchor: Path = field(kw_only=True)

    def __post_init__(self) -> None:
        absolute_path(self.path)
        absolute_path(self.anchor)
        if not self.path.is_relative_to(self.anchor):
            raise ValueError("State directory must belong to its explicit trusted anchor.")

    @contextmanager
    def open(self) -> Generator[int, None, None]:
        """Walk from / using openat; never follow a replaced ancestor symlink."""
        descriptor = os.open("/", os.O_RDONLY | os.O_DIRECTORY | os.O_CLOEXEC)
        try:
            current = Path("/")
            for part in self.path.parts[1:]:
                current /= part
                try:
                    os.mkdir(part, mode=0o700, dir_fd=descriptor)
                except FileExistsError:
                    pass
                child = os.open(
                    part,
                    os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW | os.O_CLOEXEC,
                    dir_fd=descriptor,
                )
                os.close(descriptor)
                descriptor = child
                ancestor = os.fstat(descriptor)
                sticky_temporary = ancestor.st_uid == 0 and ancestor.st_mode & stat.S_ISVTX
                below_anchor = current != self.anchor and current.is_relative_to(self.anchor)
                if below_anchor and (
                    ancestor.st_uid not in (0, os.getuid())
                    or (ancestor.st_mode & 0o022 and not sticky_temporary)
                ):
                    raise StateFailure("State ancestors must have safe ownership and permissions.")
            info = os.fstat(descriptor)
            forbidden = 0o077 if self.private else 0o022
            if info.st_uid != os.getuid() or info.st_mode & forbidden:
                raise StateFailure("State directory ownership or permissions are unsafe.")
            yield descriptor
        finally:
            os.close(descriptor)

    @contextmanager
    def file(
        self,
        directory: int,
        name: str,
        *,
        create: bool = False,
        private: bool = True,
    ) -> Generator[int, None, None]:
        descriptor = os.open(
            filename(name),
            (os.O_RDWR | os.O_CREAT if create else os.O_RDONLY)
            | os.O_NOFOLLOW
            | os.O_NONBLOCK
            | os.O_CLOEXEC,
            0o600,
            dir_fd=directory,
        )
        try:
            regular_file(descriptor, private=private)
            yield descriptor
        finally:
            os.close(descriptor)


def json_value(value: object) -> JsonValue:
    """Validate the untyped decoder result once, including nonstandard NaN/Infinity."""
    if value is None or isinstance(value, (bool, int, str)):
        return value
    if isinstance(value, float) and math.isfinite(value):
        return value
    if isinstance(value, list):
        return [json_value(item) for item in cast(list[object], value)]
    if isinstance(value, dict):
        result: dict[str, JsonValue] = {}
        for key, item in cast(dict[object, object], value).items():
            if not isinstance(key, str):
                raise StateFailure("State JSON object keys must be strings.")
            result[key] = json_value(item)
        return result
    raise StateFailure("State must contain finite JSON values.")


def unique_object(pairs: list[tuple[str, JsonValue]]) -> dict[str, JsonValue]:
    result: dict[str, JsonValue] = {}
    for key, value in pairs:
        if key in result:
            raise StateFailure("State JSON contains duplicate keys.")
        result[key] = value
    return result


@dataclass(frozen=True)
class JsonStore:
    directory: StateDirectory
    max_bytes: int = 1024 * 1024
    durable: bool = True
    private_files: bool = True

    def __post_init__(self) -> None:
        if self.max_bytes <= 0:
            raise ValueError("JSON size limit must be positive.")

    def read(self, name: str) -> JsonValue:
        with (
            self.directory.open() as directory,
            self.directory.file(directory, name, private=self.private_files) as source,
        ):
            if os.fstat(source).st_size > self.max_bytes:
                raise StateFailure("State JSON exceeds its size limit.")
            with os.fdopen(os.dup(source), "rb") as stream:
                payload = stream.read(self.max_bytes + 1)
            if len(payload) > self.max_bytes:
                raise StateFailure("State JSON exceeds its size limit.")
        try:
            decoded: object = json.loads(payload, object_pairs_hook=unique_object)
            return json_value(decoded)
        except (ValueError, RecursionError):
            raise StateFailure("State JSON is invalid.") from None

    def write(self, name: str, value: JsonValue) -> None:
        filename(name)
        try:
            payload = (json.dumps(json_value(value), indent=2, allow_nan=False) + "\n").encode()
        except (ValueError, RecursionError):
            raise StateFailure("State JSON is invalid.") from None
        if len(payload) > self.max_bytes:
            raise StateFailure("State JSON exceeds its size limit.")
        with self.directory.open() as directory:
            # Refuse replacing a symlink/special/foreign target, including dangling links.
            try:
                with self.directory.file(directory, name, private=self.private_files):
                    pass
            except FileNotFoundError:
                pass
            temporary = ".write-" + secrets.token_hex(16)
            descriptor = os.open(
                temporary,
                os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW | os.O_CLOEXEC,
                0o600,
                dir_fd=directory,
            )
            try:
                with os.fdopen(descriptor, "wb") as stream:
                    stream.write(payload)
                    stream.flush()
                    if self.durable:
                        os.fsync(stream.fileno())
                os.replace(temporary, name, src_dir_fd=directory, dst_dir_fd=directory)
                if self.durable:
                    os.fsync(directory)
            finally:
                try:
                    os.unlink(temporary, dir_fd=directory)
                except FileNotFoundError:
                    pass


@dataclass(frozen=True)
class FileLock:
    directory: StateDirectory
    name: str
    busy_message: str = "Another operation is running."
    shared: bool = False
    private: bool = True
    blocking: bool = False

    @contextmanager
    def acquire(self) -> Generator[None, None, None]:
        """Never truncate, replace or unlink the inode, including on failure."""
        with self.directory.open() as directory:
            with self.directory.file(
                directory,
                self.name,
                create=True,
                private=self.private,
            ) as descriptor:
                mode = fcntl.LOCK_SH if self.shared else fcntl.LOCK_EX
                try:
                    fcntl.flock(descriptor, mode | (0 if self.blocking else fcntl.LOCK_NB))
                except BlockingIOError:
                    raise StateFailure(self.busy_message) from None
                current = os.stat(self.name, dir_fd=directory, follow_symlinks=False)
                opened = regular_file(descriptor, private=self.private)
                if (current.st_dev, current.st_ino) != (opened.st_dev, opened.st_ino):
                    raise StateFailure("Lock identity changed; operation refused.")
                try:
                    yield
                finally:
                    fcntl.flock(descriptor, fcntl.LOCK_UN)
