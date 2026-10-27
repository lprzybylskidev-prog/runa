"""Verified complete customer source acquisition."""

import hashlib
import re
from pathlib import Path

from tools.application.deployment_context import OperationContext
from tools.core.delivery import DEVELOPMENT_MODULES, OWNER_PATHS, production_patterns

DELIVERY = production_patterns()


class SourceAcquisition:
    def __init__(self, context: OperationContext) -> None:
        self.context = context

    def resolve(self, target: str) -> str:
        if not re.fullmatch(r"(?:v\d+\.\d+\.\d+|[a-f0-9]{40})", target):
            raise ValueError("Target must be an exact vX.Y.Z release or full lowercase Git SHA.")
        if (
            target.startswith("v")
            and self.context.run(
                ["git", "cat-file", "-t", target], capture_output=True, text=True
            ).stdout.strip()
            != "tag"
        ):
            raise ValueError("Release versions must identify annotated tags.")
        sha = self.context.run(
            ["git", "rev-parse", "--verify", target + "^{commit}"], capture_output=True, text=True
        ).stdout.strip()
        if not re.fullmatch("[a-f0-9]{40}", sha):
            raise ValueError("Invalid commit identity.")
        return sha

    def tree_hash(self, sha: str, repository: Path | None = None) -> str:
        # Conservatively include module migrations/upgrades and their owned helper implementations.
        return hashlib.sha256(
            self.context.run(
                [
                    "git",
                    "-C",
                    str(repository or self.context.workspace.root),
                    "ls-tree",
                    "-r",
                    sha,
                    "src/app/database",
                    "src/app/Support/Seeding",
                    "src/runa/database",
                    "src/runa/Support/Seeding",
                    "src/runa/modules",
                    "src/app/modules",
                    "src/runa/Support/Modules/Updates",
                ],
                capture_output=True,
            ).stdout
        ).hexdigest()

    def checkout(self, sha: str, source: Path | None = None) -> Path:
        data = self.context.policy.receipt(self.context.workspace.root)
        if not re.fullmatch("[a-f0-9]{40}", sha):
            raise ValueError("Staging requires an exact commit identity.")
        path = self.context.state / "releases" / sha
        if path.resolve() != path:
            raise ValueError("Release staging must not traverse symlinks.")
        staging = {"source_checkout": str(self.context.workspace.root), "commit": sha}
        if path.exists():
            actual = self.context.run(
                ["git", "-C", str(path), "rev-parse", "HEAD"], capture_output=True, text=True
            ).stdout.strip()
            dirty = self.context.run(
                ["git", "-C", str(path), "status", "--porcelain", "--untracked-files=all"],
                capture_output=True,
                text=True,
            ).stdout
            if actual != sha or dirty:
                raise ValueError(
                    "Existing release checkout is modified; inspect it before retrying."
                )
            if self.context.policy.receipt(path).get("staging") != staging:
                raise ValueError(
                    "Existing release staging ownership differs; no source is adopted or repaired."
                )
            return path
        path.parent.mkdir(parents=True, exist_ok=True)
        self.context.run(
            [
                "git",
                "-c",
                "core.hooksPath=/dev/null",
                "clone",
                "--no-checkout",
                "--no-hardlinks",
                str(source or self.context.workspace.root),
                str(path),
            ]
        )
        self.context.run(["git", "-C", str(path), "sparse-checkout", "init", "--no-cone"])
        self.context.run(
            ["git", "-C", str(path), "sparse-checkout", "set", "--no-cone", "--stdin"],
            input="\n".join(DELIVERY) + "\n",
            text=True,
        )
        self.context.run(["git", "-C", str(path), "checkout", "--detach", sha])
        staged = {**data, "staging": staging, "commit": sha, "mode": "prod"}
        # Acquisition digests identify the original download, not a different staged commit.
        staged.pop("source_sha256", None)
        self.context.policy.write_staging_receipt(path, staged)
        _, url = self.context.policy.application_remote(self.context.workspace.root, data)
        self.context.run(["git", "-C", str(path), "remote", "set-url", "origin", url])
        self.context.run(
            ["git", "-C", str(path), "config", "--local", "runa.applicationRemote", "origin"]
        )
        return path

    def assert_delivery(self, path: Path) -> None:
        if any(
            (path / name).exists() or (path / name).is_symlink()
            for name in (*OWNER_PATHS, *DEVELOPMENT_MODULES)
        ):
            raise ValueError(
                "Owner-only or development example source is present in the production project."
            )
