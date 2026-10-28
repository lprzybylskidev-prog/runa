"""Consumed provider/storage contracts with scripted HTTP and no live services."""

from __future__ import annotations

from collections import deque
from pathlib import Path

import pytest

from tests.tools.runa.support.knowledge import Client, capabilities
from tools.application.knowledge.configuration import load
from tools.application.knowledge.provider import Embeddings
from tools.application.knowledge.store import Store
from tools.application.knowledge.transport import RequestError
from tools.core.state import JsonValue

ROOT = Path(__file__).resolve().parents[4]


@pytest.mark.parametrize(
    "override",
    [
        {"auto_truncate": None},
        {"max_input_length": True},
        {"max_client_batch_size": True},
        {"max_client_batch_size": "invalid"},
    ],
)
def test_provider_capability_types_refuse_before_tokenization_or_cpu_fallback(
    override: dict[str, JsonValue],
) -> None:
    config = load(ROOT)
    clients: list[Client] = []

    def factory(base: str) -> Client:
        client = Client(base, [{**capabilities(config), **override}])
        clients.append(client)
        return client

    with pytest.raises(ValueError, match="contract|batch limit"):
        Embeddings(config, http_factory=factory)
    assert clients[0].calls == []
    assert clients[1].calls == [("GET", "/info", None)]


def test_provider_missing_capability_is_a_safe_contract_error() -> None:
    config = load(ROOT)
    info = capabilities(config)
    del info["max_input_length"]
    client = Client(config["cpu_url"], [info])
    with pytest.raises(ValueError, match="input limits"):
        Embeddings(config, "cpu", http_factory=lambda _: client)
    assert client.calls == [("GET", "/info", None)]


def test_provider_transient_gpu_failure_validates_cpu_and_preserves_request() -> None:
    config = load(ROOT)
    cpu = Client(config["cpu_url"], [capabilities(config), [[1, 2, 3]]])
    gpu = Client(config["cuda_url"], [capabilities(config), RequestError("unavailable", 503)])
    provider = Embeddings(config, http_factory=lambda base: cpu if base == cpu.base else gpu)
    assert provider.counts(["technical query"]) == [3]
    assert provider.client is cpu
    assert cpu.calls == [
        ("GET", "/info", None),
        ("POST", "/tokenize", {"inputs": ["technical query"], "add_special_tokens": True}),
    ]
    assert gpu.calls[-1] == cpu.calls[-1]


@pytest.mark.parametrize("bad_value", [True, float("nan"), float("inf")])
def test_provider_rejects_boolean_or_nonfinite_embedding_coordinates(bad_value: float) -> None:
    config = load(ROOT)
    client = Client(
        config["cpu_url"],
        [capabilities(config), [[1]], [[bad_value, *(0 for _ in range(config["dimension"] - 1))]]],
    )
    provider = Embeddings(config, "cpu", http_factory=lambda _: client)
    with pytest.raises(ValueError, match="dimension or non-finite"):
        provider.embed(["query"])
    assert [path for _, path, _ in client.calls] == ["/info", "/tokenize", "/embed"]


def test_provider_normalizes_no_data_and_retains_numeric_vectors() -> None:
    config = load(ROOT)
    vector: list[JsonValue] = [1, *(0.0 for _ in range(config["dimension"] - 1))]
    client = Client(config["cpu_url"], [capabilities(config), [[1, 2]], [vector]])
    provider = Embeddings(config, "cpu", http_factory=lambda _: client)
    assert provider.embed([]) == []
    assert provider.embed(["query"]) == [vector]
    assert client.calls[-1][2] == {
        "inputs": ["query"],
        "truncate": False,
        "normalize": config["normalize"],
    }


@pytest.mark.parametrize("score", [True, float("inf"), "private invalid field"])
def test_store_rejects_invalid_query_scores_without_echoing_response(score: JsonValue) -> None:
    config = load(ROOT)
    client = Client(
        config["qdrant_url"], [{"result": {"points": [{"id": "chunk", "score": score}]}}]
    )
    with pytest.raises(ValueError, match="^Invalid Qdrant query points.$"):
        Store(config, http=client).query({"query": [1.0]})
    assert client.calls == [
        ("POST", "/collections/" + config["collection"] + "/points/query", {"query": [1.0]})
    ]


def test_retrieval_composes_typed_rankings_with_branch_and_source_filters(tmp_path: Path) -> None:
    from dataclasses import replace

    from tools.application.knowledge.context import KnowledgeContext
    from tools.application.knowledge.corpus import digest, metadata
    from tools.application.knowledge.ingest import parser_hash, space
    from tools.application.knowledge.retrieval import Retriever
    from tools.application.testing.support.process import RecordingRunner
    from tools.core.process import ProcessResult
    from tools.core.state import json_value

    config = load(ROOT)
    text = "# Rules\n\nExact identifier frobnicator_739.\n"
    source = tmp_path / "AGENTS.md"
    source.write_text(text)
    payload = {
        **metadata(source, tmp_path, text),
        "kind": "chunk",
        "git_branch": "fixture",
        "content": text,
        "section": "Rules",
        "content_hash": digest(text),
    }
    manifest = {
        "space": space(config),
        "sources": {
            "AGENTS.md": {
                "source_hash": digest(text),
                "document_id": payload["document_id"],
                "chunks": ["chunk"],
            }
        },
        "parser_hash": parser_hash(),
        "git_branch": "fixture",
    }
    scroll = json_value(
        {"result": {"points": [{"id": "chunk", "payload": payload}], "next_page_offset": None}}
    )
    client = Client(
        config["qdrant_url"],
        [
            {
                "result": {
                    "config": {
                        "params": {"vectors": {"size": config["dimension"], "distance": "Cosine"}}
                    }
                }
            },
            json_value({"result": [{"payload": manifest}]}),
            scroll,
            scroll,
            {"result": {"points": [{"id": "chunk", "score": 0.75}, {"id": "foreign", "score": 1}]}},
        ],
    )
    runner = RecordingRunner(
        deque(
            [
                ProcessResult(0, stdout=b"AGENTS.md\0"),
                ProcessResult(0, stdout=b"fixture\n"),
                ProcessResult(0, stdout=b"fixture\n"),
            ]
        )
    )
    context = replace(KnowledgeContext.for_workspace(tmp_path), runner=runner)

    class Provider:
        def counts(self, texts: list[str]) -> list[int]:
            return [1 for _ in texts]

        def embed(self, texts: list[str]) -> list[list[float]]:
            assert texts == [config["query_prefix"] + "frobnicator_739"]
            return [[1.0]]

    retriever = Retriever(
        tmp_path,
        config,
        context=context,
        store=Store(config, http=client),
        provider_factory=lambda config, device: Provider(),
    )
    hits = retriever.search("frobnicator_739", filters={"source_path": "AGENTS.md"})
    assert len(hits) == 1 and hits[0]["id"] == "chunk"
    assert hits[0]["signals"]["dense"] == {"rank": 1, "score": 0.75}
    assert hits[0]["signals"]["lexical"]["rank"] == 1
    assert hits[0]["payload"] == payload
    assert client.calls[-1][2] == {
        "query": [1.0],
        "limit": config["retrieval"]["candidates"],
        "with_payload": False,
        "filter": {
            "must": [
                {"key": "kind", "match": {"value": "chunk"}},
                {"key": "git_branch", "match": {"value": "fixture"}},
                {"key": "source_path", "match": {"value": "AGENTS.md"}},
            ]
        },
    }
    for top_k in (True, 0, 51):
        with pytest.raises(ValueError, match="top-k"):
            retriever.search("frobnicator_739", top_k=top_k)
    assert not client.responses and not runner.outcomes
