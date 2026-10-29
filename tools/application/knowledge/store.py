"""Qdrant REST storage; collection identity and interrupted writes are explicit."""

import math
from typing import cast

from tools.application.knowledge.configuration import KnowledgeConfig
from tools.application.knowledge.contracts import ChunkPayload, Manifest, Point, QueryPoint
from tools.application.knowledge.corpus import identity
from tools.application.knowledge.transport import Http, JsonTransport, RequestError
from tools.core.state import JsonValue

MANIFEST = identity("index-manifest")


class Store:
    def __init__(self, config: KnowledgeConfig, *, http: JsonTransport | None = None) -> None:
        self.http = http if http is not None else Http(config["qdrant_url"])
        self.path = "/collections/" + config["collection"]
        self.config = config

    def result(self, method: str, path: str, body: object = None) -> JsonValue:
        response = self.http.request(method, path, body)
        if not isinstance(response, dict) or "result" not in response:
            raise ValueError("Invalid Qdrant response envelope.")
        return response["result"]

    def object_result(self, method: str, path: str, body: object = None) -> dict[str, JsonValue]:
        result = self.result(method, path, body)
        if not isinstance(result, dict):
            raise ValueError("Invalid Qdrant result object.")
        return result

    def exists(self) -> bool:
        try:
            result = self.object_result("GET", self.path)
        except RequestError as error:
            if error.status == 404:
                return False
            raise
        config = result.get("config")
        params = config.get("params") if isinstance(config, dict) else None
        vectors = params.get("vectors") if isinstance(params, dict) else None
        if not isinstance(vectors, dict):
            raise ValueError("Invalid Qdrant vector configuration.")
        if vectors.get("size") != self.config["dimension"] or vectors.get("distance") != "Cosine":
            raise ValueError("Qdrant vector configuration mismatch; explicit rebuild required.")
        return True

    def drop(self) -> None:
        self.http.request("DELETE", self.path)

    def query(self, body: dict[str, object]) -> list[QueryPoint]:
        points = self.object_result("POST", self.path + "/points/query", body).get("points")
        if not isinstance(points, list) or any(
            not isinstance(point, dict)
            or not isinstance(point.get("id"), str)
            or type(point.get("score")) not in (int, float)
            or not math.isfinite(cast(float, point["score"]))
            for point in points
        ):
            raise ValueError("Invalid Qdrant query points.")
        return cast(list[QueryPoint], points)

    def create(self) -> None:
        self.http.request(
            "PUT", self.path, {"vectors": {"size": self.config["dimension"], "distance": "Cosine"}}
        )

    def manifest(self) -> Manifest | None:
        points = self.result(
            "POST",
            self.path + "/points",
            {"ids": [MANIFEST], "with_payload": True, "with_vector": False},
        )
        if not isinstance(points, list):
            raise ValueError("Invalid Qdrant manifest points.")
        if not points:
            return None
        point = points[0]
        if not isinstance(point, dict):
            raise ValueError("Invalid Qdrant manifest point.")
        manifest = point.get("payload")
        if (
            not isinstance(manifest, dict)
            or not isinstance(manifest.get("space"), dict)
            or not isinstance(manifest.get("sources"), dict)
        ):
            raise ValueError("Invalid knowledge manifest; refusing collection writes.")
        sources = manifest["sources"]
        assert isinstance(sources, dict)
        for source in sources.values():
            if (
                not isinstance(source, dict)
                or not all(
                    isinstance(source.get(key), str) for key in ("source_hash", "document_id")
                )
                or not isinstance(source.get("chunks"), list)
                or not all(isinstance(key, str) for key in cast(list[object], source["chunks"]))
            ):
                raise ValueError("Invalid knowledge source manifest; refusing collection writes.")
        return cast(Manifest, manifest)

    def write_manifest(self, payload: Manifest) -> None:
        self.http.request(
            "PUT",
            self.path + "/points?wait=true",
            {
                "points": [
                    {
                        "id": MANIFEST,
                        "vector": [1.0] + [0.0] * (self.config["dimension"] - 1),
                        "payload": {**payload, "kind": "index_metadata"},
                    }
                ]
            },
        )

    def upsert(self, points: list[Point]) -> None:
        self.http.request("PUT", self.path + "/points?wait=true", {"points": points})

    def payload(self, point_id: str, payload: ChunkPayload) -> None:
        self.http.request(
            "PUT",
            self.path + "/points/payload?wait=true",
            {"points": [point_id], "payload": payload},
        )

    def delete(self, ids: list[str]) -> None:
        for start in range(0, len(ids), 128):
            self.http.request(
                "POST", self.path + "/points/delete?wait=true", {"points": ids[start : start + 128]}
            )

    def all_points(self) -> dict[str, ChunkPayload]:
        result: dict[str, ChunkPayload] = {}
        offset = None
        while True:
            body: dict[str, object] = {"limit": 256, "with_payload": True, "with_vector": False}
            if offset is not None:
                body["offset"] = offset
            response = self.object_result("POST", self.path + "/points/scroll", body)
            points = response.get("points")
            if not isinstance(points, list):
                raise ValueError("Invalid Qdrant scroll points.")
            for point in points:
                if (
                    not isinstance(point, dict)
                    or not isinstance(point.get("id"), str)
                    or not isinstance(point.get("payload"), dict)
                ):
                    raise ValueError("Invalid Qdrant knowledge point.")
                if point["id"] != MANIFEST:
                    payload = point["payload"]
                    assert isinstance(payload, dict)
                    if not all(
                        isinstance(payload.get(key), str)
                        for key in (
                            "document_id",
                            "source_path",
                            "document_type",
                            "scope",
                            "source_hash",
                            "content",
                            "section",
                            "content_hash",
                        )
                    ):
                        raise ValueError("Invalid knowledge chunk payload.")
                    if "payload_schema_version" in payload:
                        from tools.application.knowledge.applicability import validate_metadata

                        if payload["payload_schema_version"] != 2:
                            raise ValueError("Unsupported knowledge payload schema.")
                        validate_metadata(payload.get("applicability"))
                    identity = point["id"]
                    assert isinstance(identity, str)
                    result[identity] = cast(ChunkPayload, payload)
            offset = response.get("next_page_offset")
            if offset is None:
                return result
