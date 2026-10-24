# Audit, Auth and Permissions acceptance map

RM-2026-011 point 18 reviewed the approved requirements against the implemented
Core contracts and prepared coverage below. This is a source/coverage map, not a
verification report or a claim that unexecuted tests pass. The active roadmap owns
execution order and acceptance gates; point 19 adds public frontend replacement,
native-error customization and minimal-scaffold regressions described below. Point 20 runs the full
selected graph only after the owner's explicit frontend appearance acceptance.

## Backend and integration

Paths in this table are relative to `src/runa/tests/Feature`. The normal owner
backend suite discovers these tests; `core-integration` groups the focused Core
composition cases without replacing the full suite. Real PostgreSQL transactions,
separate worker processes and deliberate Audit write failures provide lock/rollback
evidence; frontend request interception does not provide that evidence.

| Approved surface                                                            | Implementation/public source                                                           | Prepared behavioral coverage                                                                                                                                                                                                                                                                                    |
| --------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Audit model event selection, diff/snapshot, safe transactional writes       | `modules/audit` Capture/Contracts; public Audit guide                                  | `AuditCaptureTest.php`: actual diffs, event matrix, persisted before-state, unsafe standalone refusal and caught-savepoint failure                                                                                                                                                                              |
| Manual aggregate/relationship history, nested suppression, worker cleanup   | Audit public records/attempts and framework logging/principal scopes                   | `AuditCaptureTest.php`: explicit relationship events survive nested suppression; `AuditStorageTest.php`: sequential job attribution, no inherited human/secret context                                                                                                                                          |
| Redaction/bounds/actors, immutable filtered cursor reads                    | Audit guide; append-only events/entities and guarded reads                             | `AuditStorageTest.php`: nested/aliased secrets, unsafe values, actor spoofing and optional Auth failure; `AuditCaptureTest.php`: permission-denied and bounded filtered page/detail reads                                                                                                                       |
| Same-connection success, outer rollback/savepoint, refusal after rollback   | Audit records/attempts; settings/preparation participation                             | `AuditStorageTest.php`: root/savepoint/write failure, refusal persistence and forged reserved preparation; `CoreFoundationsTest.php`: scope unwinding/authority connection locks                                                                                                                                |
| Names, normalized identifier modes/reuse/restore, UUID ownership            | Auth identities/lifecycle guide; ChangeIdentity and lookup contracts                   | `AuthIdentityTest.php`: naming/claim constraints, exact independent UTC predicates, deleted reservation/restore conflict, competing claims and immutable identity                                                                                                                                               |
| Registration/approval/invitation/verification and mail replay               | Auth transport guide; EntryTokens/DeliverEntryMail                                     | `AuthEntryTest.php`: opt-in generic registration, independent approval, single-use/expiry/invalidation, eligibility recheck, mail once/stale suppression, concurrent account lock                                                                                                                               |
| Cookie/CSRF/session/availability boundary and default denial                | ModuleHttp/HttpAccountPolicy; Auth transport; Permissions authorizer                   | `AuthEntryTest.php`: named routes, no Fortify bypass, real CSRF, restricted ingress; `PermissionsTest.php`: real Core HTTP CSRF/login/profile/logout, zero assigned roles and no authority substitute; `ModuleApiTest.php`: availability before controller/request                                              |
| Session concurrency/remember/deadlines/activity/revocation/cleanup          | Auth session guide; SessionStore/AuthenticateSession                                   | `AuthEntryTest.php`: atomic oldest eviction, admission lock, Audit rollback, no polling activity, absolute cap, policy tightening, remembered revocation and bounded cleanup                                                                                                                                    |
| Password complexity/history/expiry/own and admin recovery                   | Auth credential guide; Passwords/ManageSecurity                                        | `AuthEntryTest.php`: current/history policy, reset replay, mandatory TOTP, captured session policies, forced/expired restriction, proof/Audit rollback                                                                                                                                                          |
| TOTP/email OTP/recovery replay, trust and step-up                           | Auth credential guide; CredentialFactors/StepUp                                        | `AuthEntryTest.php`: replay/attempt/TTL/session/action/actor binding, single-use recovery, mandatory-method tightening, trusted-device refusal for step-up and serialized proof consumption                                                                                                                     |
| Primary/secondary address routing and immediate reverification              | Auth profile guide; EmailAddresses/ProfileMail                                         | `AuthEntryTest.php`: old-primary retention, single-use confirmation race/deadline, administrative permission/proof/bypass, immediate restriction, own secondary verification/removal and captured session policy                                                                                                |
| Private profile, avatar, history and self lifecycle                         | Auth profile guide; Profiles/Avatars/Audit own summaries                               | `AuthEntryTest.php`: versions/self scope, real upload content/private blob cleanup, disabled preservation, safe history and self-change proof; `PermissionsTest.php`: actual last-admin self-service refusal                                                                                                    |
| Preferences/defaults/adoption/reset/revisions/account fencing               | Auth preference guide; AuthPreferences and browser account store                       | `AuthEntryTest.php`: declared keys/defaults/adoption/reset/isolation; `frontend/account-preferences.test.ts`: stale responses, serialized conflicts, logout/reset and rendered save failure; existing `frontend/preferences.test.ts` covers guest/scoped consumers                                              |
| Security notices/recipients/reminders, no general in-app delivery           | Auth preference guide; AuthDelivery/SecurityNotices                                    | `AuthEntryTest.php`: mandatory routing, obsolete recipient/deadline suppression, persisted single SMTP attempt and preference/Audit rollback; final representative SMTP delivery uses the native installer fixture                                                                                              |
| Policy revisions/preview/restore/exceptions                                 | Auth policy/operator guide; SettingsAuthorization/PolicyHistory                        | `AuthEntryTest.php`: publication/history/proof atomicity, restore as new revision, stale/expiring allowlist exceptions, critical effects and scheduled deactivation preservation                                                                                                                                |
| Trusted initialize/recover and selected bulk sessions                       | Auth policy/operator guide; OperatorAccounts/PermissionsSelections                     | `PermissionsTest.php` core-integration: actual system membership once, concurrent recipients, resumable invitation, broken ordinary guards recovery, disable switch, selected grants/proof/preview rollback; `AuthEntryTest.php`: private CLI file/confirmation, mandatory-factor recovery and exception limits |
| Source-owned five-field dictionaries, union/direct/role grants and Policies | Permissions guide; DeclarationCatalog/SynchronizeDeclarations and framework authorizer | `PermissionsTest.php`: no automatic grants/recursive identity, direct/role union, cross-worker changes, record-policy/eligibility/unknown-code refusal, source ownership/preview/Audit and removed-definition preservation                                                                                      |
| Custom roles/presets/copy/add/replace/revisions and force deletion          | Permissions access/authority guides; guarded access APIs                               | `PermissionsTest.php`: ordinary reviewed snapshots, stale previews, role/direct distinction, independent copied presets, real both-factor proof, RESTRICT references, protected-role rejection and aggregate Audit failure                                                                                      |
| Protected authority and last eligible administrator                         | Permissions authority guide; AuthorityLock/ProtectedAuthority                          | `PermissionsTest.php`: dedicated grants, no self escalation, lifecycle/scheduler/self-service refusal, simultaneous real worker removals, proof/revision/history rollback and force-delete effects                                                                                                              |
| Disabled Optional preservation, Core graph and no foreign persistence       | Module foundations/framework; public UUID contracts                                    | `PermissionsTest.php`: disabled/re-enabled grants and safe metadata/history; `ModuleGraphTest.php`, `ModuleDataTest.php`, `ModuleDevelopmentTest.php`: graph, ownership/source/runtime contracts and schema boundaries                                                                                          |
| Explicit development corpus and production isolation                        | Public seeding guide; DevelopmentFixtures/owned contributors                           | `PermissionsTest.php`: idempotent accounts/credentials/grants, provenance/Audit rollback, missing/cyclic/foreign contributors, independent counts and hidden setup; `SeedingTest.php`: direct/CLI production receipt refusal and application/module ownership                                                   |

## Rendered Auth and delivery

- `src/runa/tests/frontend/auth-entry.test.ts` prepares the full verification →
  factor → password restriction sequence, mandatory TOTP choice, explicit token
  submission/private fragment cleanup, late-response cancellation and CSRF retry.
- `src/runa/tests/frontend/auth-account.test.ts` prepares safe memory-only drafts,
  same/different-account reauthentication, no write replay, revision conflicts,
  polling versus activity, profile/dropdown logout, secondary-address edits and
  private factor material acknowledgement/cleanup.
- `src/runa/tests/e2e/auth-entry.spec.ts` prepares rendered login keyboard/error
  feedback at desktop/tablet/phone sizes in both themes on every configured browser
  project; entry token flows cover PL/EN, reduced motion, private fragment removal
  and no automatic POST. `auth-account.spec.ts` prepares the same size/theme matrix
  for own profile/dropdown focus and welcome-enabled/disabled navigation with
  post-logout refusal. These isolate rendering with intercepted transport; they do
  not prove real CSRF, SMTP, database locks or authenticated server admission.
- Perform the real local manual path in the public seeding guide using private
  initial setup, current verification/factor policy and both welcome settings.
  Inspect login/restricted/account states in PL/EN and light/dark at desktop,
  tablet and phone widths; use keyboard, focus/errors, reduced motion, initials
  fallback, expiry warning and deliberate reauthentication. Include sessions/trust,
  preferences, enabled avatar/history and private factor controls. Never record
  private credentials/proofs in screenshots/reports. Appearance acceptance is the
  owner's explicit decision; automated rendering does not replace it.
- `tools/maintainer/testing/bootstrap.py` prepares actual authored customer
  AGENTS/skills acquisition with and without reference module, sparse reapply and
  teammate clone. Backend/Laravel/PostgreSQL/security retain manual Audit guidance;
  those products plus frontend must carry Auth/profile/preferences/operator,
  Permissions/authority and corpus guide references. Owner instructions never ship.
- `tools/maintainer/testing/update.py` prepares reviewed instruction changes while
  preserving customer root/domain extensions, including frontend. The execution
  case `test_reviewed_non_platform_add_replace_remove_and_instruction_preservation`
  in `update-execution.py` protects actual publication/reconciliation behavior.
- `installation.py` and native `installation-host.py` prepare fresh/repeated/resumed
  installer admission, optional administrator input, SMTP/TLS, source identity and
  no secret regeneration. `update-execution.py`, `update-host.py`,
  `team-update-host.py`, `customer-initialization.py`, `customer-knowledge.py` and
  `customer-deployment-host.py` prepare exact-source forward update/resume, clone
  and customer runtime/knowledge isolation. Production gates prove AI/dev fixture
  tooling and demo inputs are absent; no owner harness becomes a customer dependency.

## Current-state and instruction review

Reviewed root README/AGENTS, owner backend/Laravel/PostgreSQL/security/frontend
skills and applicable contract references; separately reviewed the customer
AGENTS and those five skill/reference products as delivery data. Both root products
already require manual aggregate/bulk/pivot Audit, current native grants, module
ownership, supported commands and server security, so no duplicate root rule was
added. Both PostgreSQL references already specify the five database dictionary
fields, public references and exact-connection Audit; preference guidance is already
present in backend/security/frontend products. Frontend routing guidance was
completed in both products; Vue/Tailwind syntax/components are unchanged here and
presentation customization remains point 19.

Reviewed public API, module foundations/framework, database, seeding, security,
Auth/Audit/Permissions and frontend mail guides, command documentation/help,
production architecture/deployment and owner foundations/deferred registry.
Corrected obsolete absent-UI, unbound-operator and deferred-authority claims.
Retained future Admin/data-management/Filesystem/Logs/Notifications/Throttling/
Teams/Import/external-provider/WebAuthn boundaries. The default application seed
graph remains empty; explicit module scenarios are available separately. Historical
changelogs and closed roadmaps retain their original point/version context.

Independent manifest/changelog review: Audit 0.5.0, Auth 0.12.0 (Audit ^0.5.0),
Permissions 0.5.0 (Auth ^0.12.0, Audit ^0.5.0), framework API 1.0.0. Each current
manifest matches its changelog and explicit forward/no-op edges. This point changes
only guidance and owner acceptance tests, with no runtime/API/schema or dependency
change; no independent module increment or changelog entry is warranted. Platform
VERSION remains 0.4.2 until the authorized feature 0.5.0 finalization.

## Frontend adoption and minimal scaffold

- frontend/auth-entry.test.ts customization cases: default forms under a custom
  layout, subtree isolation, public headless entry CSRF retry and required
  verification without an authenticated callback bypass.
- frontend/auth-account.test.ts customization cases: customer application content
  within the existing shell, full customer shell with the public session controller,
  removal on 401 and public account-controller capability projection.
- frontend/error-customization.test.ts and fixtures/customization: static localized
  error composition retains identity/actions/request placeholders without API calls
  or executable HTML. Native/generated shared layout acquisition is exercised by
  installation-host acceptance; normal HttpErrorsTest/errors.spec.ts retain status,
  negotiation, headers, CSP, no-database and emergency behavior coverage.
- e2e/auth-account.spec.ts and its owner fixture prepare default/composed/full-shell
  responsive/theme/reduced-motion rendering and 401 reauthentication in every
  configured browser project; these scenarios remain unexecuted until point 20.
- CompositionTest.php retains the named root route with direct platform controller
  wiring. bootstrap.py covers minimal delivered app inventory, customer custom
  files across reapply/clones and propagation of frontend/Vue/Tailwind/security
  guidance. update.py covers customer frontend/error/style ownership and preservation.

Only necessary selected customization diagnostics run in point 19; full rendered,
installation/update/clone and native/emergency acceptance stays in point 20 after
explicit owner visual acceptance. No diagnostic claims full security acceptance.
