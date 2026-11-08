# RM-2026-017 — Reference settings publication prerequisite

## Approved direction — 2026-11-07

After discussing the initial proposal, the owner explicitly approved the corrected
problem statement and repair direction and requested a detailed roadmap plan.
This section supersedes the initial proposal and its pending approval below.
It authorizes the common mechanism and migration direction, not a claim that the
implementation/public signatures or final verification already exist.

### Discovery and diagnosis

During manual frontend acceptance the owner found that reference's Ping card and
widget did not demonstrate enough of Runa's customer-facing capabilities. The
owner defined reference as executable documentation, requiring actual audited
input changes, independently permissioned fields/actions and a password-protected
operation. Designing its settings editor exposed the framework integration gap
documented below: safe settings publication exists, but its authorization/domain
publication path is specially wired to Auth.

The likely historical explanation is that Auth was the first supplied consumer
with settings. That remains an explanation, not an independently verified claim
about the originating roadmap. The demonstrated current-source issue is enough
to justify correction; historical roadmaps/tags remain immutable.

### Responsibilities and boundaries

| Owner                  | Responsibility after the refactor                                                                                                                                                                                                                                                                          |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Module framework       | Common setting declarations/types, validation orchestration, secrets, revisions/conflicts, source/generation identity, native publication/recovery phases, source-owned authorization/publication extension and reusable security orchestration. Reuse existing history, operation evidence and snapshots. |
| Settings-owning module | Its setting definitions, cross-field business rules, declared read/write grants and action-proof requirements, effects and domain-specific history. A module without settings need not declare a provider.                                                                                                 |
| Auth                   | Password verification, identity/session-bound proof lifecycle and its own policy-domain consequences, including exception invalidation/forced changes. Auth's settings consume the common contract.                                                                                                        |
| Permissions            | Current action grants through its public API; availability, identity eligibility and record Policies remain independent.                                                                                                                                                                                   |
| Audit                  | Atomic history/redaction through its public API and approved capture contracts. Framework coordination does not become a second Audit engine.                                                                                                                                                              |
| Admin                  | Public presentation and command/review composition over owning APIs; no separate publication/business engine or settings storage.                                                                                                                                                                          |
| Reference              | An ordinary App-owned consumer of these same supported contracts, with runnable UI/source/tests that teach customers how to integrate each current Core module.                                                                                                                                            |

Module isolation remains mandatory. Module consumers communicate through supported
public APIs and never import foreign private Actions/Queries/Models or access
foreign tables. Framework mechanics must not retain Auth-specific class dispatch
or become the owner of password verification/grant resolution/Audit persistence.
If atomic coordination needs a reserved bridge, document its public source
contract, restricted callers, lifecycle and source validation; a broad public
namespace alone grants no authority. No new infrastructure authority is requested.

### Required execution behavior

- Establish applicable admission/health evidence before locks that prohibit it;
  declaration discovery is pure. Do not start external checks or side effects
  inside publication merely because a callback is available.
- Validate source, settings revision/generation and candidate in the native
  protocol; check current identity, grants and operation proof under the correct
  authority/session/registry locks. Record the actual lock order before code.
- Consume a required one-use password proof in the same default PostgreSQL
  transaction as candidate publication and mandatory Audit. Failure rolls all
  three back. Preserve multi-stage apply/recovery semantics: a later application
  failure uses that native recovery protocol, not a fictional all-stage rollback.
- Derive authority from trusted server context. Caller-supplied actor metadata
  never authorizes HTTP publication. Dedicated trusted CLI/operator execution
  remains separate and supported; jobs never inherit browser proof.
- Retain read/write and field-specific grants, safe error/projection behavior,
  secret set-state, replacement/clear semantics, bounded payloads, refusal after
  grant/proof/source/connection changes, and no automatic write replay.
- Standardize framework-level history/snapshots without duplicating storage.
  Keep Auth policy values/consequences and required domain effects Auth-owned.
  Secret values never enter snapshots, returned history or notifications.
- Replace the special Auth authorization/domain-participation dispatch with the
  common supported source contract. Do not add the new generic route while
  preserving the old Auth route through an adapter or double-consuming proof.

The earlier optional `SettingsAuthorization::authorizing(Connection,
SettingsContext): void` sketch is useful source evidence, not the entire solution.
Before runtime edits, point 10a records complete extension/security/publication
signatures, source placement, supported consumers and invocation phases. Existing
SettingsParticipant/SettingsPublication and public Auth/Permissions/Audit contracts
are assessed together. Avoid a generic callback registry, arbitrary caller-supplied
classes or a second settings store/runner. Necessary coherent Runa API corrections
follow the standing pre-1.0 policy; a genuinely new significant premise returns
to discussion rather than being inferred from this approval.

### Ordered delivery and acceptance

The active roadmap now owns five bounded implementation points:

1. **10a — Framework:** complete native common contracts/orchestration, source
   checks, authoring documentation and framework/security regression scenarios.
2. **10b — Auth:** migrate onto that contract, remove obsolete special routing,
   preserve policy effects/data/recovery and update consumers/tests/versions.
3. **10c — Reference backend:** implement settings, audited sample editing,
   field/action grants, action-specific proof and managed-work dispatch/status
   through public APIs, with application-owned success/refusal tests.
4. **10d — Reference UI:** deliver the coherent customer journey using shared
   Admin/Auth/UI presentation and typed public seams in both languages/themes.
5. **10e — Coverage/readiness:** reconcile the current Core capability/source
   matrix, synchronize current guidance and prepare real current assets/data,
   authorized access and concrete owner acceptance scenarios.

Each implementation point includes its own affected documentation/test authoring,
module/API version assessment and coherent commit/checklist synchronization.
Only necessary small focused diagnostics belong there. Point 11 remains the
penultimate owner manual frontend acceptance and point 12 the complete selected
automated verification/release. No earlier broad suites, image/scanner pipeline
or installation acceptance is introduced by this amendment.

Feature/0.8.0, existing branch and roadmap lifecycle remain unchanged. No early
VERSION bump, dependency addition, reset, production deployment or release is
approved by this planning checkpoint. Preserve existing settings/credentials,
reference rows/identities, native reports, caches and resources. Existing owner
SIGSEGV evidence-continuity procedures remain unchanged.

## Historical initial assessment and proposal — superseded

Source assessment, 2026-11-07, baseline `e6be09a3`. The owner has approved the
executable reference repair and maintenance obligation. This record proposes one
additional public integration premise; it is not approval or implementation.

### Initial demonstrated boundary

`ModuleSettings::update` owns an independent runner and refuses any ambient
transaction. It validates a candidate, opens its default PostgreSQL publication
transaction and invokes the global SettingsAuthorization participant before
storing the transition and candidate. Core AuditParticipation delegates that
authorization to Auth PolicyHistory only for `runa.auth`. Other module settings
providers have no documented owner authorization callback at this point.

Admin's independent Command Action admits a writer under AdminEntry, closes that
transaction and invokes the writer. This deliberately preserves independent native
settings/work protocols. Command metadata advertises password requirements but
does not consume an owning operation's proof. A reference writer must enforce
its own current grants and proof in the actual settings publication transaction.
Calling AuthStepUp before ModuleSettings::update would consume proof separately
from settings/Audit, and wrapping update in a business transaction is refused.
Neither is an acceptable example for customers. Importing Auth PolicyHistory,
AdminOperationalAuthority or private settings engines is also prohibited.

### Initial proposed owning integration

Extend the existing settings runner to support an optional source-owned
SettingsAuthorization implementation on the already selected SettingsProvider.
Reuse the existing signature:

```php
public function authorizing(
    Illuminate\Database\Connection $connection,
    Runa\Contracts\Modules\SettingsContext $context,
): void;
```

Owner: module framework. Audience: trusted module settings-provider authors.
Supported entrypoint: the native runner invokes this method; HTTP consumers never
choose a callback/class or pass a connection. Implementation home:
`src/runa/Support/Modules/Settings/ModuleSettings.php`; module implementation in
its `backend/Definitions` or an owned Action selected by that provider. Runtime:
ordinary native settings publication only, with no dependency on Admin.

Invoke it inside the default publication transaction after validated candidate/
safe changes are available, before transition/candidate writes, in addition to
existing mandatory global authorization/Audit. No settings values or secret
candidate are added to SettingsContext; sensitive changes stay `replaced=true`.
The reference provider uses supported guarded Permissions/Auth APIs to assert
current declared grants and action-specific proof. Proof consumption, publication
and synchronous Audit then roll back together. Trusted CLI/operator behavior
must remain explicit and distinct from authenticated HTTP handling; no request
can select system authority by supplying actor metadata. Existing Auth behavior
must not consume proof twice or change merely because this hook becomes available.

Before implementation, assess public import/source-check classification, existing
settings admission and authority/lock ordering, runtime descriptor coverage and
Framework API version implications. Add focused regression source for same-
transaction proof/Audit rollback, permission loss, missing proof, safe secret
context, repeated publication, existing Auth behavior and trusted CLI behavior.
The reference remains a consumer of this supported integration rather than a
special-case platform exception. Do not add a second settings store, wrapper,
observer-based authorization, startup shim or callback parameter.

### Initial decision request — resolved by the approval above

This makes an existing reserved settings-authorization contract available as a
module-owned source extension, changing a public authoring/behavioral premise.
Under the active roadmap's significant-unknown rule and AGENTS, obtain the owner's
explicit decision before implementing it. The already authorized reference repair
does not silently approve a new platform extension seam. Keep point 10a open;
manual acceptance and final verification remain blocked by unfinished repair.

At this checkpoint only roadmap, maintenance guidance and this concrete design
are recorded. No runtime/configuration/dependency/schema/data change or test/build
execution is claimed. Existing example data, assets, reports, caches and resources
are preserved.
