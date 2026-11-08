# RM-2026-017 — Action-driven refresh

Point 10h, 2026-11-07. This is implementation/source-review evidence, not owner
acceptance, environment readiness or final automated verification.

## Owning correction

The owner-approved absorbed specification replaces passive business-data freshness
with action-driven reads and backend write admission. The public forms guide owns
the reusable rule. Initial/explicit/filter/sort/page/preview and own-action reads
remain; detected identity/access loss still disposes protected state immediately.
No dependency, timer, cache, coordinator, schema/migration/reset or production
change is introduced.

| Owner                | Source-verified change                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Auth account         | Remove focus/visibility/pageshow listeners and their trailing-read flag/busy watcher. Retain explicit refresh coalescing and operation/conflict reads. Freeze the profile submission version before asynchronous operation admission.                                                                                                                                                                                                                             |
| Application session  | Keep session checks, BFCache revalidation, activity/expiry and 401/restriction suspension. Advance authorityRevision only for initial admission or actual account/session/restriction changes. Fetch optional shell profile/entry options on identity entry, not every same-session check. Preserve reauthentication's safe draft lifetime.                                                                                                                       |
| Account preferences  | Ignore successful same-account session observations when the snapshot is ready/in flight; explicit connect/refresh and restricted-flow recovery remain. Only stable native stale reasons cause conflict snapshots. Business 409s keep their refusal. The readiness helper belongs to the existing store implementation, not a second session owner.                                                                                                               |
| Delivery             | Submit the displayed delivery revision and frozen recipient values. Remove the pre-write read/current-token substitution. Existing queue, backend revision checks and local FormConflict resolve actual conflicts; writes are never replayed.                                                                                                                                                                                                                     |
| Admin                | Same-session checks no longer advance the authority stamp and therefore do not cascade through bootstrap/page/resource reads. Remove resource visibility reads. Recognized stale/removal reasons refresh the affected resource rather than every section through the page mutation counter. Independent form writes refresh their owning section; aggregate refusals reconcile their participating form. Own committed actions retain existing page invalidation. |
| Application settings | Classify actual stale reasons independently from HTTP 409. Reconcile current application settings after a revision refusal while retaining its specific error, draft and local overlap resolution.                                                                                                                                                                                                                                                                |
| Public presentation  | Remove focus/visibility/popstate listeners and successful session/step-up triggers. Retain initial hydration, own application publication and actual identity transitions, including fencing/coalescing a publication overtaking a pending read.                                                                                                                                                                                                                  |
| Reference            | Existing native HTTP controller already recognizes stable stale/missing reasons and retains original draft tokens. Add a two-view regression and an actual two-user/session HTTP regression; no new reference runtime capability.                                                                                                                                                                                                                                 |

Security listeners were inspected separately: useProtectedActivity still checks
protected-place expiry on visibility/pageshow; useApplicationSession retains
expiry and session checks. Modal focus, decoration and HTTP document-navigation
cancellation listeners retain their independent purposes. A security check's
successful same-session result does not initiate business/presentation reads.

## Backend consistency review

Source findings below are distinct from runtime PASS. Laravel Boost identified
Laravel 13.37.0 and supplied version-aware pessimistic-lock/transaction guidance;
the actual native Runa implementation and PostgreSQL contract remain authoritative.

| Native source owner                                             | Admission retained / existing regression owners                                                                                                                                                                                                                                                                                                                                                                               |
| --------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Auth ChangeIdentity / Profiles / EntryTransaction               | Identity-version checks under owning user/authority/registry locks; normalized unique claims, uniqueness-error mapping, policy/current authority and atomic Audit. AuthIdentityTest covers duplicate creation, stale lifecycle changes, restoration and competing unique claims.                                                                                                                                              |
| Auth DeliveryRouting / AccountPreferences                       | Supplied preference revision is checked with the owning locked account; recipients/current capability and mandatory delivery are independently validated. The frontend prefetch was the demonstrated gap, corrected here. Native preference/delivery coverage stays with AuthEntryTest.                                                                                                                                       |
| Auth BulkUserMutation / BulkSessions                            | Revalidate current admission and lock selected users; compare current versions, security/preference state, related effects and protected accounts with the exact reviewed fingerprint before apply. AuthEntryTest's stale bulk revocation/Audit cases and PermissionsTest's selection integration remain relevant.                                                                                                            |
| Permissions ChangeDefinitions / ChangeAssignments / DeleteRoles | Validate referenced live source-owned entries, versions, exact preview fingerprints, uniqueness, role/preset references, confirmation, current grant/proof and native atomic Audit. PermissionsTest covers stale references/previews, protected definitions and rollback.                                                                                                                                                     |
| Admin Aggregate / AggregateMutation / Projection                | Current participant set, revision and reviewed effects remain native admission requirements. Frontend business-409 classification does not weaken owning writes.                                                                                                                                                                                                                                                              |
| App EditSample / ReferenceToolsHandler                          | Native sample lock/version/Policy, unique code validation and Audit; settings review fingerprint and semantic review identity are checked by the owning publication. CoreExamplesTest covers stale/deleted records and rollback; its new two-user case checks exact stale/missing HTTP codes. Sample removal has no public endpoint: the removed-state fixture is explicitly SQL setup, not an invented delivered capability. |

No backend admission gap requiring a new concurrency mechanism was demonstrated.
No backend runtime implementation or version/check bypass was added. Full native
backend and two-session rendered acceptance remain unverified in this point.

## Diagnostic evidence and remaining scope

All calls used supported owner/application entrypoints. These bounded diagnostics
resolve specific trigger, draft/token, refusal-classification and fixture questions;
no full suite, broad browser matrix, static analysis, asset/image build, scanner
pipeline or quality graph ran.

- Owner test-frontend, four selected files / action-driven, account, delivery and
  presentation names: initial result 1 failed / 9 passed / 155 excluded. The new
  cascade assertion incorrectly counted independent /admin/activity security
  checks as business reads; corrected the fixture response and assertion.
- Repeat with account-boundary names: 19 passed / 146 excluded, exit 0, cleanup 0.
  Proves same-session security checks do not fetch bootstrap/page/resources,
  optional profile/options or preferences; actual 401 still clears protected data.
- Owner delivery/application-settings/preference diagnostics: initial 3 failed /
  6 passed / 125 excluded. Corrected the operations test helper to use real error
  envelopes and replaced the obsolete frontend-preflight expectation with one
  backend-refused write carrying the original revision. Repeat: 9 passed /
  125 excluded, exit 0, cleanup 0.
- Owner Admin action-driven diagnostics: 5 passed / 25 excluded, exit 0, cleanup 0.
  Includes non-stale business 409s and removed-target resubmission refusal.
- Application reference two-view diagnostic: first failed before any write because
  fixture codes violated the real code grammar; corrected codes. Repeat: 1 passed /
  11 excluded, exit 0, cleanup 0. Two independent controller identities retain old
  snapshots, submit original revisions, reconcile stale state without replay and
  stop editing a removed target. Transport is simulated; this is not two-browser
  real-backend acceptance.
- Application backend `runa:test:backend --filter='keeps two independent users snapshots'`:
  native exit 1 / cleanup 0. Preparation failed at CoreExamplesTest's initial login
  with HTTP 503 before entering the new case. Scoped worker cleanup list was empty.
  This supplies **zero execution coverage for the new backend case**. Do not claim
  native two-user admission PASS. Preserve the failure and rerun after the explicit
  combined test/runtime preparation in 10i or final point 12; do not reset storage,
  advance runtime metadata or bypass guards to hide the readiness limitation.

Native browser reports/screenshots remain under .runtime/artifacts/playwright;
the Vite generated-dependency dynamic-import warning remains unsuppressed.
Final selected focused results after formatting are recorded in the roadmap.

## Documentation and version assessment

Update public forms/loading/API, Auth account/preferences and Admin frontend
contracts, current acceptance references, owner/customer AGENTS and frontend form/
performance references in the same point. The customer bundle was assessed as a
separately authored instruction product, never authority for protected edits.
Vue/security/backend selectors already route to the relevant canonical owners;
no duplicate procedure or new skill is needed. Existing changelog historical
entries retain their dated claims; current guides replace obsolete passive rules.

This completes compatible frontend corrections within the existing unreleased
Auth 6.0.0 / Admin 1.1.0 candidates. No further module version or source-only update
edge is required. Framework API 2.0.0, AuthPolicies 4.0.0, Audit 1.1.1, Permissions
2.1.1 and reference 0.4.0 candidates remain unchanged; public controller signatures
and HTTP contracts remain. Platform VERSION stays 0.7.0 with approved feature/0.8.0
projection. Runtime/assets alignment belongs to 10i, owner acceptance to 11 and
complete automated verification/release to 12. No task-created Docker resource was
removed; active services/data, Qdrant/model/pnpm caches, backups, reports and recovery
state remain preserved.
