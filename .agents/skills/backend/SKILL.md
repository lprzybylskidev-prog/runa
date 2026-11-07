---
name: 'runa-backend'
description: 'Runa backend engineering guidance for application structure, failure design, dependencies, transactions, side effects, performance, and maintainability. Load for non-trivial backend implementation/review after reading the active roadmap.'
---

# Runa Backend

When adding a Runa module or customer-facing capability, apply the owner
[executable reference obligation](../../../docs/runa/maintainer/development/reference-module.md)
in the same point. Trace the working example through public APIs and owning tests.

For opt-in Admin generation and independent App pages, follow
`docs/runa/handbook/modules/admin/examples.md`. Review exact overwrite hashes,
singular settings-provider ownership and generated business/presentation boundaries;
generation never installs modules or grants permissions.

For Admin readers using on-use/hybrid APIs, select the exact-call read-plan
contract in `docs/runa/handbook/modules/admin/declarations.md#read-health-plans`.
Keep planning in owned source and consume normal guarded APIs; no private engine
imports or cached authority/availability substitutes.

Use this skill for backend architecture and implementation choices that are broader than Laravel syntax.

For module settings, follow [native settings publication](../../../docs/runa/handbook/architecture/module-settings.md).
Use the documented semantic review identity for deliberate publication and snapshot capture; keep operational generation and current authorization checks distinct.
Declare access/proof requirements and owning participation on the single settings
provider; use authorized projections for HTTP and retain the independent native
runner, atomic proof/publication/Audit and native recovery.

For lists, metadata/bootstrap, authorization intersections and settings/preference
snapshots, review repeated reads beyond Eloquent relations. Follow
[read-path performance](../../../docs/runa/handbook/architecture/application-architecture.md#read-path-performance):
measure query growth under real transaction nesting, inspect analogous consumers
and repair demonstrated amplification within authorized scope. Preserve current
authority, expiry and rollback/reused-worker freshness; do not defer a known
in-scope defect or introduce persistent permission caches.

For an explicitly application-wide review, inventory App/module, startup/HTTP,
worker/scheduler/CLI and frontend read owners. Report source, measurement and
regression coverage separately; selected Core pages are not whole-app evidence.

Before acting:

1. Read the active roadmap.
2. Inspect the relevant existing code/pattern.
3. Load only the reference file(s) needed.
4. Apply Runa project rules before generic upstream advice.

For public API, internal dependency, generator or source-layout changes, follow
[the public API boundary](../../../docs/runa/handbook/architecture/public-api.md).
Classify supported consumers and complete signatures before moving files; update
public consumers and owner/customer guidance together. Frontend replacement uses
supported facades/controllers without copied security logic.

For Admin declarations, adapters or cross-module forms, consult
[Admin contract status and ownership](../../../docs/runa/handbook/modules/admin/README.md)
and its composition contract. Specification-only APIs are not delivered entrypoints.
For owner work before platform 1.0.0, follow the standing change authorization in
AGENTS: refactor owning APIs when that simplifies the design and update current
consumers; do not preserve an old signature by adding a compatibility workaround.
Optional page declarations and frozen App bootstrap registration follow the
canonical declaration guide; registration grants no authority. Keep owning record
Policies and guarded reads, and respect the documented write/rendering delivery status.

## References

- `references/design-principles.md` — responsibilities, abstractions, dependencies, naming, hidden behavior
- `references/failure-side-effects.md` — failures, retries, side effects, time/randomness, transactions
- `references/performance-refactoring.md` — query behavior, performance, refactoring boundaries

For Laravel-specific framework behavior, also load `../laravel/SKILL.md`.
For database-specific design, load `../postgresql/SKILL.md`.
For security-sensitive work, load `../security/SKILL.md`.

Reusable test support and application tooling discovery are documented in `docs/runa/handbook/testing/strategy.md`; compose the delivered helpers rather than importing private platform cases or copying lifecycle logic.

## Shared workflow

Follow [shared skill workflow](../../../docs/runa/handbook/ai/skills-policy.md#shared-point-workflow)
for source ownership, instruction-product assessment, supported commands and final-only broad verification.

For standalone or aggregate module writes migrated to native phases, follow
[owning module transactions](../../../docs/runa/handbook/architecture/module-transactions.md).
Use the source-owned participant contract and current API versions; Admin does
not wrap old whole writes or own foreign business validation/persistence.

For owner final backend verification, follow
[verification evidence continuity](../../../docs/runa/maintainer/testing/strategy.md#verification-evidence-continuity)
and its temporary SIGSEGV procedure. Keep native results and coverage limits;
this owner-only policy does not change customer recovery.
