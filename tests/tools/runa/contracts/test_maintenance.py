#!/usr/bin/env python3
"""Maintenance validator rejection contracts; no repository fixtures are written."""

from __future__ import annotations

import contextlib
import copy
import io
import unittest
from pathlib import Path
from unittest.mock import patch

import tools.maintainer.checks as checks

ROOT = Path(__file__).resolve().parents[4]


class MaintenanceContracts(unittest.TestCase):
    def setUp(self) -> None:
        self.data = {
            kind: checks.yaml(ROOT / f"docs/runa/maintainer/maintenance/{kind}.yml")
            for kind in ("registry", "backlog", "deprecations")
        }

    def validate(self) -> None:
        with (
            patch.object(checks, "yaml", side_effect=lambda path: self.data[path.stem]),
            contextlib.redirect_stdout(io.StringIO()),
        ):
            checks.maintenance()

    def test_current_registry_passes(self) -> None:
        self.validate()

    def test_duplicate_id_and_dependency_version_drift_are_rejected(self) -> None:
        original = copy.deepcopy(self.data)
        self.data["registry"]["entries"].append(copy.deepcopy(self.data["registry"]["entries"][0]))
        with self.assertRaisesRegex(ValueError, "Duplicate"):
            self.validate()
        self.data = original
        next(e for e in self.data["registry"]["entries"] if e.get("package") == "vue")[
            "version"
        ] = "0.0.0"
        with self.assertRaisesRegex(ValueError, "Dependency version"):
            self.validate()

    def test_incomplete_maintenance_and_deprecation_are_rejected(self) -> None:
        for kind in ("backlog", "deprecations"):
            self.data[kind]["items"] = [{"id": "MT-2026-999"}]
            with self.assertRaisesRegex(ValueError, "Incomplete"):
                self.validate()
            self.data[kind]["items"] = []
