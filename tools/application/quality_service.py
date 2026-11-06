"""Context-bound quality planning and command lifecycle for customer and owner."""

import fcntl
import os
import subprocess
import uuid
from collections.abc import Sequence
from dataclasses import dataclass
from pathlib import Path
from typing import Protocol

from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_campaign import (
    archive,
    copy_log,
    input_proofs,
    load_proofs,
    reuse_plan,
    seal,
)
from tools.application.quality_execution import QualityExecutor
from tools.application.quality_models import (
    QualityGraph,
    QualityRoadmap,
    QualityScope,
    quality_definition,
    quality_roadmap,
)
from tools.application.quality_planning import arguments, resolve, show_scope, validate_declared
from tools.application.quality_reporting import read_state, save, status
from tools.application.quality_state import RunRecord, run_record
from tools.core.context import ExecutionLocation
from tools.core.state import JsonStore, StateDirectory, json_value


class QualityPlanSource(Protocol):
    def plan(
        self,
        final: bool = False,
        *,
        groups: Sequence[str] | None = None,
        all_groups: bool = False,
    ) -> tuple[QualityRoadmap, QualityGraph]: ...


@dataclass(frozen=True)
class QualityPlanner:
    reader: ConfigurationReader
    roadmaps: Path
    definition: Path
    allow_application_default: bool = False

    def plan(
        self,
        final: bool = False,
        *,
        groups: Sequence[str] | None = None,
        all_groups: bool = False,
    ) -> tuple[QualityRoadmap, QualityGraph]:
        active = sorted(self.roadmaps.glob("*.md"))
        if not active and self.allow_application_default:
            roadmap: QualityRoadmap = {"id": "application", "impact_areas": []}
        elif len(active) == 1:
            roadmap = quality_roadmap(self.reader.roadmap(active[0]))
        else:
            raise ValueError("Exactly one active roadmap is required.")
        nodes, scope = resolve(
            quality_definition(self.reader.yaml(self.definition)),
            roadmap["impact_areas"],
            final,
            groups,
            all_groups,
        )
        roadmap["quality_selection"] = scope
        return roadmap, nodes


@dataclass(frozen=True)
class QualityService:
    planner: QualityPlanSource
    executor: QualityExecutor
    directory: Path
    command_prefix: str

    def run(self, argv: Sequence[str]) -> int:
        if list(argv) == ["--help"]:
            print(
                "Usage: "
                + self.command_prefix
                + "plan|roadmap|final [--group=NAME ... | --all] [--background]; status\nManual groups include mandatory checks and prerequisites. No flags retain legacy selection. Background is supported for roadmap/final. No automatic timeouts."
            )
            return 0
        resume = "--resume" in argv
        if resume and (argv.count("--resume") != 1 or argv[0] not in ("show", "roadmap", "final")):
            raise ValueError("Resume is supported only for quality plan/roadmap/final.")
        argv = [arg for arg in argv if arg != "--resume"]
        groups, all_groups, background = arguments(argv)
        roadmap: QualityRoadmap | None = None
        nodes: QualityGraph = {}
        selection: QualityScope = {"mode": "legacy"}
        if argv[0] != "status":
            roadmap, nodes = self.planner.plan(
                argv[0] == "final", groups=groups, all_groups=all_groups
            )
            selection = roadmap.get("quality_selection", selection)
            if argv[0] == "final":
                validate_declared(roadmap, selection)
            if argv[0] == "show":
                show_scope(selection)
                for key, node in nodes.items():
                    print(
                        key
                        + ": "
                        + node["command"]
                        + " [after: "
                        + ", ".join(node["depends_on"])
                        + "]"
                    )
                if not resume:
                    return 0
        directory = self.directory
        directory.parent.mkdir(parents=True, exist_ok=True)
        # Outside replaceable evidence; do not unlink or truncate its stable inode.
        locks = StateDirectory(directory.parent, private=False, anchor=self.executor.workspace.root)
        with (
            locks.open() as parent,
            locks.file(parent, "quality.lock", create=True, private=False) as lock,
        ):
            try:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
                active = False
            except BlockingIOError:
                active = True
            if argv[0] == "status":
                # A live coordinator owns writes; status only observes it.
                status(directory, active, anchor=self.executor.workspace.root)
                return 0
            assert roadmap is not None
            if self.executor.location is ExecutionLocation.CONTAINER and any(
                node.get("execution_context") == "host" for node in nodes.values()
            ):
                raise ValueError(
                    "Selected production gates need the Docker host. Run "
                    + self.command_prefix
                    + argv[0]
                    + " in the host terminal; it delegates application checks to the devcontainer."
                )
            if active:
                raise ValueError(
                    "Another quality run is active. Use " + self.command_prefix + "status."
                )
            if directory.is_symlink():
                raise ValueError("Quality evidence directory must not be a symlink.")
            prior = directory / "state.json"
            if prior.exists():
                for node in read_state(directory, anchor=self.executor.workspace.root)[
                    "steps"
                ].values():
                    if (
                        node.get("status") in ("running", "interrupted")
                        and Path("/proc/" + str(node.get("pid"))).exists()
                    ):
                        raise ValueError(
                            "Previous quality child is still alive; stop it before restarting."
                        )
            root = self.executor.workspace.root
            branch = subprocess.check_output(
                ["git", "branch", "--show-current"], cwd=root, text=True
            ).strip()
            proofs = input_proofs(root, nodes, self.executor.environment)
            decisions: dict[str, str] = {}
            previous: RunRecord | None = None
            if resume:
                previous = read_state(directory, anchor=root)
                if (
                    previous.get("roadmap") != roadmap["id"]
                    or previous.get("branch") != branch
                    or previous.get("selection") != selection
                    or previous.get("rationale") != roadmap.get("quality_rationale")
                ):
                    raise ValueError("Resume requires the same roadmap, branch and explicit scope.")
                decisions = reuse_plan(
                    nodes, proofs, previous, load_proofs(directory, root), directory, root
                )
                for key, decision in decisions.items():
                    print(key + ": " + decision)
                if argv[0] == "show":
                    return 0
            retained = archive(directory, root)
            directory.mkdir(mode=0o700, exist_ok=True)
            # The original manifest remains in its archived attempt; latest is now writable.
            (directory / "manifest.json").unlink(missing_ok=True)
            root = self.executor.workspace.root

            def git(*args: str) -> str:
                return subprocess.check_output(["git", *args], cwd=root, text=True).strip()

            state = run_record(
                {
                    "status": "running",
                    "campaign_id": previous.get("campaign_id", uuid.uuid4().hex)
                    if previous is not None
                    else uuid.uuid4().hex,
                    "mode": argv[0],
                    "roadmap": roadmap["id"],
                    "impact_areas": roadmap["impact_areas"],
                    "branch": git("branch", "--show-current"),
                    "commit": git("rev-parse", "HEAD"),
                    "steps": {key: dict(node, status="pending") for key, node in nodes.items()},
                    "selection": selection,
                    "rationale": roadmap.get("quality_rationale"),
                }
            )
            if previous is not None and retained is not None:
                state["resumed_from"] = retained[0]
                for key, decision in decisions.items():
                    if decision.startswith("reusable:"):
                        state["steps"][key].update(previous["steps"][key])
                        state["steps"][key]["reused_from"] = retained[0]
                        copy_log(directory / "attempts" / retained[0], directory, key, root)
            JsonStore(
                StateDirectory(directory, private=False, anchor=root), private_files=False
            ).write("proof.json", json_value(proofs))
            save(directory, state, anchor=root)
            if background:
                pid = os.fork()
                if pid:
                    print(
                        "Quality started in background. Use " + self.command_prefix + "status.",
                        flush=True,
                    )
                    os._exit(0)
                os.setsid()
                with open(os.devnull) as source, (directory / "runner.log").open("a") as output:
                    os.dup2(source.fileno(), 0)
                    os.dup2(output.fileno(), 1)
                    os.dup2(output.fileno(), 2)
            result = self.executor.execute(directory, state)
            seal(directory, root, state)
            print("Overall status: " + state["status"])
            return result
