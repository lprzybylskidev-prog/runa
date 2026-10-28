"""Context-bound quality planning and command lifecycle for customer and owner."""

import fcntl
import os
import shutil
import subprocess
from collections.abc import Sequence
from dataclasses import dataclass
from pathlib import Path
from typing import Protocol

from tools.application.configuration_reader import ConfigurationReader
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
from tools.application.quality_state import run_record
from tools.core.context import ExecutionLocation


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
                return 0
        directory = self.directory
        directory.parent.mkdir(parents=True, exist_ok=True)
        # Outside replaceable evidence; do not unlink or truncate its stable inode.
        from tools.core.state import StateDirectory

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
            if directory.exists():
                shutil.rmtree(directory)
            directory.mkdir(mode=0o700)
            root = self.executor.workspace.root

            def git(*args: str) -> str:
                return subprocess.check_output(["git", *args], cwd=root, text=True).strip()

            state = run_record(
                {
                    "status": "running",
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
            print("Overall status: " + state["status"])
            return result
