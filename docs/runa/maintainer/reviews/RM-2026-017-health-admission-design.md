# RM-2026-017 health publication and operation admission discussion

Date: 2026-11-08. Source baseline: 3e837518, with runtime code e69a3b5f.
Status: proposed correction, awaiting owner decision. The owner chose to discuss
the native activity refusal before manual acceptance. That authorizes this source
investigation and review, not a change to the health/admission premise. No runtime
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
