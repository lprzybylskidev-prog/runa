#!/usr/bin/env python3
"""External development updater command; services never import this module."""

from __future__ import annotations

import argparse
import getpass
import json
import re
import signal
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path
from types import FrameType

if __name__ == "__main__" and not __package__:
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", "distribution.update", *sys.argv[1:]],
            cwd=Path(__file__).resolve().parents[1],
        )
    )

from distribution import bootstrap
from distribution.update_execution_models import ExecutionReport
from distribution.update_file_models import CustomizationReview
from distribution.update_plan_models import PreflightReport


def main(arguments: Sequence[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument("--preflight", action="store_true")
    mode.add_argument("--plan", action="store_true")
    mode.add_argument("--apply", action="store_true")
    mode.add_argument("--resume", action="store_true")
    parser.add_argument("--customization-decision", choices=("continue", "abort"))
    parser.add_argument(
        "--review-fingerprint",
        help="Exact reviewed delivered-file fingerprint, required for changed resume reviews",
    )
    parser.add_argument("--non-interactive", action="store_true")
    parser.add_argument("--installation", required=True)
    parser.add_argument("--target", required=True, help="Exact published stable vX.Y.Z release")
    parser.add_argument(
        "--repository", default=bootstrap.REPOSITORY, help="Explicitly authorized GitHub owner/name"
    )
    parser.add_argument(
        "--toolchain-image",
        default="runa-development:dev",
        help="Existing trusted owner toolchain image; no automatic pull/build",
    )
    parser.add_argument("--expected-commit", help="Independently supplied target release commit")
    parser.add_argument("--json", action="store_true")
    credentials = parser.add_mutually_exclusive_group()
    credentials.add_argument("--token-file")
    credentials.add_argument("--ask-token", action="store_true")
    args = parser.parse_args(arguments)
    if (args.preflight or args.plan) and (args.customization_decision or args.review_fingerprint):
        parser.error(
            "Customization decisions apply only to --apply/--resume; read-only modes never request consent."
        )
    result: PreflightReport | ExecutionReport
    token = ""
    try:
        if args.ask_token and not sys.stdin.isatty():
            raise bootstrap.BootstrapError(
                "Hidden credentials require an interactive terminal; use --token-file for automation."
            )
        token = (
            bootstrap.token_from_file(args.token_file)
            if args.token_file
            else bootstrap.validate_token(getpass.getpass("GitHub read token (not saved): "))
            if args.ask_token
            else ""
        )
        if args.expected_commit and not re.fullmatch(bootstrap.SHA, args.expected_commit):
            raise bootstrap.BootstrapError("Expected target commit requires a full lowercase SHA.")
        bootstrap.host_checks()
        if args.review_fingerprint and not re.fullmatch("[a-f0-9]{64}", args.review_fingerprint):
            raise bootstrap.BootstrapError(
                "Review fingerprint requires a full lowercase SHA-256 value."
            )
        client = bootstrap.GitHub(args.repository, token)
        if args.apply or args.resume:
            from distribution.update_execution import UpdateExecutor
            from distribution.update_planning import UpdatePlanner

            def choose(review: CustomizationReview) -> str:
                print(
                    "Customized services: "
                    + ", ".join(review["services"])
                    + ("; devcontainer" if review["devcontainer"] else ""),
                    file=sys.stderr,
                )
                print("Review fingerprint: " + review["fingerprint"], file=sys.stderr)
                print(json.dumps(review["files"], indent=2), file=sys.stderr)
                print(
                    "Continue applies listed eligible changes, including replacement/removal of customized files. Manual reconciliation preserves customer instruction extensions; review the extra work before consent.",
                    file=sys.stderr,
                )
                return (
                    input("Apply these exact reviewed actions, or abort [continue/abort]: ")
                    .strip()
                    .lower()
                )

            interactive = (
                not args.non_interactive
                and not args.json
                and sys.stdin.isatty()
                and sys.stderr.isatty()
            )
            result = UpdateExecutor(UpdatePlanner()).execute(
                args.installation,
                client,
                args.target,
                resume=args.resume,
                choice=args.customization_decision,
                fingerprint=args.review_fingerprint,
                toolchain=args.toolchain_image,
                expected_commit=args.expected_commit,
                choose=choose if interactive else None,
            )
        else:
            from distribution.update_planning import UpdatePlanner

            result = UpdatePlanner().preflight(
                args.installation,
                client,
                args.target,
                toolchain=args.toolchain_image,
                expected_commit=args.expected_commit,
            )
    except bootstrap.BootstrapError as error:
        result = {
            "schema_version": 1,
            "mode": "preflight",
            "dry_run": not (args.apply or args.resume),
            "status": "blocked",
            "execution_available": True,
            "mismatches": [],
            "temporary_downloads": [],
            "temporary_downloads_cleaned": True,
            "checks": [{"id": "host-or-credentials", "status": "blocked", "message": str(error)}],
        }
    mode = (
        "resume" if args.resume else "apply" if args.apply else "plan" if args.plan else "preflight"
    )
    if args.json:
        print(json.dumps({**result, "mode": mode}, indent=2))
    else:
        print(
            "Runa update "
            + mode
            + ": "
            + result["status"].upper()
            + (" (read-only)." if result.get("dry_run") else ".")
        )
        for check in result["checks"]:
            print(
                check["status"].upper()
                + ": "
                + check["id"]
                + (" — " + check["message"] if "message" in check else "")
            )
        for row in result.get("mismatches", []):
            print(row["kind"] + ": " + row["path"])
        if "plan" in result:
            print(json.dumps(result["plan"], indent=2))
        if "customization_review" in result:
            print("Delivered-file customization: " + json.dumps(result["customization_review"]))
        if "journal" in result:
            print(
                "Preserve "
                + result["journal"]
                + "; inspect the failed phase before explicit --resume."
            )
        if "temporary_downloads_cleaned" in result:
            print(
                "Temporary release downloads outside installation: "
                + (
                    "cleaned."
                    if result["temporary_downloads_cleaned"]
                    else "cleanup requires operator inspection."
                )
            )
    return 0 if result["status"] in ("ready", "completed") else 1


if __name__ == "__main__":

    def interrupt(signum: int, frame: FrameType | None) -> None:
        raise KeyboardInterrupt

    signal.signal(signal.SIGTERM, interrupt)
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print(
            "Updater interrupted; temporary downloads cleaned. Preserve pending operation evidence and resume explicitly after inspection.",
            file=sys.stderr,
        )
        raise SystemExit(130) from None
