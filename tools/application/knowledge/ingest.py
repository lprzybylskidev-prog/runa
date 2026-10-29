"""Incremental ingestion with prepare-before-write and recoverable remote state."""

import json
from collections.abc import Callable, Mapping
from pathlib import Path
from typing import cast

from tools.application.knowledge.applicability import policy_hash
from tools.application.knowledge.configuration import KnowledgeConfig
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.contracts import (
    Health,
    KnowledgeStore,
    Point,
    ProviderFactory,
    SourceState,
)
from tools.application.knowledge.corpus import chunks, digest, metadata, read_source, sources
from tools.application.knowledge.provider import Embeddings


def space(config: KnowledgeConfig) -> dict[str, object]:
    keys = (
        "schema_version",
        "model_id",
        "model_revision",
        "runtime_version",
        "dimension",
        "max_input_tokens",
        "chunk_tokens",
        "query_prefix",
        "pooling",
        "dtype",
        "normalize",
    )
    return {key: cast(Mapping[str, object], config)[key] for key in keys}


def parser_hash() -> str:
    return digest(
        "\n".join(
            Path(__file__).with_name(name).read_text() for name in ("corpus.py", "applicability.py")
        )
    )


def source_state(
    root: Path, config: KnowledgeConfig, *, context: KnowledgeContext | None = None
) -> dict[str, tuple[Path, str]]:
    return {
        p.relative_to(root).as_posix(): (p, read_source(p))
        for p in sources(root, config, context=context)
    }


def git_value(root: Path, *args: str) -> str:
    return KnowledgeContext.for_workspace(root).git_value(*args)


class KnowledgeIndexer:
    """One workspace and collection with explicit storage, provider and time seams."""

    def __init__(
        self,
        context: KnowledgeContext,
        config: KnowledgeConfig,
        store: KnowledgeStore,
        *,
        provider_factory: ProviderFactory = Embeddings,
        device: str = "auto",
        parser_fingerprint: Callable[[], str] = parser_hash,
    ) -> None:
        self.context = context
        self.config = config
        self.store = store
        self.provider_factory = provider_factory
        self.device = device
        self.parser_fingerprint = parser_fingerprint

    def status(self) -> Health:
        root, config, store = self.context.workspace.root, self.config, self.store
        if not store.exists():
            return {"state": "missing", "stale": True, "documents": 0, "chunks": 0}
        manifest = store.manifest()
        if not manifest or manifest.get("space") != space(config):
            return {"state": "incompatible", "stale": True}
        current = source_state(root, config, context=self.context)
        indexed = manifest.get("sources", {})
        changed = sorted(
            name
            for name, (_, text) in current.items()
            if name not in indexed or indexed[name]["source_hash"] != digest(text)
        )
        deleted = sorted(set(indexed) - set(current))
        points = store.all_points()
        expected = {chunk for document in indexed.values() for chunk in document["chunks"]}
        missing = sorted(expected - set(points))
        orphans = sorted(set(points) - expected)
        branch = self.context.git_value("branch", "--show-current")
        branch_changed = manifest.get("git_branch") != branch
        parser_changed = manifest.get("parser_hash") != self.parser_fingerprint()
        metadata_changed = manifest.get("metadata_policy_hash") != policy_hash(config)
        stale = bool(
            metadata_changed
            or parser_changed
            or changed
            or deleted
            or missing
            or orphans
            or manifest.get("pending")
            or branch_changed
        )
        return {
            "state": "stale" if stale else "current",
            "stale": stale,
            "documents": len(indexed),
            "chunks": len(points),
            "changed": changed,
            "deleted": deleted,
            "missing_chunks": len(missing),
            "orphan_chunks": len(orphans),
            "interrupted": bool(manifest.get("pending")),
            "branch_changed": branch_changed,
            "parser_changed": parser_changed,
            "metadata_changed": metadata_changed,
        }

    def index(self) -> int:
        root, config, store = self.context.workspace.root, self.config, self.store
        provider = self.provider_factory(config, self.device)
        # Prove dimensions before creating/writing a collection.
        provider.embed(["Runa development knowledge indexing capability check."])
        if not store.exists():
            store.create()
        previous = store.manifest()
        if previous and previous.get("space") != space(config):
            raise ValueError(
                "Knowledge model/schema mismatch; use an explicit rebuild after reviewing the change."
            )
        if not previous and store.all_points():
            raise ValueError("Unidentified non-empty knowledge collection; refusing to adopt it.")
        previous = previous or {"space": space(config), "sources": {}}
        current = store.all_points()
        branch = self.context.git_value("branch", "--show-current")
        commit = self.context.git_value("rev-parse", "HEAD")
        timestamp = self.context.clock.now().isoformat()
        documents = source_state(root, config, context=self.context)
        prepared: list[Point] = []
        state: dict[str, SourceState] = {}
        new_vectors = 0
        for name, (path, text) in documents.items():
            old = previous["sources"].get(name)
            if (
                old is not None
                and previous.get("parser_hash") == self.parser_fingerprint()
                and previous.get("metadata_policy_hash") == policy_hash(config)
                and old.get("source_hash") == digest(text)
                and all(
                    i in current
                    and current[i].get("payload_schema_version") == 2
                    and digest(current[i]["content"]) == current[i]["content_hash"]
                    for i in old["chunks"]
                )
            ):
                meta = metadata(path, root, text)
                rows: list[Point] = [{"id": i, "payload": current[i].copy()} for i in old["chunks"]]
            else:
                meta, rows = chunks(path, root, text, provider, config)
            changed = [
                row
                for row in rows
                if row["id"] not in current
                or current[row["id"]].get("content_hash") != row["payload"]["content_hash"]
            ]
            vectors = provider.embed([r["payload"]["content"] for r in changed]) if changed else []
            for row, vector in zip(changed, vectors, strict=True):
                row["vector"] = vector
            new_vectors += len(changed)
            for row in rows:
                # Refresh Git/source metadata without re-embedding unchanged semantic chunks.
                row["payload"] = {
                    **row["payload"],
                    **meta,
                    "section": row["payload"]["section"],
                    "content": row["payload"]["content"],
                    "content_hash": row["payload"]["content_hash"],
                }
                row["payload"].update(
                    git_branch=branch,
                    git_commit=commit,
                    embedding_provider="tei",
                    embedding_model=config["model_id"],
                    embedding_revision=config["model_revision"],
                    embedding_dimension=config["dimension"],
                )
                row["payload"].setdefault("indexed_at", None)
                row["payload"]["metadata_updated_at"] = timestamp
                if "vector" in row:
                    row["payload"]["indexed_at"] = timestamp
            prepared.extend(rows)
            state[name] = {
                "source_hash": meta["source_hash"],
                "document_id": meta["document_id"],
                "chunks": [r["id"] for r in rows],
            }
        for annotation in config.get("annotations", []):
            if not any(
                row["payload"]["source_path"] == annotation["source_path"]
                and row["payload"]["section"] == annotation["section"]
                for row in prepared
            ):
                raise ValueError(
                    "External annotation source/section is absent; review its coordinates."
                )
        # Reject sources modified while preparing vectors; no content writes have happened yet.
        if {
            n: digest(t) for n, (_, t) in source_state(root, config, context=self.context).items()
        } != {n: s["source_hash"] for n, s in state.items()}:
            raise ValueError("Knowledge sources changed during preparation; rerun index.")
        if (
            self.context.git_value("branch", "--show-current") != branch
            or self.context.git_value("rev-parse", "HEAD") != commit
        ):
            raise ValueError("Git context changed during preparation; rerun index.")
        # Remote pending survives loss of local state. An interrupted write never appears current.
        store.write_manifest({**previous, "pending": True})
        for start in range(0, len(prepared), 64):
            batch = [row for row in prepared[start : start + 64] if "vector" in row]
            if batch:
                store.upsert(batch)
        for row in prepared:
            if "vector" not in row and current[row["id"]] != row["payload"]:
                store.payload(row["id"], row["payload"])
        expected = {row["id"] for row in prepared}
        removed = sorted(set(current) - expected)
        store.delete(removed)
        store.write_manifest(
            {
                "space": space(config),
                "sources": state,
                "pending": False,
                "parser_hash": self.parser_fingerprint(),
                "metadata_policy_hash": policy_hash(config),
                "git_branch": branch,
                "git_commit": commit,
                "indexed_at": timestamp,
            }
        )
        result = self.status()
        result.update(embedded_chunks=new_vectors, removed_chunks=len(removed))
        print(json.dumps(result, indent=2))
        return 1 if result["stale"] else 0

    def rebuild(self) -> int:
        """Explicit development-only collection reset; prove provider readiness first."""
        from tools.application.knowledge.transport import RequestError

        root, config, store = self.context.workspace.root, self.config, self.store
        self.provider_factory(config, self.device).embed(["Rebuild capability check."])
        # Validate source parsing/token budgets before discarding the previous index.
        provider = self.provider_factory(config, self.device)
        for path, text in source_state(root, config, context=self.context).values():
            chunks(path, root, text, provider, config)
        try:
            store.drop()
        except RequestError as error:
            if error.status != 404:
                raise
        return self.index()
