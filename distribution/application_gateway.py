"""Explicit process boundary; never imports customer application or two source versions."""

import json
import sys
from collections.abc import Sequence
from pathlib import Path


class GatewayError(ValueError):
    pass


def installation_command(workspace: Path, arguments: Sequence[str]) -> list[str]:
    workspace = Path(workspace)
    if not workspace.is_absolute() or workspace.resolve() != workspace:
        raise GatewayError("Installation workspace must be absolute without symlinks.")
    marker = workspace / "tools/application/gateway.json"
    if marker.is_symlink() or not marker.is_file() or marker.stat().st_size > 4096:
        raise GatewayError(
            "This release needs its matching historical installer; installation gateway v1 is unavailable."
        )
    value = json.loads(marker.read_text())
    if value != {"schema_version": 1, "installation_protocol": 1}:
        raise GatewayError("Application installation gateway protocol is unsupported.")
    return [
        sys.executable,
        "-m",
        "tools.application.installation_gateway",
        "--protocol=1",
        "--workspace=" + str(workspace),
        *arguments,
    ]
