# Frontend adoption and presentation replacement

Runa ships usable welcome, sign-in/security, application/account and HTTP error
screens. Customers can retain them, compose application pages from public layouts
and controls, or own their complete frontend. New Runa views follow this same
rule: usable defaults and a documented presentation replacement contract.
Application changes live in `src/app`; `src/runa` remains protected.

## Ownership and startup

The supplied `src/app/resources/js/App.vue` renders `DefaultApplication` from
`@/runa/auth`. That platform component selects exact Auth pages, `/app`, `/account`
and welcome. The default `/app` is empty: no page heading, welcome card or account links. Native unknown server paths remain 404 responses.
`main.ts` imports the shared CSS through `@/runa/ui/styles.css` and mounts
`createApplication(App)` from `@/runa/ui`. This public helper returns a Vue App,
so customers can install their own plugins before mounting. It hydrates public
identity without delaying rendering; failed requests retain manifest defaults.

The retained application files are manifest/dependency files, the HTML entry,
Vue root/bootstrap, named welcome route, provider/seed/test/config extension
entrypoints and the development reference module. Each is an application-owned
integration seam. Platform screen selection and identity orchestration stay in
Runa; no forwarding application controller is needed. `src/app/config/branding.php`
is an optional presentation override (empty by default).

Customers own `src/app/resources/index.html` metadata, their own assets/styles,
route wiring and added pages/components. Application identity/version/logo belongs
in `src/app/application.json`; platform version remains independent. Theme tokens
come from the shared CSS; add application CSS after that import. Tailwind scans
application resources/modules and platform source. Keep complete utility names
statically discoverable. See [branding/layouts](../../architecture/frontend.md).

## Compose with supplied screens

A customer page can render `AuthApplication` from `@/runa/auth` with a default
scoped slot. It runs the existing application session boundary and supplies the
current unrestricted `session`; account changes remount its content; the shell retains its profile/sign-out dropdown,
expiry warning, failures and reauthentication. Its `account` prop selects supplied
account self-service, taking precedence over the content slot. Put the page behind
an application-owned named server route and enforce its own backend grants/policies.

```vue
<script setup lang="ts">
import { AuthApplication } from '@/runa/auth';
import { Button } from '@/runa/ui';
</script>
<template>
  <AuthApplication v-slot="{ session }">
    <h1>{{ session.display_name }}</h1>
    <Button>Application action</Button>
  </AuthApplication>
</template>
```

For independent page composition, `MinimalLayout`, `ApplicationLayout`,
`IdentityProvider` and controls remain available from `@/runa/ui`. Importing them
does not require a module registry or router. The supplied dedicated shell menu
components remain internal. See [page layouts](page-layouts.md) and
[components](components.md) for props, slots and navigation descriptors.

## Replace presentation in the default application

`AuthPresentationProvider` from `@/runa/auth` accepts a `components` object. It
applies only to descendants; nested providers inherit unspecified entries.
Omitted entries use supplied defaults. Pass imported component definitions, not
names, URLs or backend metadata. Keep the map stable during an active form.

| Entry               | Customer component contract                                                                                                                                                                                                                                                                       |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `minimalLayout`     | Accept and render the default content slot, including its `t` translator slot argument for welcome, and forward `header-actions` for the welcome entry links; accept layout props. A wrapper around public MinimalLayout can forward all slots/props and override header, illustration or footer. |
| `applicationLayout` | Accept ApplicationLayout props and its default/user-menu slots. Forward navigation/current/title/user identity and preserve the account/sign-out controls.                                                                                                                                        |
| `entry`             | Accept `page: EntryPage` and optional `authenticated(session)` callback. Use `useAuthEntry(page, authenticated)` once per keyed page. This includes reauthentication after expiry.                                                                                                                |
| `account`           | Accept `accountId: string`; use `useAuthAccount(accountId)` once. The wrapper remounts on account identity change.                                                                                                                                                                                |
| `welcome`           | Use `useAuthWelcome()` once to retain welcome policy and session-aware links.                                                                                                                                                                                                                     |

```vue
<script setup lang="ts">
import { AuthPresentationProvider, DefaultApplication } from '@/runa/auth';
import CustomerMinimalLayout from './layouts/CustomerMinimalLayout.vue';
const components = { minimalLayout: CustomerMinimalLayout };
</script>
<template>
  <AuthPresentationProvider :components="components">
    <DefaultApplication />
  </AuthPresentationProvider>
</template>
```

`MinimalLayout` itself is always the supplied primitive, so an override wrapper
can compose it without recursion. Preserve locale/theme controls when replacing
its header, accessible landmarks/skip links, focus behavior and reduced motion.
The supplied Auth components use the provider for layout selection, including
loading/error and restricted states. It does not rewrite independent application
uses of the public layouts. Avoid selecting the public Auth wrapper itself as
its own override; compose a customer component instead.

## Own the complete frontend

Customers can replace the HTML/Vue root and all screens while keeping the backend.
The public `@/runa/auth` facade exports the same controllers the supplied views
use; private module/resource imports and copied orchestration are unnecessary.
Controllers must run inside Vue setup and be disposed with their view. Their
returned refs/computeds remain reactive: retain refs when destructuring. A changed
entry page or account ID requires a keyed remount. Returned types are inferred;
EntryPage/EntrySession/EntryOptions/SecurityOptions/FactorMethod, ActivitySession,
AccountTab/AccountTask and Profile/AccountRow are public types.

| Controller                           | Presentation responsibilities                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `useAuthEntry(page, authenticated?)` | Render `screen`, title/description, ready/busy/failure/notice, `fieldNames`, values/errors/labels/passwordHint, naming/remember/method choices, enrollment and recovery-code states. Call submit, initialize/refresh, refreshCsrf, sendEmail, logout, codesSaved and changeMethod for the corresponding explicit actions. Bind form/heading refs for validation/focus. Honor needsProof, hasSubmit, methods, enrollmentRequired and token-page restrictions. Without callback the controller handles the approved return; with callback the parent resumes its session boundary. |
| `useAuthAccount(accountId)`          | Render server capabilities/tabs, profile/security, task/fields/errors, proofMethods, busy/failure/notice and explicit draft review. Call selectTab, choose/submit, revoke, readPage, saveDelivery, resetPreferences, restoreDraft, sendCode/sendVerification and refreshCsrf for the corresponding actions. Bind form/heading/feedback refs. Never silently resubmit a restored draft.                                                                                                                                                                                           |
| `useAuthWelcome()`                   | Render loading/failed/retry, options and optional session. The controller enforces disabled welcome. Link a restricted session to `/auth/security`, an unrestricted one to `/app`, guests to sign-in and registration only when enabled.                                                                                                                                                                                                                                                                                                                                         |
| `useApplicationSession(navigate?)`   | One boundary for the mounted private application. Render loading/failure/pending/warning/remaining; unmount protected content when paused or without a session. Render AuthEntry or a replacement with authenticated callback while paused. Use refresh/logout/activity and render only the current unrestricted session. Navigation defaults to document navigation; an optional callback integrates a customer router.                                                                                                                                                         |

Keep private forms scoped to the current account and unmount them on pause/switch.
In a full customer shell, key the private subtree by session.public_id.
The session controller fences responses, revokes preference ownership and observes
shared HTTP failures. It never replays writes. Use `request`, `ApiError` and `RequestCancelledError` from
`@/runa/http` for application requests; relative paths are below `/api`. It retains
same-origin CSRF/cookies and the session lifecycle observer. Do not scatter fetch,
store credentials in browser persistence or implement a second session mechanism.

Server authentication, current eligibility/restrictions, CSRF, module availability,
grants, record policies, recovery proofs and Audit remain authoritative. Custom
presentation must expose every applicable server-driven security step and retain
explicit retry, fragment cleanup, secret cleanup and accessible error/focus paths.
A headless API enables presentation replacement; it does not excuse omitting
recovery, 2FA enrollment/challenge, password change or explicit confirmation.
See [entry](../../modules/auth/entry-ui.md), [account](../../modules/auth/account-ui.md)
and [transport](../../modules/auth/transport.md).

## Native errors and emergency 503

All native HTTP error documents and the pre-generated emergency 503 use the same
build-time layout selection. Optionally add
`src/app/resources/js/errorLayout.ts`, exporting a synchronous SSR-safe Vue
component as its default export. With no file, Runa uses `HttpErrorLayout`, which
composes the actual MinimalLayout and supplied error content.

The component receives `HttpErrorLayoutProps` from `@/runa/ui`: `staticPreferences`
(locale/theme), `footer`, and safe localized `error` status/code/title/description/
home/retry data. Its default slot contains the supplied error content. Forward the
slot to retain status, actions and request ID; use public `HttpErrorContent` with
`error` props when deliberately rearranging that content. A minimal override can
wrap public HttpErrorLayout, forward its props/slot, and import application CSS.
No PHP/Blade application UI or second error renderer is required.

Build through normal `pnpm run build` or `pnpm run build:errors`, then generate
standalone assets through the supported production pipeline. Both native and
emergency output use the same generated templates and CSS. The emergency PHP
build merges non-secret application branding overrides without booting Laravel,
loading env files or connecting to a database. Customer branding overrides used
here must also be usable in this standalone build context (path-only helpers,
plain values; no container/request/database access).

Error components run without window/document, network, session, router or a live
application. Forward `staticPreferences` to MinimalLayout to disable preference
hydration and render language/theme links rather than JavaScript controls. Output
must contain no scripts, inline styles or handlers; the existing generator rejects
these and preserves the normal restrictive CSP. CSS/system themes and escaped
runtime substitutions remain shared. Native status, JSON negotiation, retry/no-store
headers and secret-safe request IDs remain backend/Nginx responsibilities. Do not
replace those guards when changing appearance. Rebuild the assets after changes;
outage responses cannot read live application state.

## Delivery and updates

Acquisition supplies the small default application only at first installation.
Application frontend, errorLayout.ts, styles, manifests, routes and local guidance
remain customer-owned. Reopen and teammate clones preserve committed customer
files. Updates preserve application-owned files, including frontend/error/style
customizations; they do not inspect or apply upstream replacements to customer
domain contents. Exact per-file review governs eligible delivered files and local
instruction reconciliation; it never turns protected platform updates into an
automatic replacement of customer screens. Keep application code and guidance committed in the
customer repository. New platform defaults arrive under protected src/runa; compare
new contracts with local presentation during the reviewed update.

Owner-only examples and acceptance fixtures live under src/runa/tests and
maintainer tooling, never as added customer business screens/modules. Rendered
responsive/theme/keyboard/security-flow and delivery acceptance runs in the final
roadmap verification; custom code still needs its own rendered acceptance.

The private error SSR renderer is bundled with its dependencies because its output
lives under workspace-root .runtime, outside src/node_modules. Keep this build tool
self-contained when changing artifact paths; only generated HTML/CSS/assets enter
production runtime images. See [manual preparation](../../data/seeding.md#manual-auth-navigation-scenario)
for actual environment readiness before frontend review.

Welcome entry links belong in MinimalLayout's public `header-actions` slot beside
language/theme preferences, including the signed-in application link. Customer
layout wrappers forward this slot; replacing the full header deliberately owns its
identity, entry and preference presentation. The default slot remains page content.

Welcome header authentication/application entry uses quiet text links beside
preferences, without button surfaces/borders. Preserve muted default text,
underline/color on hover, visible keyboard focus and 44px touch targets.

The default authenticated application view is empty. Account self-service is named
Panel użytkownika (User panel) and is reached only through the user dropdown,
not the sidebar or a welcome card. The dropdown groups initials/name identity
above left-aligned icon/action rows with a divider and preserved keyboard focus.
ApplicationLayout omits its heading region when title, heading and actions are
absent. Session warnings/errors remain available; custom application content still
uses the public session-scoped slot.

When consuming shared HTTP directly, treat RequestCancelledError as cancellation
of a departing document's observation, not a server success. Avoid an error flash
in that discarded view, retain real live errors, and never replay a canceled write:
its server completion may be unknown. New explicit reads may reconcile state.
The same lifecycle wraps response-body reads; idle pages carry no beforeunload
request guard, and synchronous dirty-form guards take precedence.
