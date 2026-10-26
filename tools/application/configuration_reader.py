"""Symfony YAML parsing through an explicit application process boundary."""

import json
import os
from dataclasses import dataclass
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.core.context import ExecutionContext, WorkspaceContext
from tools.core.process import InputMode, OutputMode, ProcessRunner, ProcessSpec, SubprocessRunner


@dataclass(frozen=True)
class ConfigurationReader:
    workspace: WorkspaceContext
    runtime: DevelopmentRuntime
    runner: ProcessRunner
    environment: dict[str, str]

    @classmethod
    def for_workspace(cls, root: Path):
        workspace = WorkspaceContext(root)
        return cls(
            workspace,
            DevelopmentRuntime(ExecutionContext(workspace, workspace, execution_location())),
            SubprocessRunner(),
            dict(os.environ),
        )

    def yaml(self, path: Path):
        return self.parse(path.read_text())

    def parse(self, content: str):
        script = 'require "src/vendor/autoload.php"; echo json_encode(Symfony\\Component\\Yaml\\Yaml::parse(stream_get_contents(STDIN)), JSON_THROW_ON_ERROR);'
        result = self.runner.run(
            ProcessSpec(
                self.runtime.app_command(("php", "-r", script)),
                self.workspace.root,
                self.environment,
                stdin=InputMode.BYTES,
                input=content.encode(),
                output=OutputMode.CAPTURE,
            )
        ).checked()
        value = json.loads(result.stdout)
        if not isinstance(value, dict):
            raise ValueError("Configuration must be a mapping.")
        return value

    def roadmap(self, path: Path):
        text = path.read_text()
        if not text.startswith("---\n") or "\n---\n" not in text[4:]:
            raise ValueError("Missing roadmap frontmatter.")
        return self.parse(text.split("---", 2)[1])
