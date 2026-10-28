"""Versioned internal installation process boundary, invoked only by external tools."""

import argparse
from collections.abc import Sequence
from pathlib import Path

PROTOCOL = 1


def main(arguments: Sequence[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--protocol", required=True, type=int, choices=(PROTOCOL,))
    parser.add_argument("--workspace", required=True)
    options, arguments = parser.parse_known_args(arguments)
    root = Path(options.workspace)
    if (
        not root.is_absolute()
        or root.resolve() != root
        or root != Path(__file__).resolve().parents[2]
    ):
        parser.error("Gateway must execute from the selected verified application source.")
    # Import the application only after selecting the fixed protocol/workspace.
    from tools.application.installation_service import main as install
    from tools.core.lifecycle import interrupt_signals

    with interrupt_signals():
        return install(arguments, root)


if __name__ == "__main__":
    raise SystemExit(main())
