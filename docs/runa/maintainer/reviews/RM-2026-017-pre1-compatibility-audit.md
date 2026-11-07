# RM-2026-017 pre-1.0 compatibility audit

Source audit and implementation plan, 2026-11-07. Baseline:
e9afda313e444346cb34abebd826f2ff4ce34269 (the inventory records the exact Git
identity; see its baseline field). No refactoring, migration execution, database
reset, dependency update, image build, broad test suite or final gate is performed
by this audit. The owner explicitly requested this additional scope in the active
roadmap, before manual acceptance. No new roadmap is created.

## Result and scope

Ten actionable cases are identified below, with different confidence and risk.
They include confirmed historical scaffolding, concrete redundant representations,
a dependency-integration simplification candidate and current-guidance defects.
They are not ten independently demonstrated runtime failures. Sixteen retained
mechanism families explain why superficially similar code must remain.

The [inventory](RM-2026-017-pre1-compatibility-inventory.json) records all 46 source
migrations, 76 Upgrade classes, five upgrade providers/122 declared edges and
26 current API declarations, plus the 298 lexical candidate files. Source screening
covered 1,440 tracked first-party implementation/configuration files in src/runa,
src/app, tools, distribution, bin, Docker/Compose/devcontainer and hooks. Root
Artisan forwarding, current/previous release manifests, relevant tests, owner and
customer guidance and roadmap/release procedures were inspected separately.

The source scan is a discovery method, not a proof that every line or dynamic
execution path is correct. Recommendations were checked against owning source,
current consumers and documented supported behavior. The inventory distinguishes
case decisions from family screening; counts are not coverage or quality scores.
Third-party implementation was excluded except the installed Collision command
needed to assess P06. Vendor/generated assets, localization datasets, old runtime
reports and closed roadmaps are not refactoring targets. Historical evidence was
read where it establishes a decision or published baseline, not rewritten.

The scope covers all five requested areas: database migration structure; adapters
and wrappers; fallback/alternate execution; module contracts and consumers; related
historical debt. It also covers the instruction/roadmap cause analysis. Source
findings do not certify rendered UX, production safety or complete test coverage.

## Authority and actual baseline

AGENTS, releases.md#pre-10-compatibility and the database guide already authorize
necessary incompatible owner changes, migration editing/consolidation and identified
rebuildable development/test resets before platform 1.0.0. They do not require
retaining unpublished intermediate interfaces or implementations. Public module
boundaries, business/security invariants, exact-scope inspection, real durable data,
immutable tags/closed records, dependency approval and final verification remain.
An independent Core 1.x/6.x version does not revoke this platform authorization.

Published v0.7.0 source, read directly through Git, declares Auth 5.0.0, Audit
1.1.0, Permissions 2.1.0, Admin 1.0.0 and reference 0.2.0, with Framework API ^1.0.0.
The current candidate declares Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1, Admin
1.1.0 and reference 0.4.0, with Framework API ^2.0.0. Platform VERSION remains
0.7.0; the agreed feature/0.8.0 projection is unchanged.

This distinction matters: a documented real published installation/update contract
is different from dozens of intermediate development edges. The generic updater,
real recovery evidence and actual deployed/customer data must not be discarded to
remove empty classes. Conversely, published Git history does not require executing
all earlier source-only module steps on every new installation. Current source
can be simplified without editing the published tags.

## Recommended changes

### P01 — Consolidate the reference revision migration

- Location: src/app/modules/reference/database/migrations/2026_10_22_000001_app_reference_samples.php,
  up(); and 2026_11_07_000001_app_reference_add_sample_revision.php, up().
- Evidence: the first migration creates samples without revision; the second adds
  the bigint revision/default and positive CHECK. It is the only cross-file table
  alteration among all 46 migrations. The other ALTER TABLE statements add current
  constraints to a table created in that same file; they are not repair migrations.
- Simplification: put revision/default/CHECK directly in the original CREATE and
  remove the additional development migration. Reconcile actual example storage
  and its module ledger under the existing guarded reset procedure where necessary.
  Do not fake an applied migration or disable ModuleStepHistory validation.
- Consumers: reference initializer/model/EditSample, source/module fixtures,
  CoreExamplesTest and delivered example documentation. Production excludes this
  example. Preserve unrelated Core/application data when choosing the reset scope.
- Risk: medium operationally, low schema-design uncertainty. Existing migration
  fingerprints really will change; an ordinary migrate is insufficient.
- Recommendation: refactor. No new ALTER/data-transfer workaround is needed.

### P02 — Collapse historical built-in upgrade scaffolding

- Location: all five Definitions/_Upgrades.php providers and every Upgrade_.php
  listed individually in the inventory; ModuleUpdatePlanner::chain(),
  UpgradeDefinitions::steps(), ModuleUpdates::apply(), and ModuleSettings::upgradeDefaults().
- Evidence: 76 Upgrade classes, 64 empty run bodies, 122 declared edges. Auth alone
  declares 71 edges and 46 Upgrade classes. Presentation/copy/layout/test-layout
  changes frequently added a version edge with an empty handler. Fresh installation
  walks the entire declared chain, because null initially points at 0.1.0 rather
  than the current target. Metadata validation also loads historical declarations.
- Simplification: use existing UpgradeDefinition capability to initialize each
  module directly at its current version. Consolidate real initialization into its
  owning initializer. Keep only genuinely supported current data-update edges,
  recording their actual baseline/target and purpose. At minimum, preserve the
  documented published-baseline update requirement where it remains meaningful;
  do not recreate every unpublished per-point version as executable history.
- Preserve effects: Auth singleton rows, default handling and PolicyHistory baseline;
  Permissions system role/declaration synchronization; Audit store-ready history;
  reference welcome row; native preparation participation, source fingerprinting,
  transactions, version publication and non-idempotent recovery fences. The twelve
  nonempty Upgrade bodies are inventoried and must not simply be deleted. A small
  current source-only edge can legitimately publish a new version/participation;
  the finding is historical proliferation, not that every empty step is invalid.
- Consumers: first install, explicit forward preparation, external target planning,
  application deployment, module contract/version tests, generators/examples and
  API/database guidance. Rebuild identified dev/test ledgers coherently instead of
  adding a compatibility adapter for their intermediate state. Generic customer
  module upgrade support remains intact.
- Risk: high; missing an initializer effect could create an apparently installed
  but incomplete Core. Specify actual retained edges/effects before editing.
- Recommendation: refactor the built-in declarations/initializers. A new upgrade
  engine or initializer contract is not established as necessary by this audit:
  the existing planner already accepts a null-to-current definition.

### P03 — Remove flat browser preference compatibility keys

- Location: resources/js/lib/preferences.ts, definition()/savePreference();
  resources/js/lib/preferenceStore.ts, PreferenceDefinition.legacyKey/read().
- Evidence: built-in language/theme use both scoped versioned storage and old flat
  runa.locale/runa.theme keys. Guest writes update both; reads import the flat key
  when the scoped key is absent. This path exists specifically for the old layout.
- Simplification: use the current scoped guest store alone; remove built-in legacy
  key import/dual writes and the obsolete option if no supported current consumer
  needs it. Update useLocale/useTheme consumers/tests and current preferences docs.
  Do not introduce a second old-key reader or automatic import on account entry.
- Consumers: both layouts, signed-in/guest preference composition, public store
  types and tests/frontend/runa/unit/preferences.test.ts. Preserve current scoped
  values, guest/account separation, hydration guards and denied-storage memory.
- Risk: low/medium: an old flat-only browser preference falls back to current
  defaults. Document that intentional pre-1.0 behavior; never copy account values
  into guest storage or silently replay writes.
- Recommendation: refactor. Explicit consented guest-to-account adoption is a
  different feature and is retained (K04).

### P04 — Retire the built-in table-view v1 conversion

- Location: resources/js/composables/useTableViews.ts, definition.migrate;
  resources/js/lib/tableViews.ts; preference/table-view regression owners.
- Evidence: the delivered current definition is version 2, but an explicit branch
  converts version 1 and wraps its old shape as current. This is a concrete old
  internal-format path, unlike normal reconciliation of columns in current views.
- Simplification: accept the current built-in v2 shape; remove the v1 conversion
  and its compatibility-only fixtures. Invalid/old values should follow the existing
  safe default/reset behavior, without writing a success claim for an unexecuted
  conversion. Preserve valid current named/default views and dirty query state.
- Consumers: every Table/useTableViews consumer, guest and account stores, table
  preferences docs/tests. Keep generic PreferenceDefinition.migrate and backend
  PreferenceProvider migration extension: customers may have real current data
  evolution. Do not remove that boundary because this one built-in path is obsolete.
- Risk: medium UX/data-presentation risk, bounded to obsolete preference format.
- Recommendation: refactor the concrete built-in conversion; retain current-schema
  normalization, independent scopes and the generic extension contract.

### P05 — Remove the duplicate Button compact prop

- Location: resources/js/components/AppButton.vue, compact || iconOnly in class
  and busy-slot logic; AppPreferenceControls.vue and current Button callers.
- Evidence: compact and iconOnly have exactly the same implementation and defaults;
  the public components guide calls compact the compatible spelling. Existing
  consumers use both names. This is an actual alias, not two different sizes.
- Simplification: keep iconOnly, migrate all Button callers/tests/docs and remove
  Button.compact. Do not remove unrelated compact props: BooleanInput and density
  controls have their own semantics. Preserve accessible names and busy behavior.
- Consumers: shared preference controls, tables/dialogs and generated/customer
  examples; the full caller inventory is required in the implementation point.
- Risk: low, with visible UI regression potential. Current consumers must change
  together; do not leave an alias to preserve the removed spelling.
- Recommendation: refactor. Assess the public presentation signature honestly.

### P06 — Simplify the native test command's split integration

- Location: Testing/Backend/ApplicationTestCommand.php, handle()/parallelPest();
  Providers/DevelopmentServiceProvider.php test binding; TestRun and the application
  test Action/Python runner as consumers. Paths in this section are under src/runa.
- Evidence: sequential execution composes Collision and temporarily rewrites global
  $_SERVER['argv']; Pest parallel execution instead launches Pest directly with a
  separate option/environment translation. The installed Collision source reads
  raw argv and constructs ParaTest options, confirming the stated constraint.
  The shared runner already launches the native Pest engine for both modes.
- Simplification: redesign this owning Runa integration around a single explicit
  native-engine argument/lifecycle model, retaining php artisan test and supported
  owner/application entrypoints. Do not keep the argv mutation merely to preserve
  today's Collision execution path, and do not add another startup shim/decorator.
  Establish command/flag parity before implementation; a dependency change is not
  assumed or authorized by this audit.
- Preserve: native terminal output/exit/signal codes, filters/discovery, serial and
  parallel semantics, database guards, suite lock, worker cleanup and real supported
  coverage options. TestRun itself solves those current requirements and stays.
- Risk: high. This is a source-verified integration simplification candidate, not
  a proven native test failure. Do not claim the parallel branch is obsolete merely
  because it differs, or restore withdrawn SIGSEGV recovery automation.
- Recommendation: refactor the owning command after its exact design/parity review;
  retain the native engines and shared lifecycle. No runtime correction is made here.

### P07 — Retire discovery-only historical selector aliases

- Location: bin/check and bin/operations symlinks; bin/command;
  tools/application/command_cli.py, unknown-name discovery branch.
- Evidence: the two old multi-action names now only invoke --management list.
  They supply no operation beyond current Artisan discovery. Fixture/packaging
  inventories still explicitly require them.
- Simplification: remove those two historical aliases and their obsolete delivery
  assertions/docs. Keep the live single-purpose recovery transports and their
  dispatcher; bin/command also serves those symlinks, so deleting its implementation
  wholesale would be wrong. Current discovery remains ./bin/artisan --management list.
- Consumers: public command guide, host-executor fixture copy list, Python command
  contract inventory and operations/delivery assertions. Preserve dated migration
  maps as evidence rather than rewriting their history.
- Risk: low; deliberate removal of obsolete invocation names.
- Recommendation: refactor the retired aliases, not the cold/tool transport boundary.

### P08 — Replace JSON strings nested inside structured read payloads

- Location: Support/Modules/Settings/SettingsSnapshotSection.php: read(); Auth
  Management/CreationSection.php: password_policy; SubjectSection.php: addresses,
  sessions, devices and exception lists; PreferenceSection.php: categories/recipients/
  delivery. Consumers include Admin useAdminSettingsSnapshots.ts, AuthAdminCreation,
  AuthAdminEmails/AuthAdminUserSection, reference contracts.ts/useReferenceWorkspace.
- Evidence: these structured values are json_encode'd into strings and parsed again
  by TypeScript. Current ManagementRecord uses DeclarationRules::json recursively,
  and AdminRecord.values already accepts recursive AdminJson. ModuleApis also
  supports bounded nested arrays/readonly DTOs. The read boundary does not require
  preserving an old scalar-only representation. The historical reason for each
  encoding is not proven; the redundant current representation is directly verified.
- Simplification: change the owning read projections to bounded native JSON arrays/
  objects, update all real consumers and authored read-payload types together, and
  remove the old string decoder. Do not support both representations as a fallback.
  Keep SettingsSnapshotSection's native admission/locking/proof/Audit orchestration;
  the reference correctly consumes that public guarded boundary instead of private
  engines. Its concatenated revision token is a current generic resource token,
  not evidence of backward compatibility by itself.
- Exclusions: encrypted storage, hashes/review envelopes, URL query serialization,
  payload-size accounting and Audit HistoryReader's deliberate ASCII text chunks
  are not this finding. Do not replace human-readable chunk semantics wholesale.
- Risk: medium/high: multiple view families and snapshot dirty/proof flows change.
  Maintain exact grants, secret-safe fields, pagination and original write revisions.
- Recommendation: refactor the concrete read serialization. No broader Admin API
  redesign or direct foreign persistence is justified.

### P09 — Correct guidance that freezes development history unconditionally

- Location: handbook/architecture/public-api.md, paragraph beginning Initialization
  and historical Upgrade files; Auth frontend/contracts/controllers.ts header and
  frontend/presentation.ts compatibility wording.
- Evidence: the guide unconditionally forbids consolidating empty handlers and
  requires a new forward edge for compatible source changes. That wording is too
  broad for the standing owner pre-1.0 policy and reinforces P02. Controller comments
  can similarly be read as a blanket freeze, though their code only exports current
  ReturnType aliases. There is no runtime old-controller implementation to remove.
- Simplification: link the canonical owner pre-1.0 policy and distinguish actual
  installed/recovery identities from unpublished development steps. Describe current
  consumer contracts without declaring them permanently frozen. Keep Vue Component
  replacement flexibility: wrappers consuming partial props/$attrs are a valid
  current Vue feature, not sufficient reason to narrow all replacement types.
- Consumers: owner agents, delivered public guidance and separately authored customer
  instructions. Customers still cannot edit protected Runa source; owner authority
  must not be copied as customer maintenance permission.
- Risk: medium guidance risk, low code risk.
- Recommendation: correct current wording/references in its owning point; do not
  duplicate the full constitution across skills or rewrite historical roadmaps.

### P10 — Close the stale resolved-runtime maintenance record

- Location: maintainer/maintenance/backlog.yml, MT-2026-002; current PHP Dockerfile,
  current security baseline and published RM-2026-011/CHANGELOG evidence.
- Evidence: the record is still planned and says publication is pending, although
  the owning roadmap closed as 0.5.0, the changelog records runtime remediation,
  and current source pins PHP 8.4.27 without those old exception paths. This is
  stale active exception tracking, not evidence that a runtime bypass still exists.
- Simplification: reconcile the item against the recorded completed release and
  mark it resolved with the actual release/reference. No new runtime pin or scan
  exception is warranted. Keep MT-2026-001's downstream PostgreSQL retirement item:
  the corrected derivative still exists; no corrected official-image availability
  assessment was performed, so its future removal condition is not established.
- Consumers: maintainer status/knowledge/maintenance checks only; no customer runtime.
- Risk: low; preserve historical raw security findings and release records.
- Recommendation: correct current maintenance metadata, not historical evidence.

## Mechanisms reviewed and retained

Each row is a leave-unchanged recommendation for this compatibility audit. Its risk
is the consequence of removing it indiscriminately, not a new vulnerability claim.

| ID  | Source / actual purpose                                                                                                                                    | Consumer impact, risk and recommendation                                                                                                                                                                                                                                                                                                                      |
| --- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| K01 | All 26 current ApiDefinition declarations, module *Proxy.php, GuardedApi/ModuleApis/ApiDefinitions; current readonly DTO/type boundaries.                  | Per-call availability/authorization/version/source checks. One current declaration per inventoried contract, not parallel old API implementations. Retain; removal is high security/coupling risk. No PHP class_alias compatibility layer was found in the screened first-party code.                                                                         |
| K02 | Integration/Core bridges, Http/AuthAccountBridge, AdminHttpGuard; native settings authorization/participation and snapshot facade.                         | Trusted installation/identity/grant/Audit seams, separate phases and lazy Core boot. Retain; ordinary consumers still use guarded APIs. SettingsAuthorization has an authored rejection fixture and a distinct pre-write phase; it is not obsolete merely because built-in providers mainly use SettingsAccess/SettingsParticipant.                           |
| K03 | Remaining 45 CREATE migrations, DatabaseScope, separate native repositories/module ledgers, ModuleStepHistory.                                             | Same-file CHECK/FK ALTER statements are direct current schema definitions. Ownership isolation, changed-history refusal and real interrupted-operation evidence stay. PublicReferenceRule propagates gateway failure rather than inventing a missing-record fallback. Retain; high integrity risk if bypassed.                                                |
| K04 | Current guest/account stores, explicit adoptGuest, generic preference migration extension, current column normalization.                                   | One-time consent and account ownership remain meaningful without legacy flat keys. preference_legacy_claimed enforces adoption/reset lifetime, not only old-format import. Retain its behavior; do not delete the column on its name alone. Denied storage memory and corrupt/future-value defaults are current resilience.                                   |
| K05 | ModuleIntegrations, NoSettingsParticipation/NoPreparationParticipation, AnonymousAccountPolicy and deny-by-default boundaries.                             | Optional integrations/cold boot and explicit participation are supported modes, not earlier API generations. Missing Core is still unavailable. Retain; do not convert optional absence into authority.                                                                                                                                                       |
| K06 | quality_models/planning/state/evidence and flag-free impact selection.                                                                                     | Explicit manual groups and flag-free selection are current owner-approved commands. Optional fields keep actual old evidence readable; they do not prove PASS or authorize graph reuse. Retain; this audit does not change quality gates or add automatic classifiers.                                                                                        |
| K07 | Knowledge provider CPU/CUDA fallback, indexed-space/schema/freshness validation and intent=legacy/current/history/audit.                                   | Hardware resilience and distinct retrieval purposes are current requirements; legacy is the baseline scoring mode, not a second obsolete API. Retain; changing embedding identity cannot be treated as a harmless fallback.                                                                                                                                   |
| K08 | Vue public facades, presentation controllers/slots, default components, Optional image/identity/font/locale fallbacks.                                     | Real replacement/composition and bounded readiness/access-loss behavior. Retain; no copied security or forced typed wrapper is justified. P09 changes ambiguous comments, not this flexibility.                                                                                                                                                               |
| K09 | RepositoryPolicy v1/v2 projection checks, OWNER_PATHS exclusions, verified acquisition/three-way target selection.                                         | Ownership/protected-source refusal for actual customer receipts and independently acquired releases. v0.5 update support is expressly documented; it is not an inferred obligation to preserve unpublished interfaces. Retain unless the owning supported-acquisition contract is deliberately changed. No actual customer deployment inventory was obtained. |
| K10 | Host transport credentials/recovery readers, typed update/deployment journals, stable-inode locks and unknown evidence preservation.                       | Resume/stop of real retained operations, process ownership, safe rollback refusal and data protection. Current requests still use exact protocol validation. Retain; deleting a parser or journal is not a valid way to retire a compatibility path.                                                                                                          |
| K11 | Thin bin/Artisan/host/operator transports, owner/customer tooling split, generator paths and source checks.                                                | Different runtime/delivery availability and ownership, not duplicate business engines. Lexical PHP reference scanning supplements AST/PhpDoc inspection and lightweight module source checks. Legacy test-location detection refuses lost coverage instead of executing old tests; retain. P06/P07 name the actual narrower candidates.                       |
| K12 | Docker PHP/Nginx split, runtime DNS recovery, native Linux transport, dev-only AI and production isolation.                                                | Current lifecycle/platform requirements. No obsolete PHP startup shim or dropped-runtime security bypass was demonstrated. Retain; this is not image/security certification.                                                                                                                                                                                  |
| K13 | Client/server table modes, export/print adapters, mixed operation/proof phases, single/batch authority and own-history summary/detail APIs.                | Different sources, authority scopes or operations. AuthIdentities lookup, AuthAdministration reads and AuthLifecycle mutation are also distinct. Retain; merging them to reduce interface count could weaken policies or make transactions ambiguous.                                                                                                         |
| K14 | Temporary owner SIGSEGV evidence procedure and dependency/security removal conditions.                                                                     | Still explicitly required until its stated corrected-runtime stability condition is confirmed. This audit performs no SIGSEGV root-cause investigation and establishes no retirement condition. Retain; do not restore removed automation or treat unknown coverage as PASS.                                                                                  |
| K15 | Management renderer schema versions, resource revision strings, structured query serialization, Audit value text chunks and security mail direct fallback. | Current descriptor compatibility, optimistic consistency, bounded human-readable data or intentionally optional mail integration. The mail seam returns false only before send; failures do not silently select another send path. Retain; P08 excludes these roles.                                                                                          |
| K16 | Published tags/closed roadmaps/durable raw reports and independent module version assessment.                                                              | Immutable historical evidence differs from executable obsolete source. Retain history and honest module/API versions; no fake versions, blanket stable-API freeze or historical editing.                                                                                                                                                                      |

## Cause analysis and minimal proposed guidance change

The primary cause of the no-reset incident was agent error: the existing always-
loaded owner contract already stated the permission clearly, and the agent wrote
an additional restriction and then mistook that restriction for an owner decision.
The PostgreSQL/backend skills route to the correct canonical policy. Repeating the
same authorization in more skills would not have fixed that reasoning failure.

Two concrete process/documentation gaps contribute:

1. Roadmap precedence is listed above AGENTS/code guidance, but agreement/amendment
   procedure does not explicitly distinguish an owner-approved exception from an
   agent-authored scope restriction. The workflow has an explicit conflict stop for
   premature broad verification, but no equally explicit standing-authority check
   for other exclusions. Existing Decisions is already the place to record real
   changes; no new approval register or lifecycle state is necessary.
2. P09's unconditional handler-history guidance is current and source-fresh, yet
   insufficiently qualified for owner pre-1.0 maintenance. Source-hash freshness
   and semantic applicability are different. The RAG audit search returned the
   correct canonical policy; this was not demonstrated to be a retrieval outage.

Propose one short addition in maintainer/workflows/roadmaps.md, under Discussion
and activation, applied to drafting, imported plans and active amendments:

> Check scope restrictions against standing owner decisions before recording or
> executing them. Roadmap precedence does not turn an agent-authored restriction
> into an owner decision. Use Decisions to cite an explicit owner change to a
> standing rule; otherwise correct the inconsistent restriction and proceed within
> existing authority. Preserve explicit stop-and-discuss gates for new premises
> and premature broad verification.

This is proposed wording, not an installed new constitutional rule in this audit.
Its implementation point must reconcile the existing authority paragraph and
minimal reference in owner AGENTS/agent-contract if necessary, without duplicating
procedures. Customer wording must express application scope only. P09 separately
qualifies the owning public guide by linking the canonical pre-1.0 policy and
preserving actual deployed/recovery-data boundaries.

Existing hooks validate staged secrets, formatting/lint, repository/history state,
active roadmap scope and knowledge freshness. Documentation/release/quality checks
validate structure, metadata, branch/version/state/command linkage. They do not
interpret arbitrary prose and cannot prove that an author had authority to add an
exclusion. A green hook/docs result therefore did not validate the no-reset clause.
Use an explicit source/decision review in the roadmap; do not add a keyword ban or
automatic change/policy classifier that mistakes legitimate data protection for an
unauthorized restriction. Quality selection and final-only verification stay intact.

## Plan and acceptance consequences

The active roadmap adds audit point 10j and proposed implementation points 10k–10q,
then combined readiness 10r. Existing point 11 remains the sole penultimate owner
manual acceptance, and 12 remains the sole complete automated verification/release.
No other roadmap or branch is created. The scope amendment does not itself execute
or silently approve a new architecture/dependency/security premise.

P09/guidance reconciliation precedes persisted-history cleanup; P01/P02 coordinate
source fingerprints and any identified rebuild once the owning source is coherent.
Concrete preference, UI alias and structured-read changes update all current
consumers/generated examples/tests/docs in their own points. Native test integration
gets an exact design/parity review, preserving existing engines and all lifecycle
safety. CLI/maintenance cleanup stays separate. Every point assesses owner/customer
instruction products and independent module/API versions honestly.

Author meaningful regression scenarios with the corrections, using supported roots
and commands. Only necessary small diagnostics resolve point uncertainties; broad
suites/static analysis/install/delivery/image/security/AI verification remain in 12.
The final group selection is still manual and proportionate, with mandatory baseline,
real prerequisites, exact graph preview and existing evidence-continuity rules.
No selected or excluded quality area is claimed PASS by this audit.

10i evidence remains valid only for its recorded pre-refactor source. Planned UI/API
changes require fresh working assets/runtime/data and renewed combined handoff in
10r; earlier screenshots cannot accept those future changes. The current acceptance
sheet identifies this dependency. Manual acceptance is not complete. No Docker/test
resources were created or deleted by this audit; existing services, databases,
volumes, caches, backups and reports were not altered.
