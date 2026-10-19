# UX, Design, and Performance

1. Complex components should have explicit state ownership.
2. Rendering/computed paths must not hide non-obvious side effects.
3. Relevant controls define complete states such as normal, hover, focus, active, selected, disabled, and busy.
4. Disabled and busy are different states; communicate ongoing operations clearly.
5. Block accidental multiple submissions on the frontend.
6. Preserve user-entered form data after save/server errors.
7. Protect destructive actions proportionally using confirmation, undo, or another suitable mechanism.
8. Do not overuse modals.
9. Keep a clear action hierarchy: primary, secondary, destructive.
10. Action copy should describe the result (`Save changes`, not generic `OK`).
11. Treat performance proportionally; understand actual render/request/table/image/bundle costs before optimizing.
12. Code split/lazy-load truly heavy isolated areas rather than every component.
13. Do not render huge collections blindly; add virtualization only for a demonstrated need.
14. Media should use dimensions/aspect ratio and lazy loading where sensible to reduce layout shift/transfer.
15. Use pragmatic design tokens for repeated colors, spacing, radii, and typography.
16. Add a token only when it reduces real drift/repetition.
17. Design around the user task and information hierarchy, not component count.
18. Do not default every screen to a grid of dashboard cards.
19. Review purposeful motion for every changed component using `motion.md`; preserve immediate interactions and explicitly choose static behavior when movement adds no value.
20. Do not rebuild the whole design system during an unrelated feature.
21. Rendered UI is the acceptance truth; "the code looks correct" is insufficient.
22. Reuse existing project components/page patterns before inventing new ones.
23. Inspect an analogous page/component before creating a divergent pattern.
24. Maintain a practical component/pattern catalogue through real usage, not a separate design-system package by default.
