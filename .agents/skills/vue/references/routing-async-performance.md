# Vue Routing, Async Behavior, Templates, and Performance

1. Treat route params/query as explicit inputs rather than reading deep router global state everywhere.
2. Route components must react correctly when params change without assuming Vue Router remounts the component.
3. Navigation guards are for navigation concerns, not unrelated business logic.
4. Lazy-load route components.
5. `v-for` uses stable semantic keys. Do not use array index when the item has its own identity.
6. Never use array index as the key for editable or reorderable lists.
7. Do not place `v-if` and `v-for` on the same element; filter/computed or wrap intentionally.
8. Choose `v-if` vs `v-show` by semantics/frequency rather than habit.
9. `v-html` with untrusted content is forbidden unless deliberate sanitization is part of the feature.
10. Use async components only for truly heavy/conditional UI.
11. Use `Suspense` only when it genuinely simplifies the async user experience.
12. Use `KeepAlive` only with intentional cached-view semantics; account for stale forms/data and lifecycle behavior.
13. Use `Teleport` for modals/popovers when it solves a real DOM/layout constraint; do not teleport everything.
14. Clean up listeners, observers, timers, and subscriptions created during component lifecycle.
15. Do not use `onMounted` as a universal initialization hook; reserve it for mount/DOM/lifecycle-dependent work.
16. Handle async races so stale responses do not overwrite newer route/filter/user state.
17. Unexpected Vue warnings in development/tests are quality failures; final frontend gate should be warning-free.
18. Optimize after identifying a real problem. `v-memo`, `markRaw`, shallow tracking, and manual memoization are not defaults.
19. Use `markRaw` only for intentionally non-reactive objects.
20. Stabilize props/memoization only when excessive rerendering is an actual issue.
21. Use local error boundaries/`onErrorCaptured` only where the UI can genuinely recover; do not swallow unexpected failures globally.
22. Keep the Vue skill Vue-specific; do not duplicate generic HTML/CSS/HTTP/accessibility rules.
23. Prefer current installed Vue-version docs/patterns over historical community advice.
24. Load progressive references on demand rather than treating every Vue task as an invitation to load the whole framework handbook.

Use the shared HTTP document-cancellation contract for requests on views being
left: RequestCancelledError from @/runa/http cancels observation and must not become
false error feedback or an automatic write retry. Preserve real network/API errors,
synchronous dirty-form guards and explicit state reload after interrupted writes.
Do not add page-local raw fetch/unload handlers to bypass the shared lifecycle.
