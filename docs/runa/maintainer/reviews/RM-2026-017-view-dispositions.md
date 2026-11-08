# RM-2026-017 — Point-7 supplied-view dispositions

Point-7 implementation record, 2026-11-06, source through `bef757e2`.
This supplements the [renewed inventory](RM-2026-017-renewed-audit-synthesis.md#supplied-view-families)
and the [approved read-health design](RM-2026-017-admin-read-health-design.md).
It records corrections, justified retention and the final acceptance selection;
it does not certify a complete rendered matrix or replace owner acceptance.

## View and state coverage

Paths below are relative to `src/runa/modules` unless explicitly qualified.
Shared controls retain point 6's dispositions; an unchanged view does not require
a cosmetic rewrite. The renewed inventory supplies the earlier source/browser
evidence and limitations for each retained family.

| View / owning presentation                                                                                                                                          | Point-7 disposition and reason                                                                                                                                                                                                                                       | States retained for final acceptance                                                                                                                                                                           |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Root/welcome and minimal App: `auth/frontend/AuthWelcomeDefault.vue`, `DefaultApplication.vue`, `AuthApplication.vue`                                               | Retain the delivered shell, immediate frame and public presentation overrides. No evidence requires extra App features or embedding the optional example in the minimal scaffold.                                                                                    | Guest/authenticated, denied entry, slow/failed session and options, navigation and identity withdrawal.                                                                                                        |
| Login, registration, recovery, entry tokens and factors: `auth/frontend/AuthEntryFlow.vue`, `useAuthEntry.ts`, `messages.ts`                                        | Correct RA-07 registration text in both catalogues to administrator activation. Retain owning token/factor/security orchestration and configuration-based visibility.                                                                                                | Invalid/expired token, known validation, pending response, configured capability omissions, restricted session, factor challenge/setup and secret cleanup. Stateful token/mail cases remain native acceptance. |
| Protected-place/password confirmation: `auth/frontend/AuthOperationProofPageDefault.vue`, `admin/frontend/AdminEntryProof.vue`                                      | Retain the shared full-page password confirmation and server proof boundary. Strengthen final read proof expiry checks in AdminEntry; no inline operation credential form.                                                                                           | Missing/expired/refused proof, return navigation and protected-content clearing. Focused final-expiry backend regression passes.                                                                               |
| Own profile/contact/avatar: `auth/frontend/AuthAccountDefault.vue`, `AuthAccountEditor.vue`, `AuthSecondaryEmails.vue`, `AuthAvatarCropper.vue`                     | Retain current typed fields, local errors, controlled drafts and native operations. Earlier dirty-name/focus observation supports retention, without certifying writes.                                                                                              | Dirty refresh, overlap/conflict, upload failure, optional-image settlement, configured omissions and identity loss.                                                                                            |
| Own security, sessions/devices and recovery codes: `auth/frontend/AuthAccountSecurity.vue`, `AuthRecoveryCodesDialog.vue`                                           | Retain native session/device tables, shared security orchestration and separate setup-code verification. No demonstrated local presentation defect.                                                                                                                  | Enrollment/cancel, bounded secret display/cleanup, revocation, empty lists and security refusal.                                                                                                               |
| Own preferences/delivery and history: `auth/frontend/AuthAccountPreferences.vue`, `AuthHistoryDetail.vue`, `account/*`, `preferences/*`                             | Retain separate owning preference lanes, translated feedback and immutable detail. Point 6 supplies notification dismissal/locale reconciliation.                                                                                                                    | Retained dirty values, new/dismissed outcomes, locale/theme changes, history detail and permission withdrawal.                                                                                                 |
| User directory, creation and selected-user details: `auth/frontend/components/admin_users.vue`, `AuthAdminCreation.vue`, `AuthAdminUserDetails.vue`                 | Repair RA-05 by clearing the selected-subject scope on accepted close and bulk entry. Refused dirty close preserves the draft. Creation/detail/bulk lanes cannot remain refresh-eligible across subject changes.                                                     | Create-close-details-copy-Security now observed without the previous creation-with-subject 422. Dirty refusal, concurrent revision and availability loss remain final scenarios.                               |
| User detail contributors: `AuthAdminUserSection.vue`, `AuthAdminUserStatus.vue`, `AuthAdminEmails.vue`, `AuthAdminPolicyExceptions.vue`, `AuthAdminAvatar.vue`      | Retain current module-owned sections and native business restrictions. Shared controller reset repairs apply; no restored approval/account-expiry fields or duplicated security controller.                                                                          | Atomic participants across tabs, configured omissions, final action intents, invitations/addresses, optional rule expiry, sessions/devices.                                                                    |
| User bulk actions and access/authority contribution: `AuthAdminBulkAction.vue`, `AuthAdminActionGroups.vue`, Permissions contributors                               | Repair stale subject scope before bulk activation. Retain native preview/review/effects and protected-last-authority rules. No extra generic bulk engine.                                                                                                            | Selected/all filtered targets, stale review, mixed effect, protected administrator and cancelled close. Backend owning tests, rather than destructive live actions, establish invariants.                      |
| Permission/role/preset definitions: `permissions/frontend/components/admin_definitions.vue`, `admin_definition.vue`                                                 | Retain bounded catalogue and existing editor ownership. Every selection calls public selectSubject, erasing prior scope; unlike RA-05 there is no creation-only section and no demonstrated cross-mode failure.                                                      | Read-only source definition, custom creation/edit/delete, filters/search/pages, dirty close and source withdrawal.                                                                                             |
| Nested assignment/import tools: `permissions/frontend/PermissionsAssignments.vue`, assignment/import owners                                                         | Retain compact owning search/import editors. Real nested copy opens; closing it followed by Security now succeeds in Auth. No generic picker replacement.                                                                                                            | High-cardinality search, pagination race, apply/cancel, grant withdrawal and conflict remain distinct final cases.                                                                                             |
| Audit history/details: `audit/frontend/components/admin_history.vue`, `admin_detail.vue`, AdminRecordBrowser                                                        | Retain immutable projections, allowlisted initial filters and shared sorting/range/detail presentation. Existing populated/sorted/range observations give no new local repair.                                                                                       | Empty/error, authorized deep links, time range, export and long-JSON expansion. No mutable Audit editor.                                                                                                       |
| Admin dashboard/modules/work: `admin/frontend/AdminDashboard.vue`, `AdminOperations.vue`, `AdminModuleCard.vue`, `AdminOperationHistory.vue`                        | Integrate separately admitted read plans for widgets. Preserve availability/grants, isolated widget read failure and native operation histories. Critical owner unavailability omits the widget.                                                                     | Healthy/error/omitted widget, Core versus optional actions, work/recovery refusal and protected-content clearing.                                                                                              |
| Application/module settings and snapshots: `admin/frontend/AdminApplicationSettings.vue`, `AdminSettingsPage.vue`, `useAdminSettingsSnapshots.ts`, Auth policy page | Retain source-owned configuration, committed-only snapshot capture and native publication. Point-6 shared draft/read repairs apply; no generic runtime health/integration editor.                                                                                    | Dirty refresh, saved/failed/conflicting publication, capture/load/delete and capability omissions. Live publication is reserved for controlled acceptance.                                                     |
| Reference Admin page/section/widget: `src/app/modules/reference`                                                                                                    | Replace metadata-only management with an authorized read-only Ping consumer. Use public Admin facades, exact read-health plans, shared Card/Button/AdminSection, translated labels and retained read on refresh. Native API and presentation regressions pass.       | Actual page/refresh/widget observed; desktop light, phone/tablet dark and tablet English sampled. Failure/permission denial has backend/component evidence. Full matrix remains below.                         |
| Reference standalone panel, page/slot registration                                                                                                                  | Align shared feedback and Button-owned request progress; retain previous response and replacement slot. Expose registered ModulePage/ModuleSlot examples without inventing an application route or recursive host. Optional source/test exclusion is fixed together. | Component diagnostics cover PL/EN, success/failure/retained refresh and replacement slot. A composed customer shell is a separate delivery acceptance consumer.                                                |
| Native error/mail: `src/runa/resources` error/mail presentation and build inputs                                                                                    | Retain escaped replacements, guarded branding and inherited shared design. Existing 404 sample and source review reveal no new local repair. Do not generate private mail or tokens merely for a screenshot.                                                         | Native error light/dark/narrow/locales, escaped mail and optional branding use existing native/delivery checks at final verification.                                                                          |

## Real runtime observations and their limits

The local PostgreSQL development database `runa` was aligned non-destructively
through the documented trusted ModuleUpdates plan/apply integration, exact source
`bef757e2aef93184956f4aefbd8370433205f43c`, maintenance and a per-step journal.
Auth advanced 5.0.0 → 5.0.1, Admin 1.0.0 → 1.0.1; Audit 1.1.0 and Permissions
2.1.0 were retained. Reference 0.3.0 was installed, diagnosed and explicitly
enabled through its native transition preview/apply. Its sole table has one
welcome sample. No reset/reseed, existing-account/settings deletion or production
operation occurred. Journal: `.runtime/artifacts/playwright/RM-2026-017-point7/runtime-preparation.json`.

Bounded Chromium diagnostics used a task-owned Vite HMR process on loopback port
5174, normal `/api` proxy to development Nginx, normal login and password proof.
No route, intercepted response, fixture account or authorization bypass was added.
The process is stopped after observation. Nginx's production-style assets still
need readiness preparation in point 10; these observations do not hand over stale
assets as ready for manual acceptance.

Evidence under `.runtime/artifacts/playwright/RM-2026-017-point7/`:

- `browser-second.json` / Auth captures: the exact RA-05 sequence succeeds; all
  creation/detail/access/Security reads return 200, creation never receives a
  subject ID after close, no page error or generic toast appears.
- `reference-browser.json` / reference captures: page, response refresh and
  dashboard widget render; actual response reads return 200. No horizontal page
  overflow in sampled 1440, 390 and 820 px layouts. Polish/light and Polish/English
  dark samples were visually inspected. Language was restored to Polish within
  that diagnostic's browser session.
- `concurrent-read-heartbeat.json`: three bounded pairs of real reference reads
  and proof-renewing heartbeat requests all return 200, with no page errors.
  This resolves the immediate reproducibility check, not all concurrency modes.

Failed observations are retained. The first diagnostic used the wrong refresh
accessible name and captured the initial frame too early; it timed out rather
than passing. The second captured one real `/api/admin/activity` 503 and the
corresponding protected-content refusal. Its response reason was not recorded,
so its cause is unknown; do not label it a confirmed generation race. A subsequent
settled reference observation and the bounded concurrent check succeeded. Carry
heartbeat/read concurrency and safe recovery into final automated acceptance;
repeated/reproducible refusal requires an owning repair, not an unguarded retry or
weakened fence. Initial guest 401 and pre-confirmation Admin 403 are expected
security refusals, separately recorded in the successful diagnostic.

## Final rendered selection

Point 10 prepares actual current assets/data and reconciles this selection;
point 11 remains owner acceptance, and point 12 performs full selected automated
verification. These are pending, not implied PASS:

- Every directly modified view: Auth registration, user create/detail/bulk scopes,
  reference panel, reference Admin page/refresh/widget, and protected-place proof.
- Representative shared consumers: own account, user administration, Permissions
  nested access/definitions, Audit detail/range and Admin settings/snapshots.
  Distinct security/write interactions remain separately selected; repeated
  instances of the same shared control need no exhaustive page multiplication.
- PL/EN, light/dark, desktop/tablet/phone; keyboard/focus/accessible names,
  reduced motion, pending/empty/error/retained refresh, dirty/conflicting drafts,
  configured omission and identity/access/proof withdrawal by risk.
- Existing supported component/backend/E2E checks own deterministic state,
  permission/concurrency and failure behavior. Replacement/generated consumers
  and optional delivery own public composition and exclusion. Excluded native
  deployment or mail scenarios are not reported as rendered PASS.

No new reusable rule follows from this evidence record. The implementation
already synchronized owning public guidance, generator, module changelogs, owner
backend skill and customer backend product. Root AGENTS requires no duplication.
