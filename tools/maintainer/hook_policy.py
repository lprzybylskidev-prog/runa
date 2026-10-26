"""Private immutable history protection; migration exceptions have expired."""

import hashlib
import json

from tools.application.documentation import maintainer_checkout


def immutable_history(root, name, data):
    if not maintainer_checkout(root):
        return False
    mapping = root / "docs/runa/maintainer/documentation-map.json"
    if mapping.is_symlink():
        raise ValueError("Historical identity map must be regular.")
    expected = (
        json.loads(mapping.read_text())["immutable_sha256"].get(name) if mapping.is_file() else None
    )
    if expected and (data is None or hashlib.sha256(data).hexdigest() != expected):
        raise ValueError("Immutable historical source changed: " + name)
    return (
        bool(expected)
        or name.startswith("docs/runa/maintainer/roadmaps/closed/")
        and name.endswith(".md")
    )
