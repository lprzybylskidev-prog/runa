---
name: runa-frontend
description: Runa application UI behavior, presentation and copy guidance; select for every frontend change or review. Backend-only work does not activate this skill.
---

# Runa application frontend

Read the active application roadmap, query available knowledge and inspect an
analogous App consumer. Use the UI standards and guidance map in
`docs/runa/handbook/design/visual-direction.md#ui-standards-and-guidance-map`.
Load only the affected topic/section. A label-only edit needs interface copy and
its translation owner, not unrelated forms/table/session/motion procedures.
Vue and Tailwind skills apply only for their implementation concerns.

Use references/application.md to select public controls, page composition, forms,
tables, readiness, motion or the relevant module contract. Customer presentation
and repeated controls stay in src/app; consume documented public facades/controllers.
Report protected-platform defects to its owner; never copy security/session logic.

For every new or changed view, use public `FormNotifications` from `@/runa/ui`
for form-wide outcomes, with field errors beside controls. Read the forms
feedback/recovery sections when changing that behavior; a label-only edit retains
the contract without loading every form procedure. Contextual warnings,
reconciliation and durable results retain their owning presentation.

For asynchronous content follow
`docs/runa/handbook/development/frontend/loading.md`: local placeholders,
retained refresh/drafts and immediate withdrawal on identity/access loss. Table,
Button and preference-cover indicators retain their documented exceptions.
For configuration-dependent UI follow the customization guide's capability
visibility contract; disabled capabilities and empty wrappers are omitted.

Follow `docs/runa/handbook/ai/skills-policy.md#shared-point-workflow` for source
ownership, instruction products and verification timing. Rendered behavior remains
acceptance truth. Customer application authority never permits protected edits.
