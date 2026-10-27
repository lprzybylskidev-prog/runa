#!/usr/bin/env python3
"""Exercise real TEI/Qdrant contracts using a disposable collection and checkout."""

from __future__ import annotations

import contextlib
import io
import json
import math
import os
import subprocess
import tempfile
import uuid
from pathlib import Path

from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.contracts import Health
from tools.application.knowledge.corpus import identity
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.provider import Embeddings
from tools.application.knowledge.store import Store

ROOT = Path(__file__).resolve().parents[3]


def main() -> None:
    if os.environ.get("APP_ENV") == "production":
        raise ValueError("Knowledge acceptance is development-only.")
    config = json.loads((ROOT / "tools/application/knowledge/config.json").read_text())
    cpu = Embeddings(config, "cpu")
    gpu = Embeddings(config)
    if gpu.client is not gpu.gpu:
        raise ValueError("CUDA acceptance requires the GPU service; fallback is tested separately.")
    texts = ["PostgreSQL uses explicit schemas.", "Knowledge tooling must remain development-only."]
    a, b = gpu.embed(texts), cpu.embed(texts)
    similarities = [
        sum(x * y for x, y in zip(u, v, strict=True))
        / math.sqrt(sum(x * x for x in u) * sum(y * y for y in v))
        for u, v in zip(a, b, strict=True)
    ]
    assert min(similarities) > 0.999, similarities
    fallback_config = {**config, "cuda_url": "http://127.0.0.1:1"}
    with contextlib.redirect_stderr(io.StringIO()) as diagnostic:
        fallback = Embeddings(fallback_config)
        assert len(fallback.embed([texts[0]])[0]) == config["dimension"]
    assert fallback.client is fallback.cpu and "CPU fallback" in diagnostic.getvalue()
    try:
        cpu.embed(["token " * 600])
    except ValueError as error:
        assert "token budget" in str(error)
    else:
        raise AssertionError("Over-budget input was accepted.")
    config = {**config, "collection": "runa_knowledge_acceptance_" + uuid.uuid4().hex}
    store = Store(config)
    with tempfile.TemporaryDirectory(prefix="runa-knowledge-") as directory:
        root = Path(directory)

        def git(*args: str) -> None:
            subprocess.run(["git", *args], cwd=root, check=True, capture_output=True)

        def run_index(device: str = "auto") -> Health:
            with contextlib.redirect_stdout(io.StringIO()) as output:
                assert (
                    KnowledgeIndexer(
                        KnowledgeContext.for_workspace(root), config, store, device=device
                    ).index()
                    == 0
                )
            return json.loads(output.getvalue())

        git("init", "-q", "-b", "knowledge-acceptance")
        (root / "AGENTS.md").write_text("# Rules\n\nUse explicit PostgreSQL schemas.\n")
        git("add", "AGENTS.md")
        git(
            "-c",
            "user.name=Knowledge test",
            "-c",
            "user.email=test@example.invalid",
            "commit",
            "-qm",
            "fixture",
        )
        try:
            assert run_index()["embedded_chunks"] == 1
            assert run_index()["embedded_chunks"] == 0
            assert run_index("cpu")["embedded_chunks"] == 0
            (root / "docs/app").mkdir(parents=True)
            document = root / "docs/app/architecture.md"
            document.write_text("# Runtime\n\nNever deploy knowledge services into production.\n")
            git("add", "docs/app/architecture.md")
            assert KnowledgeIndexer(KnowledgeContext.for_workspace(root), config, store).status()[
                "stale"
            ]
            assert run_index()["embedded_chunks"] == 1
            document.write_text(
                "# Runtime\n\nKeep Qdrant and embedding services development-only.\n"
            )
            changed = run_index()
            assert changed["embedded_chunks"] == 1 and changed["removed_chunks"] == 1
            store.upsert(
                [
                    {
                        "id": identity("acceptance-orphan"),
                        "vector": a[0],
                        "payload": {"kind": "chunk"},
                    }
                ]
            )
            assert (
                KnowledgeIndexer(KnowledgeContext.for_workspace(root), config, store).status()[
                    "orphan_chunks"
                ]
                == 1
            )
            assert run_index()["removed_chunks"] == 1
            manifest = store.manifest()
            store.write_manifest({**manifest, "pending": True})
            assert KnowledgeIndexer(KnowledgeContext.for_workspace(root), config, store).status()[
                "interrupted"
            ]
            assert run_index()["embedded_chunks"] == 0
            document.unlink()
            assert run_index()["removed_chunks"] == 1
            git("checkout", "-qb", "changed-branch")
            assert KnowledgeIndexer(KnowledgeContext.for_workspace(root), config, store).status()[
                "branch_changed"
            ]
            assert run_index()["embedded_chunks"] == 0
            assert not KnowledgeIndexer(
                KnowledgeContext.for_workspace(root), config, store
            ).status()["stale"]
        finally:
            # Only this random test collection is removed; never the developer corpus.
            if store.exists():
                store.http.request("DELETE", store.path)
    print(
        json.dumps(
            {
                "result": "PASS",
                "cpu_cuda_cosine": similarities,
                "checks": [
                    "dimension",
                    "token_budget",
                    "cpu_fallback",
                    "initial_index",
                    "no_op",
                    "add_edit_delete",
                    "orphan_cleanup",
                    "interrupted_recovery",
                    "branch_refresh",
                ],
                "disposable_collection_removed": True,
            },
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
