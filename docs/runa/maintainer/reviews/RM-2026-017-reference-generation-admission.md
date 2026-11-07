# RM-2026-017 — Reference settings generation and snapshot admission

## Owner-approved correction — 2026-11-07

The owner approved the proposed correction after its detailed explanation and
comparison with the RM-2026-018 refresh-removal scope. That draft is now absorbed
into the active roadmap; its removal of passive frontend reads does not remove
required backend health/security/conflict checks. The decision stop is resolved;
complete exact contract/lock design before further runtime edits. No final test
PASS, point completion or release is inferred.

Point 10c is in progress. Application-owned sample editing and protected work
dispatch use the existing native protocols. Settings and snapshots exposed a
framework assumption that the previous Auth consumer (scheduled health) did not
exercise. Do not complete 10c or proceed to 10d before resolving it. No development
data reset, new package, platform version preparation or production operation occurred.

## Source-verified findings

`src/runa/ModuleFramework/ModuleHealth.php`, `inspect`, increments the singleton
registry generation whenever an on-use check publishes an observation, even if
its successful result and source are unchanged. `ReferenceHealthPolicy` uses
hybrid health. Each normal guarded reference request therefore advances that
generation; this is not merely a timing issue.

`ModuleSettings::readAuthorized` returns the current global generation.
`ModuleSettings::update` admits access, field, proof and Audit operations before
comparing the caller's expected generation inside publication. Reference settings
read/review/publication invokes real required on-use checks. Consequently, the
read generation is stale by the next review/publication, without another actor
or settings change. Existing exact generation checks correctly refuse; guessing
a later number or automatically retrying the write is not an acceptable repair.

The application settings review rejects changed revision/generation and does
not expose credential values. Freezing the diagnostic test clock did not remove
the conflict because observation publication always increments generation.

`SettingsSnapshotSection::run` admits the owner outside its transaction and
repeats that operation inside it. For hybrid health, the second call attempts
an independent check under registry/authority locks and refuses. A trial native
HealthAdmissions scope demonstrated this boundary, but was withdrawn: opening
an unconditional new scope would conflict with the existing Admin prepared-read
scope. No alternate snapshot runner or private framework import is retained in
application source.

## Approved owning correction direction

Keep global generation as operational evidence; do not remove health invalidation
without assessing availability consumers. Introduce or reuse a native semantic
settings review identity covering source/declaration identity, current settings
revision and actual availability/security state. Publication must still admit
exact health dependencies before locks, recheck source/current grants/proof under
locks, and reject genuine changes. Refreshing equivalent healthy evidence must
not alone make deliberate publication impossible. No guessed generation, automatic
write retry, caller-selected authority or cached authorization is proposed.

Correct snapshot admission in the existing framework adapter for standalone and
Admin-prepared consumption. Reuse the native admission protocol, including the
nested-read boundary, exact Auth proof/Audit calls and expiry checks. Keep one
snapshot store and publication runner. Assess whether the generation parameter
can retain its documented semantics or requires a coherent pre-1.0 replacement;
update current consumers and tests together.

The owner has approved this generation/admission correction direction. It is not
a second roadmap activation or an already delivered public signature. Record the
chosen concrete design before code, including affected APIs, complete signatures,
lock ordering and Framework API/module versions. Existing exact checks are replaced
coherently only where required by that design; availability, field grants, current
identity, proof and atomic Audit remain mandatory.

## Bounded diagnostic evidence

Supported entrypoint: `php artisan runa:test:backend
../tests/backend/app/modules/reference/CoreExamplesTest.php --parallel
--processes=1 --filter=...` in the development PHP terminal. These focused
implementation diagnostics are not the final backend suite.

- Public source-boundary assertion passed after replacing reserved principal
  construction with PublicReferenceRule.
- Audited sample edit, monotonic revision, stale write and deleted-record refusal:
  one case / eight assertions passed.
- Protected dispatch with distinct proof, replay/expiry/revocation, automatic
  sample Audit rollback and explicit dispatch/queue/proof rollback: three selected
  cases passed in the five-case diagnostic.
- Exact settings review failed with `reference_settings_stale` (409) immediately
  after an authorized read; frozen-clock reproduction retained the failure.
- Snapshot capture first failed with `module_unavailable` for `settings.snapshots`
  under the transaction. Trial admission repair exposed the generation conflict
  (409) on a current capture too.
- Earlier failures corrected controller dependency placement, schema-qualified
  unique validation using the model, and identity-column-safe fixture restoration.
  Native failures remain failures; no full verification result is inferred.
- Supported cleanup removed only `runa_test_test_1`. Initial sequential diagnostics
  used base `runa_test` and retained their test fixture data; subsequent diagnostics
  used disposable workers. Development data, caches, backups and reports were
  preserved. No task-created Docker resources exist.

Application-owned tests retain unexecuted or failing scenarios for verification
after repairs. Current source is unfinished; reference manifest 0.4.0 is a candidate,
not an installed development release. UI remains the prior Ping page/widget until
10d. Manual acceptance and final verification remain points 11 and 12.
