# RM-2026-017 health publication and operation admission discussion

Date: 2026-11-08. Source baseline: 3e837518, with runtime code e69a3b5f.
Status: owner-approved correction, 2026-11-08. The owner chose to discuss
the native activity refusal before manual acceptance. The subsequent owner decision explicitly authorizes implementation of the discussed
behavior; the source analysis below preserves the pre-implementation evidence. No runtime
source, database, account, health policy, lock, guard or retry behavior changes here.
Point 10t remains open; point 11 is not ready. Feature/0.8.0 projection is unchanged.

## Confirmed observations and source mechanism

The [icon readiness record](RM-2026-017-icon-readiness.md) retains two delayed-read
failures and a third ordinary-timing failure. Session/Admin activity receives
availability_changed/503 while reference samples refresh. Other ordinary runs pass.
This is an intermittent real readiness defect, not an icon rendering exception or
proof that all concurrent operations fail. No screenshot/test PASS resolves it.

Source establishes the following mechanism:

1. ReferenceHealthPolicy declares hybrid writable_runtime health. Core Admin,
   Auth, Audit and Permissions declare no special health checks.
2. ModuleHealth::inspect executes applicable per-use checks even when a previous
   successful observation is fresh. It writes the observation and unconditionally
   increments shared.module_registry_state.generation under its singleton lock.
   That includes a healthy-to-healthy renewal with only timestamps changing.
3. ModuleRegistry::snapshot uses generation for freshness/cache invalidation;
   ModuleAvailability evaluates actual source, flags, fences, dependencies and
   applicable health, then returns that global generation.
4. OperationGuard::enter captures it before authorization; assertCurrent refuses
   any later difference as availability_changed. ModuleHttpGuard/Controller retain
   the context across middleware/controller admission. AdminEntry additionally
   captures/rechecks its context around protected reads and proof checks.
5. Therefore a successful reference observation can invalidate an unrelated
   admitted Auth/Admin context if its publication falls between context capture
   and the next guard check. The publication and later locked transaction use the
   same singleton, but those locks do not cover the earlier unlocked ingress gap.
6. The frontend protected-activity owner treats failed renewal as an access/
   availability loss and invokes its existing withdrawal path. That explains why
   an apparently harmless refresh can remove the expected controls.

This is source-verified eligibility for the race, supported by actual HTTP failure
records. The exact failing transaction and generation values were not traced in
those older browser runs. Do not claim captured database causality or rule out an
additional defect. Since Core declares no checks, an own Core health renewal is
not the source-supported explanation for these samples. Relevant parallel reference
or scheduled health publication is the concrete candidate to reproduce next.

Original sources inspected: ModuleHealth, ModuleAvailability, ModuleRegistry,
Api/OperationGuard, Api/ModuleHttpGuard, Api/ModuleHttpController,
Support/Modules/Api/ModuleApis, Integration/Core/AdminEntry, Auth ManageSessions/
EntryTransaction, useProtectedActivity and ReferenceHealthPolicy. Current public
health guidance explicitly says every observation publication advances generation;
this is a delivered premise, not merely a missing import or local icon repair.

## Proposed behavior for owner agreement

A successful renewal of the same applicable healthy state must update diagnostic
freshness without, by itself, invalidating otherwise valid in-flight operations.
Fresh observation data and operation admission validity need distinct meanings.
An operation must still refuse an actual relevant availability/security change.

Retain required exact-operation on-use/hybrid evaluation and its independent
transaction boundary. Do not replace per-use checks with a fresh cache, request-wide
skip, extended TTL or disabled check. Preserve current-time critical expiry, failed
or invalid checks, source/registration/data compatibility, required dependencies,
settings/lifecycle/update reservations, maintenance, grants, session revocation,
proof expiry and Audit/transactional refusal. A healthy renewal cannot disguise a
failure/recovery transition or make an expired operation admission valid again.

Keep frontend withdrawal on real access/identity/proof loss. Do not swallow all
503 responses, blindly retry, replay writes, serialize browser business requests,
change activity cadence or move health policy to the client. The correction belongs
to the owning framework's publication/admission integration.

## Owning design direction and alternatives

Recommended direction: explicitly separate observation/cache freshness from the
identity/fences used to admit an operation. All consumers must receive fresh
observations; a newer timestamp alone must not mean a new authorization decision.
Do not simply remove ModuleHealth's generation increment: the registry currently
uses it to notice fresh observations, so that isolated edit risks stale data/cache
and expiry behavior.

Existing semantic settings-review and Admin-read identities are useful source
precedents, not APIs to wrap around the current failing path. Review their overlap
and update the owning primitives coherently. Whether the final representation uses
a separate internal revision or a captured semantic identity needs the full consumer
inventory and deterministic reproduction before implementation. This proposal does
not silently mandate a new database column, second registry or public signature.

Two incomplete shortcuts are rejected: suppressing generation failures in the UI,
and incrementing generation only on changed boolean health results while leaving
registry observation-cache invalidation unchanged. Neither repairs both meanings.
A global removal of generation checks would also discard real source/lifecycle/
settings and authorization fences and is outside the proposal.

## Proposed repair sequence in this active roadmap

After owner agreement to the behavior above, insert repair scope before readiness
10t, followed by the existing sole owner acceptance 11 and final verification 12:

1. Establish a bounded deterministic reproduction with two actual PostgreSQL
   connections: capture an Auth/Admin admission, publish a successful reference
   renewal in the relevant ingress interval, then exercise the native final fence.
   Record generation/health outcome and safe operation identities only. Include
   an ordinary HTTP overlap reproduction; distinguish reproduction from a model.
2. Inventory generation consumers across HTTP/PHP admission, transactions, Admin
   reads/actions, settings/snapshots, lifecycle, jobs/events, registry caching and
   recovery. Record the exact representation, owners, public signatures/version
   consequences and current-data preparation before implementing the owning fix.
   A newly discovered security/behavior premise returns to owner discussion.
3. Implement the agreed owning correction and matching current consumers, with
   meaningful regressions: successful renewal overlap proceeds; disable/source/
   settings/update/maintenance and critical health failure/expiry still refuse;
   authority/proof/session loss still withdraws; caches observe current results;
   failed-then-recovered state and prepared-call expiry cannot silently reuse an
   old admission. Reference examples/docs and both instruction products follow.
4. Commit the repair with necessary bounded diagnostics only. In 10t rebuild
   current assets and repeat real Nginx ordinary and controlled-overlap readiness,
   including drafts/proof and independent activity. Preserve failed evidence.
   Full affected suites/security/AI checks remain final point 12 after acceptance.

No separate roadmap or release-metadata change is proposed for this concrete
blocker of the active roadmap. Public API/module candidate versions require honest
assessment in the exact design; no compatibility adapter is authorized. No storage
reset, dependency update, recovery automation or exception is assumed. Existing
accounts/credentials/data, resources/caches/backups and evidence remain protected.

## Instruction and verification disposition

Owner backend/Laravel/PostgreSQL/security guidance was consulted for the review;
no Laravel API was invented or implemented. Current original module health,
transactions and public API guides were checked. Local knowledge was current at
entry (239 documents, 13584 chunks); focused retrieval was source-verified.
This private proposal adds no current reusable rule; owner/customer instruction
products remain unchanged until an accepted contract actually changes. Mandatory
hooks and incremental indexing apply to the discussion record. No diagnostic/test,
backend fix, broad verification or actual release is claimed by this document.

## Exact owning representation selected for 10s2

Owner accepted implementation on 2026-11-08. Keep registry generation as the existing
hard fence for meaningful state changes. Add internal health_revision bigint default
0, nonnegative, to the singleton's original CREATE, and healthRevision freshness metadata to the retained public
RegistrySnapshot value (fourth optional constructor argument, default 0). Every health
publication increments health_revision. Generation also advances unless publication
is exclusively a still-valid successful renewal with identical source/policy/check
identities. Registry cache lookup compares the pair; transaction locks and existing
write callbacks retain current ownership. No second registry or public context token.

Only existing, nonfuture, unexpired healthy checks with the same fingerprint/code
and successful new valid evidence qualify. Missing/failed/expired results and any
failed-then-recovered history advance generation, preserving ABA invalidation.
Availability's final observation comparison accepts only this same narrow renewal
relation and still rejects intervening generation/fence/source changes. Expiry of a
prepared call remains bounded by its original evidence; renewal never extends it.

Generation consumers reviewed: registry cache; ModuleAvailability; OperationGuard
and PHP/HTTP gateways; AdminEntry/AdminRead/ManagementPages/ModuleManagement;
ModuleGraph/Transitions/Updates; SettingsSecurity and settings publication; native
transaction runner/HealthAdmissions/ReadHealthScope; work/event/command adapters
and current generation-bound enqueue. All existing generation writers other than
neutral health renewal stay unchanged. Existing contexts, reviews and admissions
continue to capture the same hard generation; no signature change is needed there.
Cache revision is implementation-owned freshness metadata on the public snapshot value, never operation authority or a client token. Its complete additive constructor signature is documented in the owning guide.

Framework API 2.1.0 and current unreleased module candidates remain appropriate:
no public contract signature or owning module declaration changes. This platform
internal schema/behavior correction belongs to the approved feature/0.8.0. Runtime
preparation must reconstruct the identified singleton from its corrected original
CREATE, preserving the real generation and all source/slot/update metadata;
no compatibility ALTER migration, database reset, forged version or ledger is added.
Inspect/disclose exact ownership and scope before that operation. Preparation was required in 10s2 because route bootstrap and the native test identity guard already read the current registry schema.

## Implemented correction and bounded evidence — 10s2

The original two-PDO regression fails before implementation with native
OperationRejected at OperationGuard::assertCurrent (availability_changed), then
passes after the owning correction. The final focused run selects six real-connection
scenarios (neutral renewal, failure, failure/recovery, expired old observation,
changed policy and disable), graph renewal/ABA races, original exact-operation
expiry, the existing mid-evaluation failure and per-use/hybrid/scheduled policy cases.
It passes **13 cases / 73 assertions**, native exit 0, worker cleanup 0.
Command: `python3 tools/maintainer/test-backend --parallel --processes=1
--filter='fences actual state changes but accepts healthy renewal|accepts only neutral
health publications|never extends exact operation evidence|invalidates previously
evaluated dependents|runs every on-use and hybrid'` (one shell argument for filter).

ModuleAvailability accounts for the before/after generation of only its own
publications. An unexpected generation between evaluated graph members remains a
refusal even if all final booleans have recovered. Concurrent neutral publications
are admitted only through the same bounded HealthObservations comparison.
OperationGuard, authority/proof/session, maintenance and frontend withdrawal paths
are unchanged; no new dependency, retry or exception was added.

The first post-code run was blocked by the native base-test identity guard because
route boot already needed the corrected singleton column. Exact inspection of
runa/runa_test identified their matching owner roles, no incoming singleton FKs,
no active transition/reservation and singleton generations 1014/61. Under operation
and test locks, only shared.module_registry_state was reconstructed transactionally
using its corrected original migration down/up and restored singleton values.
Generation, slot order and reservation metadata were preserved; health_revision
starts at 0. Existing migration ledgers were retained without fabricated entries.
Preserved row counts before/after: local modules 5, global settings 1, users 15,
sessions 13, samples 1; testing 5/1/4/4/2. No database, module schema or volume reset.
This necessary operational preparation moved into 10s2; 10t observes the live runtime.

Intermediate added-test failures are preserved: exception assertions initially
looked for a safe reason in the deliberately generic exception message, and the
first graph fixture attempted nested process health deadlines. Corrected assertions
inspect the typed reason; the graph publication now occurs at the real pre-check
query boundary, outside either deadline. No runtime deadline behavior was weakened.
Raw evidence is under `.runtime/dev/RM-2026-017-10s2/`: reproduction-before.log,
reproduction-after-storage.log, focused-health.log, focused-health-corrected.log,
focused-health-final.log and storage-{local,testing}-{before,after}.json.

Current architecture/database/transaction guidance, executable reference narrative,
central changelog and both backend skill selectors are synchronized. Root owner and
customer AGENTS already delegate exact contracts to these guides and need no new
policy. Customer skills preserve the protected-engine boundary. Framework API 2.1.0
already is this roadmap's unreleased additive feature candidate; the snapshot's
additive freshness value is documented without new authority. No module declaration
or consumer contract change warrants a further module version increment. Platform
remains 0.7.0 with approved feature/0.8.0 projection. Current Nginx readiness, full
suites, final security/AI gates and owner acceptance are not claimed by 10s2.
