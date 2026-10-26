"""Owner tool context and argument-safe container delegation."""

import subprocess
from pathlib import Path

from tools.application.runtime import compose, inside

ROOT = Path(__file__).resolve().parents[2]


def container(script, arguments):
    if not inside():
        raise SystemExit(
            subprocess.run(
                compose()
                + ["exec", "-T", "app", "python3", str(script.relative_to(ROOT)), *arguments],
                cwd=ROOT,
            ).returncode
        )
