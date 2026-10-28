"""Small explicit filesystem/Git/config factories; no implicit stack startup."""

import os
import subprocess
from dataclasses import dataclass
from pathlib import Path


@dataclass(frozen=True)
class WorkspaceFixture:
    root: Path

    def write(self, name: str, content: str) -> Path:
        target = self.root / name
        if not target.resolve().is_relative_to(self.root.resolve()):
            raise ValueError("Fixture path escapes its workspace.")
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(content)
        return target

    def git(self, *arguments: str) -> subprocess.CompletedProcess[str]:
        return subprocess.run(
            ["git", "-c", "core.hooksPath=/dev/null", *arguments],
            cwd=self.root,
            text=True,
            capture_output=True,
            check=True,
            env=dict(
                {key: value for key, value in os.environ.items() if not key.startswith("GIT_")},
                GIT_CONFIG_NOSYSTEM="1",
                GIT_CONFIG_GLOBAL="/dev/null",
                GIT_TERMINAL_PROMPT="0",
            ),
        )


def workspace(root: Path) -> WorkspaceFixture:
    root.mkdir(parents=True, exist_ok=True)
    return WorkspaceFixture(root)


def repository(root: Path) -> WorkspaceFixture:
    fixture = workspace(root)
    fixture.git("init", "--initial-branch=main")
    fixture.git("config", "user.name", "Runa Test")
    fixture.git("config", "user.email", "runa-test@example.invalid")
    return fixture
