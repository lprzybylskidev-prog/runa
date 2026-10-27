"""Fixed optional-startup adapter; no probes or dispatch during import."""

import sys
from pathlib import Path

from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.startup_service import KnowledgeStartup
from tools.core.lifecycle import interrupt_signals
from tools.core.reporting import SafeFailure


def main(argv=None, *, startup=None):
    arguments = list(sys.argv[1:] if argv is None else argv)
    if arguments not in (["probe"], ["initialize"]):
        print("Usage: startup.py probe|initialize", file=sys.stderr)
        return 1
    startup = startup or KnowledgeStartup(
        KnowledgeContext.for_workspace(Path(__file__).resolve().parents[3])
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
