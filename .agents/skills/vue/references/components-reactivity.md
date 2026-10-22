# Vue Components and Reactivity

1. Composition API with `<script setup lang="ts">` is the default. Do not mix Options API without a concrete reason.
2. Single File Components are the default component format.
3. Component filenames/names use PascalCase.
4. Props are read-only. Create a local draft/copy only when local-edit semantics are intentional.
5. Type `defineEmits`.
6. Type `defineProps`; avoid generic `Object`, `Array`, or `any` when the shape is known.
7. Use `defineModel` only for a genuine bidirectional model contract. Otherwise use props plus events.
8. Computed values are pure and side-effect free.
9. Use `watch` for side effects/reactive synchronization; use computed for derivation.
10. Avoid `deep: true` unless the real dependency requires it.
11. Use `watchEffect` only when automatic dependency tracking is the intended behavior; prefer `watch` for explicit dependencies.
12. Clean up async watcher work, timers, subscriptions, and obsolete effects.
13. Do not copy a prop into a ref unless local draft semantics are deliberate.
14. Choose `ref` vs `reactive` consciously: `ref` suits independent values; `reactive` may suit a coherent object.
15. Use `shallowRef`/`shallowReactive` for heavy objects whose internals Vue does not need to track.
16. Do not destructure reactive state in a way that loses reactivity; use `toRefs` or direct reactive access when needed.
17. Create composables for reusable stateful concerns, not every small block of code.
18. A composable has an explicit input/output/side-effect contract rather than reading random globals.
19. Reusable composables may accept `MaybeRef`/`MaybeRefOrGetter` where it materially improves reuse.
20. A composable is not a hidden global store. Global/shared state must be designed explicitly.
21. Do not add Pinia until a real shared/global-state need exists.
22. If Pinia is introduced later, preserve reactivity correctly, including `storeToRefs` for reactive store properties when appropriate.
23. Avoid imperative DOM manipulation when Vue templates/reactivity can express the behavior.
24. Use the current installed Vue version's template-ref idiom, such as `useTemplateRef` when supported.
25. Component refs are only for intentionally exposed component/DOM APIs.
26. Use `defineExpose` sparingly for a small explicit public component API.
27. Use slots for structural flexibility and props for data/configuration.
28. Scoped-slot contracts stay small/explicit; do not expose a component's whole internals.
29. Use `provide`/`inject` only for genuinely cross-cutting subtree concerns, not as invisible prop replacement.
30. Use typed injection keys/symbols.
31. Do not micro-split components without a responsibility/reuse/readability gain.
32. Split genuinely large SFCs when they contain multiple independent responsibilities.
33. Keep component prop APIs minimal. A large pile of boolean props is a signal the component is over-generalized.
34. Prefer positive boolean prop names and avoid double negatives.
35. Do not relay the same event through many component levels blindly; reassess state/component structure.
36. Name state/composables in a way that remains understandable in Vue Devtools.

## Module component skeletons

`runa:make:module owner.code component ComponentName` previews an unregistered `resources/js/ComponentName.vue` with TypeScript script setup and a static slot wrapper. Use the existing component catalogue when implementing it; add owned frontend/slot declarations separately and verify rendered states. Generation supplies no page registration, styling or visual acceptance. See docs/runa/handbook/development/modules.md.
