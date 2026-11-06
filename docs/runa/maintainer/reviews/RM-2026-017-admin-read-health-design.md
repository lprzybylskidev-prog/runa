# Proposed Admin read health admission

Date: 2026-11-06. Roadmap: RM-2026-017, point 7.
Status: **PROPOSED — owner review required before implementation.**

The owner authorized preparing and presenting this design, not implementing it.
Gate B and feature/0.8.0 remain approved; point 7 stays open. This proposal extends
that point's scope rather than activating another roadmap. It changes no source
contract, runtime, data, dependency or release version by itself.

## Problem and verified cause

The reference module's guarded Ping succeeds outside Admin, but the native Admin
section read returns 503 `module_unavailable` after successful entry, password
proof and the inspect grant. The focused regression reached 16 assertions before
that failure; cleanup succeeded. This is recorded in the active roadmap and
`tests/backend/runa/modules/admin/EntryTest.php`.

The relevant source chain is:

1. `modules/admin/backend/Queries/Access::read()` calls `AdminEntry::read()`.
2. `Integration/Core/AdminEntry::run()` opens the default-connection transaction,
   locks the registry and authority, and checks current entry/password proof.
3. Projection resolves the owning adapter; ReferenceReader invokes guarded Ping.
4. `ModuleHealth::inspect()` must run reference.read's hybrid check, but refuses
   to execute check code inside an existing transaction.

Dashboard widgets execute their adapters inside the same entry boundary. Fixing
only the reference section would leave that analogous consumer broken.

Existing `Transactions/HealthAdmissions` solves preparation for native mutations:
it records exact-operation health evidence before locks and revalidates its
source/policy/expiry inside one root. Its current map is reusable throughout that
root. Applying that map directly to arbitrary Admin reads would let two separate
public calls use one health check. The current health guide and ModuleHealthTest
explicitly require checks for each separate on-use/hybrid operation, even with
fresh stored observations. That shortcut is not this proposal.

## Recommended decision

Add source-declared, bounded health-call plans for Admin read handlers, with
**one prepared admission per public API invocation occurrence**. Execute health
checks outside the business-read transaction; execute the real adapter/API reads
inside the existing locked Admin transaction. Keep the existing guarded entry,
permission, proof, owning Policy and result-projection checks.

This adds a small optional public metadata extension and a private read mode to
the existing admission machinery. It does not add another business runner, HTTP
protocol, authorization service or persistent cache. Existing native mutation
admission retains its current meaning and tests.

The explicit timing decision is that a call's health check runs during bounded
preparation immediately before the locked read, rather than at the method-body
boundary inside the transaction. Every planned occurrence gets its own check;
expiry/source/policy/generation are checked again before use and before returning
protected results. This is the same preparation/locked-execution distinction used
by native mutations, without their unrestricted within-root reuse across distinct
public invocations.

## Proposed source extension and ownership

The following is a proposed complete signature, **not a delivered API**:

```php
namespace Runa\Contracts\Modules;

interface ManagementReadPlanProvider extends ManagementPageProvider
{
    /** @return list<ManagementReadCall> */
    public function readPlan(
        string $binding,
        string $capability,
        ManagementRead|ManagementOptionsRead $input,
    ): array;
}

final readonly class ManagementReadCall
{
    public function __construct(
        public string $module,
        public string $contract,
        public string $version,
        public string $method,
    ) {}
}
```

The framework owns these declarations in `src/runa/Contracts/Modules`; trusted
module authors implement the optional provider in their own backend definitions.
Independent App providers can implement the same interface through the existing
frozen AdminRegistry registration. Runtime discovery remains through existing
`declarations.management`; there is no new manifest declaration root or request
parameter. This is a source extension, not a guarded business API.

`capability` is exactly `read`, `page`, `options` or `widget`. The options capability
requires ManagementOptionsRead; the others require ManagementRead. `binding` must
be an existing binding belonging to that provider. Every call must match one of
that binding's declared ManagementApiTarget module/contract/version/method tuples.
Existing module ownership and dependency validation still applies.

The returned list describes exact call occurrences in execution order, including
any applicable nested guarded calls. Duplicates mean separate calls and separate
checks. The initial bound is 128 occurrences per selected handler invocation;
existing page/widget/provider/result bounds continue to apply. Invalid, unknown,
foreign or over-limit plans fail closed. No wildcard call, PHP callback, business
argument, credential, result value or proof token appears in a plan.

The method is pure source planning based on the already validated input. It must
not execute a business handler/API, perform IO or mutate state. Providers are
trusted application code, as existing source declarations are; this is not a
sandbox against malicious source authors. Plans are not serialized to the browser.

The reference provider's selected read/page/widget plan contains exactly one
call: app.reference, Ping, 1.0.0, read. The adapter and Ping business signature stay
unchanged. No private engine import is added to the reference module.

## Execution sequence

1. **Authorize and select metadata.** Perform current Admin session, entry,
   proof, page/section/field admission and input validation through the existing
   entry boundary. Select the owning source binding/capability and immutable call
   plan, without constructing or running a business handler. Capture the current
   principal and semantic source/declaration/availability identities. End this
   short transaction before any health check. Do not discover hidden owners or
   bypass cached unavailability merely to obtain a plan.
2. **Prepare health outside transactions.** Validate the plan against the exact
   declared API definitions and method-to-operation mappings. Execute applicable
   on-use/hybrid checks separately for every listed occurrence and its required
   graph, retaining existing applicability, deadlines, advisory locks and safe
   observation publication. Do not invoke the target business methods. A declared
   method is not permission to run it; no grant or eligibility decision is cached.
   Successful health publications can advance generation. After preparation,
   recheck the selected semantic identities and capture the resulting generation;
   never adopt unrelated source/flag/plan changes as part of preparation.
3. **Execute under existing locks.** Open the existing default-connection entry
   transaction and retain the registry, authority and session/proof lock order.
   Recheck the principal, source plan, pinned generation, all entry/field grants
   and current password proof. Only then instantiate the owning read handler.
   Execute normal guarded API calls and normal result projection in that root.
   Each actual public invocation claims its own prepared occurrence; its repeated
   internal availability validations may use that occurrence's evidence. A second
   or nested public invocation needs a distinct occurrence. All current operation
   authorization and record Policies still run inside the normal guarded call.
4. **Validate and dispose.** Before response acceptance, verify generation,
   source/declaration and health evidence expiry, plus the current entry checks.
   Return only currently readable projected values. Dispose the read scope in
   `finally` on success, refusal, rollback or exception, including call frames and
   unused evidence. The next read/request/worker operation starts with no evidence.

The gateway (`ModuleApis::invoke` / OperationGuard), not application code, owns the
invocation frame. ModuleHealth may reuse only evidence belonging to that active
occurrence and its exact required-graph evaluation. A nested invoke cannot inherit
its parent's evidence as a new call. Evidence contains no authorization result;
source/policy/expiry matching alone never authorizes an API invocation.

Existing guard-only internal validations within one invocation are distinct from
another public call. The implementation must audit those boundaries explicitly;
keying the new mode only by module/operation is insufficient. Private admission
helpers remain internal under ModuleFramework, with trusted Admin integration
under Integration/Core. Framework-owned entry/proof calls are prepared by that
integration itself, never by a customer-supplied plan. Existing bootstrap activity
helpers join the same admitted entry operation; they must not accidentally start
another root or relax the separate guarded-call accounting.

## Boundaries and failures

- Apply the path to record, page and options reads and dashboard widget execution.
  Page metadata, entry advertisement, independent writes, native aggregate Save,
  operational work and synchronous configuration writes keep their own protocols.
  Post-write response reads must use the repaired read boundary without replaying
  the write. Refreshed results do not imply that a prior write can be retried.
- A provider without the optional plan keeps its existing supported reads when no
  applicable per-use checks need preparation. An unprepared on-use/hybrid call
  remains refused. No implicit one-call guess is made from all binding targets:
  targets can contain methods used only by writes or another read capability.
- A deterministic plan is required for health-sensitive guarded calls. A handler
  cannot discover an arbitrary new call under locks and ask the framework to run
  its check inside that transaction. Such a call or an extra repeated occurrence
  is refused before its owning implementation executes. An author can supply the
  correct input-based plan or compose a supported owning read operation with
  private helpers; it cannot manufacture an unlimited-call plan. Unused optional
  occurrences are discarded, never transferred to another handler/widget/request.
- If the cached discovery already hides an unhealthy owner, keep the existing
  scheduler/CLI recovery route. This repair does not introduce broad diagnostics,
  source loading behind unavailable owners or an Admin maintenance bypass.
- A health refusal for a selected section retains a safe unavailable response.
  Individual widget failures retain the existing localized unavailable widget
  projection with no protected values. Prepare widgets as separate handler plans,
  preserving that failure isolation; one failed optional widget must not turn all
  otherwise valid Admin bootstrap data into a failure. Identity, authority or
  source/generation changes affecting the root still refuse the response.
- Expiry, source/policy/plan change, disconnect/reconnect or rollback invalidates
  the corresponding scope; no ticket is refreshed inside locks. Refuse explicitly
  and require a new read. Do not silently retry the handler or extend deadlines.
- Preserve the hybrid policy and its required check frequency, registry/authority
  locks, PostgreSQL connection, native Audit and mutation protocol. No second
  connection, request-wide cache, new health mode, longer TTL, disabled test or
  transactional-check exception is proposed. Health's existing observation writes
  remain technical health publication, not business mutations from the read API.

## Alternatives considered

| Variant                                                                         | Disposition                                                                                                                                      |
| ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| Reuse one module/operation health result throughout the whole Admin request     | Reject: a second distinct public invocation can skip its own required check.                                                                     |
| Run hybrid checks inside the existing entry transaction                         | Reject: violates the explicit independent-boundary/deadline/publication contract and holds application locks during check IO.                    |
| Move adapter/business reads outside entry locks and only authorize before/after | Reject for this repair: changes the current protected-read consistency/authority guarantee.                                                      |
| Change reference to scheduled health or read PingHandler/settings directly      | Reject: conceals the integration defect or bypasses the owning guarded API.                                                                      |
| Exact occurrence planning with prepared checks and locked reads                 | Recommend: preserves call frequency and existing protected-read transaction, with an explicit authoring contract and refusal of unplanned calls. |

## Version, documentation and instruction impact

No version changes occur during design. If approved, the optional public framework
extension warrants an additive Framework API MINOR assessment, proposed 1.1.0,
with all version-checking consumers/constants reconciled coherently. Existing
^1.0.0 consumers remain compatible; an example requiring the new extension should
state ^1.1.0. AdminAccess guarded API 1.0.0 and Ping 1.0.0 retain their complete
signatures. Admin's unreleased 1.0.1 correction and reference's unreleased 0.3.0
require review against the final implementation; do not bump every Core module.
The platform proposal remains feature/0.8.0.

Implementation must update the owning Admin declarations/examples, health and
transaction/public-boundary guidance, generated Admin example, reference guide and
central/module changelogs. Current examples must distinguish the optional plan,
exact call accounting and unavailable-owner recovery. Assess both owner and
customer backend/security selectors; route them to canonical guides rather than
copying the procedure into AGENTS. This proposal does not yet change delivered
handbook rules or customer instructions.

## Verification and completion plan

After approval, implement this as the point-7 prerequisite before further rendered
acceptance. Author regressions during implementation. Run only the narrow tests
needed to resolve concrete new boundary/accounting uncertainties there; complete
backend/frontend/health/security/consumer and version/source checks stay in final
verification, preserving roadmap order and owner manual acceptance.

Required regression contracts:

- Reference direct Ping and authorized native section/widget work; missing or
  revoked inspect/entry/proof denies access and does not expose response values.
- Each separate repeated/nested call and each separate widget gets its own health
  evaluation; fresh stored observations and a prior request do not reduce counts.
- Checks execute at transaction level zero; real business reads execute inside the
  locked default-connection Admin root. The caller's transaction state is restored.
- Source/API mapping/plan/generation/health expiry changes and identity/grant/proof
  withdrawal between preparation and execution fail closed. Unplanned or excess
  calls never instantiate the owning implementation.
- Record/page/options/widget plan selection is precise. A binding's write-only
  method is not admitted just because the same binding has a read capability.
- A failed optional widget leaves the other authorized bootstrap content usable;
  unavailable owners retain their supported independent recovery route.
- Failure, nested-call refusal, rollback and reconnect leave no evidence available
  to a reused worker or next handler. No automatic business read/write replay.
- Existing native mutation admission, scheduled/no-policy reads, App registrations,
  generated examples, independent commands, aggregate Save and post-write
  projections preserve their supported behavior.

No new runtime test was executed for this design-only task. The existing failing
native evidence remains a failure; this document is neither an implemented fix
nor an acceptance result. No database or Docker resource is created or removed.

## Owner decision requested

Approve or amend the exact-occurrence read-plan extension, its preparation timing,
fail-closed dynamic-call boundary and proposed Framework API assessment. Only an
explicit approval of the resulting design authorizes implementation. The prior
answer authorized this proposal and presentation only.
