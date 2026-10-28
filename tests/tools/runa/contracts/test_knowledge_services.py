"""Knowledge dependency, scope and optional-startup refusal contracts."""

from __future__ import annotations

import io
import json
import shutil
import subprocess
import sys
import urllib.error
import urllib.request
from dataclasses import replace
from datetime import UTC, datetime
from email.message import Message
from pathlib import Path
from typing import Never

import pytest

from tests.tools.runa.support.knowledge import Provider, Store
from tools.application.knowledge.configuration import golden, load, validate
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.corpus import sources
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.startup_service import KnowledgeStartup
from tools.application.knowledge.transport import Http, RequestError
from tools.core.process import ProcessResult, ProcessSpec
from tools.core.state import JsonValue
from tools.maintainer.knowledge_cli import configuration

ROOT = Path(__file__).resolve().parents[4]


def workspace(root: Path) -> Path:
    for name in ("config.json", "golden.json"):
        target = root / "tools/application/knowledge" / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(ROOT / "tools/application/knowledge" / name, target)
    return root


def test_owner_additions_are_explicit_and_customer_load_never_opens_private_files(
    tmp_path: Path,
) -> None:
    root = workspace(tmp_path)
    owner = root / "tools/maintainer"
    owner.mkdir()
    marker = root / "docs/runa/maintainer/agent-contract.md"
    marker.parent.mkdir(parents=True)
    marker.write_text("Owner marker")
    (owner / "knowledge.json").write_text(
        json.dumps({"files": ["owner.yml"], "markdown_roots": ["docs/runa/maintainer"]})
    )
    (owner / "knowledge-golden.json").write_text('[{"query":"owner","source_path":"owner.md"}]')
    public = load(root)
    config, cases = configuration(root)
    assert "owner.yml" in config["files"] and "owner.yml" not in public["files"]
    assert len(cases) == len(golden(root)) + 1
    (owner / "knowledge.json").write_text("invalid private JSON")
    assert load(root) == public
    assert len(golden(root)) + 1 == len(cases)


def test_receipt_scope_rejects_private_sources_even_with_copied_owner_files(tmp_path: Path) -> None:
    root = workspace(tmp_path)
    subprocess.run(["git", "init", "-q"], cwd=root, check=True)
    config = load(root, additions={"files": [], "markdown_roots": ["docs/runa/maintainer"]})
    for name in (
        "docs/app/guide.md",
        "docs/runa/maintainer/private.md",
        "docs/runa/maintainer/customer-agent/AGENTS.md",
    ):
        path = root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text("# Fixture\n")
    subprocess.run(["git", "add", "."], cwd=root, check=True)
    # Even explicit owner composition cannot override a receipt, including a dangling one.
    (root / ".git/runa-bootstrap.json").symlink_to(root / "missing-receipt")
    context = replace(KnowledgeContext.for_workspace(root), owner_corpus=True)
    assert [p.relative_to(root).as_posix() for p in sources(root, config, context=context)] == [
        "docs/app/guide.md"
    ]


def test_invalid_config_refused_before_network_or_collection_changes(tmp_path: Path) -> None:
    config = load(workspace(tmp_path))
    for replacement in (
        {"dimension": True},
        {"chunk_tokens": 999},
        {"collection": "../other"},
        {"cpu_url": "http://secret:credential@embeddings"},
        {"files": ["../secret"]},
        {"retrieval": {**config["retrieval"], "dense_weight": float("nan")}},
    ):
        with pytest.raises(ValueError):
            validate({**config, **replacement})


class FailingOpener:
    def __init__(self, status: int) -> None:
        self.status = status
        self.calls = 0

    def open(self, request: str | urllib.request.Request, /, *, timeout: float) -> Never:
        self.calls += 1
        raise urllib.error.HTTPError(
            request if isinstance(request, str) else request.full_url,
            self.status,
            "private response",
            Message(),
            io.BytesIO(b"secret"),
        )


def test_injected_http_retries_transient_errors_and_never_echoes_service_body() -> None:
    delays: list[float] = []
    opener = FailingOpener(503)
    with pytest.raises(RequestError) as failure:
        Http("http://service", opener=opener, sleep=delays.append).request("GET", "/info")
    assert opener.calls == 3 and delays == [0.25, 0.5]
    assert "secret" not in str(failure.value) and failure.value.status == 503
    permanent = FailingOpener(422)
    with pytest.raises(RequestError):
        Http("http://service", opener=permanent, sleep=delays.append).request("POST", "/embed")
    assert permanent.calls == 1 and delays == [0.25, 0.5]


def test_optional_startup_production_guard_precedes_http_process_and_retry(tmp_path: Path) -> None:
    root = workspace(tmp_path)
    (root / ".env").write_text("APP_ENV=production\n")
    context = KnowledgeContext.for_workspace(root)

    class Forbidden:
        def open(self, *args: object, **kwargs: object) -> Never:
            pytest.fail("Production guard reached HTTP")

        def run(self, spec: ProcessSpec) -> Never:
            pytest.fail("Production guard reached process")

    startup = KnowledgeStartup(
        replace(context, runner=Forbidden()),
        opener=Forbidden(),
        sleep=lambda _: pytest.fail("Production guard reached sleep"),
    )
    with pytest.raises(ValueError, match="production"):
        startup.probe()
    with pytest.raises(ValueError, match="production"):
        startup.initialize()


def test_optional_startup_bounds_probe_and_retries_without_running_index_on_failure(
    tmp_path: Path,
) -> None:
    root = workspace(tmp_path)
    (root / ".env").write_text("APP_ENV=local\n")
    calls: list[ProcessSpec] = []
    delays: list[float] = []

    class Runner:
        def run(self, spec: ProcessSpec) -> ProcessResult:
            calls.append(spec)
            return ProcessResult(1)

    startup = KnowledgeStartup(
        replace(KnowledgeContext.for_workspace(root), runner=Runner()), sleep=delays.append
    )
    assert startup.initialize() == 1
    assert len(calls) == 1 and calls[0].timeout == 5 and delays == [10]
    assert calls[0].argv[-1] == "probe"


def test_parser_relocation_changes_freshness_without_changing_collection_space(
    tmp_path: Path,
) -> None:
    root = workspace(tmp_path)
    subprocess.run(["git", "init", "-q", "-b", "fixture"], cwd=root, check=True)
    (root / "AGENTS.md").write_text("# Rules\n\nPreserve unchanged vectors.\n")
    subprocess.run(["git", "add", "."], cwd=root, check=True)
    subprocess.run(
        [
            "git",
            "-c",
            "user.name=Test",
            "-c",
            "user.email=test@example.invalid",
            "commit",
            "-qm",
            "fixture",
        ],
        cwd=root,
        check=True,
    )

    class Clock:
        def now(self) -> datetime:
            return datetime(2026, 10, 27, tzinfo=UTC)

        def monotonic(self) -> float:
            return 0.0

    Store.points, Store.present, Store.fail_upsert, Provider.calls = {}, False, False, []
    config = load(root)
    indexer = KnowledgeIndexer(
        replace(KnowledgeContext.for_workspace(root), clock=Clock()),
        config,
        Store(config),
        provider_factory=Provider,
        parser_fingerprint=lambda: "before-package-migration",
    )
    assert indexer.index() == 0
    before = set(Store.points)
    previous_space = Store.manifest_data()["space"].copy()
    Provider.calls.clear()
    indexer.parser_fingerprint = lambda: "after-package-migration"
    assert indexer.status().get("parser_changed")
    assert indexer.index() == 0
    assert len(Provider.calls) == 1 and set(Store.points) == before
    assert Store.manifest_data()["space"] == previous_space
    assert Store.manifest_data().get("indexed_at") == "2026-10-27T00:00:00+00:00"


def test_physical_customer_projection_has_no_owner_import_dependency(tmp_path: Path) -> None:
    for package in ("tools", "tools/core", "tools/application", "tools/application/knowledge"):
        (tmp_path / package).mkdir(parents=True, exist_ok=True)
    shutil.copyfile(ROOT / "tools/__init__.py", tmp_path / "tools/__init__.py")
    shutil.copytree(
        ROOT / "tools/core",
        tmp_path / "tools/core",
        dirs_exist_ok=True,
        ignore=shutil.ignore_patterns("__pycache__"),
    )
    shutil.copytree(
        ROOT / "tools/application",
        tmp_path / "tools/application",
        dirs_exist_ok=True,
        ignore=shutil.ignore_patterns("__pycache__"),
    )
    code = 'from tools.application.knowledge import cli, startup_cli, ingest, retrieval; from tools.application.knowledge.configuration import load; from pathlib import Path; assert load(Path.cwd())["dimension"] == 384'
    result = subprocess.run(
        [sys.executable, "-B", "-c", code], cwd=tmp_path, capture_output=True, text=True
    )
    assert result.returncode == 0, result.stderr


def test_invalid_remote_manifest_refuses_before_any_write(tmp_path: Path) -> None:
    from tools.application.knowledge.store import Store as QdrantStore

    calls: list[str] = []

    class HttpClient:
        base = "http://qdrant"

        def request(self, method: str, path: str, body: object = None) -> JsonValue:
            calls.append(method)
            return {
                "result": [
                    {"payload": {"space": {}, "sources": {"AGENTS.md": {"chunks": "invalid"}}}}
                ]
            }

    store = QdrantStore(load(workspace(tmp_path)), http=HttpClient())
    with pytest.raises(ValueError, match="source manifest"):
        store.manifest()
    assert calls == ["POST"]


def test_injected_provider_rejects_gpu_model_mismatch_without_cpu_fallback(tmp_path: Path) -> None:
    from tools.application.knowledge.provider import Embeddings

    config = load(workspace(tmp_path))
    calls: list[str] = []

    class Client:
        def __init__(self, base: str) -> None:
            self.base = base

        def request(self, method: str, path: str, body: object = None) -> JsonValue:
            calls.append(self.base)
            return {"model_id": "wrong-model"}

    with pytest.raises(ValueError, match="mismatch"):
        Embeddings(config, http_factory=Client)
    assert calls == [config["cuda_url"]]
