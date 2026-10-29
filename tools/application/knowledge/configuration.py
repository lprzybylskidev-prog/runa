"""Delivered configuration; owner additions enter only through explicit composition."""

import json
import math
import re
from pathlib import Path
from typing import Literal, NotRequired, TypedDict, cast
from urllib.parse import urlsplit

from tools.core.state import json_value

Purpose = Literal["current_guidance", "historical_evidence", "proposal", "unknown"]


class Decision(TypedDict):
    id: str
    problem: str
    solution: str
    rationale: str | None
    status: Literal["accepted", "superseded", "proposed", "unknown"]
    date: str | None
    version: str | None
    supersedes: list[str]


class Applicability(TypedDict):
    purpose: Purpose
    effective_date: str | None
    applicable_version: str | None
    decisions: list[Decision]


class MetadataRule(TypedDict):
    pattern: str
    purpose: Purpose


class SourceAnnotation(TypedDict):
    source_path: str
    source_hash: str
    section: str
    metadata: Applicability


class RetrievalConfig(TypedDict):
    candidates: int
    dense_weight: float
    lexical_weight: float
    rrf_k: float
    per_source: int
    active_boost: float
    historical_weight: float


class KnowledgeConfig(TypedDict):
    payload_schema_version: NotRequired[int]
    metadata_rules: NotRequired[list[MetadataRule]]
    annotations: NotRequired[list[SourceAnnotation]]
    collection: str
    schema_version: int
    model_id: str
    model_revision: str
    runtime_version: str
    dimension: int
    max_input_tokens: int
    chunk_tokens: int
    batch_size: int
    query_prefix: str
    qdrant_url: str
    cpu_url: str
    cuda_url: str
    files: list[str]
    markdown_roots: list[str]
    pooling: str
    dtype: str
    normalize: bool
    retrieval: RetrievalConfig


class GoldenCase(TypedDict):
    query: str
    source_path: str
    top_k: NotRequired[int]
    section: NotRequired[str]
    filters: NotRequired[dict[str, str]]


def source_paths(value: object) -> list[str]:
    if not isinstance(value, list) or not all(
        isinstance(item, str)
        and item
        and not item.startswith("/")
        and all(part not in ("", ".", "..") for part in item.split("/"))
        and "\\" not in item
        and "\0" not in item
        for item in cast(list[object], value)
    ):
        raise ValueError("Knowledge source paths must be relative, literal paths.")
    return cast(list[str], value)


def validate(value: object) -> KnowledgeConfig:
    if not isinstance(value, dict):
        raise ValueError("Knowledge configuration must be an object.")
    data = cast(dict[str, object], value)
    for key in ("schema_version", "dimension", "max_input_tokens", "chunk_tokens", "batch_size"):
        if type(data.get(key)) is not int or cast(int, data[key]) <= 0:
            raise ValueError("Knowledge limits must be positive integers.")
    if cast(int, data["chunk_tokens"]) > cast(int, data["max_input_tokens"]):
        raise ValueError("Chunk budget exceeds the embedding input limit.")
    for key in ("collection", "model_id", "model_revision", "runtime_version", "pooling", "dtype"):
        if not isinstance(data.get(key), str) or not data[key]:
            raise ValueError("Knowledge identity fields must be nonempty strings.")
    if not re.fullmatch(r"[A-Za-z0-9_-]+", cast(str, data["collection"])):
        raise ValueError("Invalid knowledge collection identity.")
    if type(data.get("normalize")) is not bool or not isinstance(data.get("query_prefix"), str):
        raise ValueError("Invalid embedding normalization/query prefix.")
    for key in ("qdrant_url", "cpu_url", "cuda_url"):
        url = urlsplit(str(data.get(key, "")))
        if (
            url.scheme not in ("http", "https")
            or not url.hostname
            or url.username
            or url.password
            or url.query
            or url.fragment
        ):
            raise ValueError("Knowledge endpoints must be credential-free HTTP service URLs.")
    for key in ("files", "markdown_roots"):
        source_paths(data.get(key))
    settings = data.get("retrieval")
    if not isinstance(settings, dict):
        raise ValueError("Missing retrieval configuration.")
    settings = cast(dict[str, object], settings)
    for key in ("dense_weight", "lexical_weight", "rrf_k", "active_boost", "historical_weight"):
        number = settings.get(key)
        if (
            isinstance(number, bool)
            or not isinstance(number, (int, float))
            or not math.isfinite(number)
            or number <= 0
        ):
            raise ValueError("Retrieval weights must be positive finite numbers.")
    for key in ("candidates", "per_source"):
        number = settings.get(key)
        if type(number) is not int or not 1 <= number <= 1000:
            raise ValueError("Retrieval limits must be integers between 1 and 1000.")
    from tools.application.knowledge.applicability import validate_policy

    validate_policy(data)
    return cast(KnowledgeConfig, data)


def load(root: Path, *, additions: dict[str, object] | None = None) -> KnowledgeConfig:
    config = validate(json.loads((root / "tools/application/knowledge/config.json").read_text()))
    if additions is not None:
        if not {"files", "markdown_roots"} <= set(additions) or set(additions) - {
            "files",
            "markdown_roots",
            "metadata_rules",
            "annotations",
        }:
            raise ValueError(
                "Owner knowledge configuration may only add source paths and reviewed metadata."
            )
        composed: dict[str, object] = dict(config)
        for key, value in additions.items():
            if key in ("files", "markdown_roots"):
                existing = config[cast(Literal["files", "markdown_roots"], key)]
                composed[key] = list(dict.fromkeys([*existing, *source_paths(value)]))
            elif key == "metadata_rules":
                if not isinstance(value, list):
                    raise ValueError("Metadata rules must be a list.")
                composed[key] = [*config.get("metadata_rules", []), *value]
            else:
                if not isinstance(value, list):
                    raise ValueError("Annotations must be a list.")
                composed[key] = [*config.get("annotations", []), *value]
        config = validate(composed)
    return config


def golden(root: Path) -> list[GoldenCase]:
    return golden_file(root / "tools/application/knowledge/golden.json")


def golden_file(path: Path) -> list[GoldenCase]:
    if path.is_symlink():
        raise ValueError("Knowledge golden configuration must not be a symlink.")
    data = json_value(json.loads(path.read_text()))
    if not isinstance(data, list):
        raise ValueError("Golden expectations must be a list.")
    for case in data:
        if (
            not isinstance(case, dict)
            or not isinstance(case.get("query"), str)
            or not str(case["query"]).strip()
        ):
            raise ValueError("Invalid golden query.")
        source_paths([case.get("source_path")])
        top_k = case.get("top_k", 5)
        if type(top_k) is not int or not 1 <= top_k <= 50:
            raise ValueError("Invalid golden top-k.")
        if not isinstance(case.get("section", ""), str):
            raise ValueError("Invalid golden section.")
        filters = case.get("filters", {})
        if not isinstance(filters, dict) or any(
            key not in ("document_type", "scope", "roadmap_id", "status", "source_path")
            or not isinstance(value, str)
            for key, value in filters.items()
        ):
            raise ValueError("Invalid golden filters.")
    return cast(list[GoldenCase], data)
