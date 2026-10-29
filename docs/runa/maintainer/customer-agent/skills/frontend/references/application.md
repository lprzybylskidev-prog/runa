# Application frontend guidance

Inspect analogous App pages and the public component catalogue before new markup. Extract repeated application controls into src/app/resources/js/components and maintain local patterns. Consume public facades without protected edits. Rendered behavior is acceptance truth: keyboard/focus, responsive, PL/EN, themes, reduced motion and full loading/error/dirty states.

Load only the relevant canonical procedure:

- Visual direction and selected references: `docs/runa/handbook/design/visual-direction.md`.
- Components and contracts: `docs/runa/handbook/development/frontend/components.md`.
- Page structures: `docs/runa/handbook/development/frontend/page-layouts.md`.
- Fields, feedback and automatic read reconciliation: `docs/runa/handbook/development/frontend/forms.md`.
- Table providers/selection/loading/export: `docs/runa/handbook/development/frontend/tables.md`.
- Motion: `docs/runa/handbook/development/frontend/motion.md`.
- Presentation replacement and controller lifetime: `docs/runa/handbook/development/frontend/customization.md`.
- Account screens/security cleanup: `docs/runa/handbook/modules/auth/account-ui.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.
