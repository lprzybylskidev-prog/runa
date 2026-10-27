"""Small bounded JSON transport for development-only internal services."""

import json
import time
import urllib.error
import urllib.request
from collections.abc import Callable
from typing import Protocol

from tools.core.state import JsonValue, json_value


class RequestError(RuntimeError):
    def __init__(self, message: str, status: int | None = None):
        super().__init__(message)
        self.status = status


class JsonTransport(Protocol):
    base: str

    def request(self, method: str, path: str, body: object = None) -> JsonValue: ...


class Http:
    def __init__(self, base: str, *, opener=None, sleep: Callable[[float], None] = time.sleep):
        self.base = base
        self.opener = (
            opener
            if opener is not None
            else urllib.request.build_opener(urllib.request.ProxyHandler({}))
        )
        self.sleep = sleep

    def request(self, method: str, path: str, body: object = None) -> JsonValue:
        for attempt in range(3):
            request = urllib.request.Request(
                self.base + path,
                method=method,
                data=None if body is None else json.dumps(body, allow_nan=False).encode(),
                headers={"Content-Type": "application/json"},
            )
            try:
                with self.opener.open(request, timeout=60) as response:
                    data = response.read(64 * 1024 * 1024 + 1)
                    if len(data) > 64 * 1024 * 1024:
                        raise RequestError("Knowledge service response exceeds the size limit.")
                    try:
                        return json_value(json.loads(data)) if data else None
                    except (ValueError, RecursionError):
                        raise RequestError("Knowledge service returned invalid JSON.") from None
            except urllib.error.HTTPError as error:
                # Never echo source text or service response bodies to logs.
                failure = RequestError(f"{self.base}{path}: HTTP {error.code}", error.code)
                error.close()
                if error.code not in (408, 429, 500, 502, 503, 504):
                    raise failure from error
            except (urllib.error.URLError, TimeoutError, ConnectionError):
                failure = RequestError(f"{self.base}{path}: service unavailable")
            if attempt == 2:
                raise failure
            self.sleep(0.25 * (2**attempt))
