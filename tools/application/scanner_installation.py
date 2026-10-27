"""Install approved pinned scanners with release checksum verification; explicit network operation."""

import hashlib
import io
import os
import platform
import tarfile
from pathlib import Path
from urllib.request import urlopen

from tools.application.processes import ApplicationProcesses
from tools.core.context import WorkspaceContext
from tools.core.process import SubprocessRunner


def install(workspace: WorkspaceContext, processes: ApplicationProcesses) -> None:
    if platform.machine() not in ("x86_64", "AMD64"):
        raise ValueError(
            "Scanner installer currently requires the selected x86_64 development platform."
        )
    directory = workspace.runtime("tools")
    directory.mkdir(parents=True, exist_ok=True)
    existing = directory / "gitleaks"
    if (
        existing.exists()
        and processes.run(
            [str(existing), "version"],
            cwd=workspace.root,
            check=True,
            capture_output=True,
            text=True,
        ).stdout.strip()
        == "8.30.1"
    ):
        return
    version = "8.30.1"
    asset = "gitleaks_8.30.1_linux_x64.tar.gz"
    base = f"https://github.com/gitleaks/gitleaks/releases/download/v{version}/"
    with urlopen(base + "gitleaks_8.30.1_checksums.txt") as response:
        checks = response.read().decode()
    expected = next(
        line.split()[0] for line in checks.splitlines() if line.split()[-1].lstrip("*") == asset
    )
    with urlopen(base + asset) as response:
        data = response.read()
    if expected != "551f6fc83ea457d62a0d98237cbad105af8d557003051f41f3e7ca7b3f2470eb":
        raise ValueError("Scanner release checksum differs from the reviewed pin.")
    if hashlib.sha256(data).hexdigest() != expected:
        raise ValueError("Scanner release checksum mismatch.")
    with tarfile.open(fileobj=io.BytesIO(data), mode="r:gz") as archive:
        member = archive.extractfile("gitleaks")
        if member is None:
            raise ValueError("Scanner binary absent.")
        target = directory / "gitleaks"
        target.write_bytes(member.read())
        target.chmod(0o755)
    print(f"Installed gitleaks {version}; SHA256 {expected}")


def main() -> None:
    install(
        WorkspaceContext(Path(__file__).resolve().parents[2]),
        ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
    )


if __name__ == "__main__":
    main()
