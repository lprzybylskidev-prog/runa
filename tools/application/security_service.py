"""Workspace-bound security scans, strict aggregate audits and review evidence."""

from __future__ import annotations

import hashlib
import json
import os
import shutil
import subprocess
import tempfile
from collections.abc import Sequence
from dataclasses import dataclass
from functools import partial
from pathlib import Path

from tools.application.development_runtime import DevelopmentRuntime
from tools.application.processes import ApplicationProcesses, WorkspaceProcess
from tools.core.context import ExecutionContext, ExecutionLocation, WorkspaceContext
from tools.core.reporting import SafeFailure


@dataclass(frozen=True)
class SecurityService:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation
    audit_root: Path | None = None

    @classmethod
    def for_workspace(cls, root: Path, *, audit_root: Path | None = None) -> SecurityService:
        from tools.application.development_runtime import execution_location
        from tools.core.process import SubprocessRunner

        return cls(
            WorkspaceContext(root),
            ApplicationProcesses(SubprocessRunner(), dict(os.environ)),
            execution_location(),
            audit_root,
        )

    def app_command(self, args: Sequence[str]) -> tuple[str, ...]:
        return DevelopmentRuntime(
            ExecutionContext(self.workspace, self.workspace, self.location)
        ).app_command(tuple(args))

    @property
    def command(self) -> WorkspaceProcess:
        return partial(self.processes.run, cwd=self.workspace.root, check=True)

    def surface(self, reference: str = "main") -> dict[str, str]:
        files = set(
            self.command(
                ["git", "diff", "--name-only", reference, "--"], capture_output=True, text=True
            ).stdout.splitlines()
        )
        files.update(
            self.command(
                ["git", "ls-files", "--others", "--exclude-standard"],
                capture_output=True,
                text=True,
            ).stdout.splitlines()
        )
        return {
            name: hashlib.sha256((self.workspace.root / name).read_bytes()).hexdigest()
            if (self.workspace.root / name).is_file()
            else "deleted"
            for name in sorted(files)
            if not name.startswith(("docs/", ".agents/"))
        }

    def review(self) -> None:
        path = self.workspace.root / ".runtime/security-review.json"
        if not path.is_file():
            raise ValueError(
                "Missing actual agent review evidence at .runtime/security-review.json. Review the changed surface; do not auto-attest."
            )
        data = json.loads(path.read_text())
        if (
            not data.get("reviewer")
            or not data.get("reviewed_at")
            or not data.get("report")
            or data.get("blocking_findings") != []
            or data.get("surface") != self.surface()
        ):
            raise ValueError(
                "Security review evidence is incomplete, stale or has blocking findings."
            )
        report = self.workspace.root / data["report"]
        if (
            not report.resolve().is_relative_to(self.workspace.root)
            or report.is_symlink()
            or not report.is_file()
            or hashlib.sha256(report.read_bytes()).hexdigest() != data.get("report_sha256")
        ):
            raise ValueError("Security review report is missing or changed.")
        print("PASS: actual agent security review evidence matches the current changed surface.")

    def production_images(self, root: Path) -> list[str]:
        """Resolve images inside the selected customer's own prepared production context."""
        from functools import partial

        from tools.application.repository_boundary_service import RepositoryPolicy

        RepositoryPolicy(
            partial(self.processes.run, cwd=root), self.processes.environment
        ).assert_production_installation(root)
        code = (
            "import json,hashlib; from pathlib import Path; from tools.application.deployment_service import DeploymentService; "
            "from tools.application.repository_boundary_service import RepositoryPolicy; "
            "root=Path.cwd(); RepositoryPolicy.for_workspace(root).assert_production_installation(root); deployment=DeploymentService.for_workspace(root); "
            "release=deployment.active_release(); "
            'print(json.dumps({"images":deployment.runtime.dc(["config","--images"],release,capture_output=True,text=True).stdout.splitlines(),'
            '"locks":{name:hashlib.sha256((Path(release or root)/name).read_bytes()).hexdigest() '
            'for name in ("src/composer.lock","src/pnpm-lock.yaml")}}))'
        )
        selected = json.loads(
            self.command(
                ["python3", "-c", code], cwd=root, check=True, capture_output=True, text=True
            ).stdout
        )
        for name in ("src/composer.lock", "src/pnpm-lock.yaml"):
            if (
                selected["locks"].get(name)
                != hashlib.sha256((self.workspace.root / name).read_bytes()).hexdigest()
            ):
                raise ValueError(
                    "Selected production dependency locks differ from the scanned/audited checkout."
                )
        if not selected["images"]:
            raise ValueError("Selected production context contains no runtime images.")
        return selected["images"]

    def trivy(
        self, production_root: Path | None = None, *, additional_images: Sequence[str] = ()
    ) -> None:
        if self.location is ExecutionLocation.CONTAINER:
            raise ValueError("Run repository and runtime-image scanning on the Docker host.")
        # Owner acceptance scans the owner repository and a separate customer fixture's
        # exact runtime images. Customer execution uses its own production context.
        selected = self.production_images(production_root or self.workspace.root) + list(
            additional_images
        )
        reports = self.workspace.root / ".runtime/security"
        from tools.core.state import StateDirectory

        with StateDirectory(reports, anchor=self.workspace.root).open():
            pass
        failures = []

        def scan_command(arguments: Sequence[str], label: str) -> None:
            print("Scanning: " + label, flush=True)
            try:
                self.command(arguments)
            except (subprocess.CalledProcessError, OSError, SafeFailure):
                failures.append(label + " (scanner failure)")
                print(
                    "FAIL: " + failures[-1] + "; continuing to collect remaining reports.",
                    flush=True,
                )

        def report(name: str) -> str:
            path = reports / name
            # A failed scanner must not leave an old report looking freshly generated.
            path.unlink(missing_ok=True)
            path.touch(mode=0o600, exist_ok=False)
            return str(path) if scanner.is_file() else "/reports/" + name

        scanner = self.workspace.root / ".runtime/tools/trivy"
        if scanner.is_file():
            prefix = [str(scanner)]
        elif self.location is not ExecutionLocation.CONTAINER and shutil.which("docker"):
            prefix = [
                "docker",
                "run",
                "--rm",
                "-v",
                str(self.workspace.root) + ":/scan:ro",
                "-v",
                "runa-trivy-cache:/root/.cache/trivy",
                "-v",
                str(reports) + ":/reports",
                "aquasec/trivy:0.76.0",
            ]
        else:
            raise ValueError(
                "Run image scanning on the Docker host. Repository scanning also accepts pinned Trivy 0.76.0 at .runtime/tools/trivy."
            )
        output = self.command(prefix + ["--version"], capture_output=True, text=True).stdout
        if "Version: 0.76.0" not in output:
            raise ValueError("Trivy version must be exactly 0.76.0.")
        print("Private Trivy reports: " + str(reports), flush=True)
        scan_command(
            prefix
            + [
                "fs",
                "--include-dev-deps",
                "--format",
                "json",
                "--output",
                report("repository.json"),
                "--scanners",
                "vuln,secret,misconfig",
                "--severity",
                "HIGH,CRITICAL",
                "--exit-code",
                "1",
                "--skip-dirs",
                ".git,.runtime,.pnpm-store,node_modules,vendor,src/node_modules,src/vendor",
                "--skip-files",
                "**/.env,**/.env.*,**/storage/**",
                str(self.workspace.root) if scanner.is_file() else "/scan",
            ],
            "repository",
        )
        for image in sorted(set(selected)):
            # Feed an image archive; Docker socket never enters the scanner container.
            archive_fd, archive_name = tempfile.mkstemp(prefix="image-", suffix=".tar", dir=reports)
            os.close(archive_fd)
            archive = Path(archive_name)
            report_name = "image-" + hashlib.sha256(image.encode()).hexdigest()[:12] + ".json"
            try:
                report_output = report(report_name)
                self.command(["docker", "image", "save", "-o", str(archive), image])
                if scanner.is_file():
                    scan = prefix
                    target = str(archive)
                else:
                    scan = [
                        "docker",
                        "run",
                        "--rm",
                        "-v",
                        str(archive) + ":/image.tar:ro",
                        "-v",
                        "runa-trivy-cache:/root/.cache/trivy",
                        "-v",
                        str(reports) + ":/reports",
                        "aquasec/trivy:0.76.0",
                    ]
                    target = "/image.tar"
                scan_command(
                    scan
                    + [
                        "image",
                        "--format",
                        "json",
                        "--output",
                        report_output,
                        "--input",
                        target,
                        "--scanners",
                        "vuln,secret",
                        "--severity",
                        "HIGH,CRITICAL",
                        "--exit-code",
                        "1",
                    ],
                    image,
                )
            except (subprocess.CalledProcessError, OSError, SafeFailure):
                failures.append(image + " (image export/scan failure)")
            finally:
                archive.unlink(missing_ok=True)
        try:
            self.dependencies()
        except (subprocess.CalledProcessError, OSError, ValueError, SafeFailure):
            failures.append("dependency audit failed")
        if failures:
            raise ValueError(
                "Security checks failed: "
                + "; ".join(failures)
                + ". Inspect the private JSON reports."
            )
        print(
            "PASS: repository/image security policy and locked dependency audits; raw reports are retained."
        )

    def dependencies(self) -> None:
        root = self.workspace.root
        if self.audit_root is not None:
            if self.location is ExecutionLocation.CONTAINER:
                raise ValueError("Explicit development checkout delegation is host-only.")
            for name in ("src/composer.lock", "src/pnpm-lock.yaml"):
                if (root / name).read_bytes() != (self.audit_root / name).read_bytes():
                    raise ValueError(
                        "Dependency audit checkout lockfiles differ from the scanned checkout."
                    )
            if (
                not (self.audit_root / ".env.dev.local").is_file()
                or not (self.audit_root / ".devcontainer/compose.resources.yaml").is_file()
            ):
                raise ValueError("The audit target is not a prepared development checkout.")
            runtime = DevelopmentRuntime(
                ExecutionContext(
                    WorkspaceContext(self.audit_root),
                    WorkspaceContext(self.audit_root),
                    ExecutionLocation.HOST,
                )
            )

            def command(args: Sequence[str]) -> tuple[str, ...]:
                return runtime.app_command(tuple(args))
        else:
            command = self.app_command
        failures = []
        for label, arguments in (
            ("Composer", ["composer", "--working-dir=src", "audit"]),
            ("pnpm", ["pnpm", "--dir", "src", "audit", "--audit-level=high"]),
        ):
            try:
                self.command(command(arguments), cwd=self.audit_root or root)
            except (subprocess.CalledProcessError, OSError, SafeFailure):
                failures.append(label)
        if failures:
            raise ValueError("Locked dependency audits failed: " + ", ".join(failures))

    def secrets_scan(self) -> None:
        if self.location is not ExecutionLocation.CONTAINER:
            self.command(self.app_command(["php", "artisan", "runa:security:secrets"]))
            return
        names = (
            self.command(
                ["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z"],
                capture_output=True,
            )
            .stdout.decode()
            .split("\0")
        )
        with tempfile.TemporaryDirectory(prefix="runa-secret-scan-") as folder:
            from tools.application.secret_scanner import SecretScanner

            scanner = SecretScanner(self.workspace.root / ".runtime/tools/gitleaks", self.processes)
            scanner.scan(self.workspace.root, history=True)
            snapshot = Path(folder) / "content"
            snapshot.mkdir()
            for name in names:
                if not name:
                    continue
                source = self.workspace.root / name
                if not source.exists() and not source.is_symlink():
                    continue
                destination = snapshot / name
                destination.parent.mkdir(parents=True, exist_ok=True)
                destination.write_bytes(
                    os.readlink(source).encode() if source.is_symlink() else source.read_bytes()
                )
            scanner.scan(snapshot)
