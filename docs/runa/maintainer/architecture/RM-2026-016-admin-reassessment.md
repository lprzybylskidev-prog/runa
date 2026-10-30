# Admin design reassessment before platform 1.0

Review date: 2026-10-30. Owner-requested review from the beginning of
[RM-2026-016](../roadmaps/active/RM-2026-016-admin-panel.md), following the owner's
explicit rejection of compatibility-driven design during platform development.
This is a source-grounded review and proposed correction, not approval of a new
transaction/security architecture or a claim that point 3 is complete.

## Evidence and disposition

Reviewed the active roadmap, original architecture review, all four Admin contract
guides, committed point-2 entry/diagnostics/dispatch and current uncommitted point-3
declarations/discovery/projection/tests. Checked the relevant owning Auth profile,
step-up/entry transaction, Permissions assignment preview/access transaction/state,
framework API/declaration/guard/availability/health and frontend shell/component
sources. Module/global settings and management boundaries were checked against the
published operation matrix and ownership guides. No full verification was run.

Knowledge was current before point 3 (199 documents / 9,597 chunks). At this
reassessment it is stale because of the current documentation edits; focused
original sources, rather than stale retrieval, ground the findings below. Three
focused checks in the active roadmap remain evidence for their exact current
worktree scenarios; they do not establish architectural completeness.

Earlier points 1–2 remain historical completed work. Point 3 is open and uncommitted.
Its proposed 0.2.0 development version/no-op edge is part of the held worktree,
not a released module. The owner policy has changed; neither earlier approval nor
focused tests require retaining a deficient design.

## Findings requiring design correction

### R1 — High: atomic Save is designed around unchanged old write entrypoints

[The composition contract](../../handbook/modules/admin/composition.md) mandates
unchanged Auth/Permissions signatures, owner savepoints, a before/after and
requiresBefore/invalidates graph, and assignment-before-profile ordering. Current
[AccessState.user](../../../../src/runa/modules/permissions/backend/Support/AccessState.php)
includes identity_version in the snapshot used by
[ChangeAssignments.preview/apply](../../../../src/runa/modules/permissions/backend/Actions/ChangeAssignments.php).
[ManageProfiles](../../../../src/runa/modules/auth/backend/Actions/ManageProfiles.php)
changes that version. Re-invoking the old assignment operation after the profile
write therefore invalidates the originally reviewed fingerprint. The specification
works around this coupling with a particular order instead of making owner
operations participate in a coherent transaction protocol.

Recommendation: revise the owning Auth/Permissions transaction APIs and shared
trusted participation together. Prepare the complete declared command set, acquire
canonical locks, validate every owner revision/review against the same locked
pre-write snapshot, then apply owner mutations and Audit under one root commit.
Both standalone and aggregate execution should use the same owning implementation;
Admin selects declared participants and orchestrates, without private foreign
SQL/models or a parallel business engine. Avoid a generic dependency graph unless
actual supported participants require it. Independent runner/external-effect
exclusions remain valid technical boundaries.

This changes an approved architecture premise. Exact revised signatures and
proof/authorization/revision semantics must be agreed before runtime work resumes.
It does not authorize dropping review, reusing a consumed proof, validating only
once outside locks or silently replaying writes. ARCH-003 remains in force.

### R2 — High: health-driven composition exclusion is an engine limitation

[OperationGuard.enter](../../../../src/runa/ModuleFramework/Api/OperationGuard.php)
requests operation-specific availability.
[ModuleHealth.inspect](../../../../src/runa/ModuleFramework/ModuleHealth.php)
refuses executing applicable on_use/hybrid checks inside a transaction. The
composition matrix therefore excludes those participants even if their actual
business mutation is transactional. Entry and native authorization also invoke
nested guards, so merely calling a health check once before the outer transaction
does not solve the underlying admission protocol.

Recommendation: design shared native admission in two explicit phases: actual
required health work outside the business transaction; a non-request-controlled,
source/operation/graph-bound admission context revalidated with availability,
expiry, maintenance, revisions and authority under root locks. Reuse it for
standalone and aggregate operations. Do not skip an on-use check because a generic
cache is fresh, or add an Admin-only bypass. Keep diagnosis and external health
execution outside the root. This is an architectural proposal requiring agreement,
not permission to weaken health/maintenance semantics.

### R3 — High: lookup and presentation wire contracts are incomplete

In [declarations](../../handbook/modules/admin/declarations.md), ManagementRead and
AdminReadQuery have no field/lookup identity, while a section can contain several
reference fields. A single options method cannot identify the requested field.
The worktree Projection.read responds by refusing all options if any lookup is
unreadable. That prevents disclosure but also blocks a separately authorized
lookup; it is a workaround for the missing contract.

ManagementPage.component is validated but absent from the Page wire shape.
Module component keys lack a wire build/manifest identity corresponding to the
existing module component registry. Source file presence alone cannot establish
that the browser's compiled component belongs to the current declaration. Widget
bindings return ManagementRecord, which cannot supply the widget wire's
observed_at/expires_at metadata except by inventing an unrelated values convention.

Recommendation: revise these still-unreleased DTOs directly: explicit field-bound
options requests/results, complete page/component projection with a build identity,
and an explicit widget result carrying safe values and observation freshness.
Carry these corrections into point 4–5 and the generator before their consumers
are authored. Do not add aliases to preserve the new draft shapes.

### R4 — Medium: unavailable optional bindings discard unrelated pages

The worktree [ManagementPages.collect/binding](../../../../src/runa/ModuleFramework/Management/ManagementPages.php)
validates every binding's target availability before admitting the owner. A single
unavailable optional target throws; discover discards all that owner's pages and
widgets. Independent pages that do not use that target disappear as well. This
conflicts with the intended independent-page/section failure isolation.

Separate structural/source-invalid declarations from current target availability.
Check availability per actual page/section/widget binding; make required lost
participants a blocking form state and preserve unrelated readable pages. Keep
all-or-nothing Save and safe diagnostics. Add a case with two pages under one owner,
one using an unavailable optional dependency, rather than only the current
single-owner malformed-declaration fixture.

### R5 — Medium: the shell contract lacks entry advertisement and explicit session sharing

[Frontend specification](../../handbook/modules/admin/frontend.md) requires the
account-menu entry to be available before entry password confirmation; every
specified AdminAccess method already requires that proof. There is no specified
minimal entry-advertisement operation, nor a current Admin capability in Auth's
session/profile projection. This is a contract gap, not evidence of a current
unauthorized endpoint.

[AuthApplication](../../../../src/runa/modules/auth/frontend/AuthApplication.vue)
always creates its own useApplicationSession controller. The proposed Admin
application also owns one and expects the shell to share it, while the additive
Auth props list has no supplied session-controller input. Adding many navigation
props alone does not establish one session owner.

Recommendation: explicitly specify a minimal session-authenticated capability
read exposing only current Admin entry availability, without bootstrap data or a
proof bypass. Extract/share the session shell/orchestration directly in platform
presentation; let Auth/account/Admin compose it with one session controller.
Update actual facade consumers instead of maintaining the old AuthApplication
shape merely for compatibility. Keep account-menu-only entry and App router seams.

### R6 — Medium: global settings storage ownership is not finalized

[Operations specification](../../handbook/modules/admin/operations.md) makes
shared.application_settings a platform-provided technical aggregate with built-in
presentation keys, but assigns its migration to App and defers delivery ownership.
Required platform behavior cannot depend on an unspecified customer-authored
migration. Conversely, platform upgrades must not overwrite customer migrations.

Recommendation: let the platform own the technical store/migration and its
versioned installation path; App owns declared business/presentation definitions,
values and source assets. Record the implementation home and exact bootstrap/update
order before point 10. Keep global settings independent of Admin/module settings,
no runtime migration and source fallbacks when storage is unavailable.

### R7 — Medium: obsolete-contract preservation is encoded as a future gate

[The current compatibility tests](../../../../tests/tools/runa/contracts/test_admin_contract_specification.py)
freeze every baseline Core method signature, permission and API version. The
ledger is useful inventory and historical evidence, but treating it as a permanent
constraint would reject the direct refactors now authorized by the owner.

Replace blanket unchanged-signature assertions with tests of the reviewed current
contracts and real source/generated consumers as each interface changes. Preserve
unchanged public behavior when it still makes sense, not because the old test
prohibits improvement. Legacy metadata/resource declarations should have one
current authoritative representation after migration; assess whether both entity
and page representations still serve distinct consumers. An optional interface
or a source-owned lazy bootstrap callback is not inherently a compatibility shim.

### R8 — Low: unnecessary intermediate Admin version/history churn

The held point-3 worktree increments unpublished Admin 0.1.0 to 0.2.0 and adds an
empty declarations upgrade. Both states belong to the same unreleased creation
roadmap; no deployed 0.1.0 requirement was identified. Keep one development version
until the agreed creation release and add an update path only for real supported
installed states. This does not justify deleting migration/history already shipped
in actual releases or discarding durable operator/development data implicitly.

### R9 — Medium: diagnostics read too much before discarding it

Committed [AdminDiagnostics.module](../../../../src/runa/Integration/Core/AdminDiagnostics.php)
loads ModuleManagement.read only to return diagnostic codes. That read also loads
settings, integrations and active work. Output is filtered, so this is not a
confirmed secret disclosure; it is unnecessary coupling and work in the protected
read transaction. Expose the required source/declaration diagnostics directly from
the owning framework engine and consume that narrow result. Fixed dispatchers and
lazy source checks remain useful lifecycle/security boundaries, not legacy glue.

## Review of the whole implementation sequence

| Point                    | Disposition before resuming                                                                                                                                                                                                                                             |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1: contracts             | Revise the unreleased read/widget/component/lookup and transaction contracts; retire blanket baseline preservation. Keep the inventory and past evidence as historical facts.                                                                                           |
| 2: Core/entry            | Keep dependency direction, grants, session/action proof, maintenance fencing and lazy HTTP dispatch. Revisit narrow diagnostics and shared admission with the owning framework. Source-only edges for actually released predecessor modules remain assessed separately. |
| 3: discovery/projection  | Keep the current worktree as unfinished work. Correct R3–R4 and align registration/components/handler capabilities with the revised contracts; do not commit it as complete.                                                                                            |
| 4: shell                 | Define one shared session owner, minimal entry advertisement, actual component/build projection and App replacement seams before rendering.                                                                                                                             |
| 5: tables/forms          | Use field-bound lookups and owning bounded resource reads; avoid forcing read-only/widget adapters to implement unsupported write methods. Keep source-declared safe commands and existing table/form primitives.                                                       |
| 6: atomic Save           | Implement the revised owner-supported root protocol after agreement, rather than special profile/assignment ordering and compatibility exclusions. Preserve proofs, preview/Audit atomicity and refusal handling.                                                       |
| 7: Auth                  | Change native owner APIs/read projections where useful; update self-service and administrative consumers together. Do not add a duplicate administrative business implementation to avoid touching Auth.                                                                |
| 8: Permissions/Audit     | Reuse the revised owning transaction machinery. Keep reviewed access/authority workflows, last-eligible-superadministrator protection and append-only authorized Audit reads.                                                                                           |
| 9: operations            | Add Audit at each real owning transaction/publication boundary. Native settings/lifecycle/work recovery and external-effect separation remain justified. Maintenance stays CLI-only.                                                                                    |
| 10: settings             | Finalize platform store ownership and installation/update order; App owns definitions/values/assets. Branding precedence/fallback remains ARCH-004.                                                                                                                     |
| 11: generators/docs      | Generate only the revised current contracts, with working App/module/replacement examples. Synchronize owner/customer consumption guidance and remove superseded paths.                                                                                                 |
| 12: verification/release | Keep final-only affected-area verification, baseline/hooks and existing milestone cadence. Update version assumptions from actual changed contracts; do not promote platform MAJOR implicitly.                                                                          |

## Recommended agreement

Keep the functional scope, ARCH-002 maintenance decision, ARCH-003 stronger
security workflows, ARCH-004 branding ownership, dependency/foreign-persistence
boundaries and feature/0.7.0 projection. Approve revising ARCH-001 so owning
Auth/Permissions/framework APIs can directly support coherent shared transactions
and admission, rather than wrapping unchanged operations. Then rewrite the exact
contracts and affected implementation points before further runtime changes.

The owner's pre-1.0 authorization already resolves compatibility permission. The
remaining agreement concerns the newly identified transaction/admission architecture;
it is required by AGENTS roadmap execution's significant-unknown rule, not by
old interface preservation. No security-policy relaxation is proposed.

## Instruction products and verification scope

The root owner contract and release workflow now record the owner's standing
pre-1.0 authorization. The owner backend skill no longer mandates additive-only
Admin integration. The public module version guide explains the owner-only Core
scope; customer application/module approval and protected-source restrictions
remain unchanged, so the customer AGENTS product requires no policy change.
Its separate backend consumption guidance continues to require implemented public
entrypoints and owning Policies; point-3 additions remain in the held worktree.

This review changes no runtime architecture and runs no broad gate. Documentation
links, roadmap open state and staged-content hooks are the proportionate checks
for recording this review. Future contract/runtime corrections belong to the
revised active roadmap, not to a separate active roadmap or an unapproved release.

## Owner disposition — 2026-10-30

The owner explicitly approves rebuilding native Auth/Permissions transaction APIs
and framework admission while retaining current grants, stronger confirmations and
atomic Audit. This accepts the recommended architecture direction in R1–R2 and
resolves the implementation hold. Exact phased signatures are agent-owned work
under the active roadmap's new point 2a; point 2b records the related unreleased
read/presentation corrections before point 3 resumes. Earlier findings retain
their reviewed source state; this approval is not a runtime or verification PASS.
