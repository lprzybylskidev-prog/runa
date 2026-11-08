# RM-2026-017 current owner acceptance scenarios

Point 10r preparation, 2026-11-08. The [post-refactor readiness review](RM-2026-017-post-refactor-readiness.md)
records current source/runtime/assets, bounded observations and preserved resources.
Point 11 requires the owner's explicit result for this environment. Full automated
verification/release remains point 12. Prior 10i screenshots identify older source.

## Preconditions and identity

Open [the application](http://localhost:8080), then use normal login with the existing
privately configured development administrator (role `runa.permissions.super_admin`).
Admin entry uses the native full-page password confirmation. No password reset,
substitute credential or guard bypass is needed. Safe account discovery remains
`php artisan runa:seed:accounts`; credentials stay private.

Runtime source: `5903593ccde714088b3816fbe8493889a76067c9` plus the documentation-only
10r handoff commit. Platform VERSION remains 0.7.0, approved feature projection 0.8.0;
Framework API 2.1.0. Installed/source versions match: Admin 2.0.0, Auth 6.0.1,
Audit 1.1.2, Permissions 2.1.2 and reference 0.4.1. Current development SPA entry:
`/build/assets/index-CaW1iAUP.js`. Exact build hashes are in the linked review.
Both identified development/test reference sample tables and their installation
provenance were rebuilt; each now contains the current `welcome` example. Existing
accounts, credentials, settings, enabled flags and Core history were preserved.

The [Core matrix](../../../app/modules/reference/core-capabilities.md) owns source/API/test
links and noninteractive exclusions. Reference grants have prefix `app.reference.`;
source synchronization never assigns them. Native Admin requires `runa.admin.enter`.
Use existing authorized access; inspect restricted states in prepared isolated fixtures.

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
