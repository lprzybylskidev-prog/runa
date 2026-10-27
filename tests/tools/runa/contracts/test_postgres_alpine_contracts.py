#!/usr/bin/env python3
"""Offline rejection and resource-isolation contracts; not PostgreSQL runtime evidence."""

from __future__ import annotations

import contextlib
import copy
import io
import json
import subprocess
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from tests.tools.runa.acceptance import postgres_alpine as probe
from tools.core.state import JsonValue

ROOT = Path(__file__).resolve().parents[4]


def fixture() -> list[JsonValue]:
    order = ["a", "ą", "c", "ć", "e", "ę", "l", "ł", "n", "ń", "o", "ó", "s", "ś", "z", "ź", "ż"]
    return [
        {
            "kind": "metadata",
            "provider": "i",
            "locale": "pl-PL",
            "encoding": "UTF8",
            "recorded_collation": "version",
            "actual_collation": "version",
        },
        {"kind": "indexed_order", "values": order},
        {"kind": "sequential_order", "values": order},
        {"kind": "range", "values": ["l", "ł"]},
        [{"Plan": {"Node Type": "Index Only Scan"}}],
        {
            "kind": "semantics",
            "lower": "ąćęłńóśźż",
            "accent_equal": False,
            "case_equal": False,
            "normalized_equal": False,
        },
    ]


class CandidateProbeContracts(unittest.TestCase):
    def test_rejects_libc_version_drift_and_changed_equality(self) -> None:
        for update in ({"provider": "c"}, {"actual_collation": "upgraded"}, {"locale": "en-US"}):
            rows = fixture()
            rows[0].update(update)
            with self.subTest(update=update), self.assertRaises(ValueError):
                probe.verify(rows)
        rows = fixture()
        rows[-1]["normalized_equal"] = True
        with self.assertRaisesRegex(ValueError, "deterministic"):
            probe.verify(rows)

    def test_detects_index_order_disagreement_and_missing_index_plan(self) -> None:
        rows = copy.deepcopy(fixture())
        rows[1]["values"][1:3] = ["c", "ą"]
        with self.assertRaisesRegex(ValueError, "order/range"):
            probe.verify(rows)
        rows = fixture()
        rows[4][0]["Plan"]["Node Type"] = "Seq Scan"
        with self.assertRaisesRegex(ValueError, "Index use"):
            probe.verify(rows)

    def test_fixture_parses_and_accepts_complete_semantic_evidence(self) -> None:
        rows = fixture()
        decoded = probe.decode("\n".join(json.dumps(row) for row in rows))
        with contextlib.redirect_stdout(io.StringIO()):
            probe.verify(decoded)
        self.assertIn("U&'a\\0328'", probe.SQL)

    def test_psql_failure_removes_only_its_disposable_container_and_credentials(self) -> None:
        with (
            tempfile.TemporaryDirectory() as folder,
            patch.object(probe, "ROOT", Path(folder)),
            patch.object(probe, "inside", return_value=False),
            patch.object(probe, "run") as run,
            patch.object(
                probe.subprocess, "run", return_value=subprocess.CompletedProcess([], 0)
            ) as direct,
            contextlib.redirect_stdout(io.StringIO()),
        ):
            run.side_effect = [
                subprocess.CompletedProcess([], 0),
                subprocess.CompletedProcess([], 0, stdout="true"),
                subprocess.CalledProcessError(1, ["psql"]),
            ]
            with self.assertRaises(subprocess.CalledProcessError):
                probe.main()
            start = run.call_args_list[0].args[0]
            self.assertEqual(start[start.index("--network") + 1], "none")
            self.assertIn("--tmpfs", start)
            for forbidden in ("--entrypoint", "--volume", "-v", "--mount", "-p", "--publish"):
                self.assertNotIn(forbidden, start)
            name = start[start.index("--name") + 1]
            self.assertTrue(name.startswith("runa-pg-alpine-assessment-"))
            self.assertEqual(direct.call_args.args[0], ["docker", "rm", "-f", name])
            self.assertIn("127.0.0.1", direct.call_args_list[0].args[0])
            self.assertFalse(Path(start[start.index("--env-file") + 1]).exists())
