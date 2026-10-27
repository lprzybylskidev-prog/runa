"""Offline provider and storage fixtures shared by private knowledge contracts."""

from __future__ import annotations

import copy
from collections import deque
from typing import ClassVar, cast

from tools.application.knowledge.configuration import KnowledgeConfig
from tools.application.knowledge.contracts import ChunkPayload, Manifest, Point, QueryPoint
from tools.application.knowledge.transport import RequestError
from tools.core.state import JsonValue


class Provider:
    calls: ClassVar[list[str]] = []

    def __init__(self, config: KnowledgeConfig, device: str = "auto") -> None:
        pass

    def counts(self, texts: list[str]) -> list[int]:
        return [len(text.split()) + 2 for text in texts]

    def embed(self, texts: list[str]) -> list[list[float]]:
        self.calls.extend(texts)
        return [[1.0] + [0.0] * 383 for _ in texts]


class Store:
    # Deliberately accepts malformed evidence injected by refusal tests. Operational
    # methods expose the same consumed contracts as the real storage boundary.
    points: ClassVar[dict[str, object]] = {}
    present = False
    fail_upsert = False

    def __init__(self, config: KnowledgeConfig) -> None:
        pass

    def exists(self) -> bool:
        return self.present

    def create(self) -> None:
        type(self).present = True

    def drop(self) -> None:
        type(self).points = {}
        type(self).present = False

    @classmethod
    def manifest_data(cls) -> Manifest:
        return cast(Manifest, cls.points["manifest"])

    def manifest(self) -> Manifest | None:
        value = self.points.get("manifest")
        return cast(Manifest, copy.deepcopy(value)) if value is not None else None

    def write_manifest(self, payload: Manifest) -> None:
        self.points["manifest"] = copy.deepcopy(payload)

    def all_points(self) -> dict[str, ChunkPayload]:
        return {
            key: cast(ChunkPayload, copy.deepcopy(value))
            for key, value in self.points.items()
            if key != "manifest"
        }

    def upsert(self, points: list[Point]) -> None:
        if self.fail_upsert:
            raise RuntimeError("Simulated interrupted storage write")
        for point in points:
            self.points[point["id"]] = copy.deepcopy(point["payload"])

    def payload(self, point_id: str, payload: ChunkPayload) -> None:
        self.points[point_id] = copy.deepcopy(payload)

    def delete(self, ids: list[str]) -> None:
        for key in ids:
            self.points.pop(key)

    def query(self, body: dict[str, object]) -> list[QueryPoint]:
        raise AssertionError("Ingestion fixture must not execute retrieval queries")


class Client:
    def __init__(self, base: str, responses: list[JsonValue | RequestError]) -> None:
        self.base = base
        self.responses = deque(responses)
        self.calls: list[tuple[str, str, object]] = []

    def request(self, method: str, path: str, body: object = None) -> JsonValue:
        self.calls.append((method, path, body))
        result = self.responses.popleft()
        if isinstance(result, RequestError):
            raise result
        return result


def capabilities(config: KnowledgeConfig) -> dict[str, JsonValue]:
    return {
        "model_id": config["model_id"],
        "model_sha": config["model_revision"],
        "version": config["runtime_version"],
        "model_dtype": config["dtype"],
        "model_type": {"embedding": {"pooling": config["pooling"]}},
        "max_input_length": config["max_input_tokens"],
        "auto_truncate": False,
        "max_client_batch_size": config["batch_size"],
        "unconsumed_metadata": "preserved",
    }
