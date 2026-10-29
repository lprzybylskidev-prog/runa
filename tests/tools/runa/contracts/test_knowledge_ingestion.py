#!/usr/bin/env python3
"""Offline knowledge contracts; fake storage/provider never touch developer vectors."""

from __future__ import annotations

import contextlib
import copy
import io
import subprocess
import tempfile
import unittest
from dataclasses import replace
from pathlib import Path
from unittest.mock import patch

from tests.tools.runa.support.knowledge import Client, Provider, Store, capabilities
from tools.application.knowledge import corpus, ingest
from tools.application.knowledge.context import KnowledgeContext
from tools.application.knowledge.contracts import SourceMetadata
from tools.application.knowledge.provider import Embeddings
from tools.application.knowledge.transport import RequestError
from tools.maintainer.knowledge_cli import configuration

ROOT = Path(__file__).resolve().parents[4]

CONFIG, _ = configuration(ROOT)
CONFIG["annotations"] = []


class KnowledgeContracts(unittest.TestCase):
    def setUp(self) -> None:
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        subprocess.run(["git", "init", "-q", "-b", "test-branch"], cwd=self.root, check=True)
        self.write("AGENTS.md", "# Policy\n\nKeep secrets out of the index.\n")
        self.track()
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
            cwd=self.root,
            check=True,
        )
        Store.points, Store.present, Store.fail_upsert, Provider.calls = {}, False, False, []
        self.context = replace(KnowledgeContext.for_workspace(self.root), owner_corpus=True)
        self.indexer = ingest.KnowledgeIndexer(
            self.context, CONFIG, Store(CONFIG), provider_factory=Provider, device="cpu"
        )

    def write(self, name: str, text: str) -> Path:
        p = self.root / name
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(text)
        return p

    def track(self) -> None:
        subprocess.run(["git", "add", "."], cwd=self.root, check=True)

    def index(self) -> int:
        with contextlib.redirect_stdout(io.StringIO()):
            return self.indexer.index()

    def test_evidence_read_preserves_source_order_budget_and_never_embeds(self) -> None:
        from tools.application.knowledge.evidence import read

        self.write(
            "AGENTS.md",
            "# Policy\n## First\nUnique first paragraph.\n## Second\nUnique second paragraph.\n## Third\nUnique third paragraph.\n",
        )
        self.index()
        manifest = self.indexer.store.manifest()
        assert manifest is not None
        ids = manifest["sources"]["AGENTS.md"]["chunks"]
        calls = list(Provider.calls)
        result = read(self.indexer, ids[1], neighbors=1)
        self.assertEqual([row["id"] for row in result["results"]], ids)
        self.assertEqual(result["omitted_chunks"], 0)
        self.assertEqual(result["results"][1]["citation"]["start_line"], 5)
        self.assertEqual(result["results"][1]["citation"]["end_line"], 5)
        self.assertEqual(Provider.calls, calls)
        with self.assertRaisesRegex(ValueError, "absent"):
            read(self.indexer, "00000000-0000-0000-0000-000000000001")
        self.write("AGENTS.md", "# Changed\nSource changed.\n")
        with self.assertRaisesRegex(ValueError, "stale"):
            read(self.indexer, ids[1])

    def test_evidence_read_reports_omitted_chunks_and_unknown_duplicate_coordinates(self) -> None:
        from tools.application.knowledge.evidence import read

        self.write(
            "AGENTS.md",
            "# Policy\n## Same\n"
            + ("Repeated text. " * 250)
            + "\n## Other\n"
            + ("Repeated text. " * 250)
            + "\n",
        )
        self.index()
        manifest = self.indexer.store.manifest()
        assert manifest is not None
        ids = manifest["sources"]["AGENTS.md"]["chunks"]
        result = read(self.indexer, ids[0], section=True, neighbors=1, max_chars=2000)
        self.assertLessEqual(result["content_characters"], 2000)
        self.assertGreater(result["omitted_chunks"], 0)
        self.assertIsNone(result["results"][0]["citation"]["start_line"])
        with self.assertRaisesRegex(ValueError, "exceeds evidence budget"):
            read(self.indexer, ids[0], max_chars=1000)
        with self.assertRaisesRegex(ValueError, "UUID"):
            read(self.indexer, "../../outside")
        manifest["pending"] = True
        self.indexer.store.write_manifest(manifest)
        with self.assertRaisesRegex(ValueError, "stale"):
            read(self.indexer, ids[0])

    def test_metadata_refresh_reuses_vectors_and_retains_unknown_scope(self) -> None:
        from tools.application.knowledge.applicability import policy_hash

        self.index()
        before = self.indexer.store.all_points()
        manifest = self.indexer.store.manifest()
        assert manifest is not None
        manifest.pop("metadata_policy_hash")
        self.indexer.store.write_manifest(manifest)
        self.assertTrue(self.indexer.status()["metadata_changed"])
        calls = len(Provider.calls)
        self.index()
        self.assertEqual(len(Provider.calls) - calls, 1)  # Capability probe only.
        after = self.indexer.store.all_points()
        self.assertEqual(list(before), list(after))
        refreshed = self.indexer.store.manifest()
        assert refreshed is not None
        self.assertEqual(refreshed["metadata_policy_hash"], policy_hash(CONFIG))
        for row in after.values():
            self.assertEqual(row["payload_schema_version"], 2)
            self.assertIsNone(row["applicability"]["effective_date"])
            self.assertIsNone(row["applicability"]["applicable_version"])

    def test_source_decisions_preserve_explicit_supersession_and_missing_rationale(self) -> None:
        import json

        declaration = {
            "purpose": "historical_evidence",
            "effective_date": None,
            "applicable_version": None,
            "decisions": [
                {
                    "id": "local-deploy-forward",
                    "problem": "Data ownership",
                    "solution": "Coordinated forward steps",
                    "rationale": None,
                    "status": "accepted",
                    "date": "2026-10-21",
                    "version": None,
                    "supersedes": ["local-deploy-excluded"],
                }
            ],
        }
        self.write(
            "AGENTS.md",
            "# Rules\n<!-- runa-knowledge "
            + json.dumps(declaration)
            + " -->\nForward steps apply.\n",
        )
        self.index()
        row = next(iter(self.indexer.store.all_points().values()))
        self.assertEqual(row["applicability"], declaration)
        self.assertNotIn("runa-knowledge", row["content"])
        self.assertNotIn("rationale", row["content"])

    def test_external_annotation_hash_is_required_without_rewriting_history(self) -> None:
        text = "# History\n## Decisions\nKeep the original decision.\n"
        name = "docs/runa/maintainer/roadmaps/closed/RM-2026-098-history.md"
        text = "---\nid: RM-2026-098\nstatus: closed\n---\n" + text
        path = self.write(name, text)
        self.track()
        config = copy.deepcopy(CONFIG)
        config["annotations"] = [
            {
                "source_path": name,
                "source_hash": corpus.digest(text),
                "section": "History > Decisions",
                "metadata": {
                    "purpose": "historical_evidence",
                    "effective_date": None,
                    "applicable_version": None,
                    "decisions": [],
                },
            }
        ]
        self.indexer.config = config
        self.index()
        self.assertEqual(path.read_text(), text)
        before = copy.deepcopy(Store.points)
        path.write_text(text + "Unreviewed change.\n")
        with self.assertRaisesRegex(ValueError, "source hash changed"):
            self.index()
        self.assertEqual(Store.points, before)

    def test_fenced_annotation_example_does_not_establish_applicability(self) -> None:
        from tools.application.knowledge.applicability import extract

        example = '```html\n<!-- runa-knowledge {"example": true} -->\n```'
        self.assertEqual(extract(example), (example, []))

    def test_retrieval_intents_expose_factors_without_hiding_contrary_evidence(self) -> None:
        import json

        from tools.application.knowledge.contracts import QueryPoint
        from tools.application.knowledge.retrieval import Retriever

        for name, purpose in (("current", "current_guidance"), ("history", "historical_evidence")):
            declaration = {
                "purpose": purpose,
                "effective_date": None,
                "applicable_version": None,
                "decisions": [],
            }
            self.write(
                "docs/app/" + name + ".md",
                "# Rules\n<!-- runa-knowledge "
                + json.dumps(declaration)
                + " -->\nForward migration deployment.\n## Other\nForward migration steps.\n",
            )
        self.track()
        self.index()

        class RankedStore(Store):
            def query(self, body: dict[str, object]) -> list[QueryPoint]:
                return [{"id": key, "score": 1.0} for key in self.all_points()]

        retriever = Retriever(
            self.root,
            CONFIG,
            context=self.context,
            store=RankedStore(CONFIG),
            provider_factory=Provider,
        )
        current = retriever.search("forward migration", 3, intent="current")
        history = retriever.search("forward migration", 3, intent="history")
        self.assertEqual(current[0]["payload"]["applicability"]["purpose"], "current_guidance")
        self.assertEqual(history[0]["payload"]["applicability"]["purpose"], "historical_evidence")
        audit = retriever.search("forward migration", 3, intent="audit")
        self.assertEqual(
            {hit["payload"]["source_path"] for hit in audit},
            {"AGENTS.md", "docs/app/current.md", "docs/app/history.md"},
        )
        self.assertTrue(
            all(hit["ranking_factors"] == {"status": 1.0, "purpose": 1.0} for hit in audit)
        )
        expanded = retriever.search("forward migration", 6, {"source_path": "docs/app/current.md"})
        self.assertEqual(len(expanded), 2)
        self.assertEqual(
            len(
                retriever.search(
                    "forward migration", 6, {"source_path": "docs/app/current.md"}, per_source=1
                )
            ),
            1,
        )
        with self.assertRaisesRegex(ValueError, "Intent"):
            retriever.search("forward", intent="guessed")
        with self.assertRaisesRegex(ValueError, "Per-source"):
            retriever.search("forward", per_source=True)

    def test_evaluation_reports_missing_coverage_forbidden_sources_and_probes(self) -> None:
        from tools.application.knowledge.configuration import GoldenCase
        from tools.application.knowledge.contracts import QueryPoint
        from tools.application.knowledge.retrieval import Retriever, evaluate

        self.index()

        class RankedStore(Store):
            def query(self, body: dict[str, object]) -> list[QueryPoint]:
                return [{"id": key, "score": 1.0} for key in self.all_points()]

        retriever = Retriever(
            self.root,
            CONFIG,
            context=self.context,
            store=RankedStore(CONFIG),
            provider_factory=Provider,
        )
        case: GoldenCase = {"query": "secrets index", "source_path": "AGENTS.md"}
        result = evaluate(retriever, [{**case, "expected": [{"source_path": "missing.md"}]}])
        self.assertFalse(result["passed"])
        self.assertEqual(result["cases"][0]["coverage"], [False])
        self.assertFalse(
            evaluate(retriever, [{**case, "forbidden_sources": ["AGENTS.md"]}])["passed"]
        )
        probe = evaluate(
            retriever,
            [{"query": "diagnostic probe", "source_path": "missing.md", "probe_only": True}],
        )
        self.assertTrue(probe["passed"])
        self.assertFalse(probe["cases"][0]["passed"])
        self.assertTrue(probe["cases"][0]["probe_only"])

    def test_evidence_refuses_modified_content_and_index_repairs_payload(self) -> None:
        from tools.application.knowledge.evidence import read

        self.index()
        key, payload = next(iter(self.indexer.store.all_points().items()))
        payload["content"] = "Invented evidence absent from the source."
        self.indexer.store.payload(key, payload)
        with self.assertRaisesRegex(ValueError, "content/schema"):
            read(self.indexer, key)
        self.index()
        self.assertIn("Keep secrets", read(self.indexer, key)["results"][0]["payload"]["content"])

    def test_allowlist_excludes_untracked_secrets_and_application_code(self) -> None:
        for name in [
            "docs/untracked.md",
            ".env",
            "docs/.env.local",
            "app/Foo.php",
            "docs/raw-chat.txt",
            "tools/scratch/readme.md",
        ]:
            self.write(name, "private fixture")
        subprocess.run(
            ["git", "add", ".env", "docs/.env.local", "app", "docs/raw-chat.txt", "tools"],
            cwd=self.root,
            check=True,
        )
        self.assertEqual(
            [p.name for p in corpus.sources(self.root, CONFIG, context=self.context)], ["AGENTS.md"]
        )

    def test_symlink_cannot_import_outside_content(self) -> None:
        (self.root / "docs/app").mkdir(parents=True)
        (self.root / "docs/app/link.md").symlink_to("/etc/passwd")
        self.track()
        with self.assertRaisesRegex(ValueError, "Symlink"):
            corpus.sources(self.root, CONFIG, context=self.context)

    def test_structural_budget_and_closed_roadmap_filter(self) -> None:
        text = (
            "---\nid: RM-2026-099\nstatus: closed\n---\n# Roadmap\n## Overview\n"
            + ("Knowledge text. " * 700)
            + "\n## Implementation plan\nDo not index this checklist.\n"
        )
        p = self.write("docs/runa/maintainer/roadmaps/closed/RM-2026-099.md", text)
        meta, rows = corpus.chunks(p, self.root, text, Provider(CONFIG), CONFIG)
        self.assertEqual(meta["roadmap_id"], "RM-2026-099")
        self.assertGreater(len(rows), 1)
        self.assertTrue(all(len(r["payload"]["content"].split()) + 2 <= 420 for r in rows))
        self.assertFalse(any("Do not index" in r["payload"]["content"] for r in rows))
        self.assertEqual(
            [r["id"] for r in rows],
            [r["id"] for r in corpus.chunks(p, self.root, text, Provider(CONFIG), CONFIG)[1]],
        )

    def test_heading_level_gaps_and_fences_preserve_hierarchy(self) -> None:
        text = "# Root\n#### First\nOne.\n#### Second\n````md\n# Literal heading\n```\n````\nTwo.\n## Next\nThree.\n"
        parsed = list(corpus.sections(text))
        self.assertEqual(
            [headings for headings, _ in parsed],
            [["Root", "First"], ["Root", "Second"], ["Root", "Next"]],
        )
        self.assertIn("# Literal heading", parsed[1][1])

    def test_roadmap_examples_and_templates_are_documentation(self) -> None:
        text = "# Workflow\n```yaml\nid: RM-2026-003\nstatus: active\n```\n"
        for name in (
            "docs/runa/maintainer/workflows/roadmaps.md",
            "docs/runa/handbook/workflows/roadmap-template.md",
        ):
            with self.subTest(source=name):
                path = self.write(name, text)
                meta = corpus.metadata(path, self.root, text)
                self.assertEqual(meta["document_type"], "documentation")
                self.assertIsNone(meta["roadmap_id"])
                self.assertIsNone(meta["status"])
                self.assertEqual(meta["document_id"], corpus.identity("source:" + name))

    def test_real_roadmap_identity_survives_closure_and_ignores_body_examples(self) -> None:
        text = "---\nid: RM-2026-099\nstatus: active\n---\n# Plan\n```yaml\nid: RM-2026-003\nstatus: closed\n```\n"
        active = self.write("docs/runa/maintainer/roadmaps/active/RM-2026-099-test.md", text)
        before = corpus.metadata(active, self.root, text)
        closed_text = text.replace("status: active", "status: closed", 1)
        closed = self.write("docs/runa/maintainer/roadmaps/closed/RM-2026-099-test.md", closed_text)
        after = corpus.metadata(closed, self.root, closed_text)
        self.assertEqual(before["document_id"], after["document_id"])
        self.assertEqual(before["roadmap_id"], "RM-2026-099")
        self.assertEqual(before["status"], "active")
        self.assertEqual(after["status"], "closed")
        for invalid in [
            text.replace("status: active", "status: closed", 1),
            text.replace("id: RM-2026-099", "id: RM-2026-098", 1),
            text.replace("id: RM-2026-099", "id: RM-2026-099\nid: RM-2026-099", 1),
            "# No actual frontmatter\n" + text,
        ]:
            with self.assertRaises(ValueError):
                corpus.metadata(active, self.root, invalid)

    def test_parser_refresh_removes_false_roadmap_filter_without_emptying_docs(self) -> None:
        name = "docs/runa/maintainer/workflows/roadmaps.md"
        text = "# Workflow\n```yaml\nid: RM-2026-003\nstatus: draft\n```\n"
        self.write(name, text)
        self.track()
        original = corpus.metadata

        def legacy_metadata(path: Path, root: Path, text: str) -> SourceMetadata:
            meta = original(path, root, text)
            if meta["source_path"] == name:
                meta.update(
                    document_id=corpus.identity("roadmap:RM-2026-003"),
                    document_type="roadmap",
                    roadmap_id="RM-2026-003",
                    status="draft",
                )
            return meta

        with (
            patch.object(corpus, "metadata", legacy_metadata),
            patch.object(ingest, "metadata", legacy_metadata),
            patch.object(self.indexer, "parser_fingerprint", return_value="legacy-parser"),
        ):
            self.index()
        self.assertTrue(
            any(p.get("roadmap_id") == "RM-2026-003" for p in Store(CONFIG).all_points().values())
        )
        self.assertTrue(self.indexer.status().get("parser_changed"))
        self.index()
        points = Store(CONFIG).all_points()
        self.assertTrue(any(p["source_path"] == name for p in points.values()))
        self.assertFalse(any(p.get("roadmap_id") == "RM-2026-003" for p in points.values()))

    def test_idempotent_then_edit_delete_and_orphan_cleanup(self) -> None:
        self.write(
            "docs/runa/handbook/architecture/example.md", "# Rule\n\nOriginal technical contract.\n"
        )
        self.track()
        self.assertEqual(self.index(), 0)
        before = set(Store.points)
        Provider.calls.clear()
        self.assertEqual(self.index(), 0)
        self.assertEqual(len(Provider.calls), 1)  # Only the dimension capability probe.
        self.assertEqual(set(Store.points), before)
        self.write(
            "docs/runa/handbook/architecture/example.md", "# Rule\n\nRevised technical contract.\n"
        )
        self.assertTrue(self.indexer.status()["stale"])
        self.assertEqual(self.index(), 0)
        self.assertNotEqual(set(Store.points), before)
        (self.root / "docs/runa/handbook/architecture/example.md").unlink()
        Store.points["orphan"] = {"kind": "chunk", "content": "obsolete"}
        self.assertEqual(self.index(), 0)
        self.assertNotIn("orphan", Store.points)
        self.assertEqual(self.indexer.status().get("documents"), 1)

    def test_failed_prepare_preserves_prior_document(self) -> None:
        self.index()
        old = copy.deepcopy(Store.points)
        self.write("AGENTS.md", "# Policy\n\n```\nUnclosed block")
        with self.assertRaisesRegex(ValueError, "Unclosed"):
            self.index()
        self.assertEqual(Store.points, old)

    def test_interrupted_write_is_stale_and_recovers(self) -> None:
        self.index()
        self.write("AGENTS.md", "# Policy\n\nRevised policy.\n")
        Store.fail_upsert = True
        with self.assertRaisesRegex(RuntimeError, "interrupted"):
            self.index()
        self.assertTrue(self.indexer.status().get("interrupted"))
        Store.fail_upsert = False
        self.assertEqual(self.index(), 0)
        self.assertFalse(self.indexer.status()["stale"])

    def test_branch_change_refreshes_metadata_without_reembedding(self) -> None:
        self.index()
        subprocess.run(["git", "checkout", "-qb", "other-branch"], cwd=self.root, check=True)
        self.assertTrue(self.indexer.status().get("branch_changed"))
        Provider.calls.clear()
        self.index()
        self.assertEqual(len(Provider.calls), 1)
        self.assertTrue(
            all(p.get("git_branch") == "other-branch" for p in Store(CONFIG).all_points().values())
        )

    def test_parser_change_reparses_without_reembedding_unchanged_chunks(self) -> None:
        self.index()
        Provider.calls.clear()
        with patch.object(self.indexer, "parser_fingerprint", return_value="revised-parser"):
            self.assertTrue(self.indexer.status().get("parser_changed"))
            self.assertEqual(self.index(), 0)
            self.assertFalse(self.indexer.status()["stale"])
        self.assertEqual(len(Provider.calls), 1)

    def test_model_mismatch_refuses_collection_writes(self) -> None:
        self.index()
        Store.manifest_data()["space"]["model_revision"] = "different"
        old = copy.deepcopy(Store.points)
        with self.assertRaisesRegex(ValueError, "mismatch"):
            self.index()
        self.assertEqual(Store.points, old)

    def test_gpu_transient_failure_falls_back_but_permanent_error_does_not(self) -> None:
        for status in (503, 422):
            cpu = Client(CONFIG["cpu_url"], [capabilities(CONFIG), [[1.0]]])
            gpu = Client(
                CONFIG["cuda_url"], [capabilities(CONFIG), RequestError("Fixture refusal", status)]
            )
            provider = Embeddings(CONFIG, http_factory={cpu.base: cpu, gpu.base: gpu}.__getitem__)
            with self.subTest(status=status), contextlib.redirect_stderr(io.StringIO()) as output:
                if status == 503:
                    self.assertEqual(provider.request("/embed", {}), [[1.0]])
                    self.assertIn("CPU fallback", output.getvalue())
                    self.assertEqual(cpu.calls, [("GET", "/info", None), ("POST", "/embed", {})])
                else:
                    with self.assertRaises(RequestError):
                        provider.request("/embed", {})
                    self.assertEqual(cpu.calls, [])
                    self.assertEqual(output.getvalue(), "")


if __name__ == "__main__":
    unittest.main()
