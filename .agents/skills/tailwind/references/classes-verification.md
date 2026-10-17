# Tailwind Classes, Variants, and Verification

1. Tailwind class names must remain statically discoverable by the build.
2. Do not construct utility names from arbitrary string interpolation such as `` `bg-${color}-500` ``.
3. Map dynamic variants to explicit complete class strings.
4. Keep class order stable using the chosen Tailwind-compatible formatting/sorting tooling; do not reorder by personal preference.
5. Conditional classes should be assembled in one readable place, not deeply nested ternaries inside templates.
6. Do not add `clsx`, `tailwind-merge`, `tailwind-variants`, or similar helpers merely because they are popular. They are dependencies and require a real need plus approval.
7. If a class-merge helper is introduced later, standardize on one project approach rather than mixing multiple helpers.
8. Reusable components with variants should expose a finite, meaningful variant set rather than arbitrary styling strings as the primary API.
9. Do not introduce a variant framework when a small explicit class map is clearer.
10. Do not duplicate a long identical class pattern across many places when it represents the same semantic UI component; extract a real reusable component/pattern.
11. Do not create custom CSS solely to avoid a few repeated utility classes. Tailwind intentionally tolerates utility repetition.
12. Keep focus/hover/disabled/busy state classes near the base component styling rather than scattering a single control's states across files.
13. Prefer Tailwind utilities over ordinary custom CSS when the utility expression is clear.
14. Custom CSS is allowed when a complex effect/layout is materially clearer than an unreadable utility string.
15. Add `<style scoped>` in Vue only when the component genuinely needs local CSS; do not include it by ritual.
16. Do not use `!important` as a normal conflict-resolution technique.
17. Do not use Tailwind's important modifier `!` without a concrete reason. Repeated need usually signals a structural styling problem.
18. Any change to Tailwind sources/theme/custom utilities/content detection must be validated with a real frontend build.
19. After Tailwind changes, inspect the final rendered DOM/UI, especially for conditional classes. Source code alone does not prove the generated utility exists.
20. Do not migrate Tailwind major versions incidentally inside an unrelated roadmap. Use a conscious maintenance roadmap.
21. The local Tailwind skill follows the installed version. When syntax/utility semantics are uncertain, consult current official docs for that version rather than guessing from memory.
22. Do not install Tailwind plugins without a concrete use case and dependency approval.
23. Prefer native capabilities of the installed Tailwind version when they replace a plugin cleanly.
24. Do not safelist huge utility ranges to compensate for poorly designed dynamic classes.
25. If a safelist is genuinely required, keep it minimal and document why static discovery cannot represent the source.
26. The Tailwind skill stays Tailwind-specific and does not duplicate generic accessibility/Vue architecture.
27. Runa project tokens/components/visual language override generic skill aesthetic suggestions.
28. A Tailwind task must not redesign the application merely because a generic pattern seems more fashionable.
29. When changing a utility/component style, consider light/dark, responsive, and relevant interaction states.
30. The accepted baseline does not include `tailwind-variants`, animation utility packages, custom stack utilities, or aesthetic framework dogma unless a future roadmap explicitly approves them.
