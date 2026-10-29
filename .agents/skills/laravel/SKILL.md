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

For public API, internal dependency, generator or source-layout changes, follow
[the public API boundary](../../../docs/runa/handbook/architecture/public-api.md).
Classify supported consumers and complete signatures before moving files; update
public consumers and owner/customer guidance together. Frontend replacement uses
supported facades/controllers without copied security logic.

## References

- `references/framework-conventions.md`
- `references/eloquent-validation.md`
- `references/async-console-testing.md`

For application-layer design beyond framework mechanics, also load the backend skill.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.
