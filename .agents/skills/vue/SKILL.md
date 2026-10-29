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

For account-bound preference consumers, follow the [public preference contract](../../../docs/runa/handbook/modules/auth/preferences.md). Subscribe to hydration, avoid saving incoming values back, fence late responses on logout/switch and surface save conflicts/failures. Injected custom stores own their own account lifecycle.

For any new or changed platform/module view, apply the [frontend adoption
contract](../../../docs/runa/handbook/development/frontend/customization.md).
Expose typed public props/slots/controllers for customer presentation, retain
usable defaults and keep controller cleanup/security orchestration in Runa.
Customer components use public facades; do not require copying private composables.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.
