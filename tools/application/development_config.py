"""Development resource profiles and private local environment preparation."""

from __future__ import annotations

import json
import os
import re
import secrets
import subprocess
from collections.abc import Mapping
from dataclasses import dataclass
from pathlib import Path

from tools.application.processes import ApplicationProcesses
from tools.core.context import ExecutionLocation, WorkspaceContext
from tools.core.reporting import SafeFailure
from tools.core.state import JsonValue

PRESETS = {
    "low": ("2", "2G", "2", "1"),
    "standard": ("4", "4G", "4", "2"),
    "high": ("20", "16G", "12", "4"),
}
KEYS = ("DEV_CPU_LIMIT", "DEV_MEMORY_LIMIT", "TEST_WORKERS", "PLAYWRIGHT_WORKERS")
ALLOWED = set(KEYS) | {
    "DEV_PROFILE",
    "RUNA_UID",
    "RUNA_GID",
    "EMBEDDINGS_DEVICE",
    "EMBEDDINGS_CUDA",
}


@dataclass(frozen=True)
class DevelopmentConfig:
    workspace: WorkspaceContext
    processes: ApplicationProcesses
    location: ExecutionLocation

    @property
    def local(self) -> Path:
        return self.workspace.root / ".env.dev.local"

    @property
    def override(self) -> Path:
        return self.workspace.root / ".devcontainer/compose.resources.yaml"

    def load(self) -> dict[str, str]:
        values: dict[str, str] = dict(zip(KEYS, PRESETS["standard"], strict=True))
        values.update(
            DEV_PROFILE="standard",
            RUNA_UID=str(os.getuid()),
            RUNA_GID=str(os.getgid()),
            EMBEDDINGS_DEVICE="auto",
            EMBEDDINGS_CUDA="0",
        )
        if self.local.exists():
            for line in self.local.read_text().splitlines():
                if not line.strip() or line.lstrip().startswith("#"):
                    continue
                key, sep, value = line.partition("=")
                if not sep or key not in ALLOWED:
                    raise ValueError(f"Unsupported local profile setting: {key}")
                values[key] = value
        self.validate(values)
        return values

    def validate(self, values: Mapping[str, str]) -> None:
        if values["EMBEDDINGS_DEVICE"] not in ("auto", "cpu", "cuda") or values[
            "EMBEDDINGS_CUDA"
        ] not in ("0", "1"):
            raise ValueError("Invalid embeddings device settings.")
        if values["DEV_PROFILE"] not in (*PRESETS, "custom"):
            raise ValueError("DEV_PROFILE must be low, standard, high or custom.")
        if (
            not re.fullmatch(r"[0-9]+(?:\.[0-9]+)?", values["DEV_CPU_LIMIT"])
            or float(values["DEV_CPU_LIMIT"]) <= 0
        ):
            raise ValueError("DEV_CPU_LIMIT must be a positive number.")
        if not re.fullmatch(r"[1-9][0-9]*(?:M|G)", values["DEV_MEMORY_LIMIT"]):
            raise ValueError("DEV_MEMORY_LIMIT must be a positive integer followed by M or G.")
        for key in ("TEST_WORKERS", "PLAYWRIGHT_WORKERS", "RUNA_UID", "RUNA_GID"):
            if not values[key].isdigit() or int(values[key]) < 1:
                raise ValueError(f"{key} must be a positive integer.")

    def persist(self, values: Mapping[str, str]) -> None:
        self.validate(values)
        self.local.write_text(
            "".join(
                f"{key}={values[key]}\n"
                for key in (
                    "DEV_PROFILE",
                    *KEYS,
                    "RUNA_UID",
                    "RUNA_GID",
                    "EMBEDDINGS_DEVICE",
                    "EMBEDDINGS_CUDA",
                )
            )
        )
        self.local.chmod(0o600)
        # VS Code invokes Compose itself, so it needs the same resolved settings.
        service = {
            "cpus": float(values["DEV_CPU_LIMIT"]),
            "mem_limit": values["DEV_MEMORY_LIMIT"],
            "build": {"args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}},
            "environment": {key: values[key] for key in ("TEST_WORKERS", "PLAYWRIGHT_WORKERS")},
        }
        tier = values["DEV_PROFILE"] if values["DEV_PROFILE"] in PRESETS else "standard"
        database_memory = {"low": "256M", "standard": "512M", "high": "1G"}[tier]
        worker_memory = "256M" if tier == "low" else "512M"
        siblings: dict[str, dict[str, JsonValue]] = {
            "postgres": {
                "cpus": {"low": 0.5, "standard": 1, "high": 2}[tier],
                "mem_limit": database_memory,
            },
            "queue": {"cpus": 1, "mem_limit": worker_memory},
            "scheduler": {"cpus": 0.25, "mem_limit": "128M"},
            "nginx": {"cpus": 0.25, "mem_limit": "64M"},
            "mailpit": {
                "cpus": 0.25,
                "mem_limit": "128M",
                "build": {"args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}},
            },
            "setup": {
                "cpus": float(values["DEV_CPU_LIMIT"]),
                "mem_limit": values["DEV_MEMORY_LIMIT"],
            },
        }
        siblings["knowledge-init"] = {
            "build": {"args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}}
        }
        for role in ("setup", "queue", "scheduler"):
            siblings[role]["build"] = {
                "args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}
            }
        siblings["qdrant"] = {
            "cpus": {"low": 0.5, "standard": 1, "high": 2}[tier],
            "mem_limit": {"low": "256M", "standard": "512M", "high": "1G"}[tier],
            "build": {"args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}},
        }
        siblings["embeddings"] = {
            "cpus": {"low": 1, "standard": 2, "high": 4}[tier],
            "mem_limit": "2G",
            "build": {"args": {key: values[key] for key in ("RUNA_UID", "RUNA_GID")}},
        }
        if values["EMBEDDINGS_CUDA"] == "1":
            config = json.loads(
                (self.workspace.root / "tools/application/knowledge/config.json").read_text()
            )
            siblings["embeddings-gpu"] = {
                "image": "runa-embeddings-cuda:dev",
                "build": {
                    "context": ".",
                    "dockerfile": "docker/embeddings/cuda.Dockerfile",
                    "args": {"RUNA_UID": values["RUNA_UID"], "RUNA_GID": values["RUNA_GID"]},
                },
                "command": [
                    "--model-id",
                    config["model_id"],
                    "--revision",
                    config["model_revision"],
                    "--dtype",
                    "float32",
                    "--auto-truncate",
                    "false",
                    "--pooling",
                    "cls",
                    "--port",
                    "8080",
                    "--max-client-batch-size",
                    "16",
                    "--max-batch-tokens",
                    "2048",
                    "--tokenization-workers",
                    "2",
                    "--json-output",
                ],
                "volumes": ["embedding-cache:/data"],
                "deploy": {
                    "resources": {
                        "reservations": {
                            "devices": [{"driver": "nvidia", "count": 1, "capabilities": ["gpu"]}]
                        }
                    }
                },
                "cap_drop": ["ALL"],
                "security_opt": ["no-new-privileges:true"],
                "logging": {"driver": "json-file", "options": {"max-size": "10m", "max-file": "3"}},
                "cpus": 2,
                "mem_limit": "4G",
                "restart": "unless-stopped",
            }
        self.override.write_text(
            json.dumps({"services": {"app": service, **siblings}}, indent=2) + "\n"
        )

    def resolve_embeddings(self, values: dict[str, str]) -> None:
        if self.location is ExecutionLocation.CONTAINER:
            return
        enabled = False
        if values["EMBEDDINGS_DEVICE"] != "cpu":
            try:
                gpu = self.processes.run(
                    ["nvidia-smi", "--query-gpu=compute_cap", "--format=csv,noheader"],
                    cwd=self.workspace.root,
                    check=True,
                    capture_output=True,
                    text=True,
                )
                runtimes = self.processes.run(
                    ["docker", "info", "--format", "{{json .Runtimes}}"],
                    cwd=self.workspace.root,
                    check=True,
                    capture_output=True,
                    text=True,
                )
                enabled = "nvidia" in json.loads(runtimes.stdout) and any(
                    float(line) >= 7.5 for line in gpu.stdout.splitlines()
                )
            except (OSError, ValueError, subprocess.CalledProcessError, SafeFailure):
                enabled = False
        if values["EMBEDDINGS_DEVICE"] == "cuda" and not enabled:
            raise ValueError(
                "CUDA requested but no supported NVIDIA GPU/Docker runtime was found. Use embeddings cpu or fix the host runtime."
            )
        values["EMBEDDINGS_CUDA"] = "1" if enabled else "0"
        print(
            "Embeddings: CUDA preferred with CPU fallback."
            if enabled
            else "Embeddings: CPU (CUDA disabled or unavailable)."
        )

    def private_environment(self, path: Path) -> dict[str, str]:
        """Read local generated secrets without exposing values in diagnostics."""
        import stat

        if path.is_symlink() or any(parent.is_symlink() for parent in path.parents):
            raise ValueError("Development environment must not traverse symlinks: " + path.name)
        descriptor = os.open(path, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK)
        with os.fdopen(descriptor, "r") as stream:
            info = os.fstat(stream.fileno())
            if (
                not stat.S_ISREG(info.st_mode)
                or info.st_uid != os.getuid()
                or info.st_mode & 0o077
                or info.st_size > 32768
            ):
                raise ValueError(
                    "Development secret files must be bounded private owned regular files: "
                    + path.name
                )
            text = stream.read(32769)
        if len(text) > 32768:
            raise ValueError("Development environment file exceeds supported size.")
        return dict(
            line.split("=", 1)
            for line in text.splitlines()
            if "=" in line and not line.startswith("#")
        )

    def environment_files(self) -> None:
        # A dangling link reports exists=False; reject it before any secret creation.
        for name in (".env", ".env.dev.database", ".env.testing"):
            path = self.workspace.root / name
            if path.is_symlink() or any(parent.is_symlink() for parent in path.parents):
                raise ValueError("Development environment must not traverse symlinks: " + name)
        template = self.workspace.root / ".env.dev.example"
        if not template.exists():
            return
        env = self.workspace.root / ".env"
        if not env.exists():
            env.write_text(
                template.read_text().replace(
                    "DB_PASSWORD=\n", "DB_PASSWORD=" + secrets.token_urlsafe(32) + "\n"
                )
            )
            env.chmod(0o600)
        application = self.private_environment(env)
        infrastructure = self.workspace.root / ".env.dev.database"
        if not infrastructure.exists():
            infrastructure.write_text(
                "POSTGRES_PASSWORD="
                + secrets.token_urlsafe(32)
                + "\nAPP_DB_PASSWORD="
                + application["DB_PASSWORD"]
                + "\nTEST_DB_PASSWORD="
                + secrets.token_urlsafe(32)
                + "\n"
            )
            infrastructure.chmod(0o600)
        settings = self.private_environment(infrastructure)
        if not all(
            settings.get(key)
            for key in ("POSTGRES_PASSWORD", "APP_DB_PASSWORD", "TEST_DB_PASSWORD")
        ):
            raise ValueError("Development database credentials must be non-empty.")
        if settings["APP_DB_PASSWORD"] != application["DB_PASSWORD"]:
            raise ValueError(
                "Application and database initialization credentials differ. Reconcile local files and existing database roles explicitly."
            )
        testing = self.workspace.root / ".env.testing"
        if not testing.exists():
            testing.write_text(
                (self.workspace.root / ".env.test.example")
                .read_text()
                .replace("DB_PASSWORD=\n", "DB_PASSWORD=" + settings["TEST_DB_PASSWORD"] + "\n")
                .replace("APP_KEY=\n", "APP_KEY=" + application.get("APP_KEY", "") + "\n")
            )
            testing.chmod(0o600)
        test_settings = self.private_environment(testing)
        if (
            not test_settings.get("DB_PASSWORD")
            or test_settings["DB_PASSWORD"] != settings["TEST_DB_PASSWORD"]
        ):
            raise ValueError(
                "Testing and database initialization credentials differ. Preserve files and reconcile the existing testing role explicitly; passwords are never silently rotated."
            )
