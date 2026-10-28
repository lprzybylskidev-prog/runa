"""Scoped cancellation and signal ownership; no import-time handlers."""

import signal
from collections.abc import Generator
from contextlib import contextmanager
from threading import Event


class Cancellation:
    def __init__(self) -> None:
        self._requested = Event()

    def cancel(self) -> None:
        self._requested.set()

    def checkpoint(self) -> None:
        if self._requested.is_set():
            raise KeyboardInterrupt


def interrupted(_signum: int, _frame: object) -> None:
    raise KeyboardInterrupt


@contextmanager
def interrupt_signals() -> Generator[None, None, None]:
    """The command's main thread owns SIGTERM only for this scope."""
    previous = signal.signal(signal.SIGTERM, interrupted)
    try:
        yield
    finally:
        signal.signal(signal.SIGTERM, previous)
