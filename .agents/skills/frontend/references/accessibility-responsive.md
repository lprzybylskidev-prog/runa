# Accessibility and Responsive UI

1. WCAG 2.2 AA is the baseline target, not a formal certification claim.
2. Use semantic HTML before ARIA.
3. All functionality must be keyboard accessible; do not create keyboard traps.
4. Visible focus is mandatory.
5. Manage focus for modals/temporary layers and return focus appropriately.
6. Forms use real labels. Placeholder text is not a label.
7. Errors/instructions are programmatically associated with relevant controls.
8. Important dynamic messages are exposed to assistive technologies.
9. Color must not be the only way meaning/state is communicated.
10. Design-system color combinations should meet AA contrast.
11. UI must preserve function at 200% zoom/text scaling.
12. Minimum target size follows WCAG 2.2 around 24x24 CSS px; prefer approximately 44x44 for common touch actions where practical.
13. Respect `prefers-reduced-motion`; do not remove motion blindly when it communicates state.
14. No critical information/action may be hover-only.
15. Choose responsive breakpoints from content/layout behavior, not device catalogs.
16. Do not remove important functionality on mobile; adapt its presentation.
17. Test long and missing content.
18. Test Polish/English translation expansion.
19. Screen-reader checks are periodic for critical/complex flows, not a mechanical requirement for every trivial control.
20. Accessibility is part of normal component review.
