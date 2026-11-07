# RM-2026-017 current owner acceptance scenarios

Point 10i preparation, 2026-11-07. Development runtime/assets and actual routes
are observed in the [combined readiness review](RM-2026-017-combined-readiness.md).
Point 10i is complete, including the guarded test-base provenance repair. This
sheet is the owner's manual handoff, not owner acceptance or final automated PASS. Point 11 requires the owner's explicit result;
complete automated verification/release remains point 12. The earlier point-10
report's runtime observations remain historical evidence.

## Post-audit handoff dependency

The owner's additional pre-1.0 audit adds proposed corrections 10k–10q and renewed
combined readiness 10r in the same active roadmap. The current environment and
observations below still identify the completed 10i source. They cannot accept
future preference, control or structured-read changes. Renew affected scenarios,
assets/runtime/source identities and data/access preparation in 10r before the
sole owner acceptance point 11. Final automated verification remains 12. See the
[compatibility audit and dispositions](RM-2026-017-pre1-compatibility-audit.md).

## Preconditions and identity

Expected local origin remains `http://localhost:8080`; internal diagnostics use
the existing Nginx sibling `http://nginx:8080`. Both were reconfirmed in 10i through real Nginx requests and Chromium views. Use normal login and the
existing privately configured authorized development administrator account
(role runa.permissions.super_admin); never reset a
password, issue substitute credentials or bypass grants/maintenance for readiness.

Candidate source: platform VERSION 0.7.0, projected release 0.8.0; Framework API
2.0.0; Admin 1.1.0, Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1, reference 0.4.0.
These versions are installed in the development database after explicit forward
updates. Current SPA entry is /build/assets/index-3bYqUnwF.js; the linked readiness
review records exact source/build hashes and services. No early VERSION preparation
was needed. Test-base storage and real installation ledgers were rebuilt under
standing pre-1.0 authority; the native two-user case passes there. No lifecycle
toggle, fake version or production inclusion of reference was used.

The [application Core matrix](../../../app/modules/reference/core-capabilities.md)
owns executable source/API/test links and noninteractive/internal exclusions.
Reference grant names below have prefix `app.reference.`; source synchronization
never assigns them. Add only the intended grants through normal authorized
Permissions administration. Native Admin entry independently requires
`runa.admin.enter` and its own full-page password confirmation.

## Scenario sheet

Use route paths on the confirmed local origin. Exercise only deliberate mutations
to rebuildable example records/settings; account security and lifecycle effects
need separate intent. Preserve existing user/application data.

| Route / view                                                          | Access and concrete scenario                                                                                                                                                                           | Expected result                                                                                                                                                                                                                                                                                                      |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/`, `/auth/login`, enabled registration                              | Guest/authenticated navigation, labels, validation and PL/EN                                                                                                                                           | Correct entry/activation terminology; actionable copy preserved. Login has no introduction paragraph; registration retains activation guidance; root retains concise factual context. Aligned current assets include 10f; owner copy acceptance remains pending.                                                     |
| `/account` and top-bar subviews                                       | Account/security/preferences/history, dirty close/cancel, feedback dismissal then locale switch                                                                                                        | Drafts and focus retained, dismissed outcomes stay dismissed. Trusted-device disclosure and action-driven refresh are implemented in 10g/10h; bounded current-runtime observations belong to 10i and owner acceptance remains 11. See the action-driven refresh review for simulated versus native backend evidence. |
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

The combined readiness review records source/build hashes, routes/APIs, actual
services/installed versions, existing administrator access and preserved resources.
Its test-base provenance blocker is resolved; this candidate is ready for point 11. Copy/device/refresh
expectations reflect completed 10f–10h and inserted readiness repairs. Only the owner's explicit
result for that prepared environment completes 11; later UI repairs repeat the
affected manual acceptance. No final PASS, release or acceptance is recorded here.
