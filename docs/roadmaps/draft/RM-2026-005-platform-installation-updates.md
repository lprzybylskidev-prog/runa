---
id: RM-2026-005
title: Platform ownership, installation and GitHub updates
status: draft
branch: roadmap/RM-2026-005-platform-installation-updates
created_at: 2026-10-18
activated_at: null
closed_at: null
release_type: feature
impact_areas:
  - backend
  - frontend
  - database
  - development
  - production
  - tooling
  - security
  - ai_environment
  - documentation
---

# RM-2026-005 — Platform ownership, installation and GitHub updates

## Overview

### Goal, sequence and release

Establish protected Runa/platform versus customer application boundaries, one Artisan command vocabulary, bootstrap installation and safe GitHub-based platform updates. Reuse the functioning development and production toolchain rather than replacing it with a parallel installer.

Planning-only draft. Requires [RM-2026-004](RM-2026-004-ui-foundations.md) closed, merged and released before activation/implementation. Its successor [RM-2026-006](RM-2026-006-module-framework.md) cannot begin until this roadmap is closed, merged and released. Refresh predecessor paths after their lifecycle moves.

Planned **0.3.0**, based on 0.2.0 from RM-004. Although architectural cleanup is substantial, install/update/integrity/identity are new features: `release_type: feature` increments minor under existing release rules. The platform remains pre-1.0 and this does not authorize a major bump. Document compatibility changes instead of declaring stable 1.0. Rebase the planned arithmetic on actual VERSION at activation without rewriting historical releases.

### Scope and exclusions

Include ownership reorganization, independent identity/version manifest, unified CLI, standalone bootstrap, dev/prod installation, private GitHub release retrieval, integrity, preflight, read-only dry-run, infrastructure-change detection, update journal/recovery and documentation/local checks.

Exclude module registry/dependencies/health/settings/events/generators, actual Core/Optional modules, Auth/Admin, custom license servers, public Composer distribution, alternate package download platforms, universal enterprise infrastructure, arbitrary application deployment automation and automatic customer-source merging. Customer business code may remain ordinary PHP/Vue and need not be modular.

### Audited baseline and authoritative sources

Audit: `main` `725f75b`, VERSION 0.1.2, no active roadmap, production-ever-deployed false. Local knowledge was current; significant retrieved isolation/deployment rules were checked in their source documents. This audit predates RM-004 implementation: refresh exact paths/public contracts before activation. Closed roadmaps remain immutable.

Read `AGENTS.md`, applicable nested contracts, `docs/workflows/{roadmaps,git,releases}.md`, `docs/architecture/{application-architecture,frontend,configuration,database,api,deferred-module-concerns}.md`, `docs/development/{environment,editor-and-agents,knowledge}.md`, `docs/production/{architecture,deployment,backups}.md` where present, `docs/tooling/commands.md`, `docs/quality/gates.yml`, `docs/testing/`, `docs/security/baseline.md`, relevant backend/Laravel/PostgreSQL/Docker/security/Vue/Tailwind skills and RM-004 delivered identity/import contracts. Confirm exact documentation filenames using focused source search rather than inventing a parallel guide.

| Existing mechanism/source                                                                                                      | Reuse or change                                                                                                                                                                           |
| ------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `bootstrap/providers.php`                                         | Current technical code sits in App namespace; relocate platform behavior while preserving Laravel composition and lifecycle.                                                              |
| `app/Support/ConfigurationValidator.php`, `RuntimeHeartbeat.php`, database guards and `IdentityMigrationRepository`            | Real platform capabilities, not new modules. Preserve validation, non-superuser runtime, identity DDL and isolated test DB safety.                                                        |
| `routes/api.php`, `routes/spa.php`, existing controllers                                                                       | Preserve `/`, named API health/readiness and JSON errors; move ownership explicitly without URL churn.                                                                                    |
| `resources/js/`, `lang/`, `database/migrations/`, `database/seeders/`                                                          | Keep RM-004 public UI facade; distinguish platform assets/translations/technical migrations from customer extension roots.                                                                |
| `bin/check` symlinks, `passthrough`, `dev`, `doctor`, `quality`, `security`, `knowledge`, `xdebug`, `project`                  | Existing command families and delegates; inventory every regular file/symlink before migration. Existing aliases are not independent implementations.                                     |
| `bin/operations`, `install-prod`, `deploy`, `backup`, `production`, `prod`; `tools/project/{operations,production,runtime}.py` | Reuse exact-SHA sparse deployment, lock, coordinated backup, private settings, seed order, image identities, interrupted-operation marker, conservative rollback and simulation boundary. |
| `docker/php/Dockerfile`, `docker/nginx/production.conf`, `compose.production.yaml`, dev Compose and `.devcontainer/`           | Explicit image copies/sparse allowlists must follow relocation; no Docker-in-Docker or host PHP/Node requirement. Production currently has no AI runtime.                                 |
| `VERSION`, README state block, CHANGELOG, state.yml, hooks, release/docs checks                                                | Preserve release workflow and one-way first-real-deployment evidence. Do not treat installation simulation as real production.                                                            |
| `tests/Feature/`, `tools/testing/{operations,production,maintenance,wrappers,development-startup,release,docs,quality}.py`     | Extend behavior-preservation contracts and isolated host acceptance, do not substitute source scans for real Docker evidence.                                                             |

Current production installation already provisions settings and exact source/images, migrations, seeders, backups and readiness. It expects trusted source fetched in advance and external TLS/SMTP/backup prerequisites. Missing additions are private GitHub bootstrap/retrieval, independent application compatibility, comprehensive source integrity, explicit dry-run and structured resumable updates; do not label the whole installer absent.

## Specification

### Decisions

- 2026-10-18 — Protected source and customer extension paths are disjoint. Customer modification of platform or Runa-supplied module source is prohibited. Customization uses configuration, contracts, DI, public events/slots and customer code, with no vendor-file patching workflow.
- 2026-10-18 — Keep a single Laravel application/container. Use a local, source-distributed platform root rather than public Composer packaging or duplicate Laravel skeletons. Local autoload mapping is configuration, not a new third-party dependency.
- 2026-10-18 — Artisan is the only domain command registry. Preserve thin host/container launchers and internal Python/shell where Docker/process/early-runtime orchestration requires them; there is no second independently configured command system. Retire old user-facing aliases only after hooks/docs/devcontainer/gates and recovery paths have migrated.
- 2026-10-18 — The initial standalone bootstrap is the explicit pre-Artisan exception. Host transport and a minimal recovery executor are implementation helpers, not another application CLI.
- 2026-10-18 — Distribution is one private GitHub repository/tag mechanism for both self-managed and provider-supported installations. No separate release-bundle service or license infrastructure. Customer applications have their own source lifecycle.
- 2026-10-18 — Reuse RM-004 identity/import contracts. Separate platform VERSION from an application manifest version; updating platform never bumps customer application version.
- 2026-10-18 — Leave module-specific lifecycle to RM-006. Provide narrowly typed update/preflight contribution seams only; no placeholder registry or Core implementations.

### Requirement authority and audit corrections

Source anchors from the fully read planning conversation: USER 37/39/51/53 (complete ownership cleanup and voluntary application modules), 132/134/136 (four independent SemVer levels, developer-owned application manifest and compatibility block), 140 (GitHub only; supersedes release-package proposal), 142/144/146 (protected source, unified Artisan/internal helpers, single delivered bootstrap), 148/150 (dev/prod, reuse installer, complete minimal production), 153 (reject shared management of customer business installation), 166/168/170 (three ordered roadmaps; Codex assigns versions). The original conversation sometimes contains ASSISTANT-only “agreed” summaries; those are not owner approvals. Existing project rules and the explicit owner-supplied roadmap prompt independently establish required preflight/dry-run/infrastructure safeguards. Concrete directory/transport designs here are planner-selected implementation choices to validate, not owner-mandated filenames or a new distribution service.

### Ownership and composition map

Target ownership, subject to path collision validation in point 1:

| Area              | Runa-owned                                                                                    | Customer-owned/public integration                                                                          |
| ----------------- | --------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Backend           | `platform/src/` (`Runa\` namespace), platform provider, controllers/actions/support/contracts | Existing `app/` (`App\`) for ordinary business code and application provider bindings                      |
| HTTP/console boot | Platform provider/routes under `platform/`; protected root bootstrap composition              | Declarative application provider/route registration under `app/`/`routes/`, namespaced named API resources |
| Data              | `platform/database/{migrations,seeders,factories}`                                            | `database/` business data artifacts; shared technical tables retain schema/identity semantics              |
| Web/email/errors  | `platform/resources/{js,css,views,lang}`                                                      | `resources/` application frontend/views and `lang/` overrides through supported namespaced contracts       |
| Config            | Protected defaults under `platform/config/`                                                   | Separate application config/manifest override inputs; no editing platform defaults                         |
| Tests/docs        | Platform tests/docs remain clearly scoped                                                     | Dedicated application test roots and application docs, discovered by existing runners                      |
| Persistent files  | Stable technical storage namespaces, never replaced with release source                       | Customer data/config/storage outside protected release trees                                               |
| Future modules    | Reserved `platform/modules/`                                                                  | Reserved application module root, implemented only in RM-006                                               |

Root `artisan`, framework entry/bootstrap, platform lock/build/deployment metadata and platform tooling remain protected. Separate platform and ordinary application storage/cache namespaces as well as source: no shared ambiguous upload/temp/export directory ownership, and preserve both sets in backup/restore and update contracts. Root customer-owned route/config/resource files are generated once as application scaffold and then excluded from platform replacement/integrity ownership. Composer/build composition must preserve declared application dependencies and customer entrypoints: platform and application dependency manifests/build inputs are composed into a reviewed deployment lock/build graph, never overwritten by downloading a new root lockfile. Validate package conflicts and frozen-lock installation before deployment. Do not add a new dependency just to merge manifests.

Keep an explicit release ownership manifest listing protected files, generated scaffold files and allowed runtime/build outputs. Build hashes from committed release inputs; forbid unknown files in protected directories while excluding narrowly enumerated generated/cache paths. Framework caches and writable storage cannot be protected-source exceptions covering arbitrary PHP. Customer overrides use distinct namespaces/load precedence, not identical protected-path replacement. Preserve UI facade imports through supported alias mapping; shell/components remain optional.

### Identity and compatibility

Customer `application.json` has schema version, stable application ID, name, independent SemVer version, compatible Runa constraint and optional branding asset metadata. Default bundled application uses actual own version metadata; root VERSION remains platform canonical. Backend/frontend/email/footer consume one resolved public identity DTO; only safe fields reach browsers. A platform update reads and preserves application metadata.

Validate SemVer constraints using an already available, runtime-shipped implementation if suitable; inspect Composer runtime versus dev-only availability first. Do not assume composer/semver ships inside production images. If no suitable implementation exists, propose a dependency with activity/license/security/compatibility/size/overlap evidence for explicit approval, or specify a documented supported constraint grammar with tested semantics. Reject unsupported syntax explicitly; never silently widen compatibility. Incompatible manifests block updates before environment changes. Application developer adapts code and changes its declared range first.

### Artisan and host execution boundary

Public commands use `php artisan runa:...` in an application/management container, or `./bin/artisan runa:...` from a host checkout. Proposed families: `runa:install`, `runa:update`, `runa:update:preflight`, `runa:update:plan`, `runa:integrity`, `runa:deploy`, `runa:backup`, `runa:doctor`, `runa:quality`, `runa:security`, `runa:dev`, `runa:knowledge` (development only), plus preserved existing `runa:seed`, configuration/runtime/test-database commands. Exact names are reconciled once against existing commands in point 2. Installation and updating both support interactive and fully parameterized noninteractive invocation; automation never hangs on a prompt, invents consent or bypasses a blocker. Ordinary customer/application/module Artisan commands remain free to use their own namespaces; `runa:*` is reserved for platform operations.

Host PHP/Node remains unnecessary. Reuse the existing pinned production-runtime PHP build target for the short-lived management container where feasible, rather than creating an independent runtime stack. The host launcher runs this management container when application services are not available. Artisan parses/validates domain options and produces a versioned allowlisted execution plan; the host executor performs Docker lifecycle and bounded process operations under the existing operation lock. Never execute arbitrary commands from remote metadata; never mount Docker socket into application/management containers. Use argument arrays, explicit environment, bounded output and private plan/state files. Runtime-independent commands must bootstrap without DB/APP_KEY/HTTP health; prove this during cold install and failed-app recovery. No recursive delegation to the stopped target app.

Internal Python can continue owning proven host Compose/backup mechanics initially; move application use cases to PHP Actions. Migrate callers rather than translating all Python for aesthetics. Existing `./bin/quality`/doctor entrypoints may remain thin compatibility transports required by project governance, delegating to the same implementation; update canonical help and gate definitions consistently. Quality graph semantics/hard gates/no-CI remain unchanged.

Production images omit knowledge, scanners, Boost, development commands and management code unless explicitly needed for runtime diagnostics. Use conditional development registration plus build exclusion; unavailable commands must not become accidental public production capabilities.

### Bootstrap and install

Provide a small repository-tracked standalone Python bootstrap (available supported Linux host Python; no host application toolchain). It is the sole file initially handed to customers. Check Git/Docker/Compose/Python versions, destination ownership/free space, authorized GitHub repository and selected immutable release tag. Interactive mode lists/selects accessible releases and asks install-now versus postpone; automation supplies repository/tag/destination/dev-or-prod/install-now-or-later explicitly and fails for missing required inputs.

Obtain credentials from interactive no-echo input or approved credential helper/private file; no token in command arguments, URLs, logs, shell history, Git remote config or persisted source. Redact errors and reject cross-host credential forwarding/redirects. Resolve tag to commit and verify expected repository/TLS/authenticated source, tag/commit identity and release file hashes. A checksum downloaded from the same account detects corruption but is not an independent signature: record the trust model. Allow an independently delivered expected commit/digest to strengthen the handoff; do not invent signing infrastructure. Reject mutable/ref-mismatch and unsafe paths/symlinks; download into staging and atomically publish a clean destination without overwriting customer files.

In both dev and prod, first installation generates the clean customer application scaffold once: application manifest with its independently declared actual initial version, identity configuration and empty code/module/frontend/test extension roots. Do not generate sample business features or overwrite an existing scaffold on reinstall/update. This is platform setup, not execution of a customer-specific installer (USER 148 versus rejection in USER 153).

Bootstrap downloads GitHub source, not a custom distribution bundle. It hands off to the containerized Artisan installer, or writes clear credential-free postponed instructions. Failure preserves actionable resume context without secrets or half-installed apparent success.

`dev` reuses resource profiles, bind mount, setup fingerprints, safe migrations, test credentials, devcontainer and independent knowledge initialization. Optional AI failure never blocks application startup; final AI gates remain strict. Ordinary reopen preserves data/credentials. `prod` reuses minimal PHP-FPM/Nginx/PostgreSQL/worker/scheduler stack, persistent storage/backups and real/simulation markers. Explicitly collect/validate required domain/TLS and SMTP choices. The standard installer must produce working HTTPS and mail delivery after required domain/certificate/SMTP inputs are supplied, without requiring the customer to write missing deployment configuration or build a reverse proxy first. Reuse standard Nginx with safely mounted supplied certificate/key and generated TLS configuration, or explicitly validate a deliberately selected existing trusted TLS terminator; external termination is an alternative, not an unexplained prerequisite for the default stack. Validate SMTP delivery using supplied provider settings. Certificate issuance/provider accounts are operator inputs; do not introduce an unapproved certificate service or mail-server subsystem. Customers may later replace DB/mail/orchestration; no Kubernetes matrix is required.

### Preflight, integrity and update state

Expose independent preflight/integrity/plan commands and human plus structured output. Before mutation verify installed/target platform version/SHA, application compatibility, source ownership/integrity, host/runtime/dependency graph, infrastructure config, disk/permissions, database/services, backup readiness and known blockers. Preserve current validation and DB guards. Dry-run has no data/config/service/cache mutations; remote metadata reads and isolated ephemeral planning downloads may be permitted only outside the installation and removed afterward, explicitly reported. Never run migrations/seeders/provider mutation hooks to discover their effects. Show discoverable migrations/operations, runtime/config changes, maintenance duration assumptions, prerequisites/blockers and uncertainty; do not claim dry-run proves execution success.

Standalone integrity compares against the installed exact trusted release: modified, missing and unexpected protected files; approved generated/runtime exclusions are explicit. It reports only and never repairs. Modified protected source blocks updates: no automatic merge, reset or overwrite. Detect custom standard-infrastructure changes separately from customer business source. An unmodified standard stack can follow a release-declared migration plan. Any customized file requires explicit administrator consent for a concrete reviewed diff; unsafe/incompatible custom topology blocks with diagnostics even if consent is given. Dry-run never implies consent.

Use a stable operation ID, exclusive deployment lock and durable private journal: planned → preflight-passed → backup-complete → maintenance → staged/built → migrations → platform-upgrades/seeders → switch → health/isolation → completed. Record input/release identities, each step's start/result, safe-resume conditions and previous code/images. Do not expose secrets in reports. Revalidate inputs/preflight after remediation; completed idempotent steps may resume, ambiguous/irreversible steps require explicit operator recovery, never merely delete the marker. Fence HTTP, workers and scheduler before data changes while serving RM-004 static maintenance. Failure keeps effective maintenance and returns nonzero. Manual Laravel maintenance is not cleared by successful updater recovery unless owned by that operation.

Retrieve target from private GitHub into a separate protected tree. Compose customer application code/config/resources and dependency locks without replacing them; build exact immutable image identities. Run owned migrations/production seeders and tracked platform upgrade operations; keep generic contribution ordering/seams for RM-006. Do not invoke arbitrary application-specific installers. Backup is coordinated; code rollback is allowed only with proven schema/data compatibility, preserving current conservative fingerprint rule until an explicit stronger rule is tested. No universal rollback of transformed data.

## Implementation plan

All points require relevant source/knowledge refresh, proportionate behavioral checks, affected durable docs/skills synchronization and scoped commits. Normal project workflow applies; RM-004 visual approval protocol does not propagate here.

- [ ] 1. Refresh delivered UI contracts and map ownership/composition.
  - Requires released RM-004. Inventory every app/config/bootstrap/route/data/resource/test/storage/build path and all bin files/symlinks; classify protected/scaffold/customer/generated. Freeze target paths and dependency/build composition with no source collisions. Capture behavior baselines and customer sentinel fixtures. Document upgrade from current checkout and preproduction migration constraints.
- [ ] 2. Define unified command transport and cold management bootstrap.
  - Requires 1. Implement minimal containerized Artisan command discovery without DB/key; versioned host-plan schema, allowlisted host executor and existing operation-lock integration. Prove cold start, stopped app recovery, no socket/host PHP and no arbitrary execution. Publish command migration table for every existing public command and internal helper.
- [ ] 3. Separate backend ownership and Laravel composition.
  - Requires 2. Move platform namespace/providers/routes/controllers/commands/support/config/data artifacts, preserving App business extension roots and isolated platform/application storage, guards/identity migration repository/health/readiness/seeding/request IDs. Test named routes, discovery, schedule pulse, configuration bootstrap and nonproduction DB isolation. Synchronize architecture/database/config/API docs.
- [ ] 4. Separate frontend, views, translations, tests and build boundaries.
  - Requires 3. Move RM-004-owned assets behind unchanged public facade, namespace mail/error views/translations, wire application overrides and test roots. Update Vite/TS/PHP autoload, explicit image copies/sparse allowlist and locked build composition. Test `/`, all layout contracts, no-JS errors and emergency 503; prove application-only frontend and customer customization without protected edits.
- [ ] 5. Application manifest and independent identity/version compatibility.
  - Requires 4. Validate schema/IDs/version/range/branding, connect RM-004 provider and default bundled manifest, implement compatibility evaluator using approved runtime capabilities. Test compatible/incompatible/unsupported constraints, separate version changes and no customer version bump on platform update. Update release checks without changing platform release arithmetic.
- [ ] 6. Consolidate existing application/development/quality/operations commands.
  - Requires 5. Migrate application use cases to PHP; retain justified Python/shell delegates. Rewire wrappers, hooks, devcontainer, Compose, scripts and docs/gates to one Artisan vocabulary; compatibility wrappers are thin and dated for retirement when safe. Test help/exit/argument forwarding/lock behavior and missing runtime. Ensure production cannot register knowledge/dev tooling. Preserve background-gate stop/no-poll semantics.
- [ ] 7. Private GitHub source acquisition and standalone bootstrap.
  - Requires 6. Implement secure credential handling/tag resolution/staging/destination checks, interactive/noninteractive and install-now/postpone paths. Test synthetic private endpoint failures, denied/revoked credentials, redirects, malicious tag/path inputs, ref mismatch, interrupted downloads, existing destination and secret redaction. Validate against authorized GitHub access when available; no test credentials enter fixtures/history.
- [ ] 8. Unified dev/prod install orchestration.
  - Requires 7. Incorporate current dev setup and production installer into Artisan actions using shared host mechanics; preserve resource profiles, independent AI, secrets, migrations/seeders/backups/storage/caches and exact image identities. Generate the clean scaffold once in both modes and verify preservation on repeat/update. Provision standard TLS configuration from certificate inputs or validate an explicitly selected terminator, and verify SMTP delivery; no manual completion of missing standard infrastructure. Test fresh/postponed/repeated dev setup and isolated prod installation, no production marker in simulation, and recovery after install failure.
- [ ] 9. Trusted ownership baseline and standalone integrity report.
  - Requires 8. Build installed-release protected file inventory/digests from trusted source, distinguish generated/scaffold/customer paths and infrastructure drift. Test modified/missing/unexpected files, symlink escapes, stale baseline, malicious exclusions and no repair. Customer sentinel files remain unchanged; every protected-source mismatch blocks update preflight.
- [ ] 10. Preflight and nonmutating update plan.
  - Requires 9. Compose compatibility/infrastructure/runtime/DB/permissions/backup checks; classify unmodified versus custom infra and show concrete required consent diff. Provide human/JSON reports and migration/operation/maintenance plan. Assert DB/config/service/file fingerprints unchanged across dry-run and all foreseeable failures happen before maintenance/mutation. No module implementation yet.
- [ ] 11. Journaled platform update and failure recovery.
  - Requires 10. Integrate GitHub retrieval, staging/build, backup/fencing, owned migrations/seeders/upgrades, switch, health/isolation and completion. Extend existing in-progress/deployment evidence rather than create independent conflicting state. Add safe resumable step IDs, idempotency/checkpoint policies and manual-maintenance ownership. Inject failure at every boundary; prove nonzero exit, retained maintenance/evidence and safe resume/blocked rollback for changed schema.
- [ ] 12. Standard infrastructure upgrades and preserved business applications.
  - Requires 11. Apply release-declared standard config/runtime transitions; custom infra cannot be overwritten silently. Test before/after standard topology, explicit administrator diff consent, unsafe custom blocker, customer file/hash preservation, application dependency conflict and compatibility-block scenarios. Expose ordered preflight/update contribution interfaces for RM-006 without registry/module state.
- [ ] 13. Operator acceptance and documentation consolidation.
  - Requires 12. Run end-to-end on isolated disposable host resources: bootstrap-only delivery → dev/prod, self-managed/provider same tools, interactive/noninteractive valid update, missing-argument/no-prompt rejection, no-op, integrity block, incompatible app, dry-run, interruption/resume, backup/restore and compatible/incompatible rollback. Preserve actual image/source identity evidence and scan built images. Update existing architecture/development/production/tooling/testing/security/AI docs, AGENTS and relevant skill procedures; remove obsolete command/ownership claims without rewriting historical reports.
- [ ] 14. Full verification, release finalization, merge and final report.
  - Requires 1–13 complete. Review current-state documentation against actual capabilities (especially CLI names, startup, production isolation, ownership, TLS/SMTP and available recovery paths); record reviewed files/findings/disposition. Run full applicable local quality/security/AI/production gates including real isolated host acceptance. Fixes are inserted before this point and invalidate the prior full run. Follow roadmap/release workflows for version metadata, index/docs/release checks, closure/merge/main sanity, immutable tag/GitHub Release and branch deletion. Background full gates end the turn without polling. Finalize planned 0.3.0 from actual version; do not self-activate RM-006.

## Validation, risks and completion criteria

Use existing PHP tests/static analysis and offline operations/wrappers/release/docs/production contracts; extend contract coverage as commands move. Host simulations must prove actual cold install, file ownership, Docker lifecycle, update backup/recovery and AI exclusion. No CI and no new dependency without explicit approval. Never run acceptance against real customer data. Show exact task-created resource identities before cleanup; remove only disposable task containers/networks/tags/volumes after final use and preserve development/test data, rollback images/volumes, knowledge/model caches, backups and reports.

Compatibility risks: namespace/path relocation affects autoload/discovery/cache/build/sparse copies; pin behavioral tests before moving. Existing application checkouts may have mixed ownership; require an explicit inventory/migration and refuse ambiguous overwrites. Root Composer/pnpm lock composition can reveal incompatible customer dependencies; block rather than silently resolve upgrades. Host execution must work without target app/DB; prove management-container boot early. GitHub access/integrity trust depends on authorized repository/tag identity, not an unauthenticated checksum. Private live access and actual TLS/SMTP credentials are execution-environment prerequisites, not reasons to leave algorithms unspecified.

Acceptance: one protected ownership model and public extension path for every audited ecosystem area; ordinary application code and optional frontend remain supported; standalone bootstrap delivers exact source securely, and both install modes generate a clean scaffold once; dev and minimal prod work through one installer; all public operations share Artisan registry/delegation; independent version/range enforcement; standalone nonrepairing integrity; source modifications/custom infrastructure protected; dry-run leaves installation unchanged; update journal fences inconsistent application state and supports documented recovery; backup/first-deploy/production isolation remain intact; full local gates and release pass. No actual module framework or business-module functionality is delivered here.
