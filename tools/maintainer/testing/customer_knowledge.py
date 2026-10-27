"""Fresh customer Qdrant acceptance inside the existing isolated installation fixture."""

from __future__ import annotations

import json
import subprocess
from collections.abc import Callable, Mapping
from pathlib import Path

from tools.maintainer.testing.acceptance import (
    AcceptanceCall,
    AcceptanceContext,
    AcceptanceOutput,
    Check,
    ComposeFixture,
    Report,
)


def model_cache(owner: AcceptanceContext) -> str:
    identities = owner.output(owner.compose("ps", "--quiet", "embeddings")).splitlines()
    if len(identities) != 1:
        raise ValueError("One active owner embeddings service is required for cache reuse.")
    inspected = json.loads(owner.output(["docker", "inspect", identities[0]]))
    caches = [
        mount["Name"]
        for item in inspected
        for mount in item["Mounts"]
        if mount["Type"] == "volume" and mount["Destination"] == "/data"
    ]
    if len(caches) != 1 or not isinstance(caches[0], str) or not caches[0]:
        raise ValueError("One inspected model cache is required; owner knowledge is never copied.")
    return caches[0]


def exercise(
    root: Path,
    project: str,
    data: ComposeFixture,
    env: Mapping[str, str],
    check: Check,
    report: Report,
    call: AcceptanceCall,
    output: AcceptanceOutput,
    yaml_source: Callable[[str], ComposeFixture],
    *,
    owner: AcceptanceContext,
) -> None:
    compose = [
        "docker",
        "compose",
        "--project-directory",
        str(root),
        "--env-file",
        str(root / ".env.dev.local"),
        "-f",
        str(root / "compose.yaml"),
        "-f",
        str(root / ".devcontainer/compose.resources.yaml"),
    ]

    def command(*args: str) -> subprocess.CompletedProcess[str]:
        return call([*compose, *args], cwd=root, env=env)

    def app(*args: str) -> str:
        return output(["docker", "exec", "-w", "/workspace", project + "-app-1", *args])

    # Resolve the owner's actual Compose service, independent of its project name.
    cache = model_cache(owner)
    source = yaml_source("compose.yaml")
    for key in ("qdrant", "embeddings", "knowledge-init"):
        data["services"][key] = source["services"][key]
    data["volumes"]["qdrant-data"] = {}
    data["volumes"]["embedding-cache"] = {"external": True, "name": cache}
    (root / "compose.yaml").write_text(json.dumps(data, indent=2) + "\n")
    command("up", "-d", "--no-build", "--wait", "--wait-timeout", "240", "qdrant", "embeddings")
    actual = json.loads(output(["docker", "inspect", project + "-qdrant-1"]))[0]
    volumes = [
        m["Name"]
        for m in actual["Mounts"]
        if m["Type"] == "volume" and m["Destination"] == "/qdrant/storage"
    ]
    check(
        volumes == [project + "_qdrant-data"],
        "fresh customer Qdrant uses the actual isolated project volume",
    )
    empty = json.loads(
        app(
            "python3",
            "-c",
            'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections").read().decode())',
        )
    )
    check(
        empty["result"]["collections"] == [],
        "customer Qdrant contains no collections before first initialization",
    )
    command("up", "-d", "--no-build", "--no-deps", "knowledge-init")
    command("wait", "knowledge-init")
    status = json.loads(app("php", "artisan", "runa:knowledge:status"))
    check(
        not status["stale"] and status["documents"] > 0,
        "real first-start initialization indexes delivered customer sources",
    )
    app("php", "artisan", "runa:knowledge:doctor")
    app("php", "artisan", "runa:knowledge:eval")
    selected = app("php", "artisan", "runa:quality:plan", "--group=docs", "--group=tooling")
    check(
        "Excluded groups (not run):" in selected and "runa:security:trivy" not in selected,
        "real customer group preview excludes image acceptance without maintainer tooling",
    )
    all_groups = app("php", "artisan", "runa:quality:plan", "--all")
    check(
        "Excluded groups (not run): none" in all_groups and "tools/maintainer" not in all_groups,
        "real customer all-group preview uses delivered application commands only",
    )
    probe = """import json
from pathlib import Path
from tools.application.knowledge.configuration import load
from tools.application.knowledge.store import Store
config=load(Path.cwd()); points=Store(config).all_points()
paths={p.get('source_path') for p in points.values() if p.get('kind')=='chunk'}
assert 'AGENTS.md' in paths
assert '.agents/skills/frontend/references/application.md' in paths
assert not any('/maintainer/' in p for p in paths)
assert not any('Owner Agent Contract' in p.get('content','') for p in points.values())
print(json.dumps(sorted(paths)))"""
    paths = json.loads(app("python3", "-c", probe))
    before = app(
        "python3",
        "-c",
        'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections/runa_knowledge").read().decode())',
    )
    app("php", "artisan", "runa:knowledge:initialize")
    after = app(
        "python3",
        "-c",
        'import urllib.request; print(urllib.request.urlopen("http://qdrant:6333/collections/runa_knowledge").read().decode())',
    )
    check(
        json.loads(before)["result"]["points_count"] == json.loads(after)["result"]["points_count"],
        "repeat initialization preserves the existing collection and points",
    )
    document = root / "docs/app/knowledge-acceptance.md"
    document.write_text("# Application sentinel\n\nCustomer knowledge incremental acceptance.\n")
    call(["git", "-C", str(root), "add", "docs/app/knowledge-acceptance.md"])
    app("php", "artisan", "runa:knowledge:index")
    check(
        "docs/app/knowledge-acceptance.md" in json.loads(app("python3", "-c", probe)),
        "incremental indexing adds application knowledge",
    )
    call(["git", "-C", str(root), "rm", "-f", "docs/app/knowledge-acceptance.md"])
    app("php", "artisan", "runa:knowledge:index")
    check(
        "docs/app/knowledge-acceptance.md" not in json.loads(app("python3", "-c", probe)),
        "incremental indexing removes obsolete source identities",
    )
    hits = app(
        "php",
        "artisan",
        "runa:knowledge:search",
        "application protected platform source",
        "--top-k=5",
    )
    check(
        "maintainer/" not in hits and bool(hits.strip()),
        "fresh customer retrieval returns delivered sources only",
    )
    report["customer_knowledge"] = {
        "qdrant_volume": volumes[0],
        "preserved_model_cache": cache,
        "sources": paths,
    }
    # The outer fixture owns scoped cleanup. External model cache is never removed.
