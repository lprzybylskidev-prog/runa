# Application frontend guidance

Read docs/runa/handbook/architecture/frontend.md and relevant design/visual-direction.md and design/color-palette.md. Search existing application components and the public facade src/runa/resources/js/runa/ui/index.ts before creating controls. Import exported controls through @/runa/ui; read protected source for contracts without editing it. Extract repeated application controls into src/app/resources/js/components and maintain the local catalogue here. Never extract into src/runa or change its facade. Unsupported public capabilities require composition or a platform limitation report.

Compose MinimalLayout for compact public screens or ApplicationLayout with named exact application entry routes. Keep shell menus dedicated. Use heading/actions, breadcrumbs, optional statistics/filters, content and supplementary areas consistently. Public Form/FormField/inputs/useForm own presentation; feature validators own rules. Form disables native validation; preserve drafts on 422/409 and offer deliberate reconciliation. NumberInput uses exact decimal strings; temporal fields preserve incomplete drafts and require timezone/DST decisions; FileInput values are separate from finite JSON useForm data. Providers own HTTP, server authorization and bounded pagination.

Tables use stable public_id keys, typed providers and validated envelopes. Never infer authorization from hidden columns/actions. Shared loading/empty/error/retry, dialog focus return and keyboard behavior must remain usable on mobile/tablet/desktop, light/dark and PL/EN. Admin UI is English. Use semantic labels, visible focus, associated errors, zoom-safe content and reduced-motion behavior. Exiting content becomes inert immediately; preserve focus/drafts during interrupted transitions. Do not add duplicate page entrances or animate dense data sorting. Rendered behavior is acceptance truth. Add application patterns here when they become reusable.

Detailed public contracts and examples live in docs/runa/handbook/development/frontend/components.md, forms.md, tables.md, motion.md and page-layouts.md. Load only the relevant guide; keep application-specific additions in local App guidance. These public guides are delivered independently of the owner skill reference layout.

Use docs/runa/handbook/modules/auth/preferences.md for the delivered AuthPreferences/
AuthDelivery contracts. Declare exact source-owned preference schemas and notification
categories, use optimistic revisions, and keep account caches/requests isolated on
logout or switch. Legacy guest adoption requires explicit ownership confirmation;
server values win and reset never resurrects legacy state. Do not put credentials,
drafts or permission state in preferences, disable mandatory primary security mail,
cache recipients across jobs, or import Auth private SQL/models. Reference-only
managed security mail rechecks state and uses a persisted single SMTP attempt;
critical recovery retains direct mail. Application code consumes these contracts
without requiring conversion into an app module; protected Runa remains read-only.

Use the delivered AuthEntry component from @/runa/auth for entry and restricted-session flows; follow docs/runa/handbook/modules/auth/entry-ui.md. Preserve the application-owned App.vue integration when customizing navigation. Keep token proofs and credentials in component memory, honor server-declared restrictions and require explicit resubmission after CSRF/conflict failures; never edit protected Auth source.

Use the [Auth application/account contract](../../../../../handbook/modules/auth/account-ui.md) for welcome policy, post-security
navigation, shared-shell profile/sign-out, real-activity expiry and safe memory draft
review. Preserve application-owned composition and server guards; never replay a
write after login or persist credentials/drafts in browser preferences.

For backend authority behind these screens, read
`docs/runa/handbook/modules/auth/README.md`,
`docs/runa/handbook/modules/auth/transport.md`,
`docs/runa/handbook/modules/auth/sessions.md`,
`docs/runa/handbook/modules/auth/security.md` and
`docs/runa/handbook/modules/auth/profile.md`. UI visibility never grants access.
Use `docs/runa/handbook/modules/auth/policies-and-operators.md` and
`docs/runa/handbook/modules/permissions/README.md` with
`docs/runa/handbook/modules/permissions/authority.md` for actual current grants,
step-up and last-superadministrator constraints. Local manual scenarios and private
setup follow `docs/runa/handbook/data/seeding.md#explicit-module-development-contributors`;
no distributed password or owner test harness is required by customer workflows.

For all current/future Runa module views use
docs/runa/handbook/development/frontend/customization.md. Runa supplies usable
defaults plus documented public presentation replacement. Customer screens,
wrappers and CSS belong in src/app and survive reviewed updates/clones. Use typed
public facades/controllers, preserve identity/page cleanup, CSRF, restrictions,
reauthentication and explicit retries; never copy private security orchestration.
Native errors and generated emergency 503 share one static, CSP-constrained
presentation build. Keep the application scaffold minimal; domain pages are added
only for real customer requirements.
