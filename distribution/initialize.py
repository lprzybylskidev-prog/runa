#!/usr/bin/env python3
"""Initialize production from a complete customer repository, never a Runa release."""

import argparse
import json
import os
import posixpath
import re
import signal
import subprocess
import sys
import tempfile
from collections.abc import Mapping, Sequence
from pathlib import Path
from types import FrameType
from typing import TypedDict
from urllib.parse import unquote, urlsplit

if __name__ == "__main__" and not __package__:
    raise SystemExit(
        subprocess.call(
            [sys.executable, "-m", "distribution.initialize", *sys.argv[1:]],
            cwd=Path(__file__).resolve().parents[1],
        )
    )

from distribution import bootstrap
from tools.core.process import (
    InputMode,
    OutputMode,
    ProcessRunner,
    ProcessSpec,
    ProcessTimeout,
    SubprocessRunner,
)


class CustomerReceipt(TypedDict):
    schema_version: int
    repository: str
    source: str
    mode: str
    commit: str
    projection: bootstrap.Projection


class InitializationArguments(argparse.Namespace):
    repository: str
    destination: str
    target: str
    install: str
    settings_file: str | None
    simulation: bool


def customer_url(value: str) -> str:
    if not value or value.startswith("-") or any(c.isspace() or ord(c) < 32 for c in value):
        raise bootstrap.BootstrapError(
            "Select a credential-free customer HTTPS/SSH URL or absolute local repository."
        )
    parsed = urlsplit(value)
    if parsed.scheme in ("https", "ssh"):
        if (
            not parsed.hostname
            or parsed.password
            or (parsed.scheme == "https" and parsed.username)
            or parsed.query
            or parsed.fragment
        ):
            raise bootstrap.BootstrapError(
                "Customer repository URLs must not contain credentials/query/fragment."
            )
        host, path = parsed.hostname, parsed.path
    elif re.fullmatch(r"[A-Za-z0-9_.-]+@[A-Za-z0-9.-]+:[A-Za-z0-9_./-]+", value):
        host, path = value.split("@", 1)[1].split(":", 1)
    elif Path(value).is_absolute():
        host, path = "", value
    else:
        raise bootstrap.BootstrapError("Unsupported customer repository transport.")
    if (
        host.rstrip(".").lower() in ("github.com", "www.github.com", "ssh.github.com")
        and posixpath.normpath(unquote(path)).strip("/").lower().removesuffix(".git")
        == bootstrap.REPOSITORY.lower()
    ):
        raise bootstrap.BootstrapError(
            "Production initializes from the customer repository, never the Runa release repository."
        )
    return value


class CustomerSource:
    def __init__(
        self,
        runner: ProcessRunner | None = None,
        environment: Mapping[str, str] | None = None,
    ) -> None:
        self.runner = runner or SubprocessRunner()
        self.environment = dict(os.environ if environment is None else environment)

    def git(self, source: Path, *args: str) -> bytes:
        environment = {
            key: value for key, value in self.environment.items() if not key.startswith("GIT_")
        }
        environment["GIT_TERMINAL_PROMPT"] = "0"
        try:
            result = self.runner.run(
                ProcessSpec(
                    (
                        "git",
                        "-c",
                        "core.hooksPath=/dev/null",
                        "-c",
                        "protocol.ext.allow=never",
                        "-c",
                        "http.followRedirects=false",
                        "-C",
                        str(source),
                        *args,
                    ),
                    source,
                    environment,
                    output=OutputMode.CAPTURE,
                    timeout=600,
                )
            )
        except (OSError, ProcessTimeout):
            raise bootstrap.BootstrapError(
                "Host tool failed or timed out; check prerequisites/connectivity and retry."
            ) from None
        if result.returncode:
            raise bootstrap.BootstrapError(
                "Host tool failed. Check customer repository access, connectivity and disk space."
            )
        return result.stdout

    def acquire(self, repository: str, target: str, destination: Path) -> str:
        customer_url(repository)
        if (
            not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9._/-]{0,254}", target)
            or any(value in target for value in ("..", "//", "@{"))
            or target.endswith(("/", ".", ".lock"))
            or (target.startswith("refs/") and not target.startswith(("refs/heads/", "refs/tags/")))
        ):
            raise bootstrap.BootstrapError(
                "Select a customer branch/tag/full lowercase commit SHA."
            )
        with tempfile.TemporaryDirectory(
            prefix=".runa-customer-", dir=destination.parent
        ) as directory:
            home = Path(directory)
            source = home / "source"
            self.git(
                home,
                "clone",
                "--quiet",
                "--no-checkout",
                "--no-hardlinks",
                "--no-recurse-submodules",
                "--",
                repository,
                str(source),
            )
            source.chmod(0o700)
            if re.fullmatch(bootstrap.SHA, target):
                self.git(
                    source,
                    "fetch",
                    "--quiet",
                    "--no-tags",
                    "--no-recurse-submodules",
                    "origin",
                    target,
                )
                ref = "FETCH_HEAD"
            else:
                refs = (
                    [target.replace("refs/heads/", "refs/remotes/origin/", 1)]
                    if target.startswith("refs/")
                    else ["refs/remotes/origin/" + target, "refs/tags/" + target]
                )
                present = []
                for value in refs:
                    try:
                        self.git(source, "show-ref", "--verify", value)
                        present.append(value)
                    except bootstrap.BootstrapError:
                        pass
                if len(present) != 1:
                    raise bootstrap.BootstrapError(
                        "Missing or ambiguous customer target; use refs/heads/... or refs/tags/... explicitly."
                    )
                ref = present[0]
            sha = self.git(source, "rev-parse", "--verify", ref + "^{commit}").decode().strip()
            if not re.fullmatch(bootstrap.SHA, sha) or (
                re.fullmatch(bootstrap.SHA, target) and target != sha
            ):
                raise bootstrap.BootstrapError("Customer source commit identity is inconsistent.")
            self.git(source, "fsck", "--strict", "--no-reflogs")
            entries = bootstrap.parse_tree(self.git(source, "ls-tree", "-r", "-z", "-l", sha))
            if any(
                any(
                    name == prefix or name.startswith(prefix + "/")
                    for prefix in bootstrap.OWNER_PATHS
                )
                for name, *_ in entries
            ):
                raise bootstrap.BootstrapError(
                    "Customer revision contains owner-only source; version only the delivered customer project."
                )
            self.git(source, "checkout", "--quiet", "--detach", sha)
            bootstrap.verify_files(source, entries)
            self.git(source, "config", "--local", "runa.applicationRemote", "origin")
            receipt: CustomerReceipt = {
                "schema_version": 1,
                "repository": bootstrap.REPOSITORY,
                "source": "customer-repository",
                "mode": "prod",
                "commit": sha,
                "projection": {"version": 2, "excluded": list(bootstrap.OWNER_PATHS)},
            }
            marker = source / ".git/runa-bootstrap.json"
            marker.write_text(json.dumps(receipt, indent=2) + "\n")
            marker.chmod(0o600)
            bootstrap.publish(source, destination)
            return sha


def main(arguments: Sequence[str] | None = None, *, runner: ProcessRunner | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--repository",
        required=True,
        help="Customer repository URL; operator Git credentials remain external",
    )
    parser.add_argument("--destination", required=True)
    parser.add_argument("--target", default="main")
    parser.add_argument("--install", choices=("now", "later"), default="later")
    parser.add_argument("--settings-file")
    parser.add_argument("--simulation", action="store_true")
    args = parser.parse_args(arguments, namespace=InitializationArguments())
    bootstrap.host_checks()
    destination = bootstrap.destination_path(args.destination)
    source = CustomerSource(runner)
    sha = source.acquire(args.repository, args.target, destination)
    command = bootstrap.install_command(destination, "prod", sha)
    command.append("--non-interactive")
    if args.settings_file:
        command.append("--settings-file=" + str(Path(args.settings_file).resolve()))
    if args.simulation:
        command.append("--simulation")
    import shlex

    print(
        "Customer source prepared at " + sha + ". From the destination run: " + shlex.join(command),
        flush=True,
    )
    if args.install == "now":
        return source.runner.run(
            ProcessSpec(
                tuple(command),
                destination,
                source.environment,
                stdin=InputMode.CLOSED,
            )
        ).returncode
    return 0


if __name__ == "__main__":

    def interrupted(signum: int, frame: FrameType | None) -> None:
        raise KeyboardInterrupt

    signal.signal(signal.SIGTERM, interrupted)
    try:
        raise SystemExit(main())
    except (bootstrap.BootstrapError, OSError, ValueError, KeyError):
        error = sys.exc_info()[1]
        print(
            str(error)
            if isinstance(error, bootstrap.BootstrapError)
            else "Customer initialization failed; inspect repository/source/prerequisites. Credentials are not included in diagnostics.",
            file=sys.stderr,
        )
        raise SystemExit(1) from None
    except KeyboardInterrupt:
        print(
            "Customer initialization interrupted; external staging cleaned and published source preserved.",
            file=sys.stderr,
        )
        raise SystemExit(130) from None
