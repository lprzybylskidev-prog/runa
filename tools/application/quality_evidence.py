"""Candidate-bound closure evidence; historical one-time exceptions are retired."""

import re
import subprocess
from dataclasses import dataclass

from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_models import quality_definition
from tools.application.quality_planning import graph_hash, resolve, validate_declared
from tools.application.quality_reporting import read_state


@dataclass(frozen=True)
class ClosurePolicy:
    documentation: str = "docs/app"
    allowed_metadata: frozenset[str] = frozenset(
        {"src/app/application.json", "docs/app/CHANGELOG.md"}
    )


def closure_link_update(root, name, candidate, identifier, docs):
    if not re.fullmatch(re.escape(docs) + r"/roadmaps/draft/[^/]+\.md", name):
        return False
    path = root / name
    if not path.is_file() or path.is_symlink():
        return False
    original = subprocess.run(
        ["git", "show", candidate + ":" + name], cwd=root, capture_output=True
    )
    if original.returncode:
        return False
    pattern = (
        rb"(?<=\]\()\.\./active/(" + re.escape(identifier.encode()) + rb"-[a-z0-9-]+\.md)(?=\))"
    )
    expected, count = re.subn(pattern, rb"../closed/\1", original.stdout)
    return count > 0 and path.read_bytes() == expected


def check_evidence(root, data, definition_path, directory, *, policy=None, reader=None):
    """Bind selected-scope closure to actual complete results on the verified candidate."""
    if "quality_groups" not in data:
        return  # Immutable older releases predate explicit selection evidence.
    path = directory / "state.json"
    if not path.is_file():
        raise ValueError("Missing final quality evidence.")
    state = read_state(directory, anchor=root)
    scope = state.get("selection", {})
    validate_declared(data, scope)
    if (
        state.get("status") != "passed"
        or state.get("mode") not in ("roadmap", "final")
        or state.get("roadmap") != data["id"]
        or state.get("branch") != data["branch"]
        or state.get("rationale") != data.get("quality_rationale")
    ):
        raise ValueError("Final quality evidence does not match the closed roadmap.")
    definition = quality_definition(
        (reader or ConfigurationReader.for_workspace(root)).yaml(definition_path)
    )
    nodes, resolved = resolve(
        definition,
        [],
        groups=scope.get("requested_groups") if scope.get("mode") == "groups" else None,
        all_groups=scope.get("mode") == "all",
    )
    steps = state.get("steps", {})
    if (
        scope != resolved
        or set(steps) != set(nodes)
        or any(
            steps[key].get("status") != "passed" or steps[key].get("exit_code") != 0
            for key in nodes
        )
    ):
        raise ValueError("Incomplete or stale selected quality graph/results.")
    fields = ("status", "pid", "started_at", "finished_at", "duration_seconds", "exit_code")
    recorded = {
        key: {field: value for field, value in step.items() if field not in fields}
        for key, step in steps.items()
    }
    if graph_hash(recorded) != scope["graph_hash"]:
        raise ValueError("Quality result commands differ from the agreed graph.")
    candidate = state.get("commit", "")
    if (
        not re.fullmatch("[a-f0-9]{40}", candidate)
        or subprocess.run(
            ["git", "merge-base", "--is-ancestor", candidate, "HEAD"], cwd=root, capture_output=True
        ).returncode
    ):
        raise ValueError("Quality candidate is not an ancestor of this release.")
    policy = policy or ClosurePolicy()
    docs = policy.documentation
    allowed = policy.allowed_metadata
    prefixes = [
        docs + "/roadmaps/" + status + "/" + data["id"] + "-" for status in ("active", "closed")
    ]
    changed = subprocess.check_output(
        ["git", "diff", "--no-renames", "--name-only", candidate], cwd=root, text=True
    ).splitlines()
    changed += subprocess.check_output(
        ["git", "ls-files", "--others", "--exclude-standard"], cwd=root, text=True
    ).splitlines()
    if any(
        name not in allowed
        and not any(name.startswith(prefix) and name.endswith(".md") for prefix in prefixes)
        and not closure_link_update(root, name, candidate, data["id"], docs)
        for name in changed
    ):
        raise ValueError(
            "Substantive changes after the final quality candidate require a complete rerun."
        )
