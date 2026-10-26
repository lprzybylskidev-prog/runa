"""Compose declared owner inputs without resolving dependencies or replacing locks."""

import argparse
import json
from pathlib import Path

from tools.core.dependencies import compose

ROOT = Path(__file__).resolve().parents[2]
SECTIONS = {"composer": ("require", "require-dev"), "package": ("dependencies", "devDependencies")}


def materialize(root, check=False):
    prepared = {}
    for kind in SECTIONS:
        inputs = [root / "src" / owner / (kind + ".json") for owner in ("runa", "app")]
        for path in inputs:
            if not path.is_file() or path.is_symlink():
                raise ValueError("Missing regular owner dependency manifest.")
        result = compose(*(json.loads(path.read_text()) for path in inputs), kind)
        target = root / "src" / (kind + ".json")
        if target.is_symlink():
            raise ValueError("Composed manifest must not be a symlink.")
        if check:
            if not target.is_file() or json.loads(target.read_text()) != result:
                raise ValueError("Composed manifest is stale: " + str(target.relative_to(root)))
        else:
            prepared[target] = json.dumps(result, indent=2) + "\n"
    # Validate both owners/graphs before writing either output. Never touch locks.
    for target, content in prepared.items():
        target.write_text(content)


def main(arguments=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args(arguments)
    try:
        materialize(ROOT, args.check)
    except (ValueError, OSError) as error:
        parser.exit(1, str(error) + "\n")
    print(
        "PASS: dependency manifests match owner inputs; locks preserved."
        if args.check
        else "Composed dependency manifests; locks preserved. Validate/install with frozen locks before use."
    )


if __name__ == "__main__":
    main()
