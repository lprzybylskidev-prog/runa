"""Preserved graph, evidence, catalogue and debug-output refusal contracts."""

from __future__ import annotations

import contextlib
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

from tests.tools.runa.support.quality import FixtureReader, node
from tools.application import quality_planning as quality
from tools.application.configuration_reader import ConfigurationReader
from tools.application.quality_evidence import check_evidence, closure_link_update
from tools.application.quality_models import QualityDefinition, quality_definition
from tools.application.quality_reporting import save
from tools.application.quality_state import run_record
from tools.maintainer.release_checks import closure_policy

ROOT = Path(__file__).resolve().parents[4]
reader = ConfigurationReader.for_workspace(ROOT)


class QualityCompatibility(unittest.TestCase):
    def test_closure_allows_only_exact_predecessor_link_updates_in_existing_drafts(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            subprocess.run(["git", "init", "-q", str(root)], check=True)
            name = "docs/runa/maintainer/roadmaps/draft/RM-2026-011-next.md"
            path = root / name
            path.parent.mkdir(parents=True)
            before = "[RM-2026-010](../active/RM-2026-010-manual-quality-group-selection.md)\n"
            path.write_text(before)
            subprocess.run(["git", "-C", str(root), "add", "."], check=True)
            subprocess.run(
                [
                    "git",
                    "-C",
                    str(root),
                    "-c",
                    "user.name=Fixture",
                    "-c",
                    "user.email=fixture@example.test",
                    "commit",
                    "-qm",
                    "fixture",
                ],
                check=True,
            )
            after = before.replace("../active/", "../closed/")
            path.write_text(after)
            self.assertTrue(
                closure_link_update(root, name, "HEAD", "RM-2026-010", "docs/runa/maintainer")
            )
            for content in (before, after + "unrelated change\n", after.replace("010", "009")):
                path.write_text(content)
                self.assertFalse(
                    closure_link_update(root, name, "HEAD", "RM-2026-010", "docs/runa/maintainer")
                )
            path.write_text(after)
            self.assertFalse(
                closure_link_update(root, name, "HEAD", "RM-2026-009", "docs/runa/maintainer")
            )
            self.assertFalse(closure_link_update(root, name, "HEAD", "RM-2026-010", "docs/app"))
            new = path.with_name("RM-2026-012-new.md")
            new.write_text(after)
            self.assertFalse(
                closure_link_update(
                    root,
                    new.relative_to(root).as_posix(),
                    "HEAD",
                    "RM-2026-010",
                    "docs/runa/maintainer",
                )
            )

    def grouped_definition(self) -> QualityDefinition:
        return {
            "base": [node("baseline", "true"), node("runtime", "true", ["baseline"])],
            "impact_areas": {"tooling": [node("cheap", "true"), node("expensive", "false")]},
            "final": [node("closure", "true")],
            "mandatory": ["baseline", "closure"],
            "groups": {"docs": ["cheap"], "backend": ["runtime"], "containers": ["expensive"]},
        }

    def test_explicit_all_and_legacy_keep_distinct_honest_scope(self) -> None:
        definition = self.grouped_definition()
        nodes, scope = quality.resolve(definition, [], all_groups=True)
        self.assertIn("expensive", nodes)
        assert scope["mode"] == "all"
        self.assertEqual(scope["excluded_groups"], [])
        legacy, scope = quality.resolve(definition, ["tooling"])
        self.assertEqual(legacy, quality.select(definition, ["tooling"]))
        self.assertEqual(scope["mode"], "legacy")

    def test_group_validation_rejects_invalid_and_cyclic_selections(self) -> None:
        for args in (
            ["show", "--group="],
            ["status", "--all"],
            ["show", "--background"],
            ["final", "--all", "--group=docs"],
            ["final", "--all", "--all"],
            ["final", "--group=../command"],
            ["final", "docs"],
            ["final", "--group"],
        ):
            with self.subTest(args=args), self.assertRaises(ValueError):
                quality.arguments(args)
        self.assertEqual(
            quality.arguments(["final", "--group=docs", "--group=docs", "--background"]),
            (["docs"], False, True),
        )
        for groups in (["unknown"], []):
            with self.assertRaises(ValueError):
                quality.resolve(self.grouped_definition(), [], groups=groups)
        definition = self.grouped_definition()
        definition["base"][0]["depends_on"] = ["runtime"]
        with self.assertRaisesRegex(ValueError, "cycle"):
            quality.resolve(definition, [], groups=["backend"])
        groups = definition.get("groups")
        assert groups is not None
        groups["broken"] = ["missing"]
        with self.assertRaisesRegex(ValueError, "Missing"):
            quality.resolve(definition, [], groups=["broken"])

    def test_real_catalogues_cover_existing_steps_without_owner_customer_leaks(self) -> None:
        owner = quality_definition(reader.yaml(ROOT / "docs/runa/maintainer/quality/gates.yml"))
        customer = quality_definition(reader.yaml(ROOT / "tools/application/quality.yml"))
        owner_nodes, _ = quality.resolve(owner, [], all_groups=True)
        legacy = quality.select(owner, list(owner["impact_areas"]), True)
        self.assertTrue(set(legacy) <= set(owner_nodes))
        for definition in (owner, customer):
            docs, _ = quality.resolve(
                definition, ["tooling", "security"], groups=["docs", "tooling"]
            )
            self.assertFalse(
                any(
                    "installation-host.py" in n["command"] or "security:trivy" in n["command"]
                    for n in docs.values()
                )
            )
        customer_nodes, _ = quality.resolve(customer, [], all_groups=True)
        self.assertFalse(
            any(
                "tools/maintainer" in n["command"] or "distribution/" in n["command"]
                for n in customer_nodes.values()
            )
        )
        catalogue = json.loads(
            (ROOT / "src/runa/Tooling/Management/quality-groups.json").read_text()
        )
        assert "groups" in customer
        self.assertEqual(set(catalogue), set(customer["groups"]))
        for group in ("installation", "updates", "transport", "production"):
            with self.assertRaises(ValueError):
                quality.resolve(customer, [], groups=[group])

    def test_narrow_owner_scopes_do_not_prepare_unrelated_native_stacks(self) -> None:
        definition = quality_definition(
            reader.yaml(ROOT / "docs/runa/maintainer/quality/gates.yml")
        )
        assert "mandatory" in definition
        heavy = {
            "installation-host-acceptance",
            "production-acceptance",
            "repository-container-scan",
            "host-executor-acceptance",
            "management-host-acceptance",
            "frontend-browser",
            "backend-tests",
        }
        for groups in (
            ["knowledge"],
            ["tooling"],
            ["delivery"],
            ["security-source"],
            ["knowledge", "tooling", "delivery", "security-source"],
        ):
            with self.subTest(groups=groups):
                nodes, scope = quality.resolve(
                    definition, ["production", "security"], groups=groups
                )
                self.assertFalse(heavy & nodes.keys())
                assert scope["mode"] == "groups"
                self.assertTrue(set(definition["mandatory"]) <= nodes.keys())
                self.assertIn("production", scope["excluded_groups"])
                self.assertEqual(scope["graph_hash"], quality.graph_hash(nodes))
        delivery, _ = quality.resolve(definition, [], groups=["delivery"])
        self.assertTrue(
            {
                "production-source-contracts",
                "deployment-boundary-contracts",
                "documentation-ownership-contracts",
                "host-executor-contracts",
            }
            <= delivery.keys()
        )

    def test_explicit_security_retains_images_and_source_scope_still_requires_review(self) -> None:
        definition = quality_definition(
            reader.yaml(ROOT / "docs/runa/maintainer/quality/gates.yml")
        )
        narrow, _ = quality.resolve(definition, [], groups=["security-source"])
        broad, _ = quality.resolve(definition, [], groups=["security"])
        combined, _ = quality.resolve(definition, [], groups=["security-source", "containers"])
        self.assertTrue(
            {"dependency-audit", "security-review-contracts", "expanded-security-review"}
            <= narrow.keys()
        )
        self.assertNotIn("repository-container-scan", narrow)
        for nodes in (broad, combined):
            self.assertIn("repository-container-scan", nodes)
            self.assertIn(
                "repository-container-scan", nodes["expanded-security-review"]["depends_on"]
            )
        customer = quality_definition(reader.yaml(ROOT / "tools/application/quality.yml"))
        narrow, _ = quality.resolve(customer, [], groups=["security-source"])
        self.assertIn("application-dependencies", narrow)
        self.assertNotIn("application-container-security", narrow)
        broad, _ = quality.resolve(customer, [], groups=["security"])
        self.assertIn("application-container-security", broad)
        with self.assertRaises(ValueError):
            quality.resolve(customer, [], groups=["delivery"])

    def test_closure_requires_agreed_scope_complete_results_and_unchanged_candidate(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            subprocess.run(["git", "init", "-q", "-b", "roadmap/test", str(root)], check=True)
            (root / ".git/info/exclude").write_text(".runtime/\n")
            subprocess.run(
                [
                    "git",
                    "-C",
                    str(root),
                    "-c",
                    "user.name=Fixture",
                    "-c",
                    "user.email=fixture@example.test",
                    "commit",
                    "--allow-empty",
                    "-qm",
                    "fixture",
                ],
                check=True,
            )
            directory = root / ".runtime/quality"
            directory.mkdir(parents=True)
            definition = self.grouped_definition()
            nodes, scope = quality.resolve(definition, [], groups=["docs"])
            data = {
                "id": "fixture",
                "branch": "roadmap/test",
                "quality_groups": "docs",
                "quality_rationale": "docs scope",
            }
            state = run_record(
                {
                    "status": "passed",
                    "mode": "final",
                    "roadmap": "fixture",
                    "branch": data["branch"],
                    "rationale": data["quality_rationale"],
                    "selection": scope,
                    "commit": subprocess.check_output(
                        ["git", "-C", str(root), "rev-parse", "HEAD"], text=True
                    ).strip(),
                    "steps": {
                        key: dict(n, status="passed", exit_code=0) for key, n in nodes.items()
                    },
                }
            )
            with contextlib.nullcontext():
                save(directory, state)
                check_evidence(
                    root,
                    data,
                    root / "definition",
                    directory,
                    policy=closure_policy(),
                    reader=FixtureReader(definition),
                )
                for change in (
                    {"status": "interrupted"},
                    {"branch": "other"},
                    {"mode": "show"},
                    {"rationale": "other"},
                ):
                    save(directory, run_record(state | change))
                    with self.assertRaises(ValueError):
                        check_evidence(
                            root,
                            data,
                            root / "definition",
                            directory,
                            policy=closure_policy(),
                            reader=FixtureReader(definition),
                        )
                save(directory, state)
                with self.assertRaises(ValueError):
                    check_evidence(
                        root,
                        data | {"quality_groups": "all"},
                        root / "definition",
                        directory,
                        policy=closure_policy(),
                        reader=FixtureReader(definition),
                    )
                state["steps"]["cheap"]["status"] = "failed"
                save(directory, state)
                with self.assertRaisesRegex(ValueError, "Incomplete"):
                    check_evidence(
                        root,
                        data,
                        root / "definition",
                        directory,
                        policy=closure_policy(),
                        reader=FixtureReader(definition),
                    )
                state["steps"]["cheap"]["status"] = "passed"
                state["steps"]["cheap"]["command"] = "false"
                save(directory, state)
                with self.assertRaisesRegex(ValueError, "commands"):
                    check_evidence(
                        root,
                        data,
                        root / "definition",
                        directory,
                        policy=closure_policy(),
                        reader=FixtureReader(definition),
                    )
                state["steps"]["cheap"]["command"] = "true"
                save(directory, state)
                (root / "runtime.py").write_text("changed")
                subprocess.run(["git", "-C", str(root), "add", "runtime.py"], check=True)
                with self.assertRaisesRegex(ValueError, "Substantive"):
                    check_evidence(
                        root,
                        data,
                        root / "definition",
                        directory,
                        policy=closure_policy(),
                        reader=FixtureReader(definition),
                    )

    def test_debug_check_allows_only_reviewed_output_statements(self) -> None:
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / "bin").mkdir()
            (root / "tools/application").mkdir(parents=True)
            shutil.copy(
                ROOT / "tools/application/source_checks.py",
                root / "tools/application/source_checks.py",
            )
            subprocess.run(["git", "init", "-q", str(root)], check=True)
            sources = {
                "src/runa/resources/views/errors/page.php": "echo strtr($template, [",
                "tests/backend/runa/Fixtures/browser-document.php": "echo json_encode(['status' => $response->getStatusCode(), 'headers' => $response->headers->all(),",
            }
            for name, statement in sources.items():
                target = root / name
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_text("<?php\n" + statement + "\n")
            command = [
                sys.executable,
                str(root / "tools/application/source_checks.py"),
                "debug-residue",
            ]
            self.assertEqual(subprocess.run(command, capture_output=True).returncode, 0)
            for name, statement in sources.items():
                for content in [
                    statement + " dd('debug');",
                    statement + "\necho 'debug';",
                    "var_dump($value);",
                ]:
                    with self.subTest(name=name, content=content):
                        (root / name).write_text("<?php\n" + content + "\n")
                        self.assertNotEqual(
                            subprocess.run(command, capture_output=True).returncode, 0
                        )
                (root / name).write_text("<?php\n" + statement + "\n")
            other = root / "src/runa/Other.php"
            other.parent.mkdir(parents=True, exist_ok=True)
            other.write_text("<?php\n" + sources["src/runa/resources/views/errors/page.php"] + "\n")
            self.assertNotEqual(subprocess.run(command, capture_output=True).returncode, 0)

    def test_actual_host_production_gates_share_the_operation_group(self) -> None:
        definition = quality_definition(
            reader.yaml(ROOT / "docs/runa/maintainer/quality/gates.yml")
        )
        nodes = quality.select(definition, ["production", "security"])
        for key in ("production-acceptance", "repository-container-scan"):
            assert "exclusive_group" in nodes[key]
            self.assertEqual(nodes[key].get("exclusive_group"), "production-simulation", key)
            self.assertIn("tests.tools.runa.acceptance.installation_host", nodes[key]["command"])
            self.assertNotIn("./bin/artisan runa:production:", nodes[key]["command"])
        self.assertIn("--shared-quality-producer", nodes["production-acceptance"]["command"])
        self.assertIn("--verify-shared-quality", nodes["repository-container-scan"]["command"])
