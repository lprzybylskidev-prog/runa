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

## Concrete native contract and lock design — 2026-11-07

Owner: module framework; audience: trusted module authors and operator CLI.
Implementation stays in `Support/Modules/Settings` and the private settings/health
engines. This is a native API correction, with Framework API 2.0.0. No new table,
runner or dependency is required. AuthPolicies advances to API 4.0.0 and Auth
6.0.0 (the standing pre-1.0 policy permits replacing its existing signatures).
The not-yet-delivered ReferenceTools API remains 1.0.0, app.reference 0.4.0.

Complete changed public signatures:

```php
ModuleSettings::read(string $module): array
ModuleSettings::readAuthorized(string $module): array
ModuleSettings::update(string $module, array $patch, int $expectedRevision,
    string $expectedReview, ?SettingsActor $actor = null): TransitionResult
SettingsSnapshots::capture(string $module, string $scope, array $keys,
    string $name, int $revision, string $reviewId): string
AuthPolicies::preview(array $patch, int $revision, string $reviewId): array
AuthPolicies::publish(array $patch, int $revision, string $reviewId): array
ReferenceTools::reviewSettings(array $patch, int $revision, string $reviewId): array
ReferenceTools::publishSettings(array $patch, int $revision, string $reviewId,
    string $review): array
ReferenceTools::captureSnapshot(string $name, int $revision, string $reviewId): array
ReferenceTools::deleteSnapshot(string $publicId, int $revision, string $reviewId): array
```

Both settings reads retain `generation`, `revision`, `settings` and add `review_id`
(a lowercase SHA-256 identity). It binds the target module/revision, exact settings
provider/handler source and declarations, the registry's module/source/install/
enabled/dependency identities and fences, and normalized health evidence
(fingerprint, result, code, fresh/expired status). Equivalent evidence renewals
exclude checked/expiry timestamps; crossing expiry or changing result invalidates
the identity. Global generation remains unchanged as an operational fence. Identity
is recalculated from the locked snapshot, never cached across requests. It carries
no grant or proof: current identity, field grants, proof and health are independently
rechecked on every deliberate publication. A genuine settings revision always
invalidates even if values are restored. Admin revision strings become
`revision:review_id`; CLI replaces `--generation` with `--review`.

Publication acquires the independent runner lock, resolves declaration identity,
admits exact module/field/Auth/Audit operations outside transactions, then captures
its operational generation **after** those checks. It takes the registry row lock,
authority lock and Auth subject locks in the existing order. It compares revision
and semantic identity under those locks before proof consumption, rechecks source,
principal, grants and expiry, and retains native atomic publication/Audit and
restore/recovery. Transition history records the actual locked operational generation.

Snapshot adapter uses one native admission scope for standalone entry. It prepares
owner read plus write/Audit and Auth step-up operations (delete only) before locks.
Within an existing native mutation or Admin prepared-read scope it reuses that
scope and its exact-call evidence; missing/expired/unplanned evidence still refuses.
It never opens a second root scope under locks. Registry then authority locks,
current read/write grants, proof, storage and Audit remain in the same transaction.
Nested result reads reuse the same admitted scope; final availability/connection/
principal/expiry fences apply before commit. Admin's prepared-read call boundary
is retained rather than replaced by blanket admission.

Current consumers, source signatures, tests, CLI documentation and both instruction
products must be reconciled before 10c is complete. Only focused diagnostic cases
needed to establish these corrections run here; complete area suites remain 12.
