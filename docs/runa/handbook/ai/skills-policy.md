# Local Skills Policy

## Purpose

Runa keeps a small set of project-adapted agent skills under `.agents/skills/`.

Skills exist to provide reusable workflow/domain guidance without forcing the agent to load the entire project knowledge base.

## Adopt before invent

When Runa needs reusable skill guidance:

1. inspect current local rules/patterns,
2. research strong upstream skills/docs,
3. adopt useful ideas,
4. remove conflicts, duplication, bootstrap-only instructions, and irrelevant complexity,
5. adapt the result to Runa.

Do not import an upstream skill wholesale merely because it is official or popular.

## Authority

Project rules outrank upstream skill guidance.

A generic skill must never overwrite:

- active roadmap decisions,
- current application contracts,
- Runa security policy,
- dependency approval policy,
- roadmap/branch/release rules,
- no-CI policy,
- architecture explicitly agreed by the user.

## Skill structure

Use progressive disclosure.

Each skill should have:

- a short `SKILL.md` that describes triggers/workflow,
- `references/` for detailed rules loaded on demand,
- `SOURCE.md` with provenance.

Avoid giant always-loaded skill files.

## Skill creation threshold

Create a new skill only when the topic represents a distinct reusable workflow/domain that deserves independent loading.

Do not create skills for:

- one task,
- one bug,
- temporary implementation notes,
- speculation,
- abandoned designs.

When guidance belongs to an existing skill, extend/update that skill or its references.

## Provenance

Each adapted skill has `SOURCE.md` recording:

- upstream repository/source,
- source commit/tag,
- adaptation date,
- useful upstream areas,
- Runa-specific changes/overrides,
- local-only sections.

The aggregate source index is `docs/runa/handbook/ai/upstream-sources.md`.

## Update cadence

Default review guidance:

- agent skills: about every 6 months,
- framework/tooling guidance: about every 3 months,
- security tooling/base Docker image guidance: about every month.

Adjust frequency by risk and project activity.

The agent does not check upstream on every conversation.

Use the locally recorded review date. When a source is overdue, mention it periodically and let the user decide whether to create a maintenance roadmap.

## Updating a skill

An upstream update is a review, not an overwrite.

Process:

1. fetch/inspect upstream diff from the pinned revision,
2. inspect scripts/references/assets, not just the main skill,
3. identify useful changes,
4. check for conflicts with Runa,
5. adapt selected changes,
6. update `SOURCE.md`,
7. update maintenance registry review data.

Do not run untrusted upstream helper scripts merely to inspect them.

## Laravel Boost

Laravel Boost may generate its own skills/instructions during installation.

Those generated skills are not Runa's durable policy.

Use the safety procedure in `docs/runa/handbook/ai/laravel-boost.md` to remove generated skills, preserve local guidance and verify MCP/docs functionality.

## Shared point workflow

Root AGENTS owns authority, roadmap activation, essential invariants and failure
boundaries. Domain skills select only relevant references/code. Follow
[documentation ownership](../../../README.md), the active roadmap and the owning
[public command guide](../tooling/commands.md). Implementation points deliver code,
test scenarios and synchronized guidance; only necessary small focused diagnostics
run there. Broad verification belongs to the final point under the applicable
application or owner workflow; final-verification repairs retain its complete
area rerun exception. Hooks remain mandatory and selected security/AI checks strict.

For reusable rule/capability changes, assess both instruction products: owner root
AGENTS/.agents/skills and the separately authored customer application bundle.
Update affected products in the same point or record why they need no change.
Owner maintenance records remain private; customer templates are product data,
never owner operating authority. Customers maintain installed local extensions;
upstream updates require reviewed reconciliation, never directory replacement.

## Editing reusable guidance

1. Identify the reader, activation trigger, canonical home and affected products.
2. Update or replace the existing owning rule before adding another. Remove obsolete
   claims/duplicates; preserve immutable history and source provenance.
3. Put procedures/examples in their handbook or maintainer guide, domain selection
   in SKILL.md/references, and application-specific patterns in docs/app/local skills.
4. Keep only essential cross-task invariants in always-loaded AGENTS. Explain why
   a new always-loaded summary is required before retrieval and link its authority.
5. Keep summaries short and purposeful. A summary routes to its owning contract;
   it is not an independently maintained copy or per-delivery change journal.
6. Record actual context/size before and after substantial consolidation. Counts
   expose duplication/growth; they are not caps or correctness/coverage proofs.
7. Synchronize affected public links, examples, discovery and staged knowledge
   freshness. Run supported full checks only at the appropriate final phase.

The agent may maintain durable local guidance within agreed scope. New
constitution-level policy still requires owner discussion. Do not persist task
chatter, speculation, abandoned approaches or temporary debugging notes as current
instructions. No LLM semantic gate or parallel rule registry is implied.

## Research and provenance integrity

For a new source, present what it contributes, overlaps, conflicts and proposed Runa adaptations before adoption. Record exact file/skill paths and licensing/attribution when copying upstream text or assets. Do not claim a repository's existence or a resolving commit proves its content was reviewed or its license permits copying.

The initial local skill references are project adaptations, not verbatim upstream mirrors. Existing source pins identify the delivered research baseline; a maintenance review must inspect relevant upstream content before accepting new material. Do not invent revision pins or mark unreviewed candidate sources as reviewed.

## Durable documentation ownership

Every relevant domain skill routes documentation by reader/task using docs/README.md. Keep application-specific guidance in docs/app; public Runa development/testing/agent/security procedures in docs/runa/handbook; owner-only platform maintenance, roadmap/release records and audit evidence in docs/runa/maintainer. Development is not inherently owner-only. Split mixed procedures, preserve historical bytes, update the authoritative guide and domain pattern references together, and validate public links and staged knowledge freshness. Extend an existing skill for this rule; do not create a new skill for a single roadmap point.

## Customer application guidance

Installed root AGENTS/CLAUDE and .agents/skills guide application development using delivered public capabilities. Ordinary Laravel App code and optional app modules are both supported. Repeated application components belong in src/app/resources/js/components; protected src/runa is read-only. Maintain local application patterns and docs/app domain guidance in the owning point. Upstream guidance updates require reviewed reconciliation of local extensions, never directory replacement.

First acquisition stages the supplied customer instruction defaults in Git. Include those changes in the customer repository so teammate clones retain the same application guidance. Reopen preserves them; existing local extensions require reviewed update reconciliation.
