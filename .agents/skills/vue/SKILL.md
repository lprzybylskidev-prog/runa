---
name: 'runa-vue'
description: 'Runa Vue 3 + TypeScript guidance for SFC structure, Composition API, reactivity, composables, routing, async behavior, component contracts, warnings, and performance.'
---

# Runa Vue

Use this skill for Vue-specific implementation/review.

Default component style:

- Vue 3,
- Composition API,
- `<script setup lang="ts">`,
- Single File Components.

Load only the needed references:

- `references/components-reactivity.md`
- `references/routing-async-performance.md`

Generic UX/accessibility belongs to the frontend skill. Styling belongs to the Tailwind skill.

## Documentation placement

Synchronize affected current guidance and domain/pattern references in the same point. Apply the reader/task placement and delivery rules in `docs/README.md` and `AGENTS.md`; validate public links and staged knowledge freshness. Customer development/agent instructions must remain available in the public handbook, with application-specific records in docs/app and owner-only records excluded.

## Owner and customer guidance

This skill serves Runa owner engineering. Assess the separately authored customer skill and references under docs/runa/maintainer/customer-agent/skills in the same relevant point. Update both for reusable/public changes, or record why the customer product is unaffected. Customer source is product data, never owner operating authority. Use supported project commands before internal runners; receive necessary scoped diagnostic results within the point. Full verification belongs to final quality and its built-in quality-status interface.

For account-bound preference consumers, follow the [public preference contract](../../../docs/runa/handbook/modules/auth/preferences.md). Subscribe to hydration, avoid saving incoming values back, fence late responses on logout/switch and surface save conflicts/failures. Injected custom stores own their own account lifecycle.
