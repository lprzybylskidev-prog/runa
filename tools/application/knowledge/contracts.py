"""Typed collection payloads and narrow external-effect boundaries."""

from typing import NotRequired, Protocol, TypedDict

from tools.application.knowledge.configuration import KnowledgeConfig


class SourceMetadata(TypedDict):
    document_id: str
    source_path: str
    document_type: str
    scope: str
    roadmap_id: str | None
    status: str | None
    tags: list[str]
    source_hash: str
    kind: NotRequired[str]
    chunk_id: NotRequired[str]
    chunk_schema_version: NotRequired[int]
    heading_path: NotRequired[list[str]]
    git_branch: NotRequired[str]
    git_commit: NotRequired[str]
    embedding_provider: NotRequired[str]
    embedding_model: NotRequired[str]
    embedding_revision: NotRequired[str]
    embedding_dimension: NotRequired[int]
    indexed_at: NotRequired[str]


class ChunkPayload(SourceMetadata):
    section: str
    content: str
    content_hash: str


class Point(TypedDict):
    id: str
    payload: ChunkPayload
    vector: NotRequired[list[float]]


class SourceState(TypedDict):
    source_hash: str
    document_id: str
    chunks: list[str]


class Manifest(TypedDict):
    space: dict[str, object]
    sources: dict[str, SourceState]
    pending: NotRequired[bool]
    parser_hash: NotRequired[str]
    git_branch: NotRequired[str]
    git_commit: NotRequired[str]
    indexed_at: NotRequired[str]
    kind: NotRequired[str]


class Health(TypedDict):
    state: str
    stale: bool
    documents: NotRequired[int]
    chunks: NotRequired[int]
    changed: NotRequired[list[str]]
    deleted: NotRequired[list[str]]
    missing_chunks: NotRequired[int]
    orphan_chunks: NotRequired[int]
    interrupted: NotRequired[bool]
    branch_changed: NotRequired[bool]
    parser_changed: NotRequired[bool]
    embedded_chunks: NotRequired[int]
    removed_chunks: NotRequired[int]


class EmbeddingProvider(Protocol):
    def counts(self, texts: list[str]) -> list[int]: ...
    def embed(self, texts: list[str]) -> list[list[float]]: ...


class ProviderFactory(Protocol):
    def __call__(self, config: KnowledgeConfig, device: str) -> EmbeddingProvider: ...


class QueryPoint(TypedDict):
    id: str
    score: float


class KnowledgeStore(Protocol):
    def exists(self) -> bool: ...
    def create(self) -> None: ...
    def drop(self) -> None: ...
    def manifest(self) -> Manifest | None: ...
    def write_manifest(self, payload: Manifest) -> None: ...
    def all_points(self) -> dict[str, ChunkPayload]: ...
    def upsert(self, points: list[Point]) -> None: ...
    def payload(self, point_id: str, payload: ChunkPayload) -> None: ...
    def delete(self, ids: list[str]) -> None: ...
    def query(self, body: dict[str, object]) -> list[QueryPoint]: ...


class RankSignal(TypedDict):
    rank: int
    score: float


class SearchHit(TypedDict):
    id: str
    score: float
    signals: dict[str, RankSignal]
    payload: ChunkPayload


class CaseResult(TypedDict):
    query: str
    passed: bool
    seconds: float
    sources: list[str]


class Evaluation(TypedDict):
    passed: bool
    cases: list[CaseResult]


class DoctorReport(Health):
    invalid_metadata_chunks: int
    average_chunk_words: float
    median_chunk_words: float
    smoke: Evaluation
    passed: bool
