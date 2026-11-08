# RM-2026-017 current owner acceptance scenarios

**Ready for repeated owner acceptance (point 11):** the widget/settings-layout
feedback is corrected in 10u, with current assets and actual readiness renewed in 10v.
See [current layout evidence](RM-2026-017-application-settings-layout.md#renewed-actual-readiness--10v).
The earlier [health correction readiness](RM-2026-017-health-readiness.md) retains
its exact older asset identity and valid backend evidence. Only your explicit
manual result completes 11; full automated verification/release remains 12.

## Preconditions and identity

Open [the application](http://localhost:8080), then use normal login with the existing
privately configured development administrator (role `runa.permissions.super_admin`).
Admin entry uses the native full-page password confirmation. No password reset,
substitute credential or guard bypass is needed. Safe account discovery remains
`php artisan runa:seed:accounts`; credentials stay private.

Runtime source: `9ed1b543d8960a0dbda5b020f6b647384b0d9575` plus the documentation-only
10v handoff commit. Platform VERSION remains 0.7.0, approved feature projection 0.8.0;
Framework API 2.1.0. Installed/source versions match: Admin 2.0.0, Auth 6.0.1,
Audit 1.1.2, Permissions 2.1.2 and reference 0.4.1. Current development SPA entry:
`/build/assets/index-Dd2HJQm4.js`. Exact build hashes are in the linked review.
In 10r both identified development/test reference sample tables and their installation
provenance were rebuilt; each now contains the current `welcome` example. Existing
accounts, credentials, settings, enabled flags and Core history were preserved.
The 10s2 repair reconstructed only the identified registry singleton in each base,
preserving generation/metadata and checked account/settings/reference counts;
10t/10u/10v perform no further storage reset or account/credential change.

The [Core matrix](../../../app/modules/reference/core-capabilities.md) owns source/API/test
links and noninteractive exclusions. Reference grants have prefix `app.reference.`;
source synchronization never assigns them. Native Admin requires `runa.admin.enter`.
Use existing authorized access; inspect restricted states in prepared isolated fixtures.

## Dashboard and application settings layout

On [Modules](http://localhost:8080/admin), the reference widget belongs below the
module catalogue. Switch to [application settings](http://localhost:8080/admin?section=application):
the widget must disappear, including on direct entry/reload, and return on Modules.
Inspect grouped application identity/default preferences, compact named reset
controls and the shared refresh/save action row in PL/EN, light/dark and narrow
layouts. Additional source settings form their own nonempty group when supplied.
Edit the name without saving, explicitly refresh and check that the draft survives.
Reset restores the source default locally; saving still follows the native proof
and revision workflow. Cancel/discard your temporary draft before leaving.

## Health renewal during ordinary use

Refresh [reference samples](http://localhost:8080/admin/app.reference/samples)
while using the account/Admin views in another authenticated tab. Successful
unchanged healthy renewal should retain the session, Admin access and controls.
Check that explicit refresh retains an edited settings draft and that cancelling
password confirmation preserves it. Actual expiry/access loss still follows its
normal refusal/withdrawal; health renewal never extends an original operation proof.

## Icon acceptance

The owner delegated icon selection; this is the first manual acceptance of the
new choices. Report any meaning/style that should be adjusted. Use the same action
across views to assess consistency, with visible labels and existing effect colors.

| Sample                                                                   | Inspect                                                                                                                             |
| ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------- |
| [Login](http://localhost:8080/auth/login)                                | Login/verification/password confirmation icons; visible text, localized names and keyboard activation.                              |
| [Account](http://localhost:8080/account)                                 | Save, mail verification, crop, cancel, reset, revocation and feedback; color/icon meaning remains distinct.                         |
| [Users](http://localhost:8080/admin/runa.auth/users)                     | Create account, details, mixed bulk tools and final concrete actions; open/cancel a dialog with the keyboard and check close focus. |
| [Auth policy](http://localhost:8080/admin/runa.auth/policy)              | Save/review/publication and snapshot capture/load/delete use the same icons as reference settings.                                  |
| [Reference settings](http://localhost:8080/admin/app.reference/settings) | Refresh, review, publication, snapshot actions and empty feedback; compare PL/EN, light/dark and 390px/desktop layouts.             |

Also inspect existing reference samples/history/work, Permissions definitions and
Audit detail scenarios below. Module identity must agree in sidebar and Admin cards:
Admin adjustments, Auth key, Audit clipboard, Permissions shield and reference flask.
Module authors can declare their own frontend/icon.ts; no protected map edit is needed.

Labelled buttons retain text; icons are decorative. Busy replaces the leading icon
with a same-size spinner without moving the label/control. Existing compact tools
remain square and named. Sample Table pagination/column reorder/reset, picker clear/
done controls and Dialog close through the existing user filters/table preferences.
Feedback retains readable text and its announcement; empty inbox icons stay subtle.
Fields and top-level subviews are intentionally decorated selectively.

## Post-refactor scenarios

- **Current preference formats:** in [account preferences](http://localhost:8080/account?tab=preferences),
  change language/theme deliberately, reload and inspect retained account values.
  In a separate guest browser, inspect defaults and deliberate guest changes; login
  must keep the documented account/guest adoption boundary. Obsolete flat display
  keys and table v1 envelopes select current defaults; current scoped values and v2
  named/default table views retain edits. Use an isolated browser for obsolete-key
  fixtures rather than overwriting the owner's normal local storage.
- **Button consumers:** inspect language/theme buttons on `/`, `/auth/login`,
  `/account` and `/admin`. Repeat PL/EN, light/dark, desktop/390px and keyboard
  focus/Enter/Space. Buttons stay square and have localized accessible names.
  Sample Table refresh/reset, preference/export controls and Dialog close; busy
  icon buttons retain names/dimensions and text buttons retain text.
- **Native errors:** [this nonexistent subpath](http://localhost:8080/account/preferences?lang=pl&theme=light)
  deliberately returns the supported 404 document (`/account` itself is valid).
  Language/theme GET buttons retain the companion preference. This uses ordinary
  missing-page handling and changes no application state; no error route was added.
- **Native structured reads:** in [Auth policy](http://localhost:8080/admin/runa.auth/policy),
  inspect snapshots; capture committed settings, load into a dirty draft, cancel,
  then deliberately review/publish through normal password proof. Loading alone
  does not publish; deletion has separate destructive proof. In user creation,
  inspect password requirements; user details expose authorized addresses, delivery
  preferences, policy exceptions, sessions and trusted devices. Confirm dirty draft
  retention and actual revision conflicts. Repeat reference snapshot/load/cancel/
  publication through its supplied workspace. No credential value enters snapshots.
- **Proof and conflicts:** cancel a reference publication proof and retain the draft;
  background-tab return sends no business reload. Explicit refresh preserves a
  dirty form. Two authorized browser sessions editing the same example produce a
  real stale-write refusal and local conflict choices; writes never replay silently.

## Scenario sheet

Use route paths on the confirmed local origin. Exercise only deliberate mutations
to rebuildable example records/settings; account security and lifecycle effects
need separate intent. Preserve existing user/application data.

| Route / view                                                          | Access and concrete scenario                                                                                                                                                                           | Expected result                                                                                                                                                                                                                                                                                                      |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/`, `/auth/login`, enabled registration                              | Guest/authenticated navigation, labels, validation and PL/EN                                                                                                                                           | Correct entry/activation terminology; actionable copy preserved. Login has no introduction paragraph; registration retains activation guidance; root retains concise factual context. Aligned current assets include 10f and 10k–10q; owner copy acceptance remains pending.                                         |
| `/account` and top-bar subviews                                       | Account/security/preferences/history, dirty close/cancel, feedback dismissal then locale switch                                                                                                        | Drafts and focus retained, dismissed outcomes stay dismissed. Trusted-device disclosure and action-driven refresh are implemented in 10g/10h; bounded current-runtime observations belong to 10r and owner acceptance remains 11. See the action-driven refresh review for simulated versus native backend evidence. |
| `/admin/runa.auth/users`                                              | Create, close, open details, copy-from-user open/cancel, Security; dirty refusal and bulk preview/cancel                                                                                               | No false 422/toast, no stale prior-subject reads. No accidental bulk/security writes.                                                                                                                                                                                                                                |
| `/admin/runa.permissions/{permissions,roles,presets}` and user access | Read definitions, filters/pages, assignment/search preview/cancel                                                                                                                                      | Source definitions readonly; ordinary/protected authority and reviewed effects remain distinct.                                                                                                                                                                                                                      |
| `/admin/runa.audit/history`                                           | Range/sort, immutable detail, long values and narrow scrolling                                                                                                                                         | Authorized read-only viewer; no mutation form or leaked private payload.                                                                                                                                                                                                                                             |
| `/admin` and native application/module settings                       | Diagnostics/widget, settings dirty refresh, snapshot preview/cancel                                                                                                                                    | Actual source/runtime versions, retained draft and guarded operations; no lifecycle/publication just for visual inspection.                                                                                                                                                                                          |
| `/admin/app.reference/inspect`, dashboard widget                      | `reference.inspect`; read/refresh, locale/theme/width                                                                                                                                                  | Actual Ping label, retained old value while busy, no fictional health timestamp; grant withdrawal clears/refuses reads.                                                                                                                                                                                              |
| `/admin/app.reference/samples`                                        | `samples.read`; independent `samples.code.read`, `samples.write`, `samples.code.write`. Edit an approved example code with its current revision; compare readonly/omitted grants in prepared fixtures. | Only authorized field/control appears. Save increments revision; competing edit retains draft and asks for explicit resolution; deleted record closes editor. Backend denial/conflict remains authoritative.                                                                                                         |
| `/admin/app.reference/history`                                        | `history.read` and independent `runa.audit.read`; inspect the sample edit and protected work outcome                                                                                                   | Real action/time/outcome summaries; no code or credential values. Independent Audit grant denial remains a refusal even with owning history access.                                                                                                                                                                  |
| `/admin/app.reference/settings`                                       | `settings.read`, optional `settings.write`; independently `settings.credential.read/write`. Change label, review, cancel/confirm publication deliberately.                                             | Exact original reviewed patch and semantic identity; separate `app.reference.settings.publish` full-page proof. Keep/replace/clear credential intent without revealing prior/new value in review/history/snapshots. Dirty drafts survive refresh; stale state never auto-replays writes.                             |
| Same settings page: snapshots                                         | Capture committed label, load into changed draft with consent, review before deliberate publication; delete only a disposable named example snapshot                                                   | Capture uses committed values, load alone changes no committed settings, draft change invalidates review. Deletion uses destructive confirmation and its own `app.reference.settings.snapshot_delete` proof. Secrets never enter snapshots.                                                                          |
| `/admin/app.reference/work`                                           | `work.read`, optional `work.dispatch`; cancel proof first, then deliberately dispatch and refresh                                                                                                      | Cancel sends no write. Own `app.reference.work.dispatch` full-page proof, real work ID/admission, later actual status. Admission does not claim completion; no polling/simulated success.                                                                                                                            |
| Application presentation consumers                                    | Standalone workspace typed slot/exit/disposal, Ping panel and ModulePage/ModuleSlot composition                                                                                                        | Source/test fixtures cover unmounted consumers; do not invent a stock route or a second session/proof host. Owner may use an explicitly prepared consumer; private implementation imports remain forbidden.                                                                                                          |

## Manual matrix and remaining evidence

Sample desktop/tablet/phone, light/dark and PL/EN by risk across account, native
Admin and each reference view. Include keyboard open/close/focus, snapshot dirty
replacement, pending proof/mutations, empty/error states and retained refresh.
Each changed distinct interaction has its own scenario; shared component samples
do not stand in for missing view coverage. Prepared isolated fixtures/tests own
revocation, proof expiry/replay, Audit failure and concurrent writes; the owner
does not need to damage real account security to inspect those states.

Use PL/EN, light/dark, desktop/tablet/mobile and keyboard for affected interactions.
At most five representative views per shared component class suffice; every directly
changed distinct behavior above and in the table still needs its own scenario.
Standalone reference composition is a component/composable entrypoint, not an
additional invented route; its source and component tests are linked in the Core matrix.

Readiness observations are agent diagnostics. Only the owner's explicit result
completes point 11. Record the tested source/environment, feedback and accepted
limits; any later UI repair repeats affected acceptance. No full-suite, security,
AI, release or owner acceptance PASS is claimed by this handoff.
