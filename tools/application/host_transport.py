"""Workspace-owned host executor service and explicit process boundary."""

import base64
import contextlib
import fcntl
import hmac
import json
import os
import re
import secrets
import selectors
import signal
import socket
import socketserver
import struct
import subprocess
import sys
import threading
import time
from collections.abc import Callable
from dataclasses import dataclass
from pathlib import Path

from tools.application.development_runtime import execution_location
from tools.application.management_service import ManagementService
from tools.application.processes import ApplicationProcesses
from tools.application.repository_boundary import DevelopmentContextError
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.lifecycle import interrupt_signals
from tools.core.process import SubprocessRunner
from tools.core.reporting import SafeFailure
from tools.core.state import JsonStore, StateDirectory

LIMIT = 65536
OUTPUT_LIMIT = 64 * 1024 * 1024


def encode(value):
    return json.dumps(value, separators=(",", ":")).encode() + b"\n"


def read_json(stream):
    raw = stream.readline(LIMIT + 1)
    if not raw or len(raw) > LIMIT or not raw.endswith(b"\n"):
        raise ValueError("Missing or oversized transport frame.")

    def unique(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError("Duplicate transport field.")
            result[key] = value
        return result

    return json.loads(raw, object_pairs_hook=unique)


def validate_request(value, credentials, challenge):
    if (
        not isinstance(value, dict)
        or set(value)
        != {
            "version",
            "workspace",
            "token",
            "challenge",
            "id",
            "command",
            "arguments",
            "interactive",
        }
        or type(value["version"]) is not int
        or value["version"] != 1
        or not isinstance(value["token"], str)
        or not hmac.compare_digest(value["token"], credentials["token"])
        or value["workspace"] != credentials["workspace"]
        or value["challenge"] != challenge
        or not isinstance(value["id"], str)
        or not re.fullmatch("[a-f0-9]{32}", value["id"])
        or not isinstance(value["command"], str)
        or not re.fullmatch("runa:[a-z][a-z0-9-]*(?::[a-z][a-z0-9-]*)*", value["command"])
        or type(value["interactive"]) is not bool
        or not isinstance(value["arguments"], list)
        or len(value["arguments"]) > 128
        or any(
            not isinstance(arg, str) or len(arg.encode()) > 4096 or "\0" in arg
            for arg in value["arguments"]
        )
    ):
        raise ValueError("Unauthorized or invalid workspace request.")
    if any(
        arg == "--host-request"
        or arg.startswith("--host-request=")
        or arg in ("--plan", "--help", "-h", "--")
        for arg in value["arguments"]
    ):
        raise ValueError("Transport-only options are forbidden in execution requests.")
    return value


class Server(socketserver.ThreadingUnixStreamServer):
    daemon_threads = False
    block_on_close = True

    def __init__(self, *args, **kwargs):
        self.max_children = threading.BoundedSemaphore(8)
        super().__init__(*args, **kwargs)


class Handler(socketserver.StreamRequestHandler):
    def handle(self):
        if not self.server.max_children.acquire(blocking=False):
            return
        try:
            self.execute()
        except (ValueError, OSError, KeyError, TypeError, subprocess.SubprocessError, SafeFailure):
            try:
                self.wfile.write(
                    encode(
                        {
                            "type": "error",
                            "message": "Host request rejected or unavailable. Inspect private host receipts; use ./bin/artisan --recover-host if required.",
                        }
                    )
                )
            except OSError:
                pass
        finally:
            self.server.max_children.release()

    def execute(self):
        self.connection.settimeout(15)
        _, uid, _ = struct.unpack(
            "3i", self.connection.getsockopt(socket.SOL_SOCKET, socket.SO_PEERCRED, 12)
        )
        if uid != os.getuid():
            raise ValueError("Foreign operating-system user.")
        challenge = secrets.token_hex(32)
        self.wfile.write(encode({"type": "challenge", "value": challenge}))
        value = validate_request(read_json(self.rfile), self.server.credentials, challenge)
        if self.server.service.management.image_identity() != self.server.identity:
            raise ValueError(
                "Management source changed. Recover the host executor before execution."
            )
        self.connection.settimeout(None)
        request = value["id"]
        receipt = self.server.service.directory() / "operations" / (request + ".json")
        descriptor = os.open(receipt, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600)
        state = {
            "id": request,
            "command": value["command"],
            "state": "validating",
            "started_at": time.time(),
            "connected": True,
        }
        with os.fdopen(descriptor, "w") as stream:
            json.dump(state, stream)
        try:
            plan = self.server.service.validated_plan(value, self.server.image)
            if value["command"] in ("runa:host:status", "runa:host:stop"):
                args = [value["command"].rsplit(":", 1)[1], *value["arguments"]]
                if args[:1] == ["status"]:
                    result = self.server.public_status(args[1] if len(args) == 2 else None)
                    self.wfile.write(
                        encode(
                            {
                                "type": "stdout",
                                "data": base64.b64encode(
                                    (json.dumps(result, indent=2) + "\n").encode()
                                ).decode(),
                            }
                        )
                    )
                elif args == ["stop"]:
                    threading.Thread(target=self.server.shutdown, daemon=True).start()
                else:
                    raise ValueError("Invalid executor control.")
                self.wfile.write(encode({"type": "exit", "code": 0}))
                state.update(state="completed", exit_code=0)
            else:
                state.update(state="running")
                self.server.service.atomic(receipt, state)
                self.wfile.write(encode({"type": "accepted", "id": request}))
                self.run_operation(plan, value["interactive"], state, receipt)
        except BaseException:
            if state["state"] in ("validating", "running"):
                state.update(state="failed", exit_code=1)
            raise
        finally:
            state["finished_at"] = time.time()
            self.server.service.atomic(receipt, state)

    def run_operation(self, plan, interactive, state, receipt):
        # Fresh process owns existing locking/executors. Its stdin contains one
        # trusted plan frame followed by explicit operator input, never shell code.
        command = [sys.executable, "-m", "tools.application.host_transport", "--worker"]
        child = self.server.service.spawn(
            command,
            cwd=self.server.service.workspace.root,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            start_new_session=True,
            env=self.server.environment,
        )
        child.stdin.write(encode(plan))
        child.stdin.flush()
        if not interactive:
            child.stdin.close()
        connected = True
        total = 0
        log = self.server.service.directory() / "operations" / (plan["request_id"] + ".log")
        with (
            open(log, "xb", opener=lambda p, flags: os.open(p, flags, 0o600)) as journal,
            selectors.DefaultSelector() as selector,
        ):
            selector.register(child.stdout, selectors.EVENT_READ, "stdout")
            selector.register(child.stderr, selectors.EVENT_READ, "stderr")
            selector.register(self.connection, selectors.EVENT_READ, "client")
            client_buffer = bytearray()
            try:
                while any(key.data != "client" for key in selector.get_map().values()):
                    for key, _ in selector.select(timeout=1):
                        if key.data == "client":
                            raw = self.connection.recv(4096)
                            if not raw:
                                selector.unregister(self.connection)
                                connected = False
                                state["connected"] = False
                                self.server.service.atomic(receipt, state)
                                if not child.stdin.closed:
                                    child.stdin.close()
                                continue
                            client_buffer.extend(raw)
                            if len(client_buffer) > LIMIT:
                                raise ValueError("Input frame exceeds bound.")
                            while b"\n" in client_buffer:
                                line, _, rest = client_buffer.partition(b"\n")
                                client_buffer = bytearray(rest)
                                message = json.loads(line)
                                if message == {"type": "cancel"}:
                                    os.killpg(child.pid, signal.SIGINT)
                                    state["cancel_requested"] = True
                                elif (
                                    set(message) == {"type", "data"}
                                    and message["type"] == "stdin"
                                    and interactive
                                ):
                                    content = base64.b64decode(message["data"], validate=True)
                                    if len(content) > 4096 or child.stdin.closed:
                                        raise ValueError("Invalid interactive input.")
                                    child.stdin.write(content)
                                    child.stdin.flush()
                                elif message == {"type": "eof"}:
                                    if not child.stdin.closed:
                                        child.stdin.close()
                                else:
                                    raise ValueError("Invalid input frame.")
                        else:
                            raw = os.read(key.fileobj.fileno(), 4096)
                            if not raw:
                                selector.unregister(key.fileobj)
                                continue
                            total += len(raw)
                            if total > OUTPUT_LIMIT:
                                raise ValueError("Operation output exceeds the private log bound.")
                            journal.write(
                                encode({"stream": key.data, "data": base64.b64encode(raw).decode()})
                            )
                            if connected:
                                try:
                                    self.wfile.write(
                                        encode(
                                            {
                                                "type": key.data,
                                                "data": base64.b64encode(raw).decode(),
                                            }
                                        )
                                    )
                                except OSError:
                                    connected = False
                                    state["connected"] = False
                code = child.wait()
                state.update(state="completed" if code == 0 else "failed", exit_code=code)
                if connected:
                    try:
                        self.wfile.write(
                            encode({"type": "exit", "code": code if code >= 0 else 128 - code})
                        )
                    except OSError:
                        state["connected"] = False
            except BaseException:
                if child.poll() is None:
                    os.killpg(child.pid, signal.SIGTERM)
                    try:
                        child.wait(timeout=10)
                    except subprocess.TimeoutExpired:
                        os.killpg(child.pid, signal.SIGKILL)
                        child.wait()
                state.update(state="failed", exit_code=1, inspect_recovery=True)
                raise


@dataclass(frozen=True)
class HostExecutor:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    spawn: Callable = subprocess.Popen
    location: ExecutionLocation = ExecutionLocation.HOST

    @property
    def management(self):
        return ManagementService(
            self.workspace, self.processes, ExecutionLocation.HOST, self.browser_endpoint
        )

    def browser_endpoint(self):
        from tools.application.test_resources import endpoint

        if self.workspace.root != Path(__file__).resolve().parents[2]:
            raise ValueError("Browser endpoints require the selected tool workspace.")
        return endpoint()

    @contextlib.contextmanager
    def socket_path(self):
        descriptor = os.open(self.directory(), os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
        try:
            yield "/proc/self/fd/" + str(descriptor) + "/executor.sock"
        finally:
            os.close(descriptor)

    def directory(self):
        return self.workspace.root / ".runtime/host"

    def connect(self, client):
        # /proc/self/fd avoids the Unix pathname limit for long host checkout paths;
        # the client container uses its short mounted /workspace or /run path.
        descriptor = os.open(self.directory(), os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
        try:
            client.connect("/proc/self/fd/" + str(descriptor) + "/executor.sock")
        finally:
            os.close(descriptor)

    def atomic(self, path, value):
        JsonStore(StateDirectory(path.parent, anchor=self.workspace.root)).write(path.name, value)

    def validated_plan(self, value, image):
        # Reparse caller arguments in the trusted isolated Artisan registry. Do not
        # execute a caller-supplied plan or trust container-side argument validation.
        request = value["id"]
        command = [
            "docker",
            "run",
            "--rm",
            "--name",
            "runa-host-validate-" + request,
            "--network",
            "none",
            "--read-only",
            "--cap-drop=ALL",
            "--security-opt=no-new-privileges:true",
            "--memory=128m",
            "--cpus=1",
            "--pids-limit=128",
            "--tmpfs",
            "/tmp:rw,nosuid,nodev,noexec,size=16m",
            image,
            "--management",
            value["command"],
            *value["arguments"],
            "--plan",
            "--host-request=" + request,
        ]
        try:
            code, output, _ = self.management.bounded_output(command)
        finally:
            self.processes.run(
                ["docker", "rm", "--force", "runa-host-validate-" + request],
                cwd=self.workspace.root,
                capture_output=True,
                timeout=30,
            )
        if code:
            raise ValueError("Artisan rejected this operation or its arguments.")
        return self.management.validate_plan(output, request)

    def serve(self):
        self.management.private_directory(self.directory())
        self.management.private_directory(self.directory() / "operations")
        descriptor = os.open(
            self.directory() / "daemon.lock", os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600
        )
        with os.fdopen(descriptor, "w") as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            image = self.management.ensure_image()
            endpoint = self.directory() / "executor.sock"
            if endpoint.is_symlink():
                raise ValueError("Host socket must not be a symlink.")
            if endpoint.exists():
                endpoint.unlink()
            credentials = {
                "version": 1,
                "workspace": secrets.token_hex(32),
                "token": secrets.token_hex(32),
                "pid": os.getpid(),
                "identity": self.management.image_identity(),
            }
            # Capture only administrator-chosen startup context; no client env/cwd.
            environment = dict(self.processes.environment)
            for name in list(environment):
                if name.startswith(("GIT_", "COMPOSE_", "DOCKER_")):
                    environment.pop(name)
            environment["RUNA_HOST_WORKER"] = "1"
            with self.socket_path() as socket_path, Server(socket_path, Handler) as server:
                server.service = self
                os.chmod(endpoint, 0o600)
                server.credentials, server.image, server.identity, server.environment = (
                    credentials,
                    image,
                    self.management.image_identity(),
                    environment,
                )

                def status(request=None):
                    if request:
                        if not re.fullmatch("[a-f0-9]{32}", request):
                            raise ValueError("Invalid operation ID.")
                        return json.loads(
                            (self.directory() / "operations" / (request + ".json")).read_text()
                        )
                    return {
                        "state": "ready",
                        "workspace": credentials["workspace"],
                        "pid": os.getpid(),
                        "operations": [
                            json.loads(path.read_text())
                            for path in sorted((self.directory() / "operations").glob("*.json"))[
                                -50:
                            ]
                        ],
                    }

                server.public_status = status
                self.atomic(self.directory() / "client.json", credentials)

                def stop(signum, frame):
                    threading.Thread(target=server.shutdown, daemon=True).start()

                signal.signal(signal.SIGTERM, stop)
                signal.signal(signal.SIGINT, stop)
                try:
                    server.serve_forever(poll_interval=0.2)
                finally:
                    # Wait for in-flight mutation workers; no implicit retries/cancellation.
                    server.server_close()
                    endpoint.unlink(missing_ok=True)
                    (self.directory() / "client.json").unlink(missing_ok=True)

    def ensure(self):
        if self.location is ExecutionLocation.CONTAINER:
            raise ValueError("Host executor recovery must run on the native host.")
        self.management.private_directory(self.directory())
        try:
            with socket.socket(socket.AF_UNIX) as client:
                client.settimeout(1)
                self.connect(client)
                response = read_json(client.makefile("rb"))
                if response.get("type") == "challenge":
                    info = json.loads((self.directory() / "client.json").read_text())
                    # Pin source identity too: an old daemon must be deliberately stopped
                    # before a changed command registry can acquire host privileges.
                    if (
                        info.get("identity")
                        and info["identity"] != self.management.image_identity()
                    ):
                        raise ValueError("Executor registry changed; stop it before recovery.")
                    return
        except (OSError, ValueError, KeyError):
            # A live lock disambiguates stale filesystem state from a running daemon.
            descriptor = os.open(
                self.directory() / "daemon.lock", os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600
            )
            with os.fdopen(descriptor, "w") as lock:
                try:
                    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                except BlockingIOError:
                    raise ValueError(
                        "Host executor is running but unavailable/stale; stop it deliberately before recovery."
                    ) from None
        with open(
            self.directory() / "daemon.log", "ab", opener=lambda p, flags: os.open(p, flags, 0o600)
        ) as log:
            self.spawn(
                [sys.executable, "-m", "tools.application.host_transport", "--serve"],
                cwd=self.workspace.root,
                stdin=subprocess.DEVNULL,
                stdout=log,
                stderr=log,
                start_new_session=True,
            )
        for _ in range(120):
            try:
                with socket.socket(socket.AF_UNIX) as client:
                    client.settimeout(1)
                    self.connect(client)
                    if (
                        read_json(client.makefile("rb")).get("type") == "challenge"
                        and (self.directory() / "client.json").is_file()
                    ):
                        return
            except (OSError, ValueError):
                pass
            time.sleep(0.5)
        raise ValueError(
            "Host executor did not become ready. Inspect its private daemon.log and recover explicitly."
        )

    def control(self, arguments):
        """Recovery-only native client, using the same authenticated protocol."""
        credentials = json.loads((self.directory() / "client.json").read_text())
        with socket.socket(socket.AF_UNIX) as client:
            self.connect(client)
            stream = client.makefile("rwb", buffering=0)
            challenge = read_json(stream)
            stream.write(
                encode(
                    {
                        "version": 1,
                        "workspace": credentials["workspace"],
                        "token": credentials["token"],
                        "challenge": challenge["value"],
                        "id": secrets.token_hex(16),
                        "command": "runa:host:" + arguments[0],
                        "arguments": arguments[1:],
                        "interactive": False,
                    }
                )
            )
            while True:
                frame = read_json(stream)
                if frame["type"] == "exit":
                    return frame["code"]
                if frame["type"] == "error":
                    raise ValueError("Host executor rejected control; inspect private receipts.")
                if frame["type"] in ("stdout", "stderr"):
                    target = sys.stdout.buffer if frame["type"] == "stdout" else sys.stderr.buffer
                    target.write(base64.b64decode(frame["data"], validate=True))
                    target.flush()

    def stop(self):
        """Graceful recovery even when the old registry cannot validate new commands."""
        descriptor = os.open(self.directory() / "daemon.lock", os.O_RDWR | os.O_NOFOLLOW)
        with os.fdopen(descriptor, "w") as lock:
            try:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                return
            except BlockingIOError:
                credentials = json.loads((self.directory() / "client.json").read_text())
                pid = credentials["pid"]
                cmdline = Path("/proc", str(pid), "cmdline").read_bytes().split(b"\0")
                # Recover the exact previous release path after the approved tooling relocation.
                scripts = (
                    self.workspace.root / "tools/application/host_executor.py",
                    self.workspace.root / "tools/project/host_executor.py",
                )
                if (
                    Path("/proc", str(pid)).stat().st_uid != os.getuid()
                    or not (
                        any(os.fsencode(path) in cmdline for path in scripts)
                        or b"tools.application.host_transport" in cmdline
                    )
                    or b"--serve" not in cmdline
                ):
                    raise ValueError(
                        "Executor PID ownership is uncertain; inspect it before recovery."
                    )
                os.kill(pid, signal.SIGTERM)
        for _ in range(100):
            if not (self.directory() / "client.json").exists():
                return
            time.sleep(0.1)
        raise ValueError(
            "Host executor is draining active operations; inspect receipts before restarting."
        )


def default_service():
    return HostExecutor(
        WorkspaceContext(Path(__file__).resolve().parents[2]),
        ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
        location=execution_location(),
    )


def main(arguments=None):
    service = default_service()
    arguments = sys.argv[1:] if arguments is None else arguments
    try:
        if arguments == ["--serve"]:
            service.serve()
        elif arguments == ["--worker"] and os.environ.get("RUNA_HOST_WORKER") == "1":
            plan = read_json(sys.stdin.buffer)
            with interrupt_signals():
                raise SystemExit(service.management.execute_plan(plan, plan["request_id"]))
        elif arguments[:1] == ["--control"]:
            raise SystemExit(service.control(arguments[1:]))
        else:
            raise ValueError(
                "Internal executor entrypoint; use Artisan or host recovery transport."
            )
    except KeyboardInterrupt:
        print(
            "Host operation interrupted. Inspect its receipt and existing recovery markers before retrying.",
            file=sys.stderr,
        )
        raise SystemExit(130)
    except DevelopmentContextError:
        print(
            "Development tooling is blocked in production or unknown installation context.",
            file=sys.stderr,
        )
        raise SystemExit(2)
    except (ValueError, OSError, KeyError, TypeError, subprocess.SubprocessError, SafeFailure):
        print(
            "Host execution failed. Inspect the private operation receipt; do not retry a mutation blindly.",
            file=sys.stderr,
        )
        raise SystemExit(1)


if __name__ == "__main__":
    main()
