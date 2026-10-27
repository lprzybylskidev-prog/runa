"""Private composition of public workspace helpers and knowledge defaults."""

import json
from pathlib import Path

from tools.application.testing.support.workspaces import WorkspaceFixture as WorkspaceFixture
from tools.application.testing.support.workspaces import repository as repository
from tools.application.testing.support.workspaces import workspace as workspace


def knowledge_configuration(root: Path, *, collection: str = "runa-test") -> Path:
    source = Path(__file__).resolve().parents[4] / "application/knowledge/config.json"
    config = json.loads(source.read_text())
    config["collection"] = collection
    return workspace(root).write("knowledge.json", json.dumps(config) + "\n")
