"""Explicit native acceptance process/Git/Compose context; imports start nothing."""

import os
import subprocess
from dataclasses import dataclass, field

from tools.application.development_runtime import development_compose
from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner


@dataclass(frozen=True)
class AcceptanceContext:
    workspace: WorkspaceContext
    processes: ApplicationProcesses = field(
        default_factory=lambda: ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    )

    def call(self, arguments, *, cwd=None, env=None, capture=False, check=True):
        return self.processes.run(
            arguments,
            cwd=cwd or self.workspace.root,
            env=env,
            capture_output=capture,
            text=True,
            check=check,
            stdin=subprocess.DEVNULL,
        )

    def output(self, arguments, **kwargs):
        return self.call(arguments, capture=True, **kwargs).stdout.strip()

    def compose(self, *arguments):
        return list(development_compose(self.workspace).command(*arguments))

    def app(self, *arguments):
        return self.compose("exec", "-T", "app", *arguments)

    def commit(self, root):
        self.call(["git", "add", "--all"], cwd=root)
        self.call(
            [
                "git",
                "-c",
                "core.hooksPath=/dev/null",
                "-c",
                "user.name=Acceptance Fixture",
                "-c",
                "user.email=fixture@example.invalid",
                "commit",
                "--quiet",
                "-m",
                "test(RM-2026-012): isolated fixture",
            ],
            cwd=root,
        )
        return self.output(["git", "rev-parse", "HEAD"], cwd=root)

    def protected_containers(self):
        # Select by actual checkout identity, independent of project/container names.
        listing = self.output(
            [
                "docker",
                "ps",
                "--no-trunc",
                "--filter",
                "label=com.docker.compose.project.working_dir=" + str(self.workspace.root),
                "--format",
                '{{.ID}}\t{{.Label "runa.testing.run"}}\t{{.Label "com.docker.compose.project.working_dir"}}',
            ]
        )
        return [
            identity
            for row in listing.splitlines()
            for identity, browser_run, directory in [row.split("\t")]
            if directory == str(self.workspace.root) and not browser_run
        ]
