# Source Metadata — Backend

## Primary upstream research source

- Repository: `https://github.com/wshobson/agents`
- Reviewed commit: `044b4b21f083948e69ec878a7b07c8d0951a6ffa`
- Adaptation date: `2026-10-17`
- Update model: manual review; never auto-overwrite local skill from upstream `main`.

## Runa adaptation

Useful general backend/software-engineering rules were selected and rewritten for Runa. Generic architecture that conflicted with Laravel/Eloquent or the established Runa Actions/Services/Queries model was rejected. The local references also contain Runa-only rules established in project discussion.

## Local-only material

The local skill also contains Runa-specific rules that do not come from upstream, including project architecture, workflow, dependency, quality, language, and accepted exception decisions. These local sections must not be deleted during upstream maintenance merely because no upstream equivalent exists.

## Local authority

This skill is Runa-specific. Project rules, active roadmap decisions, and current code override upstream guidance.

No upstream helper script is trusted/executed merely because it belongs to the source repository. Future updates must be reviewed as diffs, including referenced scripts/assets.

See `docs/runa/handbook/ai/skills-policy.md`.

## Customer adaptation

Reviewed and rewritten on 2026-10-23 from the existing local Runa skill and its references. This records the inherited upstream research baseline, not a fresh upstream review. Customer procedures use delivered public contracts and application ownership; owner implementation commands and protected-source writes are excluded.

Public/internal API boundary routing was added from the owner-approved
RM-2026-012 source review on 2026-10-27. Its authoritative rule is
`docs/runa/handbook/architecture/public-api.md`; no upstream refresh or new
dependency was involved.

RM-2026-012 point 13 synchronized internal namespace guidance on 2026-10-28
against the delivered public API boundary. This is local Runa guidance; no
upstream source or dependency changed.

API enforcement routing was synchronized from local RM-2026-012 point 18 on
2026-10-28. The public boundary guide owns diagnostics and delivery detail;
no upstream refresh, dependency addition or new runtime authority was involved.

Source-placement and audience routing was reconciled on 2026-10-28 for local
RM-2026-012 point 19. The delivered public boundary/projection guides own the
procedures; no upstream refresh, dependency or protected-edit authority was added.
