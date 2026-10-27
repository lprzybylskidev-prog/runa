"""Optional, bounded readiness independent from application setup and strict AI gates."""

import sys
import time
import urllib.error
import urllib.request
from collections.abc import Callable
from dataclasses import dataclass, field

from tools.application.knowledge.configuration import load
from tools.application.knowledge.context import KnowledgeContext
from tools.core.process import ProcessSpec
from tools.core.reporting import SafeFailure


@dataclass
class KnowledgeStartup:
    context: KnowledgeContext
    opener: urllib.request.OpenerDirector = field(
        default_factory=lambda: urllib.request.build_opener(urllib.request.ProxyHandler({}))
    )
    sleep: Callable[[float], None] = time.sleep

    def guard(self):
        root = self.context.workspace.root
        self.context.assert_development()
        if self.context.environment.get("APP_ENV") == "production":
            raise ValueError("Knowledge startup is development-only.")
        environment = root / ".env"
        if not environment.exists() or "APP_ENV=local" not in environment.read_text().splitlines():
            raise ValueError("Knowledge startup requires the local development environment.")

    def reachable(self, endpoint):
        try:
            with self.opener.open(endpoint, timeout=1) as response:
                return response.status == 200
        except (urllib.error.URLError, TimeoutError, ConnectionError):
            return False

    def probe(self):
        self.guard()
        root = self.context.workspace.root
        config = load(root)
        unavailable = [
            service
            for service, endpoint in (
                ("qdrant", config["qdrant_url"] + "/readyz"),
                ("embeddings", config["cpu_url"] + "/health"),
            )
            if not self.reachable(endpoint)
        ]
        local = root / ".env.dev.local"
        if (
            local.exists()
            and "EMBEDDINGS_CUDA=1" in local.read_text().splitlines()
            and not self.reachable(config["cuda_url"] + "/health")
        ):
            print(
                "DEGRADED: optional CUDA unavailable; CPU fallback will be used when ready.",
                flush=True,
            )
        if unavailable:
            print(
                "DEGRADED: optional knowledge services unavailable: " + ", ".join(unavailable),
                flush=True,
            )
            return 1
        print(
            "Optional knowledge services reachable; strict model/retrieval checks: ./bin/artisan runa:knowledge:doctor.",
            flush=True,
        )
        return 0

    def initialize(self):
        self.guard()
        root = self.context.workspace.root
        try:
            ready = (
                self.context.runner.run(
                    ProcessSpec(
                        (sys.executable, "-m", "tools.application.knowledge.startup_cli", "probe"),
                        root,
                        self.context.environment,
                        timeout=5,
                    )
                ).returncode
                == 0
            )
            if ready:
                result = self.context.runner.run(
                    ProcessSpec(
                        (
                            sys.executable,
                            str(root / "tools/application/executors/knowledge.py"),
                            "initialize",
                        ),
                        root,
                        self.context.environment,
                    )
                )
                if result.returncode == 0:
                    return 0
        except SafeFailure:
            print("DEGRADED: optional knowledge readiness timed out or unavailable.", flush=True)
        print(
            "Knowledge initialization will retry independently; application setup is unaffected.",
            flush=True,
        )
        self.sleep(10)
        return 1
