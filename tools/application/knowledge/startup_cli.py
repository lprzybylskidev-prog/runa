"""Fixed optional-startup adapter; no probes or dispatch during import."""

import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.documentation import maintainer_checkout
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.startup_service import KnowledgeStartup
from tools.core.lifecycle import interrupt_signals
from tools.core.reporting import SafeFailure


def main(argv: Sequence[str] | None = None, *, startup: KnowledgeStartup | None = None) -> int:
    arguments = list(sys.argv[1:] if argv is None else argv)
    if arguments not in (["probe"], ["initialize"]):
        print(
            "Usage: python3 -m tools.application.knowledge.startup_cli probe|initialize",
            file=sys.stderr,
        )
        return 1
    root = Path(__file__).resolve().parents[3]
    # Fixed transport selection; customer receipts override accidentally copied owner files.
    module = (
        "tools.maintainer.knowledge_cli"
        if maintainer_checkout(root)
        else "tools.application.knowledge.cli"
    )
    startup = startup or KnowledgeStartup(
        KnowledgeContext.for_workspace(root),
        initialize_command=(sys.executable, "-m", module, "initialize"),
    )
    try:
        with interrupt_signals():
            return startup.probe() if arguments == ["probe"] else startup.initialize()
    except KeyboardInterrupt:
        return 130
    except (ValueError, OSError, SafeFailure):
        print(
            "DEGRADED: optional knowledge startup unavailable; review local environment and services.",
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
