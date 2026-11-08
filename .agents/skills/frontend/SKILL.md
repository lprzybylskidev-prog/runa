---
name: runa-frontend
description: Runa UI behavior, presentation and copy guidance; select for every frontend change or review, with Vue/Tailwind only for their implementation concerns. Backend-only work does not activate this skill.
---

# Runa Frontend

Use for frontend behavior, presentation, accessibility and user-facing copy.
Read the active roadmap, query available knowledge and inspect an analogous
consumer. Follow the [UI standards and guidance map](../../../docs/runa/handbook/design/visual-direction.md#ui-standards-and-guidance-map);
load only the affected topic/section, expanding when behavior crosses ownership.

## Select references

| Task                                                          | Read selectively                                                                                                    |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Wording/translation only                                      | Interface copy in the visual-direction guide and the owning catalogue. No unrelated forms/tables/motion references. |
| Visual identity or control states                             | references/components.md; palette and component section named by the change.                                        |
| Keyboard, focus, responsive or a11y review                    | references/accessibility-responsive.md.                                                                             |
| Layout, navigation or presentation replacement                | references/page-layouts.md.                                                                                         |
| Validation, outcomes, temporal fields or dirty reconciliation | references/forms.md.                                                                                                |
| Data view, filters, selection or export                       | references/tables.md.                                                                                               |
| Loading/readiness or transitions                              | Loading contract linked below; references/motion.md when motion changes.                                            |
| Measured performance or broad UX review                       | references/ux-design-performance.md.                                                                                |

For every new or changed view, use public `FormNotifications` from `@/runa/ui`
for form-wide outcomes, with field errors beside controls. When changing those
behaviors, read the forms feedback/recovery sections; a label-only edit retains
that contract without loading every form procedure. Contextual security warnings,
reconciliation and durable results have their own presentation owners.

For asynchronous content use the [loading contract](../../../docs/runa/handbook/development/frontend/loading.md):
shared local placeholders, retained refresh/drafts and immediate identity/access
withdrawal. Table, Button and preference-cover indicators keep their documented
exceptions. Do not add global request counters or copied session/security logic.

Platform views ship usable defaults and public replacement seams. Reuse shared
controls on repeated use, migrate analogous consumers and update their owning
contracts. Customer presentation stays in src/app through public facades; protect
configuration visibility, guarded operations and current source/build identity.
Module-specific routing is in the selected reference, not a second API catalogue.

Follow the [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for instruction products, source ownership and verification timing. Rendered
behavior remains acceptance truth; code review alone is not rendered acceptance.
