"""Private corpus/golden composition; receipts always retain customer scope."""

import json
import sys
from dataclasses import replace
from pathlib import Path

from tools.application.documentation import maintainer_checkout
from tools.application.knowledge.cli import main as application_main
from tools.application.knowledge.configuration import golden, golden_file, load
from tools.application.knowledge.context import KnowledgeContext
from tools.application.repository_boundary import assert_development_context
from tools.core.reporting import SafeFailure


def configuration(root: Path):
    assert_development_context(root)
    if not maintainer_checkout(root):
        return load(root), golden(root)
    extra = root / "tools/maintainer/knowledge.json"
    if extra.is_symlink():
        raise ValueError("Owner knowledge configuration must not be a symlink.")
    additions = json.loads(extra.read_text())
    if not isinstance(additions, dict):
        raise ValueError("Owner knowledge configuration must be an object.")
    return load(root, additions=additions), [
        *golden(root),
        *golden_file(root / "tools/maintainer/knowledge-golden.json"),
    ]


def main(argv=None):
    root = Path(__file__).resolve().parents[2]
    try:
        config, dataset = configuration(root)
        return application_main(
            argv,
            context=replace(
                KnowledgeContext.for_workspace(root), owner_corpus=maintainer_checkout(root)
            ),
            config=config,
            dataset=dataset,
        )
    except (OSError, ValueError, SafeFailure):
        print("Knowledge owner configuration is unavailable or invalid.", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
