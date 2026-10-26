"""Quality state publication and compatible latest-run status rendering."""

import re
from datetime import UTC, datetime

from tools.application.quality_planning import show_scope
from tools.core.state import JsonStore, StateDirectory, json_value


def store(directory, anchor=None):
    return JsonStore(
        StateDirectory(directory, private=False, anchor=anchor or directory.parent),
        max_bytes=16 * 1024 * 1024,
        private_files=False,
    )


def save(directory, state, *, anchor=None):
    store(directory, anchor).write("state.json", json_value(state))


def read_state(directory, *, anchor=None):
    state = store(directory, anchor).read("state.json")
    if not isinstance(state, dict) or not isinstance(state.get("steps"), dict):
        raise ValueError("Invalid quality state.")
    if state.get("status") not in ("running", "passed", "failed", "interrupted"):
        raise ValueError("Invalid quality run status.")
    for key, node in state["steps"].items():
        if not re.fullmatch(r"[a-z0-9-]+", key) or not isinstance(node, dict):
            raise ValueError("Invalid quality step state.")
        if node.get("status") not in (
            "pending",
            "running",
            "passed",
            "failed",
            "blocked",
            "interrupted",
        ):
            raise ValueError("Invalid quality step status.")
        pid = node.get("pid")
        if pid is not None and (type(pid) is not int or pid <= 0):
            raise ValueError("Invalid quality child identity.")
    return state


def status(directory, active, *, anchor=None):
    path = directory / "state.json"
    if not path.exists():
        print("No quality run recorded.")
        return
    state = read_state(directory, anchor=anchor)
    show_scope(state.get("selection", {}))
    if state["status"] == "running" and not active:
        state["status"] = "interrupted"
        for node in state["steps"].values():
            if node["status"] == "running":
                node["status"] = "interrupted"
        save(directory, state, anchor=anchor)
    for key, node in state["steps"].items():
        if active and node["status"] != "running":
            continue
        elapsed = node.get("duration_seconds")
        if elapsed is None and node["status"] == "running" and node.get("started_at"):
            elapsed = max(
                0, (datetime.now(UTC) - datetime.fromisoformat(node["started_at"])).total_seconds()
            )
        timing = f" ({elapsed:.1f}s)" if elapsed is not None else ""
        print(f"{key}: {node['status']}{timing}")
        log = directory / (key + ".log")
        if log.exists():
            print("\n".join(log.read_text(errors="replace").splitlines()[-30:]))
            if not active:
                print("Full log: " + str(log))
    if not active and state.get("duration_seconds") is not None:
        print(
            f"Total elapsed: {state['duration_seconds']:.1f}s (parallel stage durations overlap)."
        )
    print("Process is still running." if active else "Overall status: " + state["status"])
