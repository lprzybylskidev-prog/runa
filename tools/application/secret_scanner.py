"""One strict Gitleaks invocation policy for staged and whole-repository content."""

import tempfile
from dataclasses import dataclass
from pathlib import Path

from tools.application.processes import ApplicationProcesses


@dataclass(frozen=True)
class SecretScanner:
    binary: Path
    processes: ApplicationProcesses

    def scan(self, target: Path, *, history: bool = False) -> None:
        if not self.binary.is_file():
            raise ValueError(
                "Pinned Gitleaks is missing. Run ./bin/artisan runa:dev:setup inside the devcontainer."
            )
        with tempfile.TemporaryDirectory(prefix="runa-gitleaks-") as directory:
            config = Path(directory) / "scanner.toml"
            config.write_text("[extend]\nuseDefault = true\n")
            environment = {
                key: value
                for key, value in self.processes.environment.items()
                if not key.startswith("GITLEAKS_")
            }
            flags = [
                "--config",
                str(config),
                "--gitleaks-ignore-path",
                str(Path(directory) / ".no-ignore"),
                "--ignore-gitleaks-allow",
                "--redact",
                "--exit-code",
                "1",
                "--no-banner",
            ]
            self.processes.run(
                [str(self.binary), "git" if history else "dir", str(target), *flags],
                cwd=target,
                env=environment,
                check=True,
            )
