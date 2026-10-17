---
name: "runa-backend"
description: "Runa backend engineering guidance for application structure, failure design, dependencies, transactions, side effects, performance, and maintainability. Load for non-trivial backend implementation/review after reading the active roadmap."
---

# Runa Backend

Use this skill for backend architecture and implementation choices that are broader than Laravel syntax.

Before acting:
1. Read the active roadmap.
2. Inspect the relevant existing code/pattern.
3. Load only the reference file(s) needed.
4. Apply Runa project rules before generic upstream advice.

## References

- `references/design-principles.md` — responsibilities, abstractions, dependencies, naming, hidden behavior
- `references/failure-side-effects.md` — failures, retries, side effects, time/randomness, transactions
- `references/performance-refactoring.md` — query behavior, performance, refactoring boundaries

For Laravel-specific framework behavior, also load `../laravel/SKILL.md`.
For database-specific design, load `../postgresql/SKILL.md`.
For security-sensitive work, load `../security/SKILL.md`.
