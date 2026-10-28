"""Import-safe security command composition."""

import argparse
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from tools.application.security_service import SecurityService
from tools.core.reporting import SafeFailure


def main(argv: Sequence[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "action", choices=["trivy", "review-changed-surface", "dependencies", "secrets"]
    )
    parser.add_argument(
        "--dependencies-root",
        type=Path,
        help="Prepared development checkout with matching dependency locks",
    )
    options = parser.parse_args(argv)
    security = SecurityService.for_workspace(
        Path(__file__).resolve().parents[2],
        audit_root=options.dependencies_root.resolve() if options.dependencies_root else None,
    )
    try:
        if options.action == "trivy":
            from tools.application.deployment_service import DeploymentService

            with DeploymentService.for_workspace(
                Path(__file__).resolve().parents[2]
            ).context.lock():
                security.trivy()
        else:
            {
                "review-changed-surface": security.review,
                "dependencies": security.dependencies,
                "secrets": security.secrets_scan,
            }[options.action]()
        return 0
    except (ValueError, OSError, KeyError, SafeFailure, subprocess.CalledProcessError) as error:
        print(
            "Security failed: "
            + (
                str(error)
                if isinstance(error, (ValueError, SafeFailure))
                else "Required security input is unavailable."
            ),
            file=sys.stderr,
        )
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
