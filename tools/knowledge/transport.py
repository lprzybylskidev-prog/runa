"""Small bounded JSON transport for development-only internal services."""
import json
import time
import urllib.error
import urllib.request


class RequestError(RuntimeError):
    def __init__(self, message, status=None):
        super().__init__(message)
        self.status = status


class Http:
    def __init__(self, base):
        self.base = base
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))

    def request(self, method, path, body=None):
        for attempt in range(3):
            request = urllib.request.Request(
                self.base + path, method=method,
                data=None if body is None else json.dumps(body, allow_nan=False).encode(),
                headers={'Content-Type': 'application/json'},
            )
            try:
                with self.opener.open(request, timeout=60) as response:
                    data = response.read()
                    return json.loads(data) if data else None
            except urllib.error.HTTPError as error:
                # Never echo source text or service response bodies to logs.
                failure = RequestError(f'{self.base}{path}: HTTP {error.code}', error.code)
                if error.code not in (408, 429, 500, 502, 503, 504):
                    raise failure from error
            except (urllib.error.URLError, TimeoutError, ConnectionError) as error:
                failure = RequestError(f'{self.base}{path}: service unavailable')
            if attempt == 2:
                raise failure
            time.sleep(.25 * (2 ** attempt))
