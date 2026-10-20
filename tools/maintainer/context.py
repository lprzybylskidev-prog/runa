"""Owner tool context and argument-safe container delegation."""
from pathlib import Path
import subprocess
import sys
ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(1, str(ROOT / 'tools/application'))
from runtime import compose, inside


def container(script, arguments):
    if not inside():
        raise SystemExit(subprocess.run(compose() + ['exec', '-T', 'app', 'python3', str(script.relative_to(ROOT)), *arguments], cwd=ROOT).returncode)
