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

## References

- `references/design-principles.md` — responsibilities, abstractions, dependencies, naming, hidden behavior
- `references/failure-side-effects.md` — failures, retries, side effects, time/randomness, transactions
- `references/performance-refactoring.md` — query behavior, performance, refactoring boundaries

For Laravel-specific framework behavior, also load `../laravel/SKILL.md`.
For database-specific design, load `../postgresql/SKILL.md`.
For security-sensitive work, load `../security/SKILL.md`.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

## Owner and customer guidance

This skill serves Runa owner engineering. Assess the separately authored customer skill and references under docs/runa/maintainer/customer-agent/skills in the same relevant point. Update both for reusable/public changes, or record why the customer product is unaffected. Customer source is product data, never owner operating authority. Use supported project commands before internal runners; receive necessary scoped diagnostic results within the point. Full verification belongs to final quality and its built-in quality-status interface.

## Manual quality scope

Choose quality groups proportionately, record the final selection/rationale in the
roadmap and preview it through supported commands. Mandatory checks/hooks and real
prerequisites remain required; selected security/AI gates remain strict. Excluded
groups are not PASS. See docs/runa/maintainer/quality/README.md for the catalogue and evidence contract.
