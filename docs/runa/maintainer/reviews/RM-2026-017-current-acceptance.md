# RM-2026-017 current owner acceptance scenarios

Point 10e source reconciliation, 2026-11-07. This sheet supersedes the scenario
selection in the [dated point-10 report](RM-2026-017-point-10-review.md), whose
runtime observations and failed evidence remain unchanged historical records.
This is a preparation sheet, **not a working-runtime or owner acceptance claim**.
Point 10i must record aligned source/runtime/assets, actual URLs/API observations
and usable existing authorized access after points 10f–10h. Point 11 records the
owner's explicit result. Complete automated verification remains point 12.

## Preconditions and identity

Expected local origin remains `http://localhost:8080`; internal diagnostics use
the existing Nginx sibling `http://nginx:8080`. These are prior environment facts
to reconfirm in 10i, not newly tested availability. Use normal login and the
existing privately configured authorized development account; never reset a
password, issue substitute credentials or bypass grants/maintenance for readiness.

Candidate source: platform VERSION 0.7.0, projected release 0.8.0; Framework API
2.0.0; Admin 1.1.0, Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1, reference 0.4.0.
These are source declarations. The earlier report's installed versions and asset
hashes do not certify this candidate. 10i owns explicit nondestructive forward
updates and the development asset build, plus any required approved early version
preparation under release policy. No reset, lifecycle toggle as migration, fake
version or production build inclusion of reference is permitted.

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

| Route / view                                                          | Access and concrete scenario                                                                                                                                                                           | Expected result                                                                                                                                                                                                                                                                                    |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/`, `/auth/login`, enabled registration                              | Guest/authenticated navigation, labels, validation and PL/EN                                                                                                                                           | Correct entry/activation terminology; actionable copy preserved. Login has no introduction paragraph; registration retains activation guidance; root retains concise factual context. Source changes from 10f await aligned assets and real-page confirmation in 10i.                              |
| `/account` and top-bar subviews                                       | Account/security/preferences/history, dirty close/cancel, feedback dismissal then locale switch                                                                                                        | Drafts and focus retained, dismissed outcomes stay dismissed. Trusted-device disclosure and action-driven refresh are implemented in 10g/10h; their current-runtime rendered acceptance remains pending 10i/11. See the action-driven refresh review for simulated versus native backend evidence. |
| `/admin/runa.auth/users`                                              | Create, close, open details, copy-from-user open/cancel, Security; dirty refusal and bulk preview/cancel                                                                                               | No false 422/toast, no stale prior-subject reads. No accidental bulk/security writes.                                                                                                                                                                                                              |
| `/admin/runa.permissions/{permissions,roles,presets}` and user access | Read definitions, filters/pages, assignment/search preview/cancel                                                                                                                                      | Source definitions readonly; ordinary/protected authority and reviewed effects remain distinct.                                                                                                                                                                                                    |
| `/admin/runa.audit/history`                                           | Range/sort, immutable detail, long values and narrow scrolling                                                                                                                                         | Authorized read-only viewer; no mutation form or leaked private payload.                                                                                                                                                                                                                           |
| `/admin` and native application/module settings                       | Diagnostics/widget, settings dirty refresh, snapshot preview/cancel                                                                                                                                    | Actual source/runtime versions, retained draft and guarded operations; no lifecycle/publication just for visual inspection.                                                                                                                                                                        |
| `/admin/app.reference/inspect`, dashboard widget                      | `reference.inspect`; read/refresh, locale/theme/width                                                                                                                                                  | Actual Ping label, retained old value while busy, no fictional health timestamp; grant withdrawal clears/refuses reads.                                                                                                                                                                            |
| `/admin/app.reference/samples`                                        | `samples.read`; independent `samples.code.read`, `samples.write`, `samples.code.write`. Edit an approved example code with its current revision; compare readonly/omitted grants in prepared fixtures. | Only authorized field/control appears. Save increments revision; competing edit retains draft and asks for explicit resolution; deleted record closes editor. Backend denial/conflict remains authoritative.                                                                                       |
| `/admin/app.reference/history`                                        | `history.read` and independent `runa.audit.read`; inspect the sample edit and protected work outcome                                                                                                   | Real action/time/outcome summaries; no code or credential values. Independent Audit grant denial remains a refusal even with owning history access.                                                                                                                                                |
| `/admin/app.reference/settings`                                       | `settings.read`, optional `settings.write`; independently `settings.credential.read/write`. Change label, review, cancel/confirm publication deliberately.                                             | Exact original reviewed patch and semantic identity; separate `app.reference.settings.publish` full-page proof. Keep/replace/clear credential intent without revealing prior/new value in review/history/snapshots. Dirty drafts survive refresh; stale state never auto-replays writes.           |
| Same settings page: snapshots                                         | Capture committed label, load into changed draft with consent, review before deliberate publication; delete only a disposable named example snapshot                                                   | Capture uses committed values, load alone changes no committed settings, draft change invalidates review. Deletion uses destructive confirmation and its own `app.reference.settings.snapshot_delete` proof. Secrets never enter snapshots.                                                        |
| `/admin/app.reference/work`                                           | `work.read`, optional `work.dispatch`; cancel proof first, then deliberately dispatch and refresh                                                                                                      | Cancel sends no write. Own `app.reference.work.dispatch` full-page proof, real work ID/admission, later actual status. Admission does not claim completion; no polling/simulated success.                                                                                                          |
| Application presentation consumers                                    | Standalone workspace typed slot/exit/disposal, Ping panel and ModulePage/ModuleSlot composition                                                                                                        | Source/test fixtures cover unmounted consumers; do not invent a stock route or a second session/proof host. Owner may use an explicitly prepared consumer; private implementation imports remain forbidden.                                                                                        |

## Manual matrix and remaining evidence

Sample desktop/tablet/phone, light/dark and PL/EN by risk across account, native
Admin and each reference view. Include keyboard open/close/focus, snapshot dirty
replacement, pending proof/mutations, empty/error states and retained refresh.
Each changed distinct interaction has its own scenario; shared component samples
do not stand in for missing view coverage. Prepared isolated fixtures/tests own
revocation, proof expiry/replay, Audit failure and concurrent writes; the owner
does not need to damage real account security to inspect those states.

10i must append exact prepared source commit, source/build hashes, observed
routes/API results, current services/installed versions, safe access instructions,
pending blockers and any task-resource cleanup. Copy expectations reflect completed 10f; amend pending device/refresh
expectations after 10g–10h before presenting this sheet. Only the owner's explicit
result for that prepared environment completes 11; later UI repairs repeat the
affected manual acceptance. No final PASS, release or acceptance is recorded here.
