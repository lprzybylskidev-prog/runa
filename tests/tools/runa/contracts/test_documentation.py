#!/usr/bin/env python3
"""Actual customer source/knowledge/documentation boundaries using disposable Git fixtures."""

from __future__ import annotations

import contextlib
import hashlib
import io
import json
import shutil
import subprocess
import tempfile
import unittest
from dataclasses import replace
from pathlib import Path

from tools.application import docs_checks
from tools.application.documentation import maintainer_checkout, roadmap_root
from tools.application.hooks import commit_message
from tools.application.knowledge import corpus
from tools.application.knowledge.configuration import golden, load
from tools.application.knowledge.context import KnowledgeContext
from tools.maintainer.knowledge_cli import configuration as owner_configuration

ROOT = Path(__file__).resolve().parents[4]


class DocumentationOwnership(unittest.TestCase):
    def setUp(self) -> None:
        self.folder = tempfile.TemporaryDirectory(prefix="runa-doc-ownership-")
        self.addCleanup(self.folder.cleanup)
        self.root = Path(self.folder.name)
        subprocess.run(
            ["git", "init", "-q", "-b", "documentation-test", str(self.root)], check=True
        )
        for name in ("AGENTS.md", "README.md", "docs/README.md"):
            self.copy(name)
        for name in ("docs/app", "docs/runa/handbook", ".agents", "tools/application/knowledge"):
            shutil.copytree(
                ROOT / name, self.root / name, ignore=shutil.ignore_patterns("__pycache__")
            )
        product = ROOT / "docs/runa/maintainer/customer-agent"
        shutil.copy2(product / "AGENTS.md", self.root / "AGENTS.md")
        shutil.rmtree(self.root / ".agents/skills")
        shutil.copytree(product / "skills", self.root / ".agents/skills")
        self.copy("tools/application/quality.yml")
        self.receipt = self.root / ".git/runa-bootstrap.json"
        self.receipt.write_text("{}\n")
        self.track()

    def copy(self, name: str) -> None:
        target = self.root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(ROOT / name, target)

    def write(self, name: str, text: str) -> Path:
        target = self.root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(text)
        return target

    def track(self) -> None:
        subprocess.run(["git", "add", "."], cwd=self.root, check=True)

    def validate(self) -> None:
        with contextlib.redirect_stdout(io.StringIO()):
            docs_checks.validate(self.root)

    def copy_owner_inputs(self) -> None:
        for name in (
            "docs/runa/maintainer/agent-contract.md",
            "tools/maintainer/knowledge.json",
            "tools/maintainer/knowledge-golden.json",
        ):
            self.copy(name)
        self.write(
            "docs/runa/maintainer/internal.md",
            "# Private owner roadmap\nInternal sentinel, never customer knowledge.\n",
        )
        self.track()

    def test_actual_public_projection_has_complete_docs_skills_empty_roadmaps_and_no_owner_history(
        self,
    ) -> None:
        self.validate()
        self.assertFalse((self.root / "docs/runa/maintainer").exists())
        self.assertNotIn("runa:current-state", (self.root / "README.md").read_text())
        for state in ("draft", "active", "paused", "closed"):
            self.assertEqual(list((self.root / "docs/app/roadmaps" / state).glob("*.md")), [])
        self.assertEqual(len(list((self.root / ".agents/skills").glob("*/SKILL.md"))), 9)
        paths = {
            p.relative_to(self.root).as_posix() for p in corpus.sources(self.root, load(self.root))
        }
        self.assertIn("docs/app/README.md", paths)
        self.assertIn("docs/runa/handbook/development/editor-and-agents.md", paths)
        self.assertIn("docs/runa/handbook/security/baseline.md", paths)
        self.assertFalse(any("/maintainer/" in p for p in paths))
        self.assertTrue(all("/maintainer/" not in q["source_path"] for q in golden(self.root)))

    def test_copied_owner_sources_and_broad_allowlist_cannot_cross_customer_receipt(self) -> None:
        self.copy_owner_inputs()
        self.assertFalse(maintainer_checkout(self.root))
        config = load(self.root)
        self.assertNotIn("docs/runa/maintainer", config["markdown_roots"])
        config["markdown_roots"] = ["docs"]
        config["files"] += ["docs/runa/maintainer/internal.md"]
        paths = {p.relative_to(self.root).as_posix() for p in corpus.sources(self.root, config)}
        self.assertFalse(any("/maintainer/" in p for p in paths))
        self.receipt.unlink()
        self.assertTrue(maintainer_checkout(self.root))
        self.assertIn("docs/runa/maintainer", owner_configuration(self.root)[0]["markdown_roots"])
        self.assertTrue(
            any("/maintainer/" in q["source_path"] for q in owner_configuration(self.root)[1])
        )

    def test_customer_authoring_templates_never_enter_owner_retrieval(self) -> None:
        self.copy_owner_inputs()
        self.receipt.unlink()
        self.write("docs/runa/maintainer/customer-agent/AGENTS.md", "# Customer template sentinel")
        self.write(
            "docs/runa/maintainer/customer-agent/skills/frontend/SKILL.md", "# Customer UI rules"
        )
        self.track()
        config, _ = owner_configuration(self.root)
        config["files"].append("docs/runa/maintainer/customer-agent/AGENTS.md")
        paths = {
            p.relative_to(self.root).as_posix()
            for p in corpus.sources(
                self.root,
                config,
                context=replace(KnowledgeContext.for_workspace(self.root), owner_corpus=True),
            )
        }
        self.assertIn("docs/runa/maintainer/internal.md", paths)
        self.assertFalse(any(p.startswith("docs/runa/maintainer/customer-agent/") for p in paths))

    def test_customer_template_links_use_installed_paths_and_public_boundaries(self) -> None:
        product = self.root / "docs/runa/maintainer/customer-agent"
        product.mkdir(parents=True)
        template = product / "AGENTS.md"
        template.write_text("[Public guide](docs/runa/handbook/ai/skills-policy.md)")
        docs_checks.links(self.root, [template], logical_paths={template: self.root / "AGENTS.md"})
        template.write_text("[Owner guide](docs/runa/maintainer/agent-contract.md)")
        with self.assertRaisesRegex(ValueError, "owner-only"):
            docs_checks.links(
                self.root, [template], logical_paths={template: self.root / "AGENTS.md"}
            )

    def test_public_link_cannot_require_owner_source_even_when_it_exists(self) -> None:
        self.copy_owner_inputs()
        self.receipt.unlink()
        self.write("docs/app/notes.md", "[Internal](../runa/maintainer/internal.md)\n")
        with self.assertRaisesRegex(ValueError, "owner-only"):
            self.validate()

    def test_legacy_roots_and_materialized_owner_docs_are_rejected(self) -> None:
        legacy = self.root / "docs/development"
        legacy.mkdir()
        with self.assertRaisesRegex(ValueError, "placement"):
            self.validate()
        legacy.rmdir()
        self.copy_owner_inputs()
        with self.assertRaisesRegex(ValueError, "owner-only"):
            self.validate()

    def test_missing_directories_duplicate_root_and_symlink_sources_fail(self) -> None:
        (self.root / "docs/app/roadmaps/active/.gitkeep").unlink()
        (self.root / "docs/app/roadmaps/active").rmdir()
        with self.assertRaisesRegex(ValueError, "roadmap directory"):
            self.validate()
        (self.root / "docs/app/roadmaps/active").mkdir()
        duplicate = self.root / "src/app/docs"
        duplicate.mkdir(parents=True)
        with self.assertRaisesRegex(ValueError, "docs/app"):
            self.validate()
        duplicate.rmdir()
        (self.root / "docs/app/linked.md").symlink_to(ROOT / "AGENTS.md")
        with self.assertRaisesRegex(ValueError, "symlink"):
            self.validate()

    def test_module_local_documentation_is_rejected(self) -> None:
        for owner in ("app", "runa"):
            local = self.root / ("src/" + owner + "/modules/example/docs")
            local.mkdir(parents=True)
            with self.assertRaisesRegex(ValueError, "central docs"):
                self.validate()
            local.rmdir()

    def test_owner_and_application_roadmap_ids_are_distinct_and_lifecycle_identity_is_stable(
        self,
    ) -> None:
        text = "---\nid: RM-2026-099\nstatus: active\n---\n# Plan\n"
        app = self.write("docs/app/roadmaps/active/RM-2026-099-test.md", text)
        owner = self.write("docs/runa/maintainer/roadmaps/active/RM-2026-099-test.md", text)
        a = corpus.metadata(app, self.root, text)
        b = corpus.metadata(owner, self.root, text)
        self.assertNotEqual(a["document_id"], b["document_id"])
        self.assertEqual(b["document_id"], corpus.identity("roadmap:RM-2026-099"))
        closed_text = text.replace("status: active", "status: closed")
        closed = self.write("docs/app/roadmaps/closed/RM-2026-099-test.md", closed_text)
        self.assertEqual(
            a["document_id"], corpus.metadata(closed, self.root, closed_text)["document_id"]
        )

    def test_commit_scope_uses_application_roadmap_despite_copied_owner_history(self) -> None:
        self.copy_owner_inputs()
        self.write(
            "docs/app/roadmaps/active/RM-2026-099-test.md",
            "---\nid: RM-2026-099\nstatus: active\n---\n",
        )
        self.write(
            "docs/runa/maintainer/roadmaps/active/RM-2026-098-test.md",
            "---\nid: RM-2026-098\nstatus: active\n---\n",
        )
        message = self.write("message.txt", "docs(RM-2026-099): application notes\n")
        commit_message(self.root, message)
        self.assertEqual(roadmap_root(self.root), self.root / "docs/app/roadmaps")
        self.receipt.unlink()
        with self.assertRaisesRegex(ValueError, "scope"):
            commit_message(self.root, message)

    def test_closed_roadmaps_and_dated_reports_match_approved_immutable_hashes(self) -> None:
        mapping = json.loads((ROOT / "docs/runa/maintainer/documentation-map.json").read_text())
        for name, expected in mapping["immutable_sha256"].items():
            with self.subTest(source=name):
                self.assertEqual(hashlib.sha256((ROOT / name).read_bytes()).hexdigest(), expected)
        for name in mapping["paths"].values():
            path = ROOT / name
            if "/roadmaps/" in name and path.name.startswith("RM-"):
                self.assertEqual(
                    len(list((ROOT / "docs/runa/maintainer/roadmaps").glob("*/" + path.name))),
                    1,
                    name,
                )
            else:
                self.assertTrue(path.is_file(), name)
