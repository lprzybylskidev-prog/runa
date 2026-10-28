# Components reference

The authoritative delivered contract is [the public components guide](../../../../docs/runa/handbook/development/frontend/components.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For public screen/layout composition and full replacement, use [frontend
adoption](../../../../docs/runa/handbook/development/frontend/customization.md).
New platform views require usable defaults and a documented public replacement
contract in the same point. Keep presentation separated from reusable controllers;
export supported contracts through facades rather than private imports.

Use public Avatar for repeated profile-image/initials presentation. Forward the
ApplicationLayout userAvatar prop in wrappers, and consume current profile display
through useApplicationSession rather than copying private profile orchestration.
Profile/image presentation is never authentication or authorization state.

Auth authenticator setup uses public AuthTotpSetup from `@/runa/auth` for private
QR/manual-key presentation; see the [presentation contract](../../../../docs/runa/handbook/development/frontend/customization.md).

Auth one-time recovery values use public AuthRecoveryCodesDialog from `@/runa/auth`
for both initial enrollment and replacement; its acknowledgement/cleanup contract
is in the public presentation guide. Alert icons center vertically against content
without forcing centered message text.

Account factor actions belong in shared dialogs; trusted-device management is part
of two-factor settings and new trust requires an active factor plus fresh proof.
Follow the public account UI/presentation guides for independent controller state,
secret cleanup, feedback focus and legacy custom-presentation seams.

Use public FormConflict for local overlapping/ambiguous edit decisions; its current
values and accept/keep events are presentation-only. Follow the public forms guide
for baseline merging, shared revision queues and explicit subsequent saves.

Public AuthAccountLoading and the accountLoading presentation entry supply the
initial account structure; see the public account UI and customization guides.

Use the named public controller, props, slots, events and component-handle types
listed in docs/runa/handbook/development/frontend/customization.md#public-presentation-types.
Import types from the same facade as the capability; application wrappers should
not derive their contracts from private implementation imports.

Compose account cards from one public `useAuthAccount` instance per keyed account;
reuse its conflict/list state and save methods rather than creating per-card
controllers or preference adapters. The [account UI guide](../../../../docs/runa/handbook/modules/auth/account-ui.md#automatic-account-refresh)
owns the lifetime and shared-write contract.
