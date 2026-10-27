"""Dense Qdrant retrieval plus local BM25 and weighted reciprocal-rank fusion."""

import math
import re
import statistics
from collections import Counter

from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.provider import Embeddings
from tools.application.knowledge.store import Store

FILTERS = ("document_type", "scope", "roadmap_id", "status", "source_path")


def tokens(text):
    return re.findall(r"[\w]+(?:[-/.][\w]+)*", text.lower())


def lexical(query, points):
    terms = set(tokens(query))
    bags = {key: Counter(tokens(p["content"])) for key, p in points.items()}
    sizes = {key: sum(bag.values()) for key, bag in bags.items()}
    average = sum(sizes.values()) / max(1, len(sizes)) or 1
    frequencies = {term: sum(term in bag for bag in bags.values()) for term in terms}
    scores = {}
    for key, bag in bags.items():
        score = 0
        for term in terms:
            count = bag[term]
            idf = math.log(1 + (len(bags) - frequencies[term] + 0.5) / (frequencies[term] + 0.5))
            score += idf * count * 2.2 / (count + 1.2 * (0.25 + 0.75 * sizes[key] / average))
        if score:
            scores[key] = score
    return sorted(scores.items(), key=lambda row: (-row[1], row[0]))


class Retriever:
    def __init__(
        self, root, config, device="cpu", *, context=None, store=None, provider_factory=Embeddings
    ):
        self.root, self.config = root, config
        self.context = context or KnowledgeContext.for_workspace(root)
        self.store = store if store is not None else Store(config)
        health = KnowledgeIndexer(self.context, config, self.store).status()
        if health["stale"]:
            raise ValueError(
                "Knowledge index is stale/missing/incompatible; run ./bin/artisan runa:knowledge:index and open original sources."
            )
        self.points = self.store.all_points()
        self.branch = self.context.git_value("branch", "--show-current")
        self.provider = provider_factory(config, device)
        self.health = health

    def search(self, query, top_k=5, filters=None):
        if not query.strip() or not 1 <= top_k <= 50:
            raise ValueError("Provide a nonempty query and top-k between 1 and 50.")
        filters = filters or {}
        if set(filters) - set(FILTERS):
            raise ValueError("Unsupported metadata filter.")
        settings = self.config["retrieval"]
        if any(
            not isinstance(settings[k], (int, float))
            or not math.isfinite(settings[k])
            or settings[k] <= 0
            for k in (
                "dense_weight",
                "lexical_weight",
                "rrf_k",
                "active_boost",
                "historical_weight",
            )
        ):
            raise ValueError("Retrieval weights and RRF constant must be positive finite numbers.")
        if any(
            type(settings[k]) is not int or not 1 <= settings[k] <= 1000
            for k in ("candidates", "per_source")
        ):
            raise ValueError("Candidate/per-source limits must be integers between 1 and 1000.")
        vector = self.provider.embed([self.config["query_prefix"] + query])[0]
        conditions = {"kind": "chunk", "git_branch": self.branch, **filters}
        selected = {
            key: p
            for key, p in self.points.items()
            if all(p.get(k) == v for k, v in conditions.items())
        }
        response = self.store.query(
            {
                "query": vector,
                "limit": max(settings["candidates"], top_k * 4),
                "with_payload": False,
                "filter": {
                    "must": [
                        {"key": key, "match": {"value": value}} for key, value in conditions.items()
                    ]
                },
            }
        )
        dense = [(p["id"], p["score"]) for p in response if p["id"] in selected]
        sparse = lexical(query, selected)[: max(settings["candidates"], top_k * 4)]
        scores, diagnostics = {}, {}
        for name, ranking in [("dense", dense), ("lexical", sparse)]:
            for rank, (key, score) in enumerate(ranking, 1):
                scores[key] = scores.get(key, 0) + settings[name + "_weight"] / (
                    settings["rrf_k"] + rank
                )
                diagnostics.setdefault(key, {})[name] = {"rank": rank, "score": score}
        for key in scores:
            state = selected[key].get("status")
            scores[key] *= (
                settings["active_boost"]
                if state == "active"
                else settings["historical_weight"]
                if state in ("closed", "paused", "deprecated")
                else 1
            )
        results, counts = [], Counter()
        for key in sorted(scores, key=lambda key: (-scores[key], key)):
            point = selected[key]
            if counts[point["source_path"]] >= settings["per_source"]:
                continue
            counts[point["source_path"]] += 1
            results.append(
                {"id": key, "score": scores[key], "signals": diagnostics[key], "payload": point}
            )
            if len(results) == top_k:
                break
        return results


def evaluate(retriever, dataset):
    results = []
    for case in dataset:
        started = retriever.context.clock.monotonic()
        hits = retriever.search(case["query"], case.get("top_k", 5), case.get("filters"))
        passed = any(
            hit["payload"]["source_path"] == case["source_path"]
            and case.get("section", "").lower() in hit["payload"].get("section", "").lower()
            for hit in hits
        )
        results.append(
            {
                "query": case["query"],
                "passed": passed,
                "seconds": round(retriever.context.clock.monotonic() - started, 3),
                "sources": [hit["payload"]["source_path"] for hit in hits],
            }
        )
    return {"passed": all(row["passed"] for row in results), "cases": results}


def doctor(retriever):
    points = retriever.points
    expected = {
        "embedding_provider": "tei",
        "embedding_model": retriever.config["model_id"],
        "embedding_revision": retriever.config["model_revision"],
        "embedding_dimension": retriever.config["dimension"],
        "chunk_schema_version": retriever.config["schema_version"],
    }
    invalid = sum(
        any(p.get(key) != value for key, value in expected.items()) for p in points.values()
    )
    sizes = [len(tokens(p["content"])) for p in points.values()]
    smoke = evaluate(
        retriever,
        [
            {
                "query": "production knowledge Qdrant embeddings prohibition",
                "source_path": "docs/runa/handbook/ai/knowledge-system.md",
                "filters": {"source_path": "docs/runa/handbook/ai/knowledge-system.md"},
            }
        ],
    )
    return {
        **retriever.health,
        "invalid_metadata_chunks": invalid,
        "average_chunk_words": round(statistics.mean(sizes), 1) if sizes else 0,
        "median_chunk_words": statistics.median(sizes) if sizes else 0,
        "smoke": smoke,
        "passed": not invalid and bool(points) and smoke["passed"],
    }
