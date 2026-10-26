"""Immutable identities; installation and environment policy belongs to callers."""

from dataclasses import dataclass
from enum import Enum
from pathlib import Path


def absolute_path(path: Path) -> Path:
    if not path.is_absolute() or ".." in path.parts:
        raise ValueError("Context paths must be absolute without parent traversal.")
    return path


@dataclass(frozen=True)
class WorkspaceContext:
    root: Path

    def __post_init__(self) -> None:
        absolute_path(self.root)

    def runtime(self, area: str) -> Path:
        if not area or area in (".", "..") or "/" in area or "\\" in area:
            raise ValueError("Runtime area must be a single directory name.")
        return self.root / ".runtime" / area


class ExecutionLocation(Enum):
    HOST = "host"
    CONTAINER = "container"


@dataclass(frozen=True)
class ExecutionContext:
    tools: WorkspaceContext
    target: WorkspaceContext
    location: ExecutionLocation


@dataclass(frozen=True)
class ReleaseContext:
    operator: WorkspaceContext
    source: WorkspaceContext
    revision: str

    def __post_init__(self) -> None:
        if not self.revision or any(character in self.revision for character in "\0\r\n"):
            raise ValueError("Release revision requires an explicit identity.")


@dataclass(frozen=True)
class ComposeContext:
    workspace: WorkspaceContext
    files: tuple[Path, ...]
    env_file: Path
    project: str | None = None

    def __post_init__(self) -> None:
        if not self.files:
            raise ValueError("Compose requires explicit configuration files.")
        for path in (*self.files, self.env_file):
            absolute_path(path)
        if self.project is not None and not self.project:
            raise ValueError("Compose project must not be empty.")

    def command(self, *arguments: str) -> tuple[str, ...]:
        project = ("--project-name", self.project) if self.project is not None else ()
        files = tuple(argument for path in self.files for argument in ("-f", str(path)))
        return (
            "docker",
            "compose",
            *project,
            "--project-directory",
            str(self.workspace.root),
            "--env-file",
            str(self.env_file),
            *files,
            *arguments,
        )
