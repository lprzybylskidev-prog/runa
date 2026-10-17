---
name: "runa-docker"
description: "Runa Docker/Compose/devcontainer/production-container guidance for builds, service topology, lifecycle, health, security, resources, diagnostics, and destructive-operation safety."
---

# Runa Docker

Use this skill for Dockerfiles, Compose, devcontainer, production containers, health checks, container diagnostics, and Docker security.

First identify context:
- development,
- production,
- shared runtime concern.

Do not rewrite infrastructure merely because a generic pattern looks cleaner. Preserve Runa topology and dev/prod parity rules.

Load only:
- `references/build-compose.md`
- `references/lifecycle-devcontainer.md`
- `references/security-operations.md`

No Docker-in-Docker. No GitHub Actions. No Kubernetes baseline.
