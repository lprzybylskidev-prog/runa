"""Fetch customer Git into disposable staging; never alter installation Git history."""

import os
import posixpath
import re
import subprocess
import tempfile
from collections.abc import Iterator, Mapping
from contextlib import contextmanager
from dataclasses import dataclass, field
from pathlib import Path

from tools.application.processes import WorkspaceProcess
from tools.application.repository_boundary_service import RepositoryPolicy
from tools.core.delivery import OWNER_PATHS

COMPOSED = {"src/composer.json", "src/composer.lock", "src/package.json", "src/pnpm-lock.yaml"}


@dataclass(frozen=True)
class CustomerSource:
    run: WorkspaceProcess
    environment: Mapping[str, str] = field(repr=False)

    @classmethod
    def for_workspace(cls, root: Path) -> "CustomerSource":
        from functools import partial

        from tools.application.processes import ApplicationProcesses
        from tools.core.process import SubprocessRunner

        environment = dict(os.environ)
        processes = ApplicationProcesses(SubprocessRunner(), environment)
        return cls(partial(processes.run, cwd=root), environment)

    @property
    def policy(self) -> RepositoryPolicy:
        return RepositoryPolicy(self.run, self.environment)

    @staticmethod
    def application_path(path: str) -> bool:
        return path.startswith(("src/app/", "docs/app/")) or path in COMPOSED

    @staticmethod
    def excluded(path: str) -> bool:
        return any(path == item or path.startswith(item + "/") for item in OWNER_PATHS)

    def git(self, root: Path, *arguments: str) -> bytes:
        environment = {
            key: value for key, value in self.environment.items() if not key.startswith("GIT_")
        }
        environment["GIT_TERMINAL_PROMPT"] = "0"
        try:
            return self.run(
                [
                    "git",
                    "-c",
                    "core.hooksPath=/dev/null",
                    "-c",
                    "protocol.ext.allow=never",
                    "-C",
                    str(root),
                    *arguments,
                ],
                cwd=root,
                env=environment,
                stdin=subprocess.DEVNULL,
                capture_output=True,
                check=True,
                timeout=180,
            ).stdout
        except (subprocess.CalledProcessError, subprocess.TimeoutExpired):
            raise ValueError(
                "Customer Git operation failed. Check the selected remote, target and operator credentials; no running release was changed."
            ) from None

    @staticmethod
    def validate_target(target: object) -> str:
        if (
            not isinstance(target, str)
            or len(target) > 255
            or not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9._/-]*", target)
            or any(value in target for value in ("..", "//", "@{"))
            or target.endswith(("/", ".", ".lock"))
        ):
            raise ValueError(
                "Target must be a branch, tag, refs/heads/... or refs/tags/... name, or full lowercase SHA."
            )
        if target.startswith("refs/") and not target.startswith(("refs/heads/", "refs/tags/")):
            raise ValueError("Only branch/tag refs are supported.")
        return target

    def tree(self, root: Path, ref: str) -> dict[str, tuple[str, str]]:
        entries: dict[str, tuple[str, str]] = {}
        for row in self.git(root, "ls-tree", "-rz", ref).split(b"\0"):
            if row:
                metadata, name = row.split(b"\t", 1)
                path = name.decode("utf-8")
                if (
                    any(
                        part in ("", ".", "..") or part.lower() == ".git"
                        for part in path.split("/")
                    )
                    or "\\" in path
                    or any(ord(char) < 32 or ord(char) == 127 for char in path)
                ):
                    raise ValueError("Unsafe source path in customer revision.")
                mode, kind, identity = metadata.decode("ascii").split()
                if self.excluded(path):
                    raise ValueError("Owner-only source is not part of a customer revision.")
                if (
                    mode not in ("100644", "100755", "120000")
                    or kind != "blob"
                    or (self.application_path(path) and mode not in ("100644", "100755"))
                ):
                    raise ValueError("Submodules and application symlinks are unsupported: " + path)
                entries[path] = (mode, identity)
                if len(entries) > 50000:
                    raise ValueError("Customer source exceeds 50000 files.")
        for name, (mode, _) in entries.items():
            if mode != "120000":
                continue
            visited: set[str] = set()
            current = name
            while entries[current][0] == "120000":
                if current in visited or len(visited) >= 32:
                    raise ValueError("Cyclic/oversized customer symlink chain.")
                visited.add(current)
                target = self.git(root, "cat-file", "blob", entries[current][1]).decode("utf-8")
                if (
                    target.startswith("/")
                    or "\\" in target
                    or any(ord(c) < 32 or ord(c) == 127 for c in target)
                ):
                    raise ValueError("Unsafe customer symlink target.")
                current = posixpath.normpath(posixpath.join(posixpath.dirname(current), target))
                if current not in entries:
                    raise ValueError(
                        "Customer symlink escapes source or has no tracked file target."
                    )
        return entries

    def validate_release(self, installed: Path) -> None:
        """A recorded immutable deployment must remain its own exact commit."""

        recorded = self.policy.receipt(installed)
        if (
            self.git(installed, "rev-parse", "HEAD").decode().strip() != recorded.get("commit")
            or self.git(installed, "status", "--porcelain=v1", "--untracked-files=all").strip()
        ):
            raise ValueError(
                "Successful release checkout is modified; inspect its source/evidence before deployment."
            )

    @contextmanager
    def fetch(
        self, root: Path, state: Path, target: str = "main", installed: Path | None = None
    ) -> Iterator[tuple[Path, str]]:
        target = self.validate_target(target)
        _, url = self.policy.application_remote(root)
        with tempfile.TemporaryDirectory(prefix="customer-source-", dir=state) as directory:
            source = Path(directory)
            self.git(source, "init", "--bare", "--quiet")
            if re.fullmatch("[a-f0-9]{40}", target):
                ref = target
            else:
                candidates = (
                    [target]
                    if target.startswith("refs/")
                    else ["refs/heads/" + target, "refs/tags/" + target]
                )
                rows = (
                    self.git(source, "ls-remote", "--refs", "--", url, *candidates)
                    .decode()
                    .splitlines()
                )
                refs = {row.split("\t", 1)[1] for row in rows}
                if len(refs) != 1:
                    raise ValueError(
                        "Customer target is missing or ambiguous; select an explicit refs/heads/... or refs/tags/... name."
                    )
                ref = refs.pop()
            self.git(
                source, "fetch", "--quiet", "--no-tags", "--no-recurse-submodules", "--", url, ref
            )
            sha = self.git(source, "rev-parse", "--verify", "FETCH_HEAD^{commit}").decode().strip()
            if not re.fullmatch("[a-f0-9]{40}", sha) or (
                re.fullmatch("[a-f0-9]{40}", target) and sha != target
            ):
                raise ValueError(
                    "Fetched customer commit identity differs from the requested target."
                )
            self.tree(source, sha)
            if installed is not None:
                self.validate_release(installed)
            self.git(source, "update-ref", "refs/heads/selected", sha)
            self.git(source, "symbolic-ref", "HEAD", "refs/heads/selected")
            yield source, sha
