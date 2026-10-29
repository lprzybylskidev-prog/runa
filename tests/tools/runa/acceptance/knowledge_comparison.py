"""Final-only real-corpus comparison with the unchanged, pinned baseline search module."""

from __future__ import annotations

import hashlib
import importlib.util
import json
import tempfile
from collections.abc import Mapping
from dataclasses import replace
from pathlib import Path
from typing import Protocol, cast

from tools.application.documentation import maintainer_checkout
from tools.application.knowledge.cli import operation_lock
from tools.application.knowledge.configuration import GoldenCase
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.contracts import SearchHit
from tools.application.knowledge.retrieval import Retriever, evaluate
from tools.core.state import JsonStore, StateDirectory, json_value
from tools.maintainer.knowledge_cli import configuration

ROOT = Path(__file__).resolve().parents[4]
BASELINE = "36460e621269c695fbf0f644ac22917660dbbdad"


class LegacySearch(Protocol):
    def __call__(
        self, instance: Retriever, query: str, top_k: int, filters: Mapping[str, str] | None
    ) -> list[SearchHit]: ...


def main() -> None:
    config, dataset = configuration(ROOT)
    directory = StateDirectory(ROOT / ".runtime/knowledge", private=False, anchor=ROOT)
    with directory.open():
        pass
    with operation_lock(ROOT, "eval").acquire():
        candidate = Retriever(
            ROOT,
            config,
            context=replace(
                KnowledgeContext.for_workspace(ROOT), owner_corpus=maintainer_checkout(ROOT)
            ),
        )
        source = candidate.context.git(
            "show", BASELINE + ":tools/application/knowledge/retrieval.py"
        )
        # This exact owner-reviewed Git module is used only by final acceptance.
        # Reuse compatible current transport/store seams on the same current corpus;
        # compare search ranking, not an invented replay of the old installation.
        with tempfile.TemporaryDirectory(prefix="baseline-", dir=directory.path) as folder:
            path = Path(folder) / "retrieval.py"
            path.write_bytes(source)
            spec = importlib.util.spec_from_file_location("runa_reviewed_baseline_retrieval", path)
            if spec is None or spec.loader is None:
                raise ValueError("Pinned baseline module cannot be loaded.")
            module = importlib.util.module_from_spec(spec)
            spec.loader.exec_module(module)
            legacy_search = cast(LegacySearch, module.Retriever.search)

            class BaselineRetriever(Retriever):
                def search(
                    self,
                    query: str,
                    top_k: int = 5,
                    filters: Mapping[str, str] | None = None,
                    *,
                    intent: str = "legacy",
                    per_source: int | None = None,
                ) -> list[SearchHit]:
                    return legacy_search(self, query, top_k, filters)

            baseline = BaselineRetriever(
                ROOT,
                config,
                context=replace(
                    KnowledgeContext.for_workspace(ROOT), owner_corpus=maintainer_checkout(ROOT)
                ),
            )
            # The baseline has no supported read API. Both search modules use the
            # same current evaluation budgets; raw-hit coverage is compared below.
            fixed: list[GoldenCase] = [{**case, "expected": []} for case in dataset]
            before = evaluate(baseline, fixed)
            after = evaluate(candidate, fixed)
            complete = evaluate(candidate, dataset)
        regressions = [
            a["query"]
            for a, b in zip(before["cases"], after["cases"], strict=True)
            if a["passed"] and not b["passed"] and not a.get("probe_only", False)
        ]
        report = {
            "baseline_commit": BASELINE,
            "baseline_source_sha256": hashlib.sha256(source).hexdigest(),
            "candidate_commit": candidate.context.git_value("rev-parse", "HEAD"),
            "branch": candidate.branch,
            "comparison_scope": "Unchanged baseline search algorithm and candidate on identical current corpus/model; current compatible store/context seams. Raw-hit comparison plus candidate bounded evidence coverage, not historical installation replay.",
            "baseline": before,
            "candidate_search": after,
            "candidate_evidence": complete,
            "regressions": regressions,
            "passed": not regressions and complete["passed"],
        }
        reports = JsonStore(directory, max_bytes=2 * 1024 * 1024, private_files=False)
        name = "retrieval-comparison-" + candidate.context.git_value("rev-parse", "HEAD") + ".json"
        reports.write(name, json_value(report))
        print(
            json.dumps(
                {
                    "report": str(directory.path / name),
                    "passed": report["passed"],
                    "regressions": regressions,
                },
                indent=2,
            )
        )
        if not report["passed"]:
            raise AssertionError(
                "Fixed real-corpus retrieval expectations regressed or candidate coverage failed."
            )
