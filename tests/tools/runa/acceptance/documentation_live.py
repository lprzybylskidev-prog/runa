#!/usr/bin/env python3
"""Real embeddings/Qdrant ownership transition in a uniquely named disposable collection."""

from __future__ import annotations

import contextlib
import io
import json
import shutil
import subprocess
import tempfile
import uuid
from dataclasses import replace
from pathlib import Path

from tools.application.knowledge.configuration import KnowledgeConfig, load
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.corpus import sources
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.retrieval import Retriever, doctor
from tools.application.knowledge.store import Store

ROOT = Path(__file__).resolve().parents[4]


def main() -> None:
    from tests.tools.runa.acceptance.knowledge_comparison import main as comparison
    from tests.tools.runa.acceptance.knowledge_live import main as knowledge_acceptance

    knowledge_acceptance()
    comparison()
    collection = "runa_documentation_test_" + uuid.uuid4().hex
    with tempfile.TemporaryDirectory(prefix="runa-documentation-live-") as folder:
        root = Path(folder)

        def write(name: str, value: str) -> None:
            p = root / name
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_text(value)

        def copy(name: str) -> None:
            p = root / name
            p.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(ROOT / name, p)

        def git(*args: str) -> None:
            subprocess.run(["git", *args], cwd=root, check=True, capture_output=True)

        git("init", "-q", "-b", "documentation-acceptance")
        for name in (
            "tools/application/knowledge/config.json",
            "tools/maintainer/knowledge.json",
            "docs/app/README.md",
            "docs/runa/handbook/ai/knowledge-system.md",
        ):
            copy(name)
        write(
            "docs/runa/maintainer/agent-contract.md",
            "# Owner policy\n\nPrivate platform release guidance.\n",
        )
        write(
            "docs/runa/maintainer/private.md",
            "# Private decision\n\nOwner-only sentinel private_739.\n",
        )
        write(
            "docs/runa/maintainer/customer-agent/AGENTS.md",
            "# Customer template\n\nTemplate sentinel template_739, never owner operating context.\n",
        )
        git("add", ".")
        git(
            "-c",
            "user.name=Documentation Fixture",
            "-c",
            "user.email=fixture@example.invalid",
            "commit",
            "-qm",
            "fixture baseline",
        )

        def config() -> KnowledgeConfig:
            additions = json.loads((root / "tools/maintainer/knowledge.json").read_text())
            additions["annotations"] = []
            return {**load(root, additions=additions), "collection": collection}

        context = replace(KnowledgeContext.for_workspace(root), owner_corpus=True)
        store = Store(config())

        def indexer() -> KnowledgeIndexer:
            return KnowledgeIndexer(context, config(), store, device="cpu")

        if store.exists():
            raise RuntimeError("Disposable collection unexpectedly exists; refusing to reuse it.")
        try:
            with contextlib.redirect_stdout(io.StringIO()):
                assert indexer().index() == 0
            assert any("/maintainer/" in p["source_path"] for p in store.all_points().values())
            assert not any(
                "/customer-agent/" in p["source_path"] for p in store.all_points().values()
            )
            (root / ".git/runa-bootstrap.json").write_text("{}\n")
            customer = config()
            assert indexer().status()["stale"]
            with contextlib.redirect_stdout(io.StringIO()):
                assert indexer().index() == 0
            points = store.all_points()
            assert not any("/maintainer/" in p["source_path"] for p in points.values())
            manifest = store.manifest()
            assert manifest is not None
            assert not any("/maintainer/" in name for name in manifest["sources"])
            assert not indexer().status()["stale"]
            retrieval = Retriever(root, customer, "cpu", context=context, store=store)
            assert retrieval.search(
                "application documentation roadmap lifecycle",
                filters={"source_path": "docs/app/README.md"},
            )
            assert retrieval.search(
                "production knowledge prohibition",
                filters={"source_path": "docs/runa/handbook/ai/knowledge-system.md"},
            )
            assert not retrieval.search(
                "private_739", filters={"source_path": "docs/runa/maintainer/private.md"}
            )
            assert doctor(retrieval)["passed"]
            broad = customer.copy()
            broad["markdown_roots"] = ["docs"]
            broad["files"] = customer["files"] + ["docs/runa/maintainer/private.md"]
            assert not any(
                "/maintainer/" in p.relative_to(root).as_posix()
                for p in sources(root, broad, context=context)
            )
            print(
                "PASS: actual embeddings/index/retrieval/doctor remove owner vectors and retain app/public knowledge in customer receipt scope."
            )
        finally:
            print("Scoped disposable Qdrant cleanup: " + collection, flush=True)
            store.http.request("DELETE", store.path)
            assert not store.exists()
    print(
        "Preserved the live project collection, Qdrant volumes/model caches and all application data."
    )


if __name__ == "__main__":
    main()
