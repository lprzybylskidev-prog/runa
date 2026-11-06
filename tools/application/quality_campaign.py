"""Explicit quality input proofs and retained native attempts; no execution policy."""

from __future__ import annotations

import fnmatch
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import uuid
from collections.abc import Mapping
from pathlib import Path
from typing import TypedDict

from tools.application.quality_models import QualityGraph
from tools.application.quality_reporting import read_state
from tools.application.quality_state import RunRecord
from tools.core.state import JsonStore, StateDirectory, json_value


class NodeProof(TypedDict):
    identity: str
    dependencies: dict[str, str]


SHARED_INPUTS = (
    "tools/core/**",
    "tools/application/quality*.py",
    "tools/application/testing/**",
    "tools/maintainer/quality*",
    "tools/maintainer/test_cli.py",
    "src/composer.lock",
    "src/pnpm-lock.yaml",
    "src/composer.json",
    "src/package.json",
    "src/vendor/composer/installed.json",
    "src/node_modules/.modules.yaml",
    "src/runa/Testing/**",
    "src/runa/testing/**",
    ".env",
    ".env.testing",
)


def digest(value: object) -> str:
    return hashlib.sha256(
        json.dumps(value, sort_keys=True, separators=(",", ":")).encode()
    ).hexdigest()


def _content(root: Path, name: str) -> str:
    path = root / name
    if not path.is_relative_to(root) or any(p == ".." for p in path.parts):
        raise ValueError("Quality input must remain inside its workspace.")
    for ancestor in (path, *path.parents):
        if ancestor == root.parent:
            break
        if ancestor.is_symlink():
            raise ValueError("Quality inputs cannot traverse symlinks.")
    if not path.exists():
        return "missing"
    if not path.is_file():
        raise ValueError("Quality input must be a regular file.")
    return hashlib.sha256(path.read_bytes()).hexdigest()


def input_proofs(
    root: Path, nodes: QualityGraph, environment: Mapping[str, str]
) -> dict[str, NodeProof]:
    """Hash reviewed inputs, including additions/deletions and private config digests."""
    names = (
        subprocess.check_output(
            ["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z"], cwd=root
        )
        .decode()
        .split("\0")
    )
    names = sorted(set(filter(None, names)))
    shared: dict[str, str] = {}
    for pattern in SHARED_INPUTS:
        selected = [name for name in names if fnmatch.fnmatchcase(name, pattern)]
        if not any(character in pattern for character in "*?["):
            selected.append(pattern)
        shared.update({name: _content(root, name) for name in selected})
    runtime = {"python": sys.version}
    for binary in ("php", "node", "pnpm"):
        location = shutil.which(binary)
        runtime[binary] = (
            hashlib.sha256(Path(location).resolve().read_bytes()).hexdigest()
            if location
            else "missing"
        )
    proofs: dict[str, NodeProof] = {}
    pending = set(nodes)
    while pending:
        ready = sorted(key for key in pending if set(nodes[key]["depends_on"]) <= proofs.keys())
        if not ready:
            raise ValueError("Quality input graph has unresolved dependencies.")
        for key in ready:
            node = nodes[key]
            declaration = node.get("reuse")
            values: dict[str, str] = {}
            env: dict[str, str | None] = {}
            if declaration is not None:
                for pattern in declaration["paths"]:
                    selected = [name for name in names if fnmatch.fnmatchcase(name, pattern)]
                    if not any(character in pattern for character in "*?["):
                        selected.append(pattern)
                    values.update({name: _content(root, name) for name in selected})
                # Values are only hash inputs; serialized proof contains no environment values.
                env = {key: environment.get(key) for key in declaration["environment"]}
            proofs[key] = {
                "identity": digest(
                    {
                        "graph": nodes,
                        "node": node,
                        "shared": shared,
                        "runtime": runtime,
                        "inputs": values,
                        "environment": env,
                    }
                ),
                "dependencies": {
                    dependency: proofs[dependency]["identity"]
                    if nodes[dependency].get("reuse") is not None
                    and "unknown" not in proofs[dependency]["dependencies"].values()
                    else "unknown"
                    for dependency in node["depends_on"]
                },
            }
            pending.remove(key)
    return proofs


def archive(directory: Path, root: Path) -> tuple[str, RunRecord] | None:
    """Snapshot known native evidence before overwriting latest; unknown files stay put."""
    if not (directory / "state.json").exists():
        return None
    if (directory / "manifest.json").exists():
        verify_evidence(directory, root)
    state = read_state(directory, anchor=root)
    identifier = uuid.uuid4().hex
    destination = directory / "attempts" / identifier
    source = StateDirectory(directory, private=False, anchor=root)
    target = StateDirectory(destination, private=False, anchor=root)
    hashes: dict[str, str] = {}
    with source.open() as incoming, target.open() as outgoing:
        for name in [
            "state.json",
            "proof.json",
            "runner.log",
            *(key + ".log" for key in state["steps"]),
        ]:
            if not (directory / name).exists():
                continue
            with source.file(incoming, name, private=False) as descriptor:
                digestor = hashlib.sha256()
                with target.file(outgoing, name, create=True, private=False) as output:
                    while chunk := os.read(descriptor, 1024 * 1024):
                        digestor.update(chunk)
                        view = memoryview(chunk)
                        while view:
                            view = view[os.write(output, view) :]
                    os.fsync(output)
            hashes[name] = digestor.hexdigest()
    JsonStore(target, private_files=False).write(
        "manifest.json", json_value({"schema_version": 1, "files": hashes})
    )
    return identifier, state


def verify_evidence(evidence: Path, root: Path) -> dict[str, str]:
    manifest = JsonStore(
        StateDirectory(evidence, private=False, anchor=root), private_files=False
    ).read("manifest.json")
    if (
        not isinstance(manifest, dict)
        or manifest.get("schema_version") != 1
        or not isinstance(manifest.get("files"), dict)
    ):
        raise ValueError("Missing archived quality evidence manifest.")
    files: dict[str, str] = {}
    for name, expected in manifest["files"].items():
        if (
            not isinstance(expected, str)
            or not re.fullmatch(r"(?:state|proof)\.json|runner\.log|[a-z0-9-]+\.log", name)
            or _content(evidence, name) != expected
        ):
            raise ValueError("Archived quality evidence is missing or changed.")
        files[name] = expected
    return files


def reuse_plan(
    nodes: QualityGraph,
    proofs: dict[str, NodeProof],
    prior: RunRecord,
    prior_proofs: Mapping[str, NodeProof],
    evidence: Path,
    root: Path,
) -> dict[str, str]:
    """No declaration, unknown proof or native failure can become a reused PASS."""
    files = verify_evidence(evidence, root)
    if "state.json" not in files or "proof.json" not in files:
        raise ValueError("Archived quality evidence has no input proof.")
    recorded_proofs = JsonStore(
        StateDirectory(evidence, private=False, anchor=root), private_files=False
    ).read("proof.json")
    if prior != read_state(evidence, anchor=root) or json_value(prior_proofs) != recorded_proofs:
        raise ValueError("Quality reuse must consume the retained native state and proof.")
    result: dict[str, str] = {}
    for key, node in nodes.items():
        old = prior["steps"].get(key)
        if (
            node.get("reuse") is None
            or node["reuse"].get("always_run", False)
            or node.get("execution_context") == "host"
        ):
            result[key] = "rerun: current/external state or no reviewed reuse inputs"
        elif "unknown" in proofs[key]["dependencies"].values():
            result[key] = "unknown: prerequisite has no reviewed input boundary"
        elif old is None:
            result[key] = "pending: no previous native result"
        elif old["status"] != "passed" or old.get("exit_code") != 0:
            result[key] = "rerun: native result is incomplete or failed"
        elif key + ".log" not in files or proofs[key] != prior_proofs.get(key):
            result[key] = "stale: command, inputs, prerequisites or evidence changed"
        else:
            result[key] = "reusable: native PASS and exact input/prerequisite proof"
    return result


def load_proofs(directory: Path, root: Path) -> dict[str, NodeProof]:
    decoded = JsonStore(
        StateDirectory(directory, private=False, anchor=root), private_files=False
    ).read("proof.json")
    if not isinstance(decoded, dict):
        raise ValueError("Invalid quality input proof.")
    result: dict[str, NodeProof] = {}
    for key, row in decoded.items():
        if (
            not isinstance(row, dict)
            or set(row) != {"identity", "dependencies"}
            or not isinstance(row["identity"], str)
            or not re.fullmatch(r"[a-f0-9]{64}", row["identity"])
            or not isinstance(row["dependencies"], dict)
        ):
            raise ValueError("Invalid quality node input proof.")
        dependencies: dict[str, str] = {}
        for name, identity in row["dependencies"].items():
            if not isinstance(identity, str) or (
                identity != "unknown" and not re.fullmatch(r"[a-f0-9]{64}", identity)
            ):
                raise ValueError("Invalid quality prerequisite input proof.")
            dependencies[name] = identity
        result[key] = {"identity": row["identity"], "dependencies": dependencies}
    return result


def seal(directory: Path, root: Path, state: RunRecord) -> None:
    """Publish terminal digests after all native writers have exited."""
    names = ["state.json", "proof.json", *(key + ".log" for key in state["steps"])]
    files = {name: _content(directory, name) for name in names if (directory / name).exists()}
    JsonStore(StateDirectory(directory, private=False, anchor=root), private_files=False).write(
        "manifest.json", json_value({"schema_version": 1, "files": files})
    )


def copy_log(source: Path, destination: Path, key: str, root: Path) -> None:
    incoming = StateDirectory(source, private=False, anchor=root)
    outgoing = StateDirectory(destination, private=False, anchor=root)
    with incoming.open() as src, outgoing.open() as dst:
        with (
            incoming.file(src, key + ".log", private=False) as reader,
            outgoing.file(dst, key + ".log", create=True, private=False) as writer,
        ):
            os.ftruncate(writer, 0)
            while chunk := os.read(reader, 1024 * 1024):
                view = memoryview(chunk)
                while view:
                    view = view[os.write(writer, view) :]
            os.fsync(writer)
