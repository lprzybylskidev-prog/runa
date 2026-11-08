# Core capability examples

This is the source map for the optional development-only reference 0.4.1.
All four supplied Core modules are represented below. Module versions and public
API versions are separate: current source declares Admin 2.0.0, Auth 6.0.1,
Audit 1.1.2 and Permissions 2.1.2. The reference accepts their compatible optional
ranges in [module.json](../../../../src/app/modules/reference/module.json) and
requires Framework API ^2.1.0. A source candidate does not establish installation,
working assets, rendered acceptance or complete verification.

Use the [installation and grant procedure](README.md#install-and-observe) first.
Paths below are relative to `src/app/modules/reference/` unless a repository root
is stated. `ReferenceTools` 1.0.0 is the example's own guarded business API;
its [complete signatures and named HTTP routes](README.md#guarded-core-backend-examples)
remain authoritative. HTTP, native Admin fallback readers and standalone UI call
the same owning workflows. Do not instantiate private Core handlers.

Start at the application-owned
[Admin declarations](../../../../src/app/modules/reference/backend/Definitions/ReferenceManagement.php),
[grant dictionary](../../../../src/app/modules/reference/backend/Definitions/ReferencePermissions.php),
[settings provider](../../../../src/app/modules/reference/backend/Definitions/ReferenceSettings.php),
[business API implementation](../../../../src/app/modules/reference/backend/Actions/ReferenceToolsHandler.php),
[sample mutation](../../../../src/app/modules/reference/backend/Actions/EditSample.php),
[work mutation](../../../../src/app/modules/reference/backend/Actions/DispatchWork.php),
[record policy](../../../../src/app/modules/reference/backend/Policies/SamplePolicy.php),
[snapshot adapter](../../../../src/app/modules/reference/backend/Management/ReferenceSnapshots.php)
and [workspace controller](../../../../src/app/modules/reference/frontend/useReferenceWorkspace.ts).

## Core matrix

Test keys in the last column identify actual cases in the test map below. These
are coverage ownership, not claims that the suites have passed on this candidate.

| Core capability / public entrypoint                                                                                                                          | Declaration and owning source                                                                                                                                                        | Reproduction and expected outcome                                                                                                                                                                                                                                                                                          | Success / refusal tests                                      |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| **Admin:** native access, declared page/section/widget; AdminAccess 1.0.0 is shell-owned, `@/runa/admin` supplies public frontend composition                | `Definitions/ReferenceManagement.php`; `Management/ReferenceReader.php`; `frontend/components/inspect_section.vue`, `response_widget.vue`                                            | `/admin/app.reference/inspect` and `/admin`: read the real Ping 1.0.0 response; refresh retains it. Separate `runa.admin.enter` and `app.reference.reference.inspect` are required. No business mutation or invented health timestamp.                                                                                     | P1–P4; private native reference read/withdrawal cases        |
| **Admin:** custom pages, field/read/write projection and replacement lifetime; public `AdminPageProps`, `useAdminPageContext` (Admin 1.1.0)                  | `ReferenceManagement`; `Management/Reference{Samples,Settings,History,Work}Reader.php`; `frontend/components/tools_page.vue`, `workspace.vue`                                        | `/admin/app.reference/{samples,settings,history,work}`: advertised readable pages share the native session/proof host and exit checks. Standalone consumers use the same typed workspace/controller and their existing admitted identity. Field grants omit values; read-only access omits write controls.                 | B10; W2, W7, W10, W11                                        |
| **Permissions:** source-owned action/field grants; Permissions 1.0.0 through `ModuleApis::resolve`                                                           | `Definitions/ReferencePermissions.php`, `ReferenceManagement`; `Policies/SamplePolicy.php`; `Queries/ReadSamples.php`; `Actions/EditSample.php`; `Definitions/ReferenceSettings.php` | Samples: grant `samples.read`, then independently code read/write and sample write. Observe omission, readonly projection and backend 403 for ungranted writes. Settings: `settings.write` alone cannot replace/clear credential. Registration/sync never assigns grants. Prefix example grants with `app.reference.`.     | B1, B10, B12; W2, W7                                         |
| **Audit:** opt-in single-model Auditable / AuditModelPolicy and guarded AuditReads 1.1.0                                                                     | `Models/Sample.php`; `Actions/EditSample.php`; `Actions/ReferenceToolsHandler.php::history`                                                                                          | `/admin/app.reference/samples`: edit a code with its current revision. `/admin/app.reference/history`: read actual action/time/outcome summaries with both `history.read` and `runa.audit.read`. Optional HTTP `sample` filter also checks owning sample access. No code or secret values are reprojected by this history. | B2, B3, B8, B11; W1, W9                                      |
| **Audit:** explicit event through AuditRecords 1.0.0 in the owning transaction                                                                               | `Actions/DispatchWork.php`; native settings/snapshot participation via `ReferenceSettings`, `Management/ReferenceSnapshots.php`                                                      | Dispatch from `/admin/app.reference/work`: durable work admission and `work.dispatched` history commit together. Audit refusal rolls queue/work/proof back. Publication and snapshot mutations use native transactional Audit, not a post-commit wrapper.                                                                  | B6, B9; W4                                                   |
| **Auth:** action-specific password proof, AuthStepUp 2.0.0 owned by native mutation/settings orchestration; `useAuthPasswordConfirmation` from `@/runa/auth` | `Actions/DispatchWork.php::definition`; `ReferenceSettings::access`; `ReferenceSnapshots::DELETE_ACTION`; `frontend/useReferenceWorkspace.ts`                                        | Work dispatch, settings publication and snapshot deletion each open the shared full-page confirmation for their own action. Cancel sends no write. Admin entry proof cannot replace it; expired/consumed proof refuses. No inline password/2FA form or copied security controller.                                         | B4–B7, B9; W3, W4, W8                                        |
| **Framework settings consumed with Core:** ModuleSettings authorized read/update and SettingsAccessProvider, Framework API 2.1.0                             | `ReferenceSettings`; `ReferenceToolsHandler::{settings,reviewSettings,publishSettings}`                                                                                              | `/admin/app.reference/settings`: edit/review label or independently granted credential intent, then deliberately publish exact patch/revision/review_id/review. Health renewal does not invalidate semantic review; real source/revision changes do. Credential is set-state only; keep/replace/clear are distinct.        | B4, B9, B12; W3, W7, W8                                      |
| **Framework snapshots consumed with Core:** SettingsSnapshotSection, Framework API 2.1.0                                                                     | `Management/ReferenceSnapshots.php`; `ReferenceToolsHandler::{snapshots,captureSnapshot,deleteSnapshot}`                                                                             | Same settings page: capture the committed label, load into a draft and review before publishing. Dirty replacement needs consent. Credential never enters snapshots. Delete requires its own proof and current review identity.                                                                                            | B7; W5, W6                                                   |
| **Framework managed work consumed with Core:** ModuleTransactions / TransactionalMutation and ModuleWork, Framework API 2.1.0                                | `Actions/DispatchWork.php`; `Definitions/ReferenceJobs.php`, `ReferenceJobHandler.php`, `ReferenceWorkPolicy.php`                                                                    | `/admin/app.reference/work`: dispatch and explicitly refresh status; admitted does not mean completed. CLI `app:reference:work` and the hourly schedule demonstrate the trusted operational path. Worker checkpoints and scoped storage demonstrate real execution, not a fake integration.                                | B5, B6; W4; private end-to-end reference lifecycle/work case |

The remaining framework examples (lifecycle, health, PostgreSQL identities,
typed Ping HTTP/PHP, events/listeners, CLI/scheduler/storage, ModulePage/ModuleSlot)
have their [reproduction map](README.md#capability-reproduction-and-deliberate-exclusions).
They are not additional Core modules. Source dictionaries and entrypoint metadata
alone do not certify runtime behavior.

## Test map and execution

Application backend cases live in
[CoreExamplesTest.php](../../../../tests/backend/app/modules/reference/CoreExamplesTest.php).
The fixture requires isolated prepared Core; it does not teach an install/enable
bypass. Its database metadata writes are fixture setup only.

| Key | Actual case, in file order                                                                  |
| --- | ------------------------------------------------------------------------------------------- |
| B1  | omits the separately granted field and refuses reads and writes independently               |
| B2  | audits a sample edit and rejects stale or deleted records without overwriting               |
| B3  | rolls the sample and revision back when mandatory automatic Audit refuses                   |
| B4  | requires exact settings review and one own proof while keeping secret projections safe      |
| B5  | dispatches durable work with its own proof and refuses replay expiry and revoked authority  |
| B6  | rolls back dispatch queue proof and explicit history when Audit refuses                     |
| B7  | loads committed snapshots into values without publishing or copying secrets                 |
| B8  | requires the Audit read grant independently of owning history access                        |
| B11 | shows an audited sample change through authorized history without reprojecting field values |
| B12 | refuses credential replacement and clear without the independent field grant                |
| B9  | rolls settings secret revision and its proof back on required Audit refusal                 |
| B10 | projects reference Admin read and field grants independently before native fallback reads   |

[workspace.test.ts](../../../../tests/frontend/app/modules/reference/workspace.test.ts)
owns W1–W11 in file order: sample conflicts/no replay; identity withdrawal/late
reads; exact reviewed publication/own proof; cancelled work/admission; snapshot
draft/review invalidation; snapshot business conflict; readonly/omitted fields;
cancelled proof/changed reviewed input; removed record; typed slot/disposal;
clean-read versus pending-mutation navigation.
[presentation.test.ts](../../../../tests/frontend/app/modules/reference/presentation.test.ts)
owns P1–P4: retained Ping/localized dismissal; replacement/disposal; unavailable
widget/localization; native section refresh/controller composition.
[ModuleContractTest.php](../../../../tests/backend/app/modules/reference/ModuleContractTest.php)
checks source boundaries; it does not replace behavioral cases.

Use the [supported application runners](../../../runa/handbook/testing/strategy.md)
from the configured development terminal, respecting roadmap verification timing:

```sh
php artisan runa:test:backend ../tests/backend/app/modules/reference --parallel --processes=1
php artisan runa:test:frontend ../tests/frontend/app/modules/reference/workspace.test.ts ../tests/frontend/app/modules/reference/presentation.test.ts
```

Owner-only `tests/backend/runa/Feature/ModuleReferenceTest.php` additionally owns
native installation/retained-data lifecycle, worker/event/schedule completion,
read-health refusal and production exclusion. It is excluded from customer delivery
and is never a customer prerequisite. Production/source projection has its own
owner tooling checks; application component tests cannot establish that isolation.

## Deliberate limits and native Core workflows

The example demonstrates consumption of each Core module with a small owning
scenario. It does not duplicate every Core account/security/administration screen
or expose trusted internal entrypoints as a customer API.

| Owner / remaining public API families                                                                                                                    | Working native entrypoint or exclusion reason                                                                                                                                                                                                                                                                                                                                                                                |
| -------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Auth: AuthIdentities 1.0.0, AuthAdministration 2.1.0, AuthLifecycle 2.1.0, AuthInvitations 1.0.0, AuthProfiles 2.1.0                                     | Identity/account administration is already executable in `/admin/runa.auth/users` and the shared Auth entry/account views. The reference owns shared samples, not foreign identities, invitations or profile edits. See [Auth](../../../runa/handbook/modules/auth/README.md).                                                                                                                                               |
| Auth: AuthSessions 1.0.0, AuthSecurity 1.2.0, AuthPreferences 1.1.0, AuthDelivery 1.0.0, AuthPolicies 4.0.0, AuthBulkSessions 2.0.0, AuthBulkUsers 2.0.0 | `/account`, Auth administration and native policy settings supply these security/preferences/delivery and reviewed bulk workflows. The reference reuses the existing identity/locale/proof host; it neither changes real account security to demonstrate sample work nor creates another session system.                                                                                                                     |
| Permissions: PermissionsAccess 2.2.0, PermissionsAuthority 1.0.0, PermissionsAuthorityBatch 1.0.0, PermissionsSelections 1.0.0                           | Native `/admin/runa.permissions/{permissions,roles,presets}` and user access administration own reviewed assignments/selections/protected authority. The reference declares its own grants and consumes guarded checks; it never self-grants or mutates foreign assignments. See [Permissions](../../../runa/handbook/modules/permissions/README.md).                                                                        |
| Audit: OwnHistory 1.0.0, OwnHistoryDetails 1.0.0                                                                                                         | `/account` supplies current-principal security history; `/admin/runa.audit/history` supplies the authorized immutable viewer. The example's history is explicitly module/sample-scoped through AuditReads, not a substitute for private account history or an Audit mutation UI. See [Audit](../../../runa/handbook/modules/audit/README.md).                                                                                |
| Admin: AdminOperations 1.0.0 and trusted framework bridges                                                                                               | `/admin` owns diagnostics, lifecycle/recovery/work administration and global settings. Trusted Admin/framework integration is not a generic application dispatcher. Reference pages consume their owning API; no generic health/integration editor, copied recovery engine or privileged backend bypass is added. See [Core operation coverage](../../../runa/handbook/modules/admin/operations.md#core-operation-coverage). |

Bulk foreign-subject edits, multi-module aggregate business edits and genuine
external integrations remain outside this sample's domain. Native settings/protected
work already show transactional Core participation. The credential has no external
effect. Logs, queue internals, registry locks, recovery engines and private grant/
identity resolvers are implementation details, not interactive extension APIs.
Lifecycle/health, scheduler/event and production-exclusion examples are operational
or noninteractive by nature; their CLI/backend reproduction and owners above are
intentional, not missing controls. No current Core module is omitted from the matrix.
