"""Final-only fresh-context route fixture with and without optional retrieval."""

from __future__ import annotations

import json
from dataclasses import replace
from pathlib import Path

from tools.application.documentation import maintainer_checkout
from tools.application.knowledge.cli import operation_lock
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.evidence import read
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.retrieval import Retriever
from tools.core.state import JsonStore, StateDirectory, json_value
from tools.maintainer.knowledge_cli import configuration

ROOT = Path(__file__).resolve().parents[4]


def main() -> None:
    config, _ = configuration(ROOT)
    cases = [
        (
            "backend",
            "references/design-principles.md",
            "docs/runa/handbook/architecture/application-architecture.md",
            "thin controllers Actions Services Query Eloquent application architecture",
        ),
        (
            "frontend",
            "references/components.md",
            "docs/runa/handbook/development/frontend/customization.md",
            "public presentation replacement typed controllers slots default views",
        ),
        (
            "docker",
            "references/lifecycle-devcontainer.md",
            "docs/runa/handbook/development/editor-and-agents.md",
            "editor reopen recovery stopped app setup devcontainer",
        ),
        (
            "rag",
            "references/retrieval-evaluation.md",
            "docs/runa/handbook/development/knowledge.md",
            "knowledge evidence read intent applicability section expansion",
        ),
    ]
    results: list[dict[str, object]] = []
    with operation_lock(ROOT, "search").acquire():
        retriever = Retriever(
            ROOT,
            config,
            context=replace(
                KnowledgeContext.for_workspace(ROOT), owner_corpus=maintainer_checkout(ROOT)
            ),
        )
        indexer = KnowledgeIndexer(retriever.context, config, retriever.store)
        for domain, reference, canonical, query in cases:
            route = [
                "AGENTS.md",
                f".agents/skills/{domain}/SKILL.md",
                f".agents/skills/{domain}/{reference}",
                canonical,
            ]
            # Offline navigation opens only explicit entrypoint/reference/guide paths;
            # no bootstrap, web request, private customer template or whole-corpus read.
            texts = [(ROOT / name).read_text() for name in route]
            assert all(text.strip() for text in texts)
            hits = retriever.search(query, 5, intent="current")
            matches = [hit for hit in hits if hit["payload"]["source_path"] == canonical]
            assert matches, f"Canonical navigation source absent for {domain}"
            evidence = read(indexer, matches[0]["id"], max_chars=12000)
            assert evidence["results"]
            results.append(
                {
                    "task": domain,
                    "selected_paths": route,
                    "offline_words": sum(len(t.split()) for t in texts),
                    "online_sources": [hit["payload"]["source_path"] for hit in hits],
                    "evidence_characters": evidence["content_characters"],
                    "passed": True,
                }
            )
        report = {
            "passed": True,
            "scope": "Deterministic entrypoint/reference route fixture without prior chat; online canonical-source search/read plus offline source-only navigation. This does not certify autonomous LLM choices.",
            "cases": results,
        }
        directory = StateDirectory(ROOT / ".runtime/knowledge", private=False, anchor=ROOT)
        JsonStore(directory).write(
            "navigation-" + retriever.context.git_value("rev-parse", "HEAD") + ".json",
            json_value(report),
        )
        print(json.dumps(report, indent=2))
