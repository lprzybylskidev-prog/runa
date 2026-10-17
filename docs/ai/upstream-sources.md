# Upstream Skill Research Sources

This index records the primary upstream repositories reviewed while adapting Runa's local skills.

The local Runa skills are not mirrors. They contain project-specific selection, deduplication, and overrides.

| Local skill | Primary upstream research source | Pinned reviewed commit | Reviewed/adapted |
|---|---|---|---|
| Backend | `https://github.com/wshobson/agents` | `044b4b21f083948e69ec878a7b07c8d0951a6ffa` | 2026-10-17 |
| Laravel | `https://github.com/laravel/boost` | `f1411f90d54cb01ffe30606b7ca92789aad737d9` | 2026-10-17 |
| Frontend | `https://github.com/sablier-labs/agent-skills` and project research set | `41e29982b5551b17c3fc6dc81720310bb05b2482` | 2026-10-17 |
| Vue | `https://github.com/vuejs-ai/skills` | `c9d355ff23f654309dd02006be671859df0a134c` | 2026-10-17 |
| Tailwind | `https://github.com/sablier-labs/agent-skills` and project research set | `41e29982b5551b17c3fc6dc81720310bb05b2482` | 2026-10-17 |
| PostgreSQL | `https://github.com/wshobson/agents` | `044b4b21f083948e69ec878a7b07c8d0951a6ffa` | 2026-10-17 |
| Security | `https://github.com/openai/skills` | `49f948faa9258a0c61caceaf225e179651397431` | 2026-10-17 |
| Docker | `https://github.com/docker/skills` | `25f7e03184ce455ea1604bea00f7b40f95ca50e5` | 2026-10-17 |
| RAG/Qdrant | `https://github.com/qdrant/skills` | `d2127f6e1b9c6611fff10fe939c6fb17761aaec2` | 2026-10-17 |

Additional community repositories and official documentation were consulted during research. Local `SOURCE.md` files describe the adaptation scope.

## Maintenance

These pins record what was reviewed, not what should be auto-installed.

Upstream updates are reviewed manually according to `docs/ai/skills-policy.md`.

Never replace local skills by pulling current upstream `main`.

## Research context

The accepted research also discussed `magnus919/agent-skills` (backend/Compose), `laravel/agent-skills` (Laravel simplification/conventions), `supabase/agent-skills` (PostgreSQL), `ofershap/tailwind-best-practices`, `PaulRBerg/agent-skills` (Tailwind), and community Vue/RAG sources. These are research leads rather than additional installed skills or verified pinned adaptations. Preserve that distinction; inspect and record exact paths/revisions/license before copying any new material.

The seven supplied commit identifiers were confirmed to resolve in their named repositories during the bootstrap audit on 2026-10-17. This checks provenance identifiers, not the completeness of the original upstream review. No source was upgraded or dependency approved by this check.
