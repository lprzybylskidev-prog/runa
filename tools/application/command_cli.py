import subprocess
import sys

from tools.application.runtime import ROOT


def main():
    name = sys.argv[1]
    if name not in ("format", "lint", "typecheck", "analyse", "test", "deploy"):
        raise SystemExit(
            subprocess.run([str(ROOT / "bin/artisan"), "--management", "list"], cwd=ROOT).returncode
        )
    raise SystemExit(
        subprocess.run(
            [str(ROOT / "bin/artisan"), "--management", "runa:" + name, *sys.argv[2:]], cwd=ROOT
        ).returncode
    )


if __name__ == "__main__":
    main()
