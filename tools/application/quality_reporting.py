"""Quality state publication and compatible latest-run status rendering."""

from datetime import UTC, datetime
from pathlib import Path

from tools.application.quality_planning import show_scope
from tools.application.quality_state import RunRecord, run_record
from tools.core.state import JsonStore, StateDirectory, json_value


def store(directory: Path, anchor: Path | None = None) -> JsonStore:
    return JsonStore(
        StateDirectory(directory, private=False, anchor=anchor or directory.parent),
        max_bytes=16 * 1024 * 1024,
        private_files=False,
    )


def save(directory: Path, state: RunRecord, *, anchor: Path | None = None) -> None:
    store(directory, anchor).write("state.json", json_value(state))


def read_state(directory: Path, *, anchor: Path | None = None) -> RunRecord:
    return run_record(store(directory, anchor).read("state.json"))


def status(directory: Path, active: bool, *, anchor: Path | None = None) -> None:
    path = directory / "state.json"
    if not path.exists():
        print("No quality run recorded.")
        return
    state = read_state(directory, anchor=anchor)
    show_scope(state.get("selection", {"mode": "legacy"}))
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
        started_at = node.get("started_at")
        if elapsed is None and node["status"] == "running" and started_at:
            elapsed = max(
                0, (datetime.now(UTC) - datetime.fromisoformat(started_at)).total_seconds()
            )
        timing = f" ({elapsed:.1f}s)" if elapsed is not None else ""
        print(f"{key}: {node['status']}{timing}")
        log = directory / (key + ".log")
        if log.exists():
            print("\n".join(log.read_text(errors="replace").splitlines()[-30:]))
            if not active:
                print("Full log: " + str(log))
    duration = state.get("duration_seconds")
    if not active and duration is not None:
        print(f"Total elapsed: {duration:.1f}s (parallel stage durations overlap).")
    print("Process is still running." if active else "Overall status: " + state["status"])
