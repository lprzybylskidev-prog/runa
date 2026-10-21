# Source Metadata — Docker

## Primary upstream research source

- Repository: `https://github.com/docker/skills`
- Reviewed commit: `25f7e03184ce455ea1604bea00f7b40f95ca50e5`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Official Docker agent-skill guidance was the primary source for build strategy, cache/context hygiene, and container hardening. Community Compose guidance also informed readiness and operational safety. Runa rejected mandatory Alpine/distroless, Bake, multi-arch, signing pipelines, Docker Bench, custom seccomp/AppArmor, and orchestration beyond Compose.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.
