# Tailwind Theme and Layout

1. Always use syntax supported by the actually installed Tailwind version.
2. On Tailwind v4, prefer its CSS-first configuration model (`@theme`, `@utility`, `@custom-variant`, etc.) where appropriate.
3. On Tailwind v4, do not use obsolete v3 `@tailwind base/components/utilities` directives; use the current import/config mechanism.
4. Avoid `@apply` as the default component-building strategy. Use utilities, Vue components, tokens, or custom utilities first; `@apply` remains allowed when it genuinely improves clarity.
5. Arbitrary values are exceptions. Prefer an existing token/utility when one represents the design.
6. A repeated arbitrary value is evidence that a token/reusable pattern may be missing.
7. Use the shared spacing scale; do not create random pixel spacing without a real reason.
8. Application colors come from theme/tokens rather than ad-hoc component hex values for recurring concepts.
9. Light/dark behavior should derive from semantic tokens where possible rather than dozens of unrelated per-component overrides.
10. Prefer semantic color concepts such as surface, muted surface, primary text, secondary text, danger, success, warning, and border over coupling every component directly to raw palette names.
11. Do not create hundreds of custom utility classes. A custom utility should express a real repeated pattern not clearly represented by existing utilities.
12. Prefer `gap-*` for normal flex/grid spacing, especially when wrapping/responsive behavior matters.
13. Use mobile-first responsive classes: base = smallest layout, breakpoint variants expand/adapt.
14. Do not treat `sm:` as "mobile"; base styles are mobile.
15. Prefer dynamic viewport units such as `min-h-dvh` over blindly using classic viewport height where mobile browser chrome behavior matters and the installed Tailwind version supports it.
16. Use `size-*` when width and height are intentionally identical and the installed Tailwind version supports it.
17. Prefer current compact idioms only when they improve readability. Do not perform utility code-golf.
18. Do not invent custom `v-stack`/`h-stack` abstractions by default; explicit `flex flex-col gap-*` etc. is already understandable.
19. Layout primitives should be consistent across analogous screens.
20. Container width should come from a small coherent set of project patterns, not be reinvented per page.
21. Maintain a limited, deliberate z-index scale. Do not solve stacking bugs with `z-[999999]`.
22. For sticky/fixed UI, inspect stacking context, overflow, transforms, and parent structure before increasing z-index.
23. Do not use `overflow-hidden` to hide a broken layout.
24. Wide tables must preserve access to data through scrolling/responsive presentation; do not simply clip columns.
25. Use text truncation only when the complete value remains available somewhere appropriate.
26. Typography/prose utilities are for document-like content, not normal form/list/control UI.
27. CSS variables and Tailwind theme/tokens can work together, especially for semantic light/dark values.
28. Theme switching should not require mass duplication of utility sets when variables/tokens can express the semantic change.
29. Avoid inline styles for design-system values. Inline styles are appropriate for truly dynamic runtime values that Tailwind cannot statically represent well.
30. Runtime values may flow through CSS custom properties when that is clearer/safer than constructing dynamic Tailwind class names.
