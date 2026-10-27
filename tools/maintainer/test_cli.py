"""Private owner composition of the delivered test lifecycle."""

from __future__ import annotations

import sys
from collections.abc import Sequence

from tools.application.documentation import maintainer_checkout
from tools.application.testing.cli import boundary
from tools.application.testing.cli import main as shared_main
from tools.application.testing.runner import TestLayout
from tools.maintainer.context import ROOT

OWNER = TestLayout(
    "runa",
    "../tests/backend/runa",
    "../tests/backend/runa/phpunit.xml",
    "runa/frontend/vitest.config.mjs",
    "runa/frontend/playwright.config.mjs",
    ("tests/tools/runa",),
)


def main(argv: Sequence[str] | None = None) -> int:
    arguments = list(sys.argv[1:] if argv is None else argv)
    if "--help" not in arguments and not maintainer_checkout(ROOT):
        raise ValueError("Owner tests require a receipt-free maintenance checkout.")
    return shared_main(arguments, layout=OWNER, module="tools.maintainer.test_cli")


if __name__ == "__main__":
    raise SystemExit(boundary(main))
