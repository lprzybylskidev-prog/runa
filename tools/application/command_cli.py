import os
import sys
from pathlib import Path

from tools.application.processes import ApplicationProcesses
from tools.core.process import SubprocessRunner

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    processes = ApplicationProcesses(SubprocessRunner(), dict(os.environ))
    name = sys.argv[1]
    if name not in ("format", "lint", "typecheck", "analyse", "test", "deploy"):
        raise SystemExit(
            processes.run([str(ROOT / "bin/artisan"), "--management", "list"], cwd=ROOT).returncode
        )
    raise SystemExit(
        processes.run(
            [str(ROOT / "bin/artisan"), "--management", "runa:" + name, *sys.argv[2:]], cwd=ROOT
        ).returncode
    )


if __name__ == "__main__":
    main()
