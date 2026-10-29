"""Import-safe knowledge command adapter with explicit application composition."""

import argparse
import json
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime, execution_location
from tools.application.knowledge.configuration import GoldenCase, KnowledgeConfig, golden, load
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.evidence import read
from tools.application.knowledge.ingest import KnowledgeIndexer
from tools.application.knowledge.retrieval import (
    FILTERS,
    INTENTS,
    PURPOSE_WEIGHTS,
    REFERENCE_WEIGHT,
    Retriever,
    doctor,
    evaluate,
)
from tools.application.knowledge.store import Store
from tools.core.context import ExecutionContext, ExecutionLocation
from tools.core.lifecycle import interrupt_signals
from tools.core.process import InputMode, ProcessSpec
from tools.core.reporting import SafeFailure
from tools.core.state import FileLock, JsonStore, StateDirectory, json_value

READ_COMMANDS = frozenset(("read", "search", "status", "stale", "doctor", "eval"))


def operation_lock(root: Path, command: str) -> FileLock:
    """Coordinate one checkout: concurrent snapshots or one exclusive mutation."""
    shared = command in READ_COMMANDS
    return FileLock(
        StateDirectory(root / ".runtime/knowledge", private=False, anchor=root),
        "index.lock",
        private=False,
        shared=shared,
        busy_message=(
            "Knowledge read blocked by an exclusive writer; retry after it completes."
            if shared
            else "Knowledge write blocked by active readers or another writer; retry after completion."
        ),
    )


def run(
    argv: Sequence[str],
    *,
    context: KnowledgeContext,
    config: KnowledgeConfig | None = None,
    dataset: Sequence[GoldenCase] | None = None,
) -> int:
    ROOT = context.workspace.root
    parser = argparse.ArgumentParser(
        description="Development-only knowledge indexing and hybrid retrieval."
    )
    parser.add_argument(
        "command",
        choices=[
            "read",
            "search",
            "index",
            "status",
            "rebuild",
            "stale",
            "doctor",
            "eval",
            "initialize",
        ],
    )
    parser.add_argument(
        "--device",
        choices=["auto", "cpu"],
        default=None,
        help="Prefer CUDA when reachable, or force CPU for fallback acceptance.",
    )
    parser.add_argument("query", nargs="?")
    parser.add_argument("--top-k", type=int, default=5)
    parser.add_argument("--explain", action="store_true")
    parser.add_argument(
        "--json",
        action="store_true",
        help="Structured response (legacy JSON commands remain JSON).",
    )
    parser.add_argument("--intent", choices=INTENTS, default="legacy")
    parser.add_argument("--per-source", type=int)
    parser.add_argument("--neighbors", type=int, default=0)
    parser.add_argument(
        "--section", action="store_true", help="Expand chunks in the same heading section."
    )
    parser.add_argument("--max-chars", type=int, default=12000)
    for field in ("document_type", "scope", "roadmap_id", "status", "source_path"):
        parser.add_argument("--" + field.replace("_", "-"))
    args = parser.parse_args(argv)
    if args.command in ("search", "read") and not args.query:
        parser.error(args.command + " requires a query or chunk ID")
    if args.command != "read" and (args.neighbors or args.section or args.max_chars != 12000):
        parser.error("context expansion options require read")
    context.assert_development()
    if context.environment.get("APP_ENV") == "production" or not (ROOT / "artisan").exists():
        raise ValueError("Knowledge CLI is unavailable in production.")
    if execution_location() is ExecutionLocation.HOST:
        runtime = DevelopmentRuntime(
            ExecutionContext(context.workspace, context.workspace, ExecutionLocation.HOST)
        )
        return context.runner.run(
            ProcessSpec(
                runtime.app_command(("php", "artisan", "runa:knowledge:" + argv[0], *argv[1:])),
                ROOT,
                context.environment,
                stdin=InputMode.INHERIT,
            )
        ).returncode
    # Also inspect Laravel's actual local environment, rather than trusting the caller's default.
    environment = ROOT / ".env"
    if not environment.exists() or "APP_ENV=local" not in environment.read_text().splitlines():
        raise ValueError("Knowledge CLI requires the local development environment.")
    config = config or load(ROOT)
    dataset = golden(ROOT) if dataset is None else dataset
    # Retain stable inode/ownership/symlink safeguards for legacy coordination files.
    with operation_lock(ROOT, args.command).acquire():
        local = ROOT / ".env.dev.local"
        cuda = local.exists() and "EMBEDDINGS_CUDA=1" in local.read_text().splitlines()
        device = args.device or ("auto" if cuda else "cpu")
        indexer = KnowledgeIndexer(context, config, Store(config), device=device)
        if args.command == "read":
            result = read(
                indexer,
                args.query,
                neighbors=args.neighbors,
                section=args.section,
                max_chars=args.max_chars,
            )
            print(json.dumps(result, indent=2))
            return 0
        if args.command == "rebuild":
            return indexer.rebuild()
        if args.command in ("search", "doctor", "eval"):
            if args.command == "doctor":
                health = indexer.status()
                if health["stale"]:
                    print(
                        json.dumps(
                            {
                                **health,
                                "passed": False,
                                "instruction": "Run ./bin/artisan runa:knowledge:index; review model/schema mismatch before rebuild.",
                            },
                            indent=2,
                        )
                    )
                    return 1
            retriever = Retriever(ROOT, config, device, context=context, store=indexer.store)
            if args.command == "search":
                filters = {
                    key: getattr(args, key) for key in FILTERS if getattr(args, key) is not None
                }
                hits = retriever.search(
                    args.query, args.top_k, filters, intent=args.intent, per_source=args.per_source
                )
                if args.explain or args.json:
                    print(
                        json.dumps(
                            {
                                "filters": filters,
                                "retrieval": config["retrieval"],
                                "intent": args.intent,
                                "purpose_weights": PURPOSE_WEIGHTS.get(args.intent, {}),
                                "reference_weight": REFERENCE_WEIGHT,
                                "per_source": args.per_source,
                                "results": hits,
                            },
                            indent=2,
                        )
                    )
                else:
                    for rank, hit in enumerate(hits, 1):
                        p = hit["payload"]
                        print(
                            f"{rank}. {p['source_path']} — {p['section']}\n{p['content'][:420]}\n"
                        )
                    print("Open original source files before acting on retrieved rules.")
                return 0
            result = doctor(retriever) if args.command == "doctor" else evaluate(retriever, dataset)
            if args.command == "eval":
                report_dir = StateDirectory(ROOT / ".runtime/knowledge", private=False, anchor=ROOT)
                stamp = context.clock.now().strftime("%Y%m%dT%H%M%S%fZ")
                JsonStore(report_dir, max_bytes=2 * 1024 * 1024).write(
                    "evaluation-" + stamp + ".json", json_value(result)
                )
            print(json.dumps(result, indent=2))
            return 0 if result["passed"] else 1
        if args.command == "initialize":
            store = indexer.store
            # The shared CLI lock serializes existence checks and first writes.
            # An empty collection without a manifest can follow interrupted first setup.
            if not store.exists() or (not store.manifest() and not store.all_points()):
                return indexer.index()
            result = indexer.status()
            print(json.dumps(result, indent=2))
            if result["stale"]:
                print(
                    "Existing knowledge index requires attention; run ./bin/artisan runa:knowledge:index explicitly."
                )
            return 0
        if args.command == "index":
            return indexer.index()
        result = indexer.status()
        print(json.dumps(result, indent=2))
        if result["stale"]:
            print(
                "Run ./bin/artisan runa:knowledge:index before relying on knowledge results.",
                file=sys.stderr,
            )
        return 1 if result["stale"] else 0


def main(
    argv: Sequence[str] | None = None,
    *,
    context: KnowledgeContext | None = None,
    config: KnowledgeConfig | None = None,
    dataset: Sequence[GoldenCase] | None = None,
) -> int:
    try:
        with interrupt_signals():
            return run(
                list(sys.argv[1:] if argv is None else argv),
                context=context
                or KnowledgeContext.for_workspace(Path(__file__).resolve().parents[3]),
                config=config,
                dataset=dataset,
            )
    except KeyboardInterrupt:
        return 130
    except SafeFailure as error:
        print(str(error), file=sys.stderr)
        return error.exit_code
    except ValueError as error:
        print(
            "Error: "
            + (
                str(error)
                if not isinstance(error, (UnicodeError, json.JSONDecodeError))
                else "Invalid knowledge configuration/source encoding."
            ),
            file=sys.stderr,
        )
        return 1
    except (OSError, RuntimeError):
        print(
            "Knowledge command failed; check local configuration, services and index freshness.",
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
