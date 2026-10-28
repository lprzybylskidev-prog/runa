"""Safe command boundary: report fixed diagnostics, never exception payloads."""

from collections.abc import Callable
from dataclasses import dataclass, field
from typing import Protocol, TextIO


class SafeFailure(Exception):
    """Messages are caller-authored diagnostics, never captured child output."""

    def __init__(self, message: str, exit_code: int = 1) -> None:
        super().__init__(message)
        self.exit_code = exit_code


class Reporter(Protocol):
    def error(self, message: str) -> None: ...


@dataclass(frozen=True)
class TextReporter:
    stream: TextIO
    secrets: tuple[str, ...] = field(default=(), repr=False)

    def error(self, message: str) -> None:
        for secret in sorted(self.secrets, key=len, reverse=True):
            if secret:
                message = message.replace(secret, "[redacted]")
        print(message, file=self.stream, flush=True)


@dataclass(frozen=True)
class CommandResult:
    exit_code: int = 0


def command_boundary(action: Callable[[], CommandResult], reporter: Reporter) -> int:
    try:
        return action().exit_code
    except SafeFailure as error:
        reporter.error(str(error))
        return error.exit_code
    except KeyboardInterrupt:
        return 130
    except OSError:
        reporter.error("Operation unavailable; check access and required tools.")
        return 1
