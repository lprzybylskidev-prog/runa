"""External review decoders preserve consent evidence and refuse invalid recovery input."""

import json
from collections import deque
from collections.abc import Sequence
from functools import partial
from pathlib import Path

import pytest

from distribution import update_customization, update_modules
from distribution.processes import ExternalProcesses
from distribution.update_contracts import Blocker
from distribution.update_file_models import file_journal
from distribution.update_models import RuntimeSnapshot, planned_modules
from tools.application.testing.support.process import RecordingRunner
from tools.core.process import ProcessResult
from tools.core.state import JsonValue


def test_file_journal_preserves_evidence_and_legacy_missing_actions() -> None:
    assert file_journal({"legacy": {"retained": True}}) == {"legacy": {"retained": True}}
    before: dict[str, JsonValue] = {"mode": "100755", "sha256": "a" * 64, "extension": True}
    row: dict[str, JsonValue] = {
        "path": "bin/example",
        "ownership": "delivered",
        "action": "update",
        "local": "modified",
        "upstream": "modified",
        "conflict": "overlapping-change",
        "original": before,
        "current": before,
        "target": {"mode": "120000", "sha256": "b" * 64},
        "id": "c" * 32,
        "state": "replacing",
        "extension": {"retained": True},
    }
    value: dict[str, JsonValue] = {
        "file_actions": [row],
        "file_action_history": [],
        "extension": True,
    }
    assert file_journal(value) == value
    invalid_fields: list[tuple[str, JsonValue]] = [
        ("target", {"mode": "100644", "sha256": False}),
        ("current", {"mode": "directory", "sha256": "a" * 64}),
        ("state", "sentinel"),
        ("path", False),
        ("local", []),
    ]
    for field, invalid in invalid_fields:
        malformed: dict[str, JsonValue] = {"file_actions": [{**row, field: invalid}]}
        with pytest.raises(Blocker, match="^Invalid delivered-file action journal.$") as caught:
            file_journal(malformed)
        assert caught.value.code == "project-journal-changed"
        assert "sentinel" not in str(caught.value)
    del row["original"]
    with pytest.raises(Blocker):
        file_journal(value)


def test_compose_review_decodes_data_without_environment_resolution(tmp_path: Path) -> None:
    (tmp_path / "compose.yaml").write_text("services: {}")
    calls: list[tuple[tuple[str, ...], Path | None, float]] = []
    payload = '{"services":{"custom":{"build":{"context":"${LITERAL}"},"extension":true}}}'

    def command(
        response: str, arguments: Sequence[str], cwd: Path | None = None, timeout: float = 90
    ) -> str:
        calls.append((tuple(arguments), cwd, timeout))
        return response

    assert update_customization.configuration(
        tmp_path, "compose.yaml", command=partial(command, payload)
    ) == {"custom": {"build": {"context": "${LITERAL}"}, "extension": True}}
    args, cwd, _ = calls[0]
    assert cwd == tmp_path
    assert {"--no-interpolate", "--no-env-resolution", "--no-normalize"} <= set(args)
    assert args[args.index("--env-file") + 1] == "/dev/null"
    for payload in (
        "[]",
        '{"services":{"custom":"sentinel"}}',
        '{"services":{"custom":{"value":NaN}}}',
    ):
        with pytest.raises(Blocker) as caught:
            update_customization.configuration(
                tmp_path, "compose.yaml", command=partial(command, payload)
            )
        assert caught.value.code == "customization-review-unavailable"
        assert "sentinel" not in str(caught.value)


def test_module_response_preserves_php_empty_maps_and_extension_evidence() -> None:
    row: dict[str, JsonValue] = {
        "module": "runa.example",
        "from": None,
        "to": "1.0.0",
        "migrations": [],
        "pending_migrations": [],
        "upgrades": [],
        "fingerprint": "a" * 64,
        "completed_steps": [],
        "pending_upgrades": [],
        "extension": {"retained": True},
    }
    assert planned_modules(json.dumps([row])) == [row]
    row["migrations"] = {"initial": "b" * 64}
    row["upgrades"] = [{"key": "initial", "transactional": True}]
    assert planned_modules(json.dumps([row])) == [row]
    invalid_fields: list[tuple[str, JsonValue]] = [
        ("migrations", ["invalid"]),
        ("completed_steps", [False]),
        ("upgrades", ["sentinel"]),
        ("from", False),
    ]
    for field, invalid in invalid_fields:
        with pytest.raises(Blocker) as caught:
            planned_modules(json.dumps([{**row, field: invalid}]))
        assert caught.value.code == "module-preflight"
        assert "sentinel" not in str(caught.value)


def test_invalid_module_response_cleans_only_injected_planner(tmp_path: Path) -> None:
    (tmp_path / "src/runa/modules/example").mkdir(parents=True)
    (tmp_path / "src/runa/platform.json").write_text(
        '{"schema_version":1,"id":"runa","name":"Runa","version":"0.5.0"}'
    )
    runner = RecordingRunner(deque([ProcessResult(0, b'{"secret":"sentinel"}'), ProcessResult(0)]))
    runtime: RuntimeSnapshot = {
        "project": "fixture",
        "services": [],
        "migrations": [],
        "seed_runs": [],
        "modules": {},
        "module_steps": [],
    }
    with pytest.raises(Blocker) as caught:
        update_modules.plan(
            tmp_path,
            tmp_path,
            runtime,
            "fixture:toolchain",
            processes=ExternalProcesses(runner, {}),
        )
    assert caught.value.code == "module-preflight"
    assert "sentinel" not in str(caught.value)
    probe, cleanup = runner.calls
    name = probe.argv[probe.argv.index("--name") + 1]
    assert cleanup.argv == ("docker", "rm", "--force", name)
    assert not runner.outcomes
