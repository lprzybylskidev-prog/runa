import os
from pathlib import Path

import pytest

from tools.core.state import FileLock, JsonStore, StateDirectory, StateFailure


def test_atomic_private_json_is_bounded_and_preserves_previous_on_failure(tmp_path: Path) -> None:
    directory = StateDirectory(tmp_path / "private", anchor=tmp_path)
    store = JsonStore(directory, max_bytes=128)
    store.write("journal.json", {"phase": "prepared", "raw": "$literal"})
    assert store.read("journal.json") == {"phase": "prepared", "raw": "$literal"}
    assert (directory.path / "journal.json").stat().st_mode & 0o777 == 0o600
    with pytest.raises(StateFailure, match="size"):
        store.write("journal.json", {"large": "x" * 200})
    assert store.read("journal.json") == {"phase": "prepared", "raw": "$literal"}
    assert not list(directory.path.glob(".write-*"))


def test_write_failure_keeps_old_state_and_removes_only_own_temporary(
    tmp_path: Path,
    monkeypatch: pytest.MonkeyPatch,
) -> None:
    store = JsonStore(StateDirectory(tmp_path / "private", anchor=tmp_path))
    store.write("journal.json", {"phase": "prepared"})
    original = os.replace

    def refuse(*_args: object, **_kwargs: object) -> None:
        raise OSError("injected publication failure")

    monkeypatch.setattr(os, "replace", refuse)
    with pytest.raises(OSError, match="injected"):
        store.write("journal.json", {"phase": "completed"})
    monkeypatch.setattr(os, "replace", original)
    assert store.read("journal.json") == {"phase": "prepared"}
    assert not list(store.directory.path.glob(".write-*"))


@pytest.mark.parametrize("kind", ["symlink", "hardlink", "fifo", "public", "oversized", "invalid"])
def test_json_refuses_unsafe_files(tmp_path: Path, kind: str) -> None:
    root = tmp_path / "private"
    root.mkdir(mode=0o700)
    target = root / "journal.json"
    original = tmp_path / "preserved.json"
    original.write_text('{"preserved": true}')
    if kind == "symlink":
        target.symlink_to(original)
    elif kind == "hardlink":
        target.hardlink_to(original)
    elif kind == "fifo":
        os.mkfifo(target, mode=0o600)
    else:
        target.write_text("x" * 200 if kind == "oversized" else '{"a": 1, "a": 2}')
        target.chmod(0o644 if kind == "public" else 0o600)
    store = JsonStore(StateDirectory(root, anchor=tmp_path), max_bytes=128)
    with pytest.raises((StateFailure, OSError)):
        store.read("journal.json")
    if kind in ("symlink", "hardlink", "fifo", "public"):
        with pytest.raises((StateFailure, OSError)):
            store.write("journal.json", None)
    assert original.read_text() == '{"preserved": true}'


def test_state_refuses_ancestor_symlinks_and_escaping_names(tmp_path: Path) -> None:
    real = tmp_path / "real"
    real.mkdir()
    link = tmp_path / "linked"
    link.symlink_to(real, target_is_directory=True)
    with pytest.raises(OSError), StateDirectory(link / "state", anchor=tmp_path).open():
        pytest.fail("Symlink ancestor was accepted.")
    assert not (real / "state").exists()
    with pytest.raises(StateFailure):
        JsonStore(StateDirectory(tmp_path / "private", anchor=tmp_path)).write("../escape", None)


def test_state_refuses_writable_ancestor_before_creating_private_state(tmp_path: Path) -> None:
    ancestor = tmp_path / "unsafe"
    ancestor.mkdir(mode=0o777)
    ancestor.chmod(0o777)
    with (
        pytest.raises(StateFailure, match="ancestors"),
        StateDirectory(ancestor / "state", anchor=tmp_path).open(),
    ):
        pytest.fail("Writable ancestor was accepted.")
    assert not (ancestor / "state").exists()


def test_explicit_workspace_anchor_allows_group_writable_checkout(tmp_path: Path) -> None:
    workspace = tmp_path / "workspace"
    workspace.mkdir(mode=0o775)
    workspace.chmod(0o775)
    store = JsonStore(StateDirectory(workspace / ".runtime/private", anchor=workspace))
    store.write("journal.json", {"phase": "prepared"})
    assert store.read("journal.json") == {"phase": "prepared"}
    assert workspace.stat().st_mode & 0o777 == 0o775
    runtime = workspace / ".runtime"
    runtime.chmod(0o777)
    with pytest.raises(StateFailure, match="ancestors"):
        store.read("journal.json")


def test_lock_contention_failure_and_reacquisition_preserve_inode(tmp_path: Path) -> None:
    lock = FileLock(StateDirectory(tmp_path / "private", anchor=tmp_path), "operation.lock")
    identity: int | None = None
    with pytest.raises(RuntimeError, match="injected"), lock.acquire():
        identity = (lock.directory.path / lock.name).stat().st_ino
        with pytest.raises(StateFailure, match="Another operation"), lock.acquire():
            pytest.fail("Contended lock was acquired.")
        raise RuntimeError("injected")
    assert identity is not None
    with lock.acquire():
        assert (lock.directory.path / lock.name).stat().st_ino == identity


def test_native_suite_readable_lock_is_explicit_policy(tmp_path: Path) -> None:
    path = tmp_path / "suite.lock"
    path.write_text("")
    path.chmod(0o644)
    directory = StateDirectory(tmp_path, private=False, anchor=tmp_path)
    with pytest.raises(StateFailure), FileLock(directory, path.name).acquire():
        pytest.fail("Private lock accepted readable mode.")
    with FileLock(directory, path.name, private=False).acquire():
        assert path.stat().st_mode & 0o777 == 0o644
    path.chmod(0o666)
    with pytest.raises(StateFailure), FileLock(directory, path.name, private=False).acquire():
        pytest.fail("Writable lock was accepted.")
