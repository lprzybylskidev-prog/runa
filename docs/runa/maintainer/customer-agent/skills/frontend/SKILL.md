---
name: runa-frontend
description: Runa customer guidance for accessible responsive UI, public components, forms, tables and page patterns.
---

# Runa application frontend

Working Admin partial/full consumers and the authoring map live in
`docs/runa/handbook/modules/admin/examples.md`. Application replacements compose
public controllers and retain platform session/proof handling without protected edits.

Load for accessible responsive UI, public components, forms, tables and page patterns. Read the active application roadmap, query available local knowledge and inspect analogous App code. Project rules override generic advice. Load references/application.md only when needed. Preserve protected platform source.

Use `docs/runa/handbook/architecture/public-api.md` to distinguish supported
extension/consumption APIs from platform internals. Consume documented backend
adapters and frontend facades from application-owned code; neither a Contracts
namespace nor a resolvable import permits private integration access.

Use docs/runa/handbook/development/frontend/customization.md for every Runa/module
view, including future modules: retain defaults, compose public layouts/components
or own the frontend in src/app through supported contracts. Keep private platform
source protected and reuse security/session controllers rather than copying them.

For Admin presentation, check `docs/runa/handbook/modules/admin/frontend.md` and
its delivery status before using named exports. Specifications are not installed
capabilities; implemented replacement components stay in src/app and retain public
session/security controllers.

For data views, follow the public forms guide's automatic read reconciliation
contract: refresh reads in place, merge clean/disjoint values, preserve dirty drafts
and use local decisions only for overlapping/ambiguous changes. Share aggregate
mutation queues/revisions. Never replay failed writes, advance an unreconciled token,
reinitialize an edited form or demand a page reload after a successful refresh.

For custom initial account loading, use the public AuthAccountLoading or the
accountLoading presentation entry; see the public Auth account UI/customization
guides. Loading presentation must not mount protected controllers before session
validation.

For externally fetched client Table rows, pass the request loading state through
the public loading prop; see the public tables guide.

Shared Table owns query-change loading feedback in client/server mode. Do not add
page-local loading timers or duplicate reads; follow the public tables guide.

MinimalLayout alone enables sparse constellation-point glow and occasional
brand-illustration point laps. Keep ApplicationLayout
static and use the public layout opt-out/motion contract when wrapping layouts.

Follow `docs/runa/handbook/ai/skills-policy.md#shared-point-workflow` for shared
point timing, canonical docs and local-extension maintenance. Application scope
and protected-platform boundaries remain governed by installed AGENTS.

For session-shell and Admin composition, follow the public frontend contract's
shared session shell section. Reuse one session owner and validate the projected
component build identity; check delivery status before consuming specified exports.

For administrative Auth pages, follow `docs/runa/handbook/modules/auth/administration.md`
for public default composition and exact owning grants/proofs/revisions. Reuse
Admin's section dispatch/failure boundaries and shared subject controller; clear
independent read lanes when changing subjects. App replacements remain App-owned.

For Permissions/Audit administrative defaults, follow
`docs/runa/handbook/modules/permissions/administration.md` and
`docs/runa/handbook/modules/audit/administration.md`. Their public facades and
AdminRecordBrowser compose owning guarded workflows; App presentation replacements
retain the shared session, dirty guards and native proof/review protocols.

For operational dashboard/editor replacements, follow
`docs/runa/handbook/modules/admin/frontend.md#operational-dashboard-and-replacements`
and the owning fixed operation catalogue. Keep the shared controller's session,
dirty-draft, explicit retry and native pending/recovery semantics in App composition.

For application settings and runtime branding composition, follow
`docs/runa/handbook/modules/admin/frontend.md#application-global-settings-and-runtime-branding`
and its owning settings contract. Use public App registration/adapters and shared
controllers; preserve canonical identity and technical/source policy ownership.
