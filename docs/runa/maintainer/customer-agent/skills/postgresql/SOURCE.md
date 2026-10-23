# Source Metadata — PostgreSQL

## Primary upstream research source

- Repository: `https://github.com/wshobson/agents`
- Reviewed commit: `044b4b21f083948e69ec878a7b07c8d0951a6ffa`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

PostgreSQL table-design/query guidance was adapted around the already-fixed Runa schema/identity/migration rules. Generic rules that demanded RLS, universal FK indexing, elimination of every sequential scan, early partitioning, stored database logic, or arbitrary latency targets were rejected.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

## Customer adaptation

Reviewed and rewritten on 2026-10-23 from the existing local Runa skill and its references. This records the inherited upstream research baseline, not a fresh upstream review. Customer procedures use delivered public contracts and application ownership; owner implementation commands and protected-source writes are excluded.
