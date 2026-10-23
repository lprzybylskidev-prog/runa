---
name: 'runa-laravel'
description: 'Runa-specific Laravel guidance for framework conventions, Eloquent, validation, routes, queues, scheduler, console commands, Boost/version-aware docs, and framework primitives.'
---

# Runa Laravel

Load this skill for Laravel-specific implementation or review.

Before implementing:

1. Read the active roadmap and relevant Runa architecture docs.
2. Inspect analogous project code.
3. Use Laravel Boost version-aware Search Docs/project tools before guessing framework APIs or current-version conventions.
4. Load only the reference sections relevant to the task.

Runa architecture overrides generic Laravel starter-kit conventions.

## References

- `references/framework-conventions.md`
- `references/eloquent-validation.md`
- `references/async-console-testing.md`

For application-layer design beyond framework mechanics, also load the backend skill.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

## Owner and customer guidance

This skill serves Runa owner engineering. Assess the separately authored customer skill and references under docs/runa/maintainer/customer-agent/skills in the same relevant point. Update both for reusable/public changes, or record why the customer product is unaffected. Customer source is product data, never owner operating authority. Use supported project commands before internal runners; receive necessary scoped diagnostic results within the point. Full verification belongs to final quality and its built-in quality-status interface.
