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

The aggregate source index is `docs/ai/upstream-sources.md`.

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

Use the safety procedure in `docs/ai/laravel-boost.md` to remove generated skills, preserve local guidance and verify MCP/docs functionality.


## Self-maintenance

The agent may autonomously update local docs/skills when implementation establishes a durable reusable project rule.

Prefer editing/replacing obsolete guidance over appending duplicates.

Do not silently change constitution-level project policy; those changes require user discussion.

## Research and provenance integrity

For a new source, present what it contributes, overlaps, conflicts and proposed Runa adaptations before adoption. Record exact file/skill paths and licensing/attribution when copying upstream text or assets. Do not claim a repository's existence or a resolving commit proves its content was reviewed or its license permits copying.

The initial local skill references are project adaptations, not verbatim upstream mirrors. Existing source pins identify the delivered research baseline; a maintenance review must inspect relevant upstream content before accepting new material. Do not invent revision pins or mark unreviewed candidate sources as reviewed.
