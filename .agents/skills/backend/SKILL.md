---
name: 'runa-backend'
description: 'Runa backend engineering guidance for application structure, failure design, dependencies, transactions, side effects, performance, and maintainability. Load for non-trivial backend implementation/review after reading the active roadmap.'
---

# Runa Backend

Use this skill for backend architecture and implementation choices that are broader than Laravel syntax.

Before acting:

1. Read the active roadmap.
2. Inspect the relevant existing code/pattern.
3. Load only the reference file(s) needed.
4. Apply Runa project rules before generic upstream advice.

For public API, internal dependency, generator or source-layout changes, follow
[the public API boundary](../../../docs/runa/handbook/architecture/public-api.md).
Classify supported consumers and complete signatures before moving files; update
public consumers and owner/customer guidance together. Frontend replacement uses
supported facades/controllers without copied security logic.

## References

- `references/design-principles.md` — responsibilities, abstractions, dependencies, naming, hidden behavior
- `references/failure-side-effects.md` — failures, retries, side effects, time/randomness, transactions
- `references/performance-refactoring.md` — query behavior, performance, refactoring boundaries

For Laravel-specific framework behavior, also load `../laravel/SKILL.md`.
For database-specific design, load `../postgresql/SKILL.md`.
For security-sensitive work, load `../security/SKILL.md`.

Reusable test support and application tooling discovery are documented in `docs/runa/handbook/testing/strategy.md`; compose the delivered helpers rather than importing private platform cases or copying lifecycle logic.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.
