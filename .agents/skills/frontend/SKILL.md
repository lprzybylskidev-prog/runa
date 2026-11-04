---
name: 'runa-frontend'
description: 'Framework-agnostic Runa frontend guidance for accessibility, responsive UX, component states, visual acceptance, performance, and design-system discipline. Use with Vue/Tailwind skills for implementation.'
---

# Runa Frontend

Load for UI behavior, accessibility, responsive states and rendered acceptance.

For slow views or application-wide performance review, inspect request owners and
measure overlapping mount/focus/visibility/publication reads, not only rendering.
Follow [read-path performance](../../../docs/runa/handbook/architecture/application-architecture.md#read-path-performance)
and the forms reconciliation contract; coalescing must retain later invalidations,
current account/authority fences and dirty drafts.
Use Vue/Tailwind skills only for their implementation concerns. Inspect an analogous
view and the existing public component catalogue before inventing a pattern.

For date/time UI, follow the [application timezone contract](../../../docs/runa/handbook/development/frontend/forms.md#dates-times-and-application-timezone).
Use public formatApplicationInstant/instantTimestamp and DateTimeInput defaults; never infer
an instant's zone from the browser or display raw timestamps in typed date fields.
Keep date-only values unchanged and omit timezone annotations from input copy.
Review sibling module/App consumers when correcting a shared temporal defect.

For every asynchronous content region, read the canonical loading contract in
`docs/runa/handbook/development/frontend/loading.md`. Use public ContentLoading,
Dialog loading or AsyncContent/ViewBoundary defaults; content uses pulsing
placeholders, never a standalone Spinner. Preserve revealed DOM/drafts during
refresh; explicit owner state drives loading, never a global HTTP counter.
Source lint enforces shared circular-indicator ownership. Review all analogous
consumers when repairing a loading mismatch.

For every new or changed view, use public `FormNotifications` from `@/runa/ui`
for form-wide errors and operation success/failure; keep field errors beside controls.
Do not introduce page-local outcome banners or duplicate the same result.
Before implementation, read [the canonical feedback contract](../../../docs/runa/handbook/development/frontend/forms.md)
and follow its repeat-attempt, recovery and modal-focus requirements. Contextual
security/availability warnings, reconciliation and durable progress/results retain
their owning state presentation. Check these distinctions during view review.

For configuration-driven forms, apply [capability visibility](../../../docs/runa/handbook/development/frontend/customization.md#configuration-based-capability-visibility): omit disabled capabilities and empty wrappers, including in replacement views. Configuration editors and temporary operation/prerequisite states retain their own controls.

| Task                                   | Load selectively                                                            |
| -------------------------------------- | --------------------------------------------------------------------------- |
| Accessibility/states/performance       | references/accessibility-responsive.md; references/ux-design-performance.md |
| Reusable controls                      | references/components.md                                                    |
| Page/shell composition                 | references/page-layouts.md                                                  |
| Fields, feedback, dirty reconciliation | references/forms.md                                                         |
| Data views/providers/loading           | references/tables.md                                                        |
| Purposeful transitions/reduced motion  | references/motion.md                                                        |

Read `docs/runa/handbook/design/visual-direction.md` and relevant selected palette/
reference material before visual changes. Rendered behavior is acceptance truth;
check keyboard/focus, PL/EN, light/dark and responsive states in final acceptance.

Owner platform work extracts a repeated control on second use into the existing
Runa component system, migrates its consumers and documents its public facade.
Shell menus retain their dedicated contracts. Customer application controls stay
in src/app; the separately authored customer skill never grants platform edits.
Every Runa view ships usable defaults and a public replacement/composition seam;
security/session orchestration stays in Runa. The public customization/forms guides
own cleanup, preference hydration and dirty-draft/read-reconciliation procedures.
Do not copy those procedures into this entrypoint after each delivery.

For Admin replacement work, consult
[Admin frontend contract and delivery status](../../../docs/runa/handbook/modules/admin/frontend.md).
Use only implemented exports for the installed version; the specification does not
itself deliver controllers, routes or presentation providers.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.

For session-shell and Admin composition, follow the public frontend contract's
shared session shell section. Reuse one session owner and validate the projected
component build identity; check delivery status before consuming specified exports.
