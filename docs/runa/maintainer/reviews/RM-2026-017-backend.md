# RM-2026-017 backend, module and PostgreSQL review

Point 5 source review, 2026-11-06, on the active roadmap branch after point 4.
Gate B selected no backend business/schema repair. This review retains that
disposition and records two source-comment corrections, not a new runtime feature.
The [active roadmap](../roadmaps/active/RM-2026-017-platform-quality-and-workflows.md)
owns scope and final acceptance. This record is private owner engineering evidence.

## Grounding and limits

Knowledge status was current: 211 documents, 11,715 chunks, no changed/deleted
sources, missing/orphan chunks, interrupted write or branch/parser/metadata drift.
Bounded current-intent retrieval for module transactions, Audit, concurrency and
ownership returned Auth security, Audit and Auth identity guides. Opened the
relevant original contracts; also refreshed database, native transactions, public
API and security guidance. Boost application-info reports PHP 8.4, Laravel 13.37.0
and PostgreSQL. No new framework API or dependency was introduced.

Source walks below are bounded examination of existing owners and regression
scenarios. They do not establish whole-application correctness, measured query
performance, exhaustive security coverage or successful test execution. Test names
and assertions are source evidence; point 12 must obtain native results through
supported owner commands. No broad checks ran during this point.

## Owner and consumer dispositions

| Boundary                            | Source examined                                                                                                                                                                      | Disposition and final obligation                                                                                                                                                                                                                                                                                                                                                                    |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Native transaction root             | `src/runa/Support/Modules/Data/ModuleTransactions.php`; owning transaction guide                                                                                                     | Retain source ownership checks, bounded definitions, duplicate resource/proof refusal, preflight admission, registry generation, authority/subject locks, all-owner validation before writes, transactional proof consumption and bounded result projection. Refusal hooks run after root rollback and failed required history fails closed. No new generic transaction service or nesting adapter. |
| Auth ownership                      | `modules/auth/backend/Support/{EntryTransaction,TransactionSecurity}.php`, `Actions/ProfileMutation.php` under `src/runa`                                                            | Retain canonical sorted subject locks including retained identities; locks do not grant eligibility. Profile revisions remain owning validation, with one apply and invalidated result after refusal. Entry transport retains its own root/savepoint protocol rather than being mechanically migrated.                                                                                              |
| Permissions ownership               | `modules/permissions/backend/Support/AccessTransaction.php`, `Actions/AssignmentMutation.php` under `src/runa`                                                                       | Retain own assignment state, reviewed revisions, guarded Auth eligibility and fresh password proof. No foreign Auth models/SQL in AssignmentMutation. Root-only independent refusal history remains distinct from success Audit. Keep last-authority and concurrent removal regressions.                                                                                                            |
| Admin composition/HTTP              | `modules/admin/backend/Actions/AggregateMutation.php`, `Http/{Controllers/AggregateController,Requests/AggregateRequest}.php` under `src/runa`                                       | Retain guarded API dispatch, bounded request input and AdminResource projection. Admin coordinates native owners and rechecks the page fingerprint before writes; its section-idle proof does not replace the sensitive participant proof. Correct the obsolete point-number delivery comment only.                                                                                                 |
| Audit persistence/read              | `modules/audit/backend/Actions/{RecordEvent,RecordAttempt}.php`, `Storage/AppendEvents.php`, `Queries/ReadEvents.php` under `src/runa`                                               | Retain exact default PostgreSQL connection, transactional event/entity inserts, independent non-success entry only after root rollback, safe DTOs and server-owned actor context. Read summaries omit payload trees, batch entity references and count before cursor. Sort identifiers come from the validated AuditQuery contract. No measured index/query defect selected.                        |
| Optional actor enrichment           | `src/runa/Integration/Core/AuthAuditActor.php`, `Providers/CoreIntegrationServiceProvider.php`; AuditActorResolver                                                                   | Actual optional binding already exists; Auth unavailability falls back to the trusted actor. Correct the comment saying this is future work. Audit still has no Auth dependency. No new public extension entrypoint.                                                                                                                                                                                |
| Startup, HTTP and queue maintenance | `src/runa/Foundation/Application.php`, `Runtime/{MaintenanceState,RuntimeHeartbeat}.php`, `Http/Middleware/EnforceEffectiveMaintenance.php`; worker listeners in RunaServiceProvider | Retain native plus derived maintenance and real-daemon-only heartbeat context. Health probes cannot make a stopped worker fresh. No evidence justifies changing the IDE-open Application.php. Cold boot, HTTP refusal and reused-worker runtime results remain final verification.                                                                                                                  |
| Logs and CLI/worker context         | `src/runa/Logging/{SafeLogProcessor,QueueLogContext,ConsoleLogContext}.php`                                                                                                          | Retain allowlisted bounded diagnostic fields, static secret-safe messages, exception replacement, UTC and context cleanup. Queue identity excludes serialized arguments; CLI identity excludes command arguments. No log-payload broadening or new security exception.                                                                                                                              |
| Minimal App/reference               | `src/app/Providers/AppServiceProvider.php`, `routes/web.php`, reference `PingHandler`, `Definitions/ReferenceHttp`, `Models/Sample`                                                  | Minimal App registration and named root route remain. Reference consumes ModuleSettings and the module HTTP/model primitives; it is a development example, not a required production business module. Existing source/runtime/delivery tests remain acceptance owners.                                                                                                                              |

Paths grouped as modules above are relative to `src/runa`; they are source pointers,
not claims that every implementation in those directories was reread line by line.

## Database object purpose and retention

Read-only Boost catalog inspection of the configured development connection found
46 ordinary tables: shared 12, Auth 22, Permissions 10 and Audit 2. No ordinary,
partitioned, view, materialized-view or foreign relation was returned for public
or runa_admin. No business data was read. Grouped rows below enumerate all 46
tables; purpose follows owning migration names, canonical database/module guides
and the baseline inventory, with focused source walks above. They are retention
dispositions, not proof of every index's selectivity or every workload's cost.

| Schema           | Objects                                                           | Purpose / retained owner                                                                                                                       |
| ---------------- | ----------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| shared           | migrations, application_migrations, platform_seed_runs            | Separate platform/application migration ledgers and platform initialization history; not disposable unused domain tables.                      |
| shared           | jobs, failed_jobs                                                 | Native queue and failed-job protocol. Epoch queue moments are the documented framework exception to timestamptz.                               |
| shared           | modules, module_registry_state                                    | Retained module identities, source/settings/availability state and global coordination generation.                                             |
| shared           | module_transitions, module_update_steps                           | Fenced lifecycle transitions and reserved update-step history.                                                                                 |
| shared           | module_work                                                       | Encrypted bounded work payloads, queue status, leases and event delivery deduplication.                                                        |
| shared           | application_settings, module_settings_snapshots                   | Optimistic application presentation settings and source-owned saved module settings drafts; live-name partial uniqueness is intentional.       |
| runa_auth        | users, entry_tokens                                               | Durable identities, normalized identifier claims and single-use entry lifecycle transport.                                                     |
| runa_auth        | sessions, trusted_devices, login_windows                          | Revocable operational authentication and bounded abuse windows.                                                                                |
| runa_auth        | password_history, step_up_proofs                                  | Private password reuse prevention and session-bound operation/section proof state.                                                             |
| runa_auth        | avatar_files, secondary_emails, email_changes, profile_mail       | Private profile resources, address ownership/change workflow and private delivery.                                                             |
| runa_auth        | preferences, delivery_preferences                                 | Typed preferences, aggregate revisions and category delivery routing.                                                                          |
| runa_auth        | security_notices, notice_frequencies, known_browsers, notice_scan | Reference-only notice outbox, delivery frequency, browser/reminder cursor and scan coordination.                                               |
| runa_auth        | policy_versions, policy_exceptions                                | Immutable policy source versions and bounded account exceptions.                                                                               |
| runa_auth        | operator_tokens, initialization                                   | Hashed trusted operator setup/recovery and initialization coordination.                                                                        |
| runa_auth        | development_fixtures                                              | Owned development seeding evidence; not a production capability.                                                                               |
| runa_permissions | permissions, roles, presets                                       | Source dictionaries and owned custom roles/presets.                                                                                            |
| runa_permissions | role_permissions, preset_roles, preset_permissions                | Same-module definition membership pivots.                                                                                                      |
| runa_permissions | user_roles, user_permissions, user_access                         | UUID-referenced Auth assignments and monotonic aggregate revision; no cross-module FK.                                                         |
| runa_permissions | development_fixtures                                              | Owned development seeding evidence.                                                                                                            |
| runa_audit       | events, entities                                                  | Append-only public event identity and same-schema event/entity references; absence of mutable business timestamps/soft deletion is deliberate. |

The bounded catalog diagnostic returned zero violations for all four predicates:

- Existing FKs in these schemas use RESTRICT on delete and update.
- Existing columns named id are bigint identity.
- Existing public_id columns are non-null UUID with a valid unconditional
  single-key unique index.
- Core module FKs do not cross schema ownership.

Nineteen tables have public_id. This does not mean every table needs one, nor does
the UUID type alone prove UUIDv7 row values. Focused original CREATE review covered
Auth users, Permissions user_roles, Audit events, shared module_work and
module_update_steps. ModuleSchema supplies exposed module identity; user_roles
explicitly opts out and retains same-schema RESTRICT plus UUIDv7 subject checks.
Users retain normalized claims, positive optimistic/security revisions, UTC moments
and soft deletion. Work retains lease/status/payload constraints; update steps have
the module/step uniqueness consumer. Audit indexes have time, actor, module/action,
action/outcome and correlation read consumers in ReadEvents; entity reads are
batched. Existing indexes are retained without claiming measured usefulness for
every cardinality. No EXPLAIN ANALYZE, index removal, migration replay, data reset
or database write was needed or performed.

## Regression and version assessment

Existing source scenarios cover the retained invariants:

- `tests/backend/runa/modules/admin/EntryTest.php`: native stale revisions,
  duplicate participants/proofs, root refusal history failure and invalidated owner
  results; aggregate HTTP review/save, either owning Audit failure direction,
  consumed-proof rollback and independent PostgreSQL lock competition.
- `tests/backend/runa/Feature/PermissionsTest.php`: grants without a superadmin
  bypass, stale reviews, Audit rollback, last eligible authority and concurrent
  removals. `AuditStorageTest.php` and `AuditCaptureTest.php`: root/savepoint
  atomicity, refusal boundaries, redaction, authorized reads and suppression.
- `QueueMaintenanceHeartbeatTest.php`: real paused daemon, unchanged queued work,
  non-refreshing probes and stopped-worker context. `StructuredLoggingTest.php`:
  arbitrary-context redaction, nested scope restoration, HTTP and CLI identity.
- Existing ModuleApi, ModuleReference, ModuleData, DatabaseOwnership and
  ModuleUpdates feature suites own ingress, example, storage and update acceptance.
  No new duplicate test of comment text was added.

The two low-severity editorial findings are recorded as point-5 addition A-09:
obsolete future delivery claims in AggregateController and AuditActorResolver.
Both comments now describe existing source behavior. No executable token, public
signature, permission, schema, data or consumer changed. Admin remains 1.0.0,
Audit 1.1.0, Auth 5.0.0 and Permissions 2.1.0; guarded API versions remain
independent and unchanged. No semantic release or central changelog entry is
justified for comment-only edits. Platform 0.8.0 candidate/module-range alignment
remains the later candidate-preparation obligation, not an early version bump.

Public guides already describe native transactions and optional actor enrichment
correctly. Owner AGENTS/skills and the separately authored customer bundle need
no new rule or copied review checklist. No new reusable capability was introduced.
Only this private review, the roadmap disposition and the two owning comments need
synchronization. Existing data, inherited browser endpoint state, reports and shared
caches remain preserved; no task Docker resources were created or removed.
