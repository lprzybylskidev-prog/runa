---
id: RM-2026-012
title: Runa Architecture, Public APIs & Tooling Refactor
status: active
branch: roadmap/RM-2026-012-runa-architecture-refactor
created_at: 2026-10-26
activated_at: 2026-10-26
closed_at: null
release_type: feature
proposed_version: 0.6.0
quality_groups: all
quality_rationale: The rebuild crosses customer operations, acquisition/update, host transport, hooks, quality coordination, security, knowledge, containers, public PHP/frontend contracts, module internals, generators and every existing test integration; all groups and their real prerequisites are needed for compatibility and isolation evidence.
impact_areas:
  - backend
  - frontend
  - tooling
  - development
  - production
  - security
  - ai_environment
  - documentation
---

# RM-2026-012 — Runa Architecture, Public APIs & Tooling Refactor

## Overview

### What and why

Refactor Runa architecture around explicit public customer APIs, internal platform
and module responsibilities, extensible frontend presentation and cohesive tooling.
The completed Python rebuild and unified test infrastructure form the foundation;
remaining work covers the full src/runa tree and its supported application seams.
Preserve existing customer/owner behavior and ordinary Laravel or optional module
development while removing unclear dependencies and duplicate mechanisms.

The predecessor is [RM-2026-011](../closed/RM-2026-011-audit-auth-permissions-core.md),
released as 0.5.0. The owner explicitly requested **0.6.0 after this roadmap**.
The owner confirmed `release_type: feature` and `proposed_version: 0.6.0` on
2026-10-26: a MINOR increment with PATCH reset. The owner authorized activation
and execution of point 1 on 2026-10-26. VERSION remains 0.5.0 until verified closure.

Activation started from clean `main` at `0fab18be` after fetching origin; main was
two planning commits ahead and zero commits behind origin/main. No other roadmap
was active or paused. The predecessor remains 0.5.0, consistent with feature 0.6.0.

### Scope

All tracked Python source and executable launchers; application/maintainer and
external distribution tools; Python tests and fixtures; PHP/JS/shell/Compose/
Devcontainer callers; package/resource projection; tool pins; local hooks and
quality integration; documentation and both agent instruction products. Include
a delivered, extensible customer development environment for backend, frontend
and tooling, with shared test infrastructure and separate owner/customer suites.

The owner expanded planning on 2026-10-27 to cover all `src/runa`: public
customer extension/consumption APIs, platform/module internals, PHP composition,
frontend replacement, generators/templates, build/development support and their
source/delivery boundaries. Points 11–13 have delivered the frontend test repair,
API/placement map and platform PHP reorganization. Point 14 has delivered the built-in module execution layout; point 15 has delivered
the named frontend extension contracts; point 16 has delivered the internal frontend orchestration split; point 17 has delivered developer support, generators and build-tool placement; points 18–19 remain pending;
final layout-specific instruction reconciliation remains part of implementation.

### Out of scope

No new business feature, schema redesign, business-data migration, visual redesign,
module protocol/behavior rewrite, production Python service, remote tooling platform, CI,
general plugin/DI framework, new host PHP/Node requirement, Docker socket inside
the devcontainer, or replacement of Laravel Artisan as the customer registry.
Do not rewrite mature Python engines in PHP or move PHP Actions into Python.

## Specification

### Decisions

- 2026-10-26 — The owner authorized activation and point 1. Created
  `roadmap/RM-2026-012-python-tooling-architecture` from updated main; reviewed
  implementation ordering and confirmed broad verification occurs only in the final
  point (originally 10, renumbered 11 by the central-test-layout decision).
- 2026-10-26 — The owner explicitly confirmed `release_type: feature` and
  `proposed_version: 0.6.0`, independently of roadmap activation.
- 2026-10-26 — Preserve the four ownership domains: reusable core, delivered
  application tooling, private maintainer tooling, and separately acquired
  external distribution tooling. Core ships with application tooling rather than
  becoming another independently installed/versioned customer dependency.
- 2026-10-26 — Use explicit Python packages under the existing ownership roots,
  stdlib runtime dependencies, source execution through `python3 -m`, and private
  wheel builds for packaging validation. No PyPI publication or mandatory host
  installation. Package ownership and cold recovery take precedence over a
  fashionable source layout.
- 2026-10-26 — Target CPython 3.11 language/runtime compatibility, with the actual
  host 3.12 and development container 3.11 contexts covered in final verification.
  Do not require host/container minor versions to match or upgrade the runtime
  merely for syntax. Record supported Linux/Python combinations in public docs.
- 2026-10-26 — Prefer composition, constructor injection, immutable dataclasses
  and small Protocols at external-effect boundaries. Keep pure transformations
  as functions. No service locator, global registry mutation or DI container.
- 2026-10-26 — Adopt pytest discovery, Ruff formatting/lint and strict Pyright.
  Tools remain development dependencies with exact reviewed pins and locks.
  No new Composer/pnpm dependency is authorized by this draft; if Pyright uses
  pnpm, obtain the explicit dependency approval before editing its manifests.
- 2026-10-26 — Preserve existing quality selection, hard gates, locks, evidence,
  native exit codes and security policy. Integrate Python checks into that system,
  with no second coordinator or CI. Full verification is exclusively the last point.

- 2026-10-26 — After point 4, the owner clarified that the delivered product is
  an extensible development environment across backend, frontend and tooling,
  rather than only an application runtime. Reusable development/test mechanisms
  are customer capabilities even when Runa maintainers also use them. Private
  Runa test suites, platform fixtures and release/maintenance policy remain excluded.
- 2026-10-26 — The owner agreed that the default owner and customer test commands
  run backend, frontend and tooling tests, including Python, with optional focused
  selections. This explicitly supersedes the backend-only default and the earlier
  owner-only Python developer-tooling assumption. Python infrastructure itself
  requires meaningful private regression coverage; a customer receives its public
  tooling/support, not the platform's test suite. This remains feature scope within
  the agreed 0.6.0 roadmap; existing completed-point evidence is historical, and
  remaining points own reconciliation. No new dependency or production tooling
  inclusion is authorized by this clarification.

- 2026-10-27 — The owner approved centralizing all authored tests under repository
  `tests/backend/{app,runa}`, `tests/frontend/{app,runa}` and `tests/tools/{app,runa}`
  (container `/workspace/tests`), explicitly choosing `tools` instead of `tooling`
  as the directory name. Insert this implementation as point 10; full verification
  becomes point 11. Existing public command/scope names retain their contracts.
  Module cases and private native acceptance/fixtures move with their owning suite;
  reusable runtime/development test infrastructure stays in its source package.
  Keep customer tests configurable, private Runa suites excluded from delivery and
  all test material excluded from production images. Source generators and test
  metadata follow the central roots; legacy acquired receipts and module test
  declarations remain readable. Existing customer-owned test files are never moved
  or overwritten implicitly by an update; document explicit local reconciliation.
  This is compatible development-tooling scope within confirmed feature 0.6.0;
  no dependency or module business-API change/MAJOR release is authorized.

- 2026-10-27 — The owner requested a complete `src/runa` review and explicitly
  clarified that the desired boundary separates interfaces/types/components
  customers may use to extend their application from implementation and trusted
  integration that customers must not depend on. Physical delivery is a separate
  question; all Runa files remain protected against customer edits. The owner
  authorized recording findings and additional implementation points in this
  active roadmap, including frontend extensibility and future instruction updates.
- 2026-10-27 — The owner clarified that this task is review/planning and durable
  guidance, with no further test execution or runtime refactor. Skills, AGENTS and
  handbook boundary rules are authorized now; final layout-specific updates remain
  point 19 work. The focused frontend collection failure is a finding, not acceptance.
- 2026-10-27 — Insert proposed implementation points 11–19 below; final verification
  moves from 11 to 20. Completed execution evidence retains its original point
  references as history. Keep the confirmed compatible feature 0.6.0 projection;
  this expansion does not approve breaking public namespaces, module MAJOR changes,
  new dependencies, CI or data migration. Stop for an explicit compatibility/version
  decision if the design cannot preserve existing supported customer contracts.

- 2026-10-27 — The owner rejected a platform-wide full-DDD conversion. Customer
  applications retain ordinary Laravel development or optional application-owned
  modules, without mandatory domain layers, repositories, mappers or a separate
  domain model. Use domain boundaries/value objects only where they solve a
  concrete problem; this refactor focuses on public APIs, internal responsibilities
  and compatible extension, not imposing an architecture on customer code.

- 2026-10-27 — Renamed this active roadmap to "Runa Architecture, Public APIs &
  Tooling Refactor" and its file/local branch to RM-2026-012-runa-architecture-refactor
  to reflect the expanded scope. Retain ID, release metadata and all completed
  evidence. Original Python inventory paths remain historical identities; the
  reconciliation map records the current roadmap target.

- 2026-10-28 — The owner approved retaining the exact file/FQCN identity of
  `Runa\Support\Modules\Work\ModuleJob` in point 13. Laravel serializes this
  envelope into durable queue payloads; it remains internal, with no alias or
  duplicated engine. Correct the point-12 target map and add persisted-envelope
  regression coverage. The owner noted there is currently no database data; no
  database reset, payload rewrite or destructive operation is needed.

### Audit basis and limits

Source baseline: `0c51a1b33e2081549ed2e6becaee1b3c395aed0d` on `main`, VERSION
0.5.0. Inventory uses tracked `.py` files and Python shebang executables, excludes
symlink aliases from file/line counts, and reads source rather than importing
modules with operational side effects. No implementation, test suite, deployment
or destructive acceptance was executed to prepare this plan.

Local knowledge was available and current: 180 documents, 2753 chunks, no changed,
missing or orphan chunks. Retrieval identified the tooling contracts; the original
[command guide](../../../handbook/tooling/commands.md),
[quality guide](../../quality/README.md), [release workflow](../../workflows/releases.md)
and [documentation ownership](../../../../README.md) were read directly.
Host `python3 --version` returned 3.12.3; supported Compose delegation returned
3.11.2 from the app container. These are observations, not a portable pinning scheme.

| Ownership root       | Python source/executable files | Lines | Main responsibility                                   |
| -------------------- | -----------------------------: | ----: | ----------------------------------------------------- |
| `bin/`               |                              4 |   113 | Artisan, tool/recovery and Boost transports           |
| `tools/application/` |                             36 |  4264 | Delivered operational and development engines         |
| `tools/maintainer/`  |                             54 |  8070 | Owner dispatch, checks, tests and acceptance          |
| `distribution/`      |                             10 |  2495 | External acquisition, installation and updater        |
| Total                |                            104 | 14942 | Plus versioned symlink aliases and non-Python callers |

Repository-wide tracked source discovery found no additional Python files outside
these roots. Inline Python also exists in the PHP Alpine shell acceptance helper
and subprocess command strings. The inventory found 52 files containing
`sys.path.insert`, 11 containing `runpy`, and 289 `test_*` method definitions in
Python testing directories. These are source counts, not collected/passing tests,
coverage percentages or a target count that migration must mechanically retain.

### Findings: strengths to preserve

1. Customer/owner separation is deliberate. Acquisition sparse projection,
   receipts and source checks exclude maintainer/distribution material. Production
   Docker COPY inputs omit tooling, private tests and AI; operator execution is
   separate from application runtime. Preserve these boundaries physically.
2. Subprocesses already predominantly use argument arrays. Customer Git acquisition
   validates source paths, symlink targets, exact identities, credentials and
   destination publication. Replace mechanics without relaxing these checks.
3. Quality is a real dependency graph: manual groups, prerequisite union,
   exclusivity, bounded concurrency, graph hashes, candidate-bound evidence,
   interrupted-child detection and durable timing/status. Preserve the semantics.
4. Deployment/updater journals, operation locks, no automatic data rollback,
   test-suite locks and browser ownership journals are meaningful domain behavior.
5. Knowledge already has useful `Capabilities`, `Embeddings`, `Store`, `Http` and
   `Retriever` objects; updater `DockerRuntime` is an existing injection seam.
   Preserve model/dimension validation, CPU fallback, deterministic identities,
   pending manifests, incremental embeddings and golden retrieval expectations.
6. Existing unittest cases cover real refusal/recovery/security contracts. Shared
   fixture classes and some injected callbacks are useful foundations. Tests are
   not disposable just because they use unittest.

### Findings: architectural weaknesses and concrete evidence

| Finding                                                              | Source evidence                                                                                                                                                                                  | Required correction                                                                                                                           |
| -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Bare imports collide across ownership roots                          | Both `checks.py` files import `runtime`; owner `context.py` inserts the application directory at position 1; duplicate `docs_checks` names need special loading                                  | Fully qualified imports and explicit owner composition                                                                                        |
| Owner quality integration mutates imported implementation            | `tools/maintainer/entrypoint` assigns `quality.RUN_DIRECTORY`, `COMMAND_PREFIX`, replaces `quality.plan` with a lambda, edits `sys.argv`, then executes the application executor with `runpy`    | Inject `QualityContext`, plan source, reporter and run directory into one reusable coordinator                                                |
| Release-specific owner policy lives in delivered code                | `tools/application/quality.py` contains `approved_rm010_closure_fix`; `hooks.py` contains `approved_version_backfill` reading private owner records                                              | Move owner policy behind private validators; assess expired exception retirement against historical evidence without rewriting closed records |
| Process-global release context changes execution                     | `operations.command` saves, replaces and restores `production.ROOT`; `operations.STATE` is derived at import time                                                                                | Immutable workspace/release contexts and Compose builders, safe for two contexts in one process                                               |
| Services are coupled in both directions                              | `quality` imports `checks.yaml`; docs checks import quality; operations and production call each other; updater execution imports CLI `update`, while runtime/customization modules cross-import | Move shared data/policies downward; command adapters depend on services, never vice versa                                                     |
| External installation assumes ambient customer directory/import path | `installation.py` inserts `Path.cwd()/tools/application`; bootstrap constructs a `python -c`/`runpy` import harness                                                                              | Explicit external source and customer workspace identities, narrow application gateway, separate-version compatibility                        |
| Multiple process/Compose/environment mechanisms                      | `runtime.run`, `operations.dc`, `customer_source.git`, bootstrap `process/git`, updater `command/compose`, and acceptance `call/output`                                                          | Common process mechanism with explicit policies; retain distinct credential, timeout and production/development rules                         |
| Configuration and results are weakly typed                           | JSON dictionaries flow through management plans, quality nodes/state, journals and knowledge config                                                                                              | Validated typed boundary models; keep serialized schemas compatible                                                                           |
| Script imports execute dispatch logic                                | Owner entrypoint and several executors parse arguments at module top level                                                                                                                       | Import-safe modules and explicit `main(argv, dependencies)`                                                                                   |
| Test discovery and support reuse are fragmented                      | Hyphenated unittest files run individually from gates; `update-execution.py` loads `Updater` using `runpy`; installation harness loads sibling host scenarios the same way                       | One pytest discovery convention, support packages and ordinary imports                                                                        |
| Tests patch infrastructure globals to select workspaces              | Operations/management tests replace module ROOT/STATE/subprocess; wrapper tests mutate argv and run scripts in-process                                                                           | Inject fakes into services; real subprocess tests for public adapters                                                                         |
| Formatting and analysis are incomplete                               | Hook compiles Python syntax; no tracked pyproject/Ruff/Pyright/pytest configuration found; source checks target PHP/JS                                                                           | Python formatting, lint, strict typing and suite coverage through existing hooks/gates                                                        |
| Environment identity is too implicit                                 | `inside()` checks only `/.dockerenv`; installation acceptance contains fixed `runa-dev-app-1` and `/workspace` assumptions                                                                       | Explicit environment/context resolver preserving current guards and documented container mount                                                |

The audit does not classify every subprocess or patch as wrong. A true PHP,
Docker, Git or version-separated execution boundary still needs a process.
Pure functions need no wrapper class. Do not merge different security policies
into an indiscriminate universal executor.

### Component migration inventory

The owner's follow-up requested adjacent cleanup within this roadmap. Include
the following cross-language integration work in the existing points, without
expanding into unrelated business modules or a whole-application rewrite:

- Reconcile command metadata across management JSON catalogs, the host-plan schema,
  Python allowlists and executor routing. Keep one authoritative declaration for
  each fact and explicit consistency checks for intentionally independent security
  validators. Customer and owner catalogs remain separate; source declarations
  must not turn into caller-controlled executable/plugin registration.
- Consolidate repeated source/delivery policy and manifest-composition rules.
  `OWNER_PATHS` currently appears in bootstrap, repository boundaries and updater
  contracts; dependency composition also appears in application and updater code.
  Use canonical reusable rules or verified build-time materialization where the
  standalone bootstrap/version boundary prevents direct reuse. Preserve receipt
  schema compatibility and independently enforced projection checks.
- Make environment/configuration responsibilities explicit across PHP, Python,
  Compose and Devcontainer adapters. Share technical parsing where semantics match;
  retain raw secret values, literal dollar signs, production role checks and
  intentional differences between Compose input and application configuration.
- Finish the developer workflow across languages: document where formatting,
  linting, typing and tests run; align editor Python interpreter/format settings
  with the container toolchain; remove contradictory command/help/doc guidance.
  Reuse resource ownership/reporting contracts for PHP/JS/Python suites while
  retaining their native runners and distinct application/owner scopes.

These are tooling integration outcomes, not authorization to redesign Auth,
Audit, Permissions, module APIs, database architecture or frontend components.
The current audit does not establish that all other application areas are free
of maintainability issues.

Paths in this table are current sources. Each row owns the complete named family,
including its tests/configuration; point 1 expands the inventory into per-entrypoint
compatibility records and captures additions since this audit.

| Current components                                                                                                                                                                         | Target responsibility and disposition                                                                                                                                  |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `bin/artisan`, `command`, `passthrough`, `boost-mcp`; all their symlink aliases                                                                                                            | Thin stable transports; dispatch through fixed package entry modules; retain documented recovery/tool interfaces and stdio behavior                                    |
| `runtime.py`, `documentation.py`                                                                                                                                                           | Core workspace/runtime value objects; receipt-aware ownership policy belongs to application services                                                                   |
| `repository_boundary.py`, `customer_source.py`                                                                                                                                             | Application installation-role/source ownership and Git source services; immutable policy supplied to acquisition/build callers                                         |
| `dependencies.py`, `module_versions.py`                                                                                                                                                    | Pure manifest composition/version services plus thin compatible entrypoints                                                                                            |
| `management.py`, `host_executor.py`                                                                                                                                                        | Distinct management discovery, plan validation, executor lifecycle and request services using shared process/storage APIs                                              |
| `operations.py`, `production.py`, their executors                                                                                                                                          | Deployment state machine, release source/Compose/image/health services and production adapter                                                                          |
| `executors/dev.py`                                                                                                                                                                         | Development settings/profile/setup/lifecycle services; keep PHP-owned source checks/Xdebug in PHP                                                                      |
| `quality.py`, `executors/quality.py`, `quality.yml`                                                                                                                                        | Shared typed planner/coordinator/evidence/status services, customer composition root and unchanged public selection behavior                                           |
| `checks.py`, `docs_checks.py`, `executors/doctor.py`, `quality_check.py`                                                                                                                   | Reusable diagnostic results and documentation parsing; no dependency from quality engine to command checks                                                             |
| `security.py`, `install-scanners.py`, `executors/security.py`                                                                                                                              | Shared scanner/audit/review-evidence services and explicit pinned scanner provisioning                                                                                 |
| `hooks.py`, `git_hook.py`, `tools/application/bin/*`, `.githooks/*`                                                                                                                        | Shared staged-content/hook services and thin adapters; private owner rules supplied explicitly                                                                         |
| `test_resources.py`, `testing/hooks.py`, `testing/production-runtime.php`                                                                                                                  | Application-owned test resource lifecycle and delivered self-diagnostics; private assertion suites stay private                                                        |
| `knowledge/{configuration,corpus,ingest,provider,retrieval,store,transport,startup}.py`, configs/golden, executor                                                                          | Development-only knowledge services and public startup/CLI adapters; injected owner corpus/golden extensions                                                           |
| Maintainer `context.py`, `entrypoint`, database/migration launchers, all check/quality/test symlinks                                                                                       | Private composition roots and unchanged fixed-purpose owner transports                                                                                                 |
| Maintainer `checks.py`, `docs_checks.py`, `release_checks.py`, `owner_tests.py`                                                                                                            | Owner release/registry/docs/test policies, consuming supported application/core APIs                                                                                   |
| `localization/snapshot.py`, `service_fixtures.py`                                                                                                                                          | Import-safe owner localization and immutable service-fixture services                                                                                                  |
| Distribution `bootstrap.py`, `initialize.py`, `installation.py`                                                                                                                            | Standalone acquisition adapter plus external customer initialization/install services                                                                                  |
| Distribution `update.py`, `update_contracts.py`, `update_files.py`, `update_customization.py`, `update_modules.py`, `update_runtime.py`, `update_execution.py`                             | External update planning/compatibility/source-switch/recovery services, explicit runtime gateway; break cyclic orchestration                                           |
| Distribution `module-plan.php`, `platform-database.php`; application `read-yaml.php`                                                                                                       | Retain narrow PHP interop; callers use typed process protocols, no unnecessary Python YAML/parser dependency                                                           |
| Maintainer testing: bootstrap, customer-initialization, installation, installation-host, customer-deployment(-host), update(-host), update-execution, team-update-host, customer-knowledge | Pytest acquisition/update/deployment contracts and isolated acceptance; move fixture support out of TestCase inheritance                                               |
| Maintainer testing: quality, security, operations, production, dependencies, management(-host), host-executor(-host), repository-boundary, tool-ownership, wrappers                        | Unit/contract/host integration suites using common fakes and boundary assertions                                                                                       |
| Maintainer testing: documentation, documentation-live, docs, release, maintenance, development-startup, knowledge, knowledge-live, knowledge-retrieval, documentation_fixture              | Docs/release/startup/knowledge suites and support; retain live golden behavior                                                                                         |
| Maintainer testing: database-guards, services, docker-test-inventory, error-fallback-host, error-fastcgi-fixture, fixtures/installation-smtp.py                                            | Isolated integration helpers/servers and lifecycle assertions; preserve PHP/browser supported runner ownership                                                         |
| Maintainer testing: postgres-alpine(-contracts), postgres-switch-contracts, switch-postgres-icu, php-alpine-host.sh                                                                        | Assess which historical candidate probes remain reusable; port live contracts, explicitly retire obsolete probes with equivalent coverage and preserved dated evidence |

Non-Python callers include `src/runa/management/Actions/RunTool.php`,
`ToolingCommand.php`, `RunSourceChecks.php`, module generator command templates,
PHP module/update and platform database fixtures, `compose.yaml` setup and
knowledge-init jobs, `.devcontainer/devcontainer.json`, Dockerfile development
and production targets, `.dockerignore`, both gate YAML files, hook scripts,
package manifests and command/documentation references. Search literal paths and
constructed path families; changing only direct `.py` references is insufficient.

### Target packages and distribution model

Use ordinary `__init__.py` packages with fully qualified imports under the
existing repository roots:

```text
tools/
  __init__.py                 # inert; no owner imports or source discovery
  core/                       # reusable stdlib infrastructure
  application/                # delivered services + internal entry modules
  maintainer/                 # owner services + entry modules + private tests
distribution/
  __init__.py                 # external acquisition/install/update package
```

The generic `tools` name is repository-local, not a promised public Python import
API or third-party plugin namespace. Provide deliberately documented reusable
interfaces and composition/configuration contracts; the whole internal package
namespace does not become a supported extension API. Explicit packaging includes prevent discovery
of unrelated repository directories. Core and application form **one customer
distribution**, so customer tooling never needs to install a second core package.
Owner tooling is a separate private distribution adding `tools.maintainer`; it
does not ship a second `tools/__init__.py` or overwrite customer files. External
distribution is a separately prepared stdlib bundle containing its required core
from canonical source, not private maintainer code. There is one authored core;
generated bundle copies are artifacts, not separately maintained implementations.

Root `pyproject.toml` describes only deliverable core/application metadata and
shared tool rules. Deliver the reviewed developer-tool definitions, frozen inputs,
setup and reusable support needed by customer Python/tooling tests independently
of owner files. Only genuinely owner-specific build/test groups, private suite
roots, fixtures and owner package manifests live under `tools/maintainer` outside
customer projection. Reconcile point 2's private-only setup into shared delivered
mechanisms with explicit owner additions, without duplicating toolchain engines.
External bundle metadata lives in `distribution`. Use a reviewed setuptools build
backend with explicit file/resource lists; no package auto-discovery across the
whole checkout. Generate private wheel/build outputs under `.runtime/python/`.
Package versions derive from platform identity, without independently released
Python libraries or an extra version bump before final release metadata.

Normal source operation requires **no pip, venv or project install on the host**.
Thin launchers resolve their own repository root and start a fixed `python3 -m
tools.application.<entry>` or owner module with an explicit working directory.
Pass a separate requested repository context for hooks; never lose the customer's
hook target when selecting the tool checkout. Preserve `sys.argv[0]` alias identity
as an explicit validated adapter input before dereferencing symlinks. Do not add
`PYTHONPATH`, mutate `sys.path`, dynamically import arbitrary file paths or require
the caller's ambient cwd. Module execution is an external transport; services
inside the same interpreter call APIs directly.

Keep the reviewed single-file acquisition contract. Its minimal stdlib bootstrap
must run when copied outside any checkout and before packages exist. It may verify
and hand off to the external bundle, but must not become a duplicate installer.
Use canonical generated self-contained packaging where required to retain that
single-file interface. Never import an unverified target's application code into
the acquisition process. External installation/update interacts with a selected
customer version through an explicit, version-checked gateway; it must work without
the maintainer package and without importing two releases under the same module
identity. Test the 0.5.0-to-0.6.0 transition and interrupted source switches.

Dependency direction:

- Core imports stdlib/core only and knows no application/owner policy or resources.
- Application imports core/application only. Customer receipt scope wins even if
  owner files were accidentally copied into its tree.
- Maintainer imports core and documented reusable application APIs; private
  quality/release/corpus policies are injected at its composition root.
- External distribution imports core/external services and uses the explicit
  application gateway. It never requires maintainer imports or resources.
- Test support cannot be imported by production service packages. Customer
  self-diagnostic scenarios remain delivered, stdlib-only capabilities, separately
  classified from private platform suites. Delivered reusable developer/test
  support may use the reviewed container-only development dependencies, but
  operational source execution and cold recovery stay stdlib-only.

Verify imports statically and from physically projected artifacts: no private
directory merely hidden behind an optional import. Verify wheels from an unrelated
cwd with no repository on the import path as well as source launchers.

### Core and service contracts

Provide only infrastructure justified by current consumers:

| Contract                                                                        | Responsibility and consumers                                                                                                                         |
| ------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `WorkspaceContext`, `ExecutionContext`, `ReleaseContext`                        | Explicit workspace/runtime/installation-role and immutable release identity; never an all-purpose mutable options bag                                |
| `ProcessSpec`, `ProcessResult`, `ProcessRunner` Protocol and subprocess adapter | Literal argv, explicit cwd/env/stdin, capture/stream mode, timeout policy, exit status; used by Git, Docker, scanners and tests                      |
| `ComposeContext` / Compose command builder                                      | Explicit project/files/env/release identity; policy validation stays in dev/prod services                                                            |
| Private JSON/state store and lock context managers                              | Bounded regular files, atomic writes, durability where required, no-follow/owner/mode rules, stable inode locks; domain services own journal schemas |
| `Reporter`, command result and safe error boundary                              | Shared human diagnostics, optional justified JSON, redaction and native child status; no captured secret exception payloads                          |
| Clock/cancellation seams and owned-resource context managers                    | Deterministic tests, restored signal handlers, process-group termination/reaping, exact resource cleanup and durable recovery                        |

Do not model every filesystem call as an interface. Inject state stores or focused
operations where tests need failure simulation; use real temporary files for pure
filesystem behavior. No implicit cleanup of a durable journal or customer data.
Different process policies remain explicit: bounded management discovery,
long-running quality, credential-isolated Git and noninteractive Compose are not
interchangeable timeout/environment defaults.

Construct domain services such as `QualityPlanner`, `QualityRunner`,
`DeploymentService`, `UpdatePlanner`, `UpdateExecutor`, scanner services and
`KnowledgeIndexer` with their actual dependencies. Command objects/adapters parse
only their fixed family and orchestrate these services. A small explicit mapping
is enough; no extensible command bus or competing public Python CLI registry.
All importable modules are inert; `main(argv)` is behind a main guard or entrypoint.

Preserve existing public arguments, validation precedence, exit codes, streaming,
stdin/TTY behavior and plan-only guarantees except the explicitly owner-approved
aggregate test default and its documented scope/filter interface. Translate expected failures once at
the process boundary, preserving child failure codes and existing interrupt
semantics. Internal services return typed results or raise narrow safe exceptions;
they do not call `sys.exit`, rewrite `sys.argv`, `os.chdir` globally or monkey-patch
another module. Restore signal handlers/locks in all exits. Never replay completed
data steps after disconnect, cancellation or resume.

### Python toolchain and dependency policy

Runtime core/application/external tooling stays stdlib-only. Keep Symfony YAML via
the existing PHP helper and existing HTTP mechanisms unless evidence justifies a
separately reviewed change. Select CPython >=3.11 for language metadata and lint/
type targets. Record actual supported/tested minors; do not claim every future
Python version works from an unbounded metadata constraint alone.

Development tooling uses container-owned `.runtime/python/venv`, hash-locked Python
requirements including transitive dependencies, and `.runtime/python/{cache,build,
pycache,reports}`. Keep pnpm/Python/scanner artifact contracts; no `.venv`, `dist`,
`.pytest_cache`, `__pycache__` or egg-info scattered through source. Host operational
commands continue using system stdlib Python; project format/test/type tools run
in containers. Deliver the shared frozen development toolchain/setup needed for
customer Python tests, formatting, lint and typing; owner-only additions stay
private. Customer extensions have documented source/config/test ownership and
reviewed lock/update composition, without editing protected Runa tooling or
silently replacing platform pins. Reopen/recovery must work before installing
development packages. Production contains neither shared nor private developer
dependencies, test support or test suites.

Adopt pytest without xdist or extra fixture plugins initially. Use Ruff formatter
and lint rules covering syntax/imports, modern supported syntax, likely bugs and
unused code, with explicit reviewed exceptions. Pyright uses strict mode across
all first-party Python service, adapter, test and fixture code; validate external
JSON into typed models rather than spreading `Any`. No mass ignores, untyped
fallback imports, blanket exclusions or duplicate mypy/Black/isort stack.

Pyright's official Node distribution is preferred over introducing an additional
Python wrapper/Node downloader. It can be pinned in the reviewed container
toolchain without adding a customer pnpm dependency. Any pnpm manifest addition
still needs explicit owner approval. Pin pytest, Ruff, Pyright, setuptools and
all build/install dependencies during point 2, recording activity, licenses,
advisories/security history, Python compatibility, transitive tree/artifact size
and overlap in the existing maintenance registry/review records. This draft
selects tools, not unreviewed version numbers or a claim of vulnerability clearance.
Resolve versions deliberately once, commit locks, and install with frozen inputs.

Upstream design references reviewed on 2026-10-26:
[PyPA pyproject guide](https://packaging.python.org/en/latest/guides/writing-pyproject-toml/),
[pytest integration guidance](https://docs.pytest.org/en/stable/explanation/goodpractices.html),
[Ruff configuration](https://docs.astral.sh/ruff/configuration/), and
[Pyright configuration](https://github.com/microsoft/pyright/blob/main/docs/configuration.md).
Use their package/configuration capabilities; Runa's no-CI and verification order
override upstream workflow examples. Do not copy upstream implementation or policy.

### Delivered development environment and extension contract

The customer receives an application-development foundation for backend,
frontend and tooling: usable defaults, supported commands, reproducible tools,
public reusable interfaces/test support and documented extension/composition
points. A mechanism does not become owner-only merely because maintainers use it.
Audit each migrated component by responsibility and required consumer, rather than
classifying every test helper or development dependency as private.

Customers can add their own application code, tests, tooling, commands and
workflow configuration in documented application-owned locations, using ordinary
Laravel and optional app modules plus the established frontend/tooling patterns.
They can consume and compose supported Runa capabilities without protected edits,
copying security/lifecycle logic, private imports or maintaining a parallel runner.
Define the supported interfaces, configuration ownership, discovery/registration
rules and update compatibility. Keep registration source-owned and explicit;
this does not authorize a generic plugin bus or caller-supplied executables.

Runa's own PHP/JS/Python tests, private fixtures and platform acceptance harnesses
remain owner-only. Do not deliver those suites to customers or discover them merely
because private files were copied into a customer checkout. Customers write and
own their tests for new behavior; shared bases, factories/fakes and contract helpers
are delivered support with explicit public boundaries, separate from private test
cases. Minimal customer-authored scaffolding/documented examples must not import
private Runa suites or trigger them on installation, reopen or a test command.
Source/delivery projections and production exclusion apply to every language.

### Unified test infrastructure and regression matrix

The default customer `php artisan runa:test` and its recovery `bin/test` transport
run **backend, frontend and tooling** for the application. Provide the corresponding
aggregate owner entrypoint through the existing private maintainer transport;
it runs those three scopes for Runa, including its private Python tests. Both
commands share orchestration mechanisms with explicit ownership/configuration,
while preserving separate suite roots and reports. Do not select ownership from
accidental presence of private directories. Laravel's native test command and
existing focused backend/frontend/e2e/Python transports remain clearly scoped;
document the intentional change from the old backend-only Runa default.

Support explicit scope and test/filter selection without making users bypass
lifecycle guards. Tooling includes Python tests of the selected owner's tooling
and extensions; frontend/backend keep their native test engines. Keep explicit
browser/e2e selection and its existing prerequisites. The aggregate command is
normal developer test execution, not a replacement for the full quality/release
graph or permission to run that graph during implementation points.

Run every independently runnable selected scope and summarize each result even
when another scope fails. Return nonzero if any selected scope fails or cannot run;
missing prerequisites, collection failures and interruptions are never PASS.
A genuinely empty customer scope is reported explicitly, without running private
Runa tests or claiming platform verification. Preserve meaningful native child
statuses in per-scope evidence and deterministic aggregate failure semantics.
Use the existing suite/resource locks, ownership journals, cleanup and reports;
avoid nested lock acquisition, a second coordinator or silent retries. Cancellation
stops further scheduling and reaps owned work before releasing its resources.

Private Python tests live in `tools/maintainer/tests/python/{unit,contracts,
integration,e2e}` with importable `support` factories/fakes. Use `test_*.py`, pytest
`--import-mode=importlib`, strict registered markers and explicit collection roots.
Mark Docker/network/knowledge/database consumers and schedule them through existing
quality prerequisites/exclusive groups. Shared delivered support is separate from
these private collection roots; customer Python/tooling tests use explicit
application-owned roots and configuration. Do not add generic host pytest as the
documented owner workflow: extend the existing maintainer entrypoint mechanism
with a justified aggregate test entrypoint and focused `test-python` adapter
supporting explicit scope/filter options,
container transport, reports and native exit codes. Public Artisan remains the
customer interface; add no owner Artisan aliases or competing test coordinator.

Support includes a recording fake process runner with literal argv/cwd/env/stdin
assertions and injected failures; fake Compose/image inspection; isolated Git
repository/customer receipt factories; typed config/journal factories; controllable
clock/HTTP provider; temporary workspace fixtures; output/result assertions; and
owned Docker resource journals. Avoid one universal fixture that starts the stack.
Unit tests cannot start Docker, network services or real databases implicitly.
Split reusable customer support from owner-only fixtures; public helpers cannot
import the private suite. Test the Python layer itself: pure behavior, services,
process/configuration/state/lifecycle adapters, failures, refusal boundaries and
cross-language/packaging integration. Exercising only PHP/JS tests through Python
or accepting type/lint checks is not Python behavioral regression coverage.

Move existing useful unittest assertions under unified discovery first; pytest
can collect retained TestCase classes. Convert when fixtures/parameterization
remove actual duplication. Replace test inheritance used as a fixture library
with support objects so importing `Updater` does not collect inherited tests twice.
Preserve a mapping from old test/scenario to retained, migrated, strengthened or
deliberately retired behavior. A test count reduction requires explanation, not
necessarily a one-for-one replacement. Do not freeze incidental dict layout or
private method call order unless it is a recovery/security protocol.

| Surface                   | Required final regression evidence                                                                                                                                                                                                                                                             |
| ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| All public transports     | Every bin alias, application hook adapter, fixed-purpose maintainer command, documented direct Python command and external acquisition/install/update entry: help, valid/invalid args, no-arg behavior, exit/output/stdin/TTY and independent cwd                                              |
| Host/devcontainer         | Host stdlib cold operation, configured container execution, no container Docker socket, correct context/root and paths containing spaces; mixed Python 3.11/3.12 contexts                                                                                                                      |
| Management/host executor  | Unknown/duplicate/oversized/request-mismatched plans refused, immutable artifact binding, receipt roles, permissions, disconnected operation, cancellation, stale executor recovery and nonexecuting preview                                                                                   |
| Install/update/team clone | Verified projection, clean/reference variants, optional customer example preservation, explicit remote ownership, development initialization, 0.5.0 upgrade to 0.6.0, preflight/apply/resume, source tampering and no replay after committed data                                              |
| Production                | Separate complete customer fixture, actual image build/deploy/health, exact image and source identity, TLS/SMTP refusal/resume, forward data semantics and unsafe rollback refusal                                                                                                             |
| Quality                   | Manual/legacy selection, prerequisite union, unchanged hard failures, cycle/conflict detection, exclusivity/concurrency, candidate/hash binding, timing, background/status, interrupted coordinator and surviving child refusal                                                                |
| Security                  | Reusable direct/quality scanner results, pinned artifacts, lock consistency, all selected image reports collected despite earlier failure, secret redaction, scanner errors/findings remain nonzero, real review evidence                                                                      |
| Knowledge                 | Freshness/source filtering, owner/customer corpus split, model/input/dimension refusal, pending recovery, incremental reuse, exact golden cases, real CPU/CUDA fallback where supported, production refusal                                                                                    |
| Hooks                     | Real installed safe temporary hooks, secrets/env/key rejection, invalid message, partial staging, formatter input from index, owner/customer policy and mandatory knowledge freshness                                                                                                          |
| Packaging/isolation       | Customer without owner/external directories, owner wheel layered on customer wheel, standalone external bundle, declared resources, private suite/fixture/owner-dependency exclusion with usable shared developer tools; production contains no tooling/AI imports, services or artifacts      |
| Customer extensibility    | Physically projected customer checkout without owner/external source; add application-owned backend, frontend and Python/tooling tests and a tooling extension through public contracts without protected edits; default/scoped commands, reports, failures and reproducible shared setup work |
| Aggregate tests           | Owner and customer defaults include backend/frontend/tooling; explicit roots/filters, native per-scope statuses, all independent results, missing/empty scopes, interruption, locks, cleanup and refusal to discover private tests in customer mode                                            |
| Lifecycle                 | Lock contention, symlink/ownership refusal, signal restoration, kill/reap before cleanup, inactive worker ownership, automatic vs manually owned browser endpoints, task-only Docker cleanup and preservation of durable resources                                                             |
| Interop                   | PHP Actions and generators, shell hooks, Compose setup/knowledge jobs, Devcontainer startup, Docker build inputs, both gate definitions and JS/package script integrations                                                                                                                     |

Implement these scenarios alongside each migration. Execute full suites and broad
acceptance only in the final point; historical logs/source assertions inform the
baseline without an early acceptance run. Necessary focused diagnostics must name
the specific uncertainty they resolve and return their result in the same point.

### Security, deployment and lifecycle constraints

Preserve literal subprocess argv, sanitized credential environments, redacted
reports, allowlisted host plans, receipt-based role checks, production development
refusals, bounded input and safe publication. Shared filesystem/process services
must preserve each caller's stronger rule; no weaker common denominator.
No new infrastructure authority model: dedicated trusted operator execution
retains the documented production responsibility boundary.

Journals and receipts must remain readable across the source replacement. Retain
existing schemas when possible; version any necessary change and add explicit
compatibility/recovery instead of clearing pending state. Test two distinct
workspaces/releases in the same interpreter with no global context interference.
Do not remove locks, retry writes automatically or treat a stale PASS as current.

Acceptance must create isolated customer repositories and uniquely identified
Compose projects. Capture ownership before mutation; refuse unknown/durable
resources. Keep generated state under workspace-root `.runtime`; preserve base
development/test databases, model/Qdrant caches, backups and verification reports.
Use documented worker cleanup under the suite lock and browser recovery journals.
After final consumers finish, show exact task-created containers/networks/tags/
disposable volumes, remove only those and record removed/preserved resources.
Never prune globally or clean shared builder caches.

### Risks and mitigations

| Risk                                                   | Mitigation                                                                                                                                    |
| ------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Rebuild turns into an unbounded framework              | Deliver consumer-driven services in dependency order; pure functions stay functions; no speculative plugins/DI/command bus                    |
| Cold operations acquire development dependencies       | Stdlib source path and standalone acquisition contract; package/wheel tests are separate from operational installation                        |
| Half-migrated callers use different semantics          | Per-entrypoint contract ledger, adapters delegate to one implementation, migrate each caller family atomically                                |
| New launcher runs code from wrong workspace            | Explicit tool root vs target root and versioned external gateway; unrelated-cwd and hostile/symlink fixture cases                             |
| Flattened process/storage abstraction weakens security | Explicit policies and negative contracts for each boundary; raw secret payloads never become default diagnostics                              |
| Test reorganization hides lost coverage                | Old-to-new scenario ledger, no duplicate TestCase collection, behavior assertions and strict missing-prerequisite failure                     |
| Shared resources are deleted by improved cleanup       | Existing locks/labels/journals, exact owned-resource inventory, refusal on uncertain identity, preservation snapshots                         |
| Strict typing becomes widespread suppression           | Validate untyped inputs once, annotate meaningful interfaces, track narrowly justified exceptions and remove transitional ones before closure |
| Migration accumulates permanent shims                  | Every temporary adapter records consumers and removal point; only documented public compatibility transports remain                           |

### Documentation and instruction products

Synchronize affected current guidance in each owning implementation point:
`tools/README.md`, `distribution/README.md`, the public command/host-executor guides,
development/editor/container setup, testing strategy, security and knowledge guides;
owner quality/tooling/maintenance procedures and registry; packaging/architecture
guidance; command migration inventory; Docker/resource lifecycle references.
Public docs must never link to private implementation requirements.

Assess root AGENTS and relevant backend/Docker/security/RAG skills and references,
plus separately authored customer AGENTS/CLAUDE/skills. Establish Python conventions
in one authoritative guide and link to it rather than duplicating procedures.
Do not create a Python skill merely for this task; a genuinely reusable distinct
workflow can be evaluated during implementation under the skills policy. Record
why unaffected products need no change. Customer guidance continues ordinary
Laravel plus optional app modules with no permission to edit protected platform.

Activation and the point 1 contract baseline introduce no delivered capability or
new reusable operating rule. Root/customer instruction products need no change
for this point; the maintainer README reflects activation. Closed roadmaps and
dated audit evidence remain immutable.

### Measurable acceptance and removal criteria

Closure requires all of the following:

- Every inventory entry and public command has a recorded migration/retirement
  disposition, with equivalent meaningful scenario coverage and no silent removal.
- All internal Python integration uses qualified package APIs. No source path
  import injection, runpy orchestration, dynamic file imports, imported-function
  replacement, global workspace/plan mutation or script-as-library loading remains.
  The standalone acquisition transport is explicit and does not authorize these
  integration shortcuts elsewhere.
- Shared process, context, Compose mechanics, private state and reporting have
  one canonical implementation per responsibility and explicit policy consumers.
- Application artifacts operate without maintainer/external packages/resources;
  external bundle works outside the owner checkout; production omits all tooling,
  AI and developer dependencies. Physical artifact tests and import rules agree.
- Reproducible Python toolchain, Ruff format/lint and strict Pyright pass across
  the full first-party scope, including executable adapters and test support.
- Unified unit/contract/integration/relevant e2e suites and all selected existing
  quality gates pass; no unavailable prerequisite becomes a skip or warning PASS.
- A physically delivered customer development checkout can extend backend,
  frontend and tooling and author/run its own tests through documented public
  support, without maintainer files, private platform suites or protected edits.
  Shared developer-tool setup/locks/configuration are reproducible and update-safe.
- Owner and customer aggregate test defaults cover backend, frontend and tooling;
  focused selection, meaningful Python behavior coverage, per-scope reports and
  aggregate failures/interruptions preserve suite/resource lifecycle contracts.
- Public transports, Artisan contracts, PHP/JS/shell/Docker integrations, journal
  compatibility, native exits, cancellation and scoped cleanup remain correct.
- Transitional adapters, duplicate helpers, obsolete test runners/configuration,
  dead imports and resolved owner-specific exception machinery are retired.
  Retained public compatibility launchers contain transport only and are documented.
- Documentation and instruction products match delivery. Final evidence records
  the actual selected graph/candidate, semantic docs review and resource disposition.

### Runa source and customer API review — planning baseline (2026-10-27)

Baseline: `dc11c9d5`. This is a proposed refactor scope, not delivered architecture.
The implementation point numbers referenced here are the newly proposed numbers.

#### Purpose and evidence limits

The owner requested a review of all `src/runa`, including code responsibilities,
customer extension APIs, platform internals, frontend replacement, generators and
`stubs/modules`. The owner subsequently authorized implementation planning in the
active RM-2026-012; instruction updates are future implementation work. This review records findings
and planned outcomes; it does not claim that their implementation is complete.

The inventory covers all 859 tracked files, including the module-root `.gitkeep`.
Default `rg --files` reported 858 because it omitted that placeholder. Source
review traced declarations, public signatures, imports, consumer examples,
providers, module manifests, generator templates, public frontend facades,
replacement fixtures and delivery/build inputs. It is an architecture and code
responsibility review, not a claim that every algorithm, query, security property
or rendered state passed exhaustive validation. Local knowledge was current.

##### Complete source coverage

| Source area                                | Files | Reviewed responsibility and disposition                                                                                                                          |
| ------------------------------------------ | ----: | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Console                                    |    28 | Application/platform commands, database guards, generators and developer diagnostics need explicit execution/ownership classification.                           |
| Contracts                                  |    78 | Framework contracts mix extension declarations, values, guarded-operation context and trusted integration seams. Classify per symbol.                            |
| Database                                   |     3 | Guarded migration scope, repository and required seeds are platform infrastructure. Keep distinct from migration resources.                                      |
| Foundation                                 |     1 | Laravel application adaptation is internal bootstrap infrastructure.                                                                                             |
| Http                                       |    16 | Runtime controllers, middleware, resources and Auth bridge; distinguish supported integration from internal transport.                                           |
| Providers                                  |     2 | Composition currently combines platform mechanics, Core-module integrations and development adapters.                                                            |
| Support                                    |    97 | Includes 76 module-framework PHP files, public extension helpers, internal engines, seeding and test protection. Requires a real boundary.                       |
| Testing                                    |     9 | Shared PHP test support, native worker entrypoint, browser diagnostics and TS package bridges; development support, not authored private cases.                  |
| config                                     |    14 | Delivered defaults and supported application overrides; preserve their loading contract.                                                                         |
| database                                   |    12 | Platform migrations, required seeding and a forward-preparation CLI primitive. Preserve migration/data identities.                                               |
| frontend                                   |     3 | One reusable build plugin and two private suite configurations; split responsibilities and delivery.                                                             |
| lang                                       |     3 | Imported validation catalogue, provenance and license. Preserve audited source bytes.                                                                            |
| management                                 |    14 | Isolated Artisan bootstrap, public operation catalogues, schemas and PHP tool adapters. Distinct from module management.                                         |
| modules                                    |   367 | Auth, Audit and Permissions implementations, contracts, declarations, upgrades, migrations and Auth frontend; retain feature ownership.                          |
| resources                                  |   190 | Public facade, UI implementations, assets, translations, mail/error templates and executable error builders; separate API from implementation and build scripts. |
| routes                                     |     2 | Platform API and Auth presentation routes; preserve named routes and supported ownership.                                                                        |
| stubs                                      |    15 | Customer module-generation inputs; must follow the same public boundary as handwritten customer code.                                                            |
| tools                                      |     2 | Owner branding renderers; not platform runtime or customer extension APIs.                                                                                       |
| composer.json, package.json, platform.json |     3 | Source-owned dependency/composition/version metadata; not redundant installed packages.                                                                          |

#### Access model

Customer extensibility and physical delivery are independent dimensions. All Runa
source remains protected against customer edits. Customer implementations live in
`src/app`, customer tests in `tests/*/app` and application documentation in
`docs/app`.

Proposed classification for each symbol/resource:

1. Public extension: interfaces, values, base classes and composables customers may
   implement, extend or compose through the documented entrypoint.
2. Public consumption: guarded services, module API contracts, Vue components and
   HTTP contracts customers may call; a public interface does not authorize
   replacing a platform security binding.
3. Trusted Runa integration: transaction participants, private identity/grant
   resolvers and reserved preparation capabilities used only by named platform
   composition boundaries.
4. Internal implementation: registries, locks, persistence, resolvers, transport
   adapters and implementation views behind the supported API.
5. Development/build or owner maintenance: classify its supported consumer and
   packaging independently. Public development support still ships to customers;
   private suites and owner branding scripts do not.

Public API classification must include the complete signature: argument/result
values, exceptions, base classes, callbacks, props, slots and controller return
members. Namespace spelling, `public` visibility and a `Contracts` directory alone
cannot establish a supported customer API. Physical target directories and exact
namespaces will be frozen in point 12 before code moves; this review does not
pretend that a proposed layout already exists.

#### Findings and planned corrections

##### F01 — Central Contracts does not communicate audience

`src/runa/Contracts/Modules` contains 45 classes, 32 interfaces and one enum in a
single flat area. `SettingsProvider` and `LifecyclePolicy` are extension seams;
`ManagementPermission` is declaration data; `SettingsParticipant`,
`PreparationParticipant`, `AuthorityGuard` and related contexts also participate in
trusted platform integration. Distinguish customer-authorable declarations,
public operations and restricted composition capabilities. Group by real
responsibility after preserving supported signature identities.

##### F02 — Module Contracts mixes public APIs and restricted integration

Auth has 29 contract files, Audit 17 and Permissions 14. Examples of ordinary
public consumption are `AuthProfiles`, `AuditRecords` and `Permissions`. Auth's
`AuthorizationIdentity`, `OperatorAuthority`, `SecurityMailDelivery` and
`DevelopmentAccounts`, and Audit's `AuditWriter`, carry different audience and
execution restrictions. `Auditable` is a public extension trait, while
`AuditSuppression` is a documented local helper with its own limits; neither
should be classified mechanically as a remote module API. Keep contracts with
their owning module and make each supported use explicit.

##### F03 — Public extensions currently depend on Support types

`Contracts/Modules/WorkHandler.php` imports
`Support/Modules/Work/WorkContext.php` in its required method signature.
`ModuleModel`, `ModuleSchema`, `PublicReferenceRule`, `GuardedApi`, `ModuleApis`,
`ModuleStorage`, `ModuleEvents`, seeding and logging helpers also have documented
customer consumers. A blanket ban on `Support` imports would break valid code.
Inventory the complete public signature closure, then separate customer-facing
entrypoints from the engines and private collaborators they call.

##### F04 — Support contains the module framework itself

76 of its 97 PHP files implement module registry, lifecycle, availability, jobs,
events, settings, APIs, updates, frontend projection and management. This is a
major subsystem, not miscellaneous support. Give it an explicit internal home and
separate its documented consumer adapters from registry/locking/persistence.
Keep existing availability, transaction and recovery semantics.

##### F05 — Current literal checks cannot enforce the intended boundary

`ModuleContractSuite::lint()` accepts foreign-module PHP imports when the name
contains `Contracts`. It cannot distinguish a public contract from a private
identity resolver. Its source scan also deliberately skips non-listed extensions
and has documented dynamic-name limitations. Add audience-aware PHP and TS/Vue
import/signature checks to existing local quality ownership, including aliases,
fully qualified references and generated/customer examples. Keep explicit trusted
integration exceptions with named callers, purpose and test coverage. Do not
claim these checks sandbox trusted code.

##### F06 — Bootstrap/provider composition crosses several responsibilities

The 299-line `RunaServiceProvider` registers module engines, Core Auth/Audit/
Permissions bridges, test adapters, database command overrides, view/translation
loading, queue logging/guards and runtime heartbeats. `AuthTransportServiceProvider`
adds Fortify and account/gate composition. Split registrations by real
responsibility and runtime/development context while preserving cold startup,
provider order and route/config caches. Shared composition may intentionally know
Core modules; an isolated integration layer is clearer than scattered bridges.

##### F07 — Physical PHP paths and autoload namespaces need one coherent model

The broad `Runa\\` Composer mapping, custom manifest/registry-aware module loader,
separate lowercase `management` loader, and mixed `Tests`/`Runa\\Testing`
classes have different rules. Do not replace the guarded module loader with an
unconditional Composer bypass. Plan exact physical roots, namespaces, classmap
exclusions and dev loading together; remove avoidable PSR-4 noise without hiding
misplaced classes. Preserve documented customer names unless a breaking release
is explicitly approved.

##### F08 — Module backend roots have accumulated upgrade history

Auth has 38 `Upgrade*.php` handlers, Audit four and Permissions 14; respectively
30, four and 11 have empty `run` bodies. These are retained version-graph edges,
not automatically dead code. Group initialization/upgrades/proxies/handlers so
current capabilities are discoverable. Assess shared no-data handlers only where
step IDs, version edges, fingerprints, retry/resume and installed upgrade history
remain compatible. Do not delete or squash migration history as directory cleanup.

##### F09 — Definitions mixes declarative metadata and executable work

Auth's `Definitions` holds `IdentityApi`/`ProfileHttp` declarations alongside
`ProfileMailHandler`, `ScheduledLifecycleHandler`, cleanup/reminder handlers and
work policies. Separate pure definitions from execution handlers/policies. The
module's `Http` directory likewise has controllers and resources side by side
while requests have their own subtree. Use the same understandable convention in
all built-in modules and the generator; do not add empty layers for symmetry.

##### F10 — Private suite configuration is mixed with reusable frontend build code

`frontend/module-components.mjs` is a reusable module build plugin. Neighboring
`vitest.config.mjs` and `playwright.config.mjs` point at private `tests/*/runa`
suites, yet `frontend` is not an owner-only source projection and is copied into
the frontend build stage. Keep the reusable plugin available to customer builds;
move private configuration to owner-owned tooling/test configuration and update
its transport/dependency resolution. Build-stage presence is not proof of a
production runtime data leak.

##### F11 — Testing mixes languages and several namespaces

`Testing/TestCase.php` belongs to namespace `Tests`; neighboring commands and
`TestRun` use `Runa\\Testing`; TS Vue/Vitest/Playwright bridges and
`browserProblems.ts` sit beside a worker-database PHP entrypoint. Classify shared
customer testing APIs, runner internals and browser support explicitly and place
them by responsibility/language. `Support/TestDatabaseGuard` and
`Support/Modules/Development/ModuleContractSuite` also need coordinated treatment.
The latter has supported runtime diagnostics; do not remove it from production
merely because its directory is named Development.

##### F12 — Management is operational tooling, not module business management

`management/bootstrap.php` intentionally creates an isolated Artisan container
without the application kernel, environment, DB or key. Its commands/catalogues
serve delivered operations; this architecture is useful. Group it with delivered
PHP tooling, distinct from `Support/Modules/Management` and its read descriptors.
Preserve source-owned catalogues, JSON plan/schema validation, host/container
transport and cold recovery; do not create a second command registry.

##### F13 — Generator templates must be ordinary public API consumers

`stubs/modules/proxy.php.stub`, `model.php.stub`, `migration.php.stub` and
`test.php.stub` directly reference the currently supported Support helpers.
Organize the generator and its 15 templates as one developer capability. Every
newly generated file must use the final public contract surface, retain preview
and exact hash overwrite checks, and write only to the owning application/test/
doc roots. Customers must never need a private platform dependency to implement a
recommended skeleton.

##### F14 — Generated frontend anatomy disagrees with current module discovery

The generator's component target is `resources/js/<Name>.vue`; the module Vite
plugin discovers `frontend/components/<snake_case>.vue`, while Auth also owns
feature presentation directly under `frontend`. This does not prove a generated
component is promised automatic registration: its current contract explicitly
omits that step. It does reveal divergent authoring conventions. Define canonical
private/public component locations and opt-in registration, then synchronize the
generator, build plugin, module example, docs and error messages.

##### F15 — Frontend public facades exist, but their boundary is porous

`resources/js/runa/{ui,auth,http,modules}` is an existing supported facade.
`@runa/*` also exposes the implementation tree. The application Reference panel
currently imports `@runa/lib/http` although `@/runa/http` exists. Existing bundle
boundary tests check tree shaking/production content, not every customer import.
Keep public facade imports stable, correct examples and restrict customer-source
imports to supported entrypoints. Internal platform implementations can still
compose one another through explicit internal dependencies.

##### F16 — Auth replacement is implemented but its typing can be clearer

`AuthPresentationProvider` merges subtree overrides and supplied defaults.
`AuthEntry`, `AuthAccount` and `AuthWelcome` select customer components;
`useAuthEntry`, `useAuthAccount`, `useAuthWelcome` and `useApplicationSession` are
publicly exported. `AuthPresentation` currently types every replacement as the
broad Vue `Component`; controller results are inferred from large implementation
functions. Publish consumer-facing props/slot/controller types where needed and
validate them through application-owned fixtures without private imports.
Preserve fallback defaults, keyed account/page remounts, CSRF handling and session
disposal; typed metadata is no substitute for real replacement behavior.

##### F17 — Auth account orchestration is too concentrated

`useAuthAccount.ts` has 1,512 lines, `useAuthEntry.ts` 593, and the default account
view 659. The account controller combines profile editing, avatars, sessions,
trusted devices, proof, delivery preferences, history, draft/conflict handling and
read reconciliation. Extract feature collaborators behind the existing public
controller, sharing account revision, request epoch, cancellation and mutation
ordering. Avoid independent caches/controllers that duplicate reads, restore a
previous account, reset drafts or replay writes.

##### F18 — Generic frontend infrastructure includes Auth-specific behavior

`lib/accountPreferences.ts` sends directly to Auth preferences, while
`lib/preferenceStore.ts` binds guest/account preferences; `lib/welcomeModules.ts`
contains Runa module catalogue presentation. Distinguish generic store/UI
contracts from Auth/session integration and the supplied welcome presentation.
Keep public preference behavior and late-response/logout fencing intact. Internal
motion/layer helpers should not accidentally become customer APIs simply because
they have a TypeScript export.

##### F19 — Supplied routing is minimal, but should stay separate from extensibility

`DefaultApplication.vue` selects paths directly from `window.location.pathname`;
Auth application navigation and presentation routes also declare `/app` and
`/account`. Full replacement through `src/app` and public session controllers is
supported; the small default is not a general application router. Document that
boundary, centralize repeated route/entry data where useful and ensure customers
can add pages/navigation without editing this dispatcher. No router dependency is
approved by this review.

##### F20 — Executable builders live among runtime resources

`resources/errors/build.php` and `render.mjs` build static errors;
`resources/views/errors/page.php`, messages and templates serve runtime rendering.
Move executable build responsibilities to delivered build tooling, leaving
resources and runtime adapters with their owners. Preserve generated artifact
locations, standalone/cold error handling and custom error-layout adoption.
Mail bridge views such as `views/mail/html/layout.blade.php` intentionally include
`runa-mail`; do not delete them as apparent duplication without preserving native
Laravel Markdown integration and application overrides.

##### F21 — Owner branding scripts have an obsolete source home

`tools/assets/render-favicon.mjs` and `render-mail-logo.mjs` depend on Playwright,
a particular local endpoint and Runa branding. Move them to private owner asset
tooling with an explicit documented execution context and output ownership.
Static logos/fonts/icons/licenses remain ordinary resources. Do not turn these
scripts into a generic customer plugin mechanism.

##### F22 — Database casing and normal Laravel resources are not defects

`Database` contains PHP infrastructure; `database` contains migrations, seeders
and a forward-only deploy primitive. Preserve that distinction in the target map.
Likewise keep clear ownership for config, routes, Foundation, Http, validated
language snapshots, resources and manifests. `runa/composer.json` and
`runa/package.json` feed application manifest composition; deleting them as
redundant would remove a source of truth. Directory count alone is not a defect.

##### F23 — Development/runtime exposure needs explicit classification

Production copies complete Console, Support and module trees; those contain
source diagnostics, generation support, development contributors and module
frontend sources as well as runtime code. Some operator diagnostics are required
in production and must remain available. Classify execution mode separately from
API audience and express the required source/image projection explicitly. Reject
private suite/test dependencies in runtime while preserving cold commands,
forward updates, module discovery and trusted operator recovery.

##### F24 — Documentation and instruction products must describe the same boundary

The module guide still describes a skeleton contract test inside module source
and cites Reference version 0.1.0 after central test relocation and 0.1.1. It also
uses module-local tests wording. Correct current-state guidance in its owning
implementation point. Plan one public boundary guide and route owner/customer
AGENTS, backend/Laravel/frontend skills, generators and examples through it.
Historical roadmaps/evidence and imported provenance remain unchanged.

##### F25 — Focused frontend replacement probe currently fails before execution

The supported command was:

```bash
python3 tools/maintainer/test-frontend ../tests/frontend/runa/unit/auth-entry.test.ts ../tests/frontend/runa/unit/auth-account.test.ts --filter=customization:
```

It selected only replacement behavior in two files. Both files failed during
collection: dependency scanning could not resolve `@vue/test-utils`, and Vitest
reported that it could not find the current suite when Vue Test Utils registered
`afterEach`. No selected customization case executed. This is an observed test
infrastructure failure, not evidence that a rendered customer view itself failed.
The earlier simple application welcome case is not sufficient acceptance for
these richer imports. Fix canonical package resolution/single Vitest identity
first; do not disable dependency scanning/assertions, swallow warnings or move
authored tests back into `src` to manufacture PASS.

#### Frontend extensibility assessment

The capability exists in source: public UI/layouts/forms, HTTP and module-page/
slot APIs; subtree presentation overrides; public headless Auth controllers; a
minimal application root that customers own; and public error-layout composition.
Existing replacement fixtures import only public facades. They cover layout-only
replacement, custom login retaining CSRF/restriction behavior, custom account
presentation, application content under the supplied session boundary and a full
customer shell that removes content after 401.

The focused attempt above did not execute those cases, so this review does not
report a fresh runtime PASS. Repair and rerun that exact focused selection in
point 11. Later public-only consumer fixtures must additionally cover authored
application pages/forms/navigation, module pages/slots, custom error layouts,
controller disposal/account switch, draft preservation and compatibility with
existing defaults. Implement scenarios during the relevant points; broad browser,
type, production and projected-customer acceptance runs only in final point 20.

#### Planned compatibility and validation

The active roadmap retains the already approved compatible feature 0.6.0 target.
The first boundary-design point must record a public API/namespace/alias and
module-manifest compatibility map before implementation. Public means supported
use, including documented helpers currently under Support. Internal moves do not
authorize breaking those uses. An incompatible API or module contract change
requires explicit owner approval and the appropriate MAJOR decision first.

Preserve data schemas/migration history, module version/step identities, guarded
operations, current command names and native exits, hook/runner lifecycle,
customer source/configuration ownership and delivery receipt compatibility.
Assess module patches/minors and dependent constraints for actual implementation
changes. No new dependency, CI, general plugin system or runtime authority layer
is authorized.

No full suite, static analysis, image build, scanner pipeline or broad acceptance
ran for this review. No container/network/image/volume was created or removed;
the focused browser runner used existing development infrastructure. Source
classification and import checks are prevention aids, not security isolation.

## Implementation plan

Each implementation point produces code, test code,
synchronized documentation, a logical commit and checklist update. Refresh the
active roadmap, relevant decisions, local knowledge and relevant skills before
each point. Do not routinely run suites at point boundaries. Only necessary small,
fast checks resolving a concrete uncertainty belong before the final point;
mandatory commit hooks always apply. No broad baseline, installation acceptance,
static analysis, image build or scanner pipeline is a prerequisite for later
implementation. Build their harnesses/scenarios first and execute them at the end.

- [x] 1. Capture executable contracts and the migration ledger.
  - Prerequisites: owner approval to activate; confirmed release metadata; updated
    predecessor/main and normal roadmap branch activation.
  - Expand the audited inventory to every tracked Python file, inline Python,
    symlink alias, resource and caller. Record ownership, context, dependencies,
    public arguments/output/exits, current test coverage and target disposition.
  - Extract behavioral expectations from current source/documentation and existing
    evidence. Add contract fixture definitions for all command families without
    executing broad baseline acceptance. Resolve source/doc disagreements explicitly.
  - Record public compatibility boundaries, owner-only exceptions and temporary
    adapters with their consumers/removal point. Complete dependency/order review.
  - Deliver the ledger and architecture documentation, test definitions and commit;
    do not turn this point into a new architecture-choice questionnaire.

- [x] 2. Establish packages, reproducible development tooling and test discovery.
  - Prerequisites: point 1 contracts and packaging/delivery inventory.
  - Add explicit package initializers, customer/owner/external package metadata,
    allowlisted resources, CPython policy, frozen reviewed dependencies and registry
    records. Keep the source host path stdlib-only and development installs container-owned.
  - Configure pytest importlib discovery/markers, Ruff and strict Pyright scope.
    Add common fake runner/config/repository factories and the supported owner
    `test-python` transport; integrate definitions with existing quality groups.
  - Keep unmigrated behavior callable until its owning point replaces it; temporary
    import adapters are tracked, not accepted as the final architecture.
  - Add physical package/import-boundary and standalone-launcher test scenarios.
    Synchronize tool setup/testing/package docs and instruction assessments.

- [x] 3. Implement shared context, process, state and lifecycle infrastructure.
  - Prerequisites: point 2 packages and test support.
  - Implement the core contracts above, preserving explicit timeout/stdio/env and
    strong filesystem/lock policies; add typed error/result/reporting boundaries.
  - Add unit/failure/cancellation tests, including two workspaces/releases in one
    process and process reaping before lock/resource release.
  - Migrate first reusable runtime/Compose consumers; remove their duplicate
    mechanics only after all callers in that slice use the canonical service.
  - Document API ownership, dependency direction and runtime artifact locations.

- [x] 4. Migrate application guards, development and management/host transport.
  - Prerequisites: point 3 infrastructure.
  - Migrate repository receipt/source policies, dependency composition and module
    version checks, development config/setup and host/container resolution.
  - Separate discovery/image identity, validated plans, request transport and
    executor lifecycle into injected services, preserving fixed allowlists and
    existing PHP Action ownership. Make imports inert.
  - Update bin/hook bootstrap adapters, PHP paths and Compose/Devcontainer startup
    together. Add cold/host/container, invalid-plan, role/refusal, disconnect and
    compatibility regression scenarios. Keep application startup independent of AI.
  - Reconcile catalog/schema/allowlist routing facts and environment parsing
    boundaries; add consistency contracts without removing independent validation.
  - Synchronize public commands, host executor/development docs and affected products.

- [x] 5. Rebuild shared quality, diagnostics, scanners and hook services.
  - Prerequisites: point 4 guards and transports.
  - Reconcile shared customer development/test capability versus private owner
    policy. Refactor point 2's private-only Python setup/config/locks into delivered
    reusable mechanisms with explicit private additions; define supported customer
    configuration/composition contracts and keep operational/cold paths stdlib-only.
  - Replace owner quality monkey-patching with explicit context/planner/policy
    injection. Separate YAML/config parsing, planning, execution, evidence/status
    and reporting; preserve graph semantics, latest-run locks and background handoff.
  - Unify scanner/audit/result services for direct diagnostics and quality; preserve
    strict findings/errors, all-report collection and content-bound actual review.
  - Separate shared staged checks from owner history/release rules; preserve index
    content, partial staging, secret scanning and knowledge freshness. Add changed
    Python Ruff checks to hooks without full analysis/suites there.
  - Update private quality/release/registry/docs checks with qualified imports and
    application APIs. Move/remove owner exception code from delivered packages after
    reviewing removal conditions; preserve historical byte records.
  - Add scheduler, evidence, hook/security and import-isolation scenarios. Update
    both gate definitions and quality/security/hook documentation in the same point.

- [x] 6. Migrate deployment and external acquisition/install/update.
  - Prerequisites: points 4–5 shared services and policies.
  - Replace global production ROOT/STATE mutation with explicit release contexts;
    separate source acquisition, Compose/image validation, data transitions,
    health/isolation and deploy/rollback orchestration.
  - Rebuild external installation/update as package services using the explicit
    application gateway, separating contracts/customization/files/runtime from CLI
    orchestration. Break updater import cycles and preserve durable journal phases.
  - Preserve standalone bootstrap, verified sparse projection, external tool bundle
    operation, remote/history boundaries and PHP module/database gateways.
  - Consolidate repeated delivery exclusions and manifest-composition rules across
    acquisition, application and updater, including version-separated artifact checks.
  - Add 0.5.0 upgrade, two-release, interrupted source/data switch, TLS/SMTP,
    unsafe rollback and production/customer distribution regression scenarios.
  - Include shared customer developer-tool/support delivery and reconciliation of
    customer-owned extensions/configuration/frozen inputs in acquisition/update
    scenarios; private suites stay excluded and production remains tooling-free.
  - Synchronize installation/update/production/security docs and package projections.
    Do not run real installation/build/deployment acceptance yet.

- [x] 7. Migrate development-only knowledge and owner extensions.
  - Prerequisites: points 3–5 context/process/config services and owner injection.
  - Package corpus/config/provider/store/index/retrieval/startup with typed models,
    injected HTTP/store/clock and explicit owner corpus/golden composition.
  - Preserve hashes/schema identities, branch freshness, incremental embeddings,
    pending write recovery, exact golden expectations and controlled CPU fallback.
    Review parser-hash relocation so an implementation move does not silently reset
    the model/collection or falsely reuse incompatible parsing.
  - Keep independent knowledge-init and optional application startup behavior;
    production/customer-role guards precede execution as before.
  - Migrate fake/live knowledge scenarios and docs, with real retrieval/AI gates
    deferred to the final point. Preserve caches and collections.

- [x] 8. Deliver shared test infrastructure, aggregate runners and private suites.
  - Prerequisites: points 4–7 migrated domain APIs and their test code.
  - Move remaining unittest cases into unified discovery; retain useful assertions,
    replace fixture inheritance/runpy with support factories and qualified imports,
    and reconcile the old-to-new coverage ledger.
  - Consolidate host acceptance process/Git/Compose/resource helpers; retain unique
    project labels, ownership snapshots, journal recovery and suite exclusivity.
    Parameterize container identity instead of fixed dev container names.
  - Migrate localization/service fixtures, error/SMTP/FastCGI helpers and still-live
    PostgreSQL probes. Assess and document obsolete historical probe retirement.
  - Separate delivered reusable test bases/fakes/helpers from private Runa cases,
    fixtures and acceptance harnesses across PHP/JS/Python. Provide customer-owned
    Python/tooling discovery/configuration and public extension/support contracts;
    customers can add tests and tools without maintainer imports or protected edits.
  - Implement owner and customer aggregate defaults for backend/frontend/tooling,
    with Python behavioral tests, explicit scoped/filter options, per-scope results
    and deterministic aggregate failure semantics. Keep independently runnable
    scopes running after failures, report empty/blocked scopes accurately, and
    preserve cancellation, existing native engines, PHP workers, suite/resource
    locks, reports and cleanup without nested locks or a second coordinator.
  - Keep focused backend/frontend/e2e/Python transports and route their scopes
    through the existing quality scheduler. Add aggregate ownership/failure and
    projected customer extension scenarios now; execute broad acceptance in point
    20 only. Document exact commands, prerequisites and changed default semantics.

- [x] 9. Complete caller migration and retire the old architecture.
  - Completed: caller/facade retirement, explicit service composition, private
    analysis separation, source-bundle preparation, full-scope function/fixture
    annotations and complete alias/resource/non-Python/inline reconciliation.
    Full strict analysis, artifact and runtime PASS belong to point 20.
  - Prerequisites: points 1–8 implemented; no old-only service consumer.
  - Reconcile every ledger row; update remaining PHP/JS/shell/generator/Compose/
    Docker/gate/config references, symlink behavior and compatibility transports.
  - Remove transitional shims, runpy/dynamic import wiring, sys.path/PYTHONPATH
    injection, global context monkey-patching, duplicate subprocess/Compose/state
    helpers, obsolete runners/tests/config and unused dependencies.
  - Finalize package file/resource lists and standalone bundle preparation. Retain
    only public documented transport wrappers; no parallel service implementations.
  - Finish full-scope Python annotations and rule configuration without hiding
    difficult modules. Complete semantic documentation and both instruction-product
    assessments; record all retained exceptions and their justified boundaries.
  - Reconcile customer projection/toolchain resources and caller/help/catalog
    defaults against aggregate test semantics. Update owner/customer instruction
    products and public guides for authoring backend/frontend/tooling extensions
    and tests, supported public contracts and configuration/update ownership.
  - Align editor/container Python settings and document one coherent cross-language
    command workflow; retain native PHP/JS runners and existing quality ownership.
  - Prepare the final graph definitions and acceptance scenarios; do not run an
    early full gate, static analysis, image build or acceptance phase here.

- [x] 10. Centralize authored tests by technology and ownership.
  - Prerequisites: points 1–9 complete and committed; owner approval on 2026-10-27.
  - Move PHP/application/platform/module cases and fixtures to tests/backend;
    frontend/unit/browser/native scenarios to tests/frontend; Python/private native
    tooling scenarios to tests/tools. Each area separates app and runa ownership.
  - Update runner discovery/configurations, imports/autoloading, relative fixtures,
    generators, module test metadata and source checks without duplicate suites or
    symlink facades. Preserve public commands, scopes, native exits, locks/reports,
    process/resource lifecycle and customer-owned extension configuration.
  - Reconcile acquisition/update projection and old receipt compatibility, package
    resources and strict analysis scopes; exclude private suites from customers and
    every test root from production. Retain immutable historical source maps and
    update current target mappings rather than rewriting their baseline identities.
  - Synchronize public testing/module/command guides and both instruction products.
    Include focused ownership/discovery/generator/projection regressions; full
    suites, analysis, builds and live acceptance remain exclusively in point 20.
  - Commit completed implementation, documentation and checklist together.

- [x] 11. Repair central frontend test package resolution.
  - Prerequisites: completed point 10; observed F25 collection failure.
  - Repair Vue Test Utils/Vitest dependency resolution and shared module identity
    for suites outside src using the existing installed packages. Preserve central
    test placement, frozen pins, supported transports and native failure reporting.
  - Do not suppress failed collection, required assertions or diagnostics, weaken
    configuration, introduce a second dependency installation or claim PASS from
    the simpler welcome case.
  - Add meaningful regression coverage for richer shared test imports. After the
    repair, the exact two-file customization selection in F25 is a justified
    focused diagnostic; no broad frontend suite or acceptance runs here.
  - Synchronize affected test support guidance and commit the completed fix.

- [x] 12. Define the complete public/internal API and placement map.
  - Prerequisites: point 11 complete; review F01–F25 and existing public guides.
  - Record each source area's owner, public extension/consumption entrypoints,
    trusted integration and internal implementation, separately from dev/runtime
    mode and customer/production projection. Cover every source directory, module,
    frontend facade, generator and test-support resource; no unclassified residue.
  - Inventory supported PHP symbols and TS/Vue imports, signature value/exception/
    base/callback types, props/slots/controller members and existing customer and
    generated consumers. In particular retain WorkHandler/WorkContext and the
    documented public Support helpers; Contracts is not a blanket audience rule.
  - Freeze an explicit current-to-target file/namespace/alias map and dependency
    direction before source moves. Keep exact public identities and observable
    behavior compatible with approved feature 0.6.0; stop for owner decision before
    any incompatible public/module contract or proposed MAJOR change.
  - Record narrow named callers for reserved integration and required production
    diagnostics. Define understandable public/internal homes by responsibility;
    preserve module ownership and avoid a generic Support/Utils replacement.
    Keep ordinary Laravel and optional app modules equally supported; no full-DDD
    conversion or mandatory customer domain/repository/mapper layers.
  - Document the agreed map and migration dependencies; no broad checks here.
  - Frozen specification: [API and placement map](../../architecture/api-boundary/README.md),
    with complete baseline file and source-anchored signature inventories.

- [x] 13. Reorganize platform PHP internals and composition.
  - Prerequisites: point 12's compatible target map; findings F03–F07 and F22–F23.
  - Give the module framework an explicit internal home and separate supported
    adapters/base classes from registries, locks, persistence and resolvers.
    Reconcile the public signature closure without customer private imports.
  - Split provider composition by platform mechanics, Core-module integration and
    development context. Keep integration bridges explicit; preserve cold startup,
    queue/logging/heartbeat behavior and configuration/route cache ordering.
  - Reconcile Database infrastructure versus migration resources, Foundation,
    Http transports and command ownership. Preserve PostgreSQL/Audit/authorization,
    transaction ordering, availability fences, recovery and operator boundaries.
  - Update Composer/development autoloading and guarded module loading together;
    eliminate avoidable classmap/PSR-4 ambiguity without bypassing source checks.
  - Deliver regression scenarios and current docs in the same point. Only
    necessary focused uncertainty checks run; full analysis/suites remain final.

- [x] 14. Separate built-in module contracts from private execution.
  - Prerequisites: point 13; findings F02, F08–F09 and the point-12 contract map.
  - Organize Auth/Audit/Permissions public DTOs/interfaces/traits and documented
    local helpers separately from trusted participants/resolvers, private queries,
    models, handlers and policies. Public API use never authorizes replacement of
    trusted security bindings or direct foreign persistence.
  - Separate pure Definitions from executable work handlers/policies where their
    responsibilities differ; align controller/request/resource and proxy locations
    across built-in modules and the planned generator convention.
  - Group initialization/upgrades coherently. Retain migration bytes, version edges,
    step IDs/fingerprints, retry/resume and installed-history compatibility; no
    deletion/squash of historical upgrades disguised as cleanup. Consolidate empty
    handler implementations only when those contracts are demonstrably preserved.
  - Assess each changed module's independent compatible version/changelog and
    dependent constraints. Add representative public/guarded integration scenarios
    and synchronize module guides; broad runtime/version acceptance remains final.

- [x] 15. Make frontend extension contracts complete and explicit.
  - Prerequisites: point 14; findings F15–F16, F18–F19 and frontend assessment.
  - Preserve supported ui/auth/http/modules facade imports and publish the complete
    customer-facing type/props/slot/controller surface needed for own screens.
    Distinguish public module exports from private components/composables and
    prevent examples such as Reference from teaching implementation imports.
  - Repair the existing strict TypeScript failure in
    `tests/frontend/runa/unit/fixtures/customization/CustomerEntry.vue` (line 31,
    untyped callback parameter), observed during development setup on 2026-10-28.
    Preserve replacement behavior and required assertions.
  - Keep defaults usable; support subtree presentation/layout overrides, custom
    forms/pages/navigation, full frontend replacement, module pages/slots and
    application error-layout composition without protected source edits.
  - Keep the minimal default dispatcher separate from application routing choices;
    remove unnecessary duplicated route/entry declarations through owned existing
    contracts. Do not introduce an unapproved router or global state dependency.
  - Add customer-owned consumer fixtures using only public entrypoints: supplied
    and custom layout, headless entry with CSRF/refusal, custom account, full shell
    with 401 cleanup, module page/slot and static error composition. Cover keyed
    page/account lifetimes, reactive props and retained fallback/default behavior.
  - Update supported frontend adoption/type contracts in the same point; execute
    only focused diagnostics for concrete uncertainties, not broad browser builds.

- [x] 16. Refactor frontend orchestration behind the public contracts.
  - Prerequisites: point 15; findings F17–F18.
  - Split the 1,512-line account controller by profile/avatar, sessions/trust,
    proof/security, preferences/delivery and history responsibilities where useful.
    Refactor entry/default presentation similarly when it improves actual cohesion;
    do not create layers or components solely to meet a line-count threshold.
  - Keep the public controller contract stable. Share account revision, mutation
    ordering, request epoch, cancellation and session lifetime across collaborators;
    avoid duplicate reads, independent conflicting stores or copied security logic.
  - Separate generic UI/preference contracts from Auth-specific transport/session
    integration and welcome presentation while retaining public default behavior.
  - Preserve dirty drafts, local conflict decisions, automatic read reconciliation,
    account-switch isolation, teardown and no-write-replay semantics. Implement
    concurrency/disposal/replacement regressions now; broad execution stays final.
  - Synchronize frontend/component references and public examples in this point.

- [x] 17. Organize developer support, generators and executable build tooling.
  - Prerequisites: points 13–16; findings F10–F14 and F20–F21.
  - Place isolated management PHP tooling/catalogues/schemas with their delivered
    tooling responsibility, separate from module management. Keep one source-owned
    Artisan registry and preserve cold recovery and host/container plan validation.
  - Separate public PHP/browser test support from private suite configurations and
    runner internals. Reconcile namespaces, dependency resolution and production
    requirements; do not classify runtime contract diagnostics by folder name.
  - Keep the reusable module Vite plugin delivered, place private Vitest/Playwright
    configurations with owner tooling, and retain source/dependency resolution
    without another package installation or symlink directory facades.
  - Organize ModuleGenerator and all stubs/modules templates as one developer
    capability. Generated PHP/Vue/tests consume only public APIs and follow the
    canonical module frontend anatomy/registration and central test/doc roots.
    Preserve preview, exact hash overwrite guards and ownership restrictions.
  - Move executable static-error builders to delivered build tooling and owner
    branding renderers to private maintenance tooling. Preserve runtime resources,
    generated artifact locations, native mail bridges, licenses and overrides.
  - Update source/config references, resource manifests and affected guides; add
    scoped generator/build/support scenarios without running broad builds here.

- [ ] 18. Enforce API boundaries and reconcile delivery/upgrade ownership.
  - Prerequisites: points 12–17; findings F05, F07, F10, F15 and F23.
  - Extend existing local checks for declared public/internal boundaries across PHP
    and TS/Vue, including aliases/FQCNs, signature closure and customer/generated
    examples. Replace blanket Contracts acceptance with explicit audience mapping;
    allow reserved imports only for named trusted platform integration callers.
  - Keep ordinary Laravel customer applications supported without requiring app
    modules. These checks prevent accidental coupling, not malicious trusted code.
    Do not add CI, a generic authority layer, a new registry or a dependency.
  - Reconcile source acquisition/update preservation, exact receipt compatibility,
    module source/runtime metadata, Composer/pnpm composition, Docker copy inputs,
    development/production catalogs and quality/test/editor paths with the map.
  - Preserve customer-authored code/config/tests, public development support,
    required runtime diagnostics and trusted operator recovery. Exclude private
    suites/owner configs and development-only packages from inappropriate outputs.
  - Implement projected-customer extension/update and production-isolation
    scenarios for final execution. Keep existing scoped quality ownership; broad
    package/image/install/upgrade/scanner acceptance belongs only to point 20.

- [ ] 19. Synchronize architecture guidance and retire obsolete placement.
  - Prerequisites: points 12–18 implemented; finding F24 and final source map.
  - Write one authoritative public API/internal boundary and source-placement guide,
    with exact supported entrypoints and customer examples for PHP and frontend.
    Update root AGENTS, owner backend/Laravel/frontend and affected Docker/security
    references, plus separately authored customer AGENTS/skills and source metadata.
  - Skills route to public procedures rather than duplicating them. Require future
    capabilities to declare owner, audience, public signature and implementation
    home; generated examples must follow the same rule as customer handwritten code.
  - Reconcile current module/frontend/test/command/build docs and metadata with the
    implemented layout, including the stale generated-test and Reference-version
    wording. Never edit closed roadmaps, dated evidence or imported snapshots.
  - Remove old empty directories, duplicate configurations, obsolete current-state
    references and temporary shims when their replacements are complete. Preserve
    public identities, private runtime artifacts, backups, reports and resource/data
    histories; record deliberate retained paths instead of claiming total cleanup.
  - Prepare the public-only backend/frontend/module/generator consumer matrix and
    final quality graph definitions. Commit completed implementation/docs/products;
    no full verification or acceptance runs in this point.

- [ ] 20. Full verification, release finalization, merge and final report.
  - Prerequisites: points 1–19 complete and committed, migration/coverage ledgers
    reconciled, only this final point open; no unsafe worktree or unresolved premise.
  - Review current-state README/docs/metadata/AGENTS/skills/customer products against
    actual delivered behavior, startup, commands and development/production scope.
    Record sources, findings and disposition; historical records remain immutable.
  - Confirm `quality_groups: all` and rationale against final scope. Preview exact
    graph with `python3 tools/maintainer/quality-plan --all`; inspect commands,
    prerequisites, exclusivity and inclusion of all Python format/lint/type/unit/
    contract/package and relevant real integration/e2e scenarios above.
  - Run full verification using `python3 tools/maintainer/quality-roadmap --all
--background`; end the turn after launch and provide `python3
tools/maintainer/quality-status`. Do not poll, substitute internal runners or
    interpret missing/excluded evidence as PASS. Selected security/AI gates are strict.
  - Verify the point-12 boundary map, public signature/import contracts, generated
    consumers, customer-owned backend/module/frontend extensions, default and fully
    replaced UI, session/account teardown, build projections and retained upgrade
    histories. No open regression or unclassified public dependency may be waived.
  - Include actual host/container/standalone/package/customer upgrade and production
    acceptance, scanners, hooks, knowledge golden/freshness and supported PHP/JS/
    database/browser suites. Verify both aggregate command defaults, targeted
    selections, scope failures/interruptions and Python behavior. In a projected
    customer fixture, add and run customer-owned backend/frontend/tooling tests and
    a tooling extension using only delivered public support; assert no private
    suite or production developer-tool leakage. Collect complete candidate-bound results and artifact
    manifests. Inspect/show/clean task-created disposable resources after their last
    consumer; record exact removed and preserved identities.
  - Any required fix becomes an inserted implementation point immediately before
    this point; keep the roadmap active and rerun the complete final verification
    from the beginning after the fix. Stop for discussion if a premise changes.
  - After PASS, reconcile feature 0.6.0 with baseline and follow the release workflow:
    VERSION/platform manifest, owner changelog/README, closure/review/report evidence,
    incremental knowledge refresh and focused docs/release checks. Preserve the
    independent customer application/module versions unless their own code changed.
  - Close on the roadmap branch, merge with a merge commit, sanity-check main,
    create annotated `v0.6.0`, push/publish under normal owner policy and delete the
    roadmap branch. Report gate, release, migration limitations and resource cleanup.

## Execution evidence

### Point 1 — contract baseline and migration ledger (2026-10-26)

- Activation commit: `32d55d2f`, from clean main `0fab18be` after successful
  origin fetch and fast-forward reconciliation (two local planning commits,
  no missing remote commits). The owner authorized activation and this point;
  release metadata remains confirmed feature 0.6.0 over platform 0.5.0.
- Delivered [baseline and contract review](../../tooling/python-rebuild/README.md),
  [per-file ledger](../../tooling/python-rebuild/migration-ledger.md),
  [source inventory](../../tooling/python-rebuild/inventory.json) and private
  `tools/maintainer/testing/fixtures/python-command-contracts.json` definitions.
  Inventory covers 104 Python source/executable files, 37 aliases, 37 integration
  resources, 68 non-Python reference files, 58 inline/internal integration candidate
  files and 289 test method definitions. Fixture definitions cover 178 execution
  surfaces, including all 72 management declarations, and 17 regression scenarios.
- Reviewed source/documentation disagreements and their owning correction points,
  version-separated distribution boundaries, dependency order, existing owner-only
  exception retirement and required public compatibility transports. No new premise
  or implementation-order conflict was found; no temporary adapter was introduced.
- Knowledge was current before activation. A query during the branch transition
  correctly refused the stale branch context; incremental refresh restored current
  state and the repeated query succeeded. Original command/ownership guides were
  consulted rather than treating retrieved historical text as operating authority.
- Focused checks validate inventory completeness/hashes, alias and resource
  identities, exact command declarations, scenario bindings and local links;
  documentation/state checks and mandatory commit hooks apply. These are structural
  checks of this baseline, not runtime acceptance or coverage claims. No broad
  suite, static analysis, build, scanner pipeline or destructive test was run.
- Root AGENTS and owner/customer instruction products need no update: this point
  establishes a private migration baseline and changes no delivered command or
  reusable procedure. The maintainer README reflects activation; other current
  runtime claims remain valid. No test containers, networks, volumes or image tags
  were created; active development data, caches, backups and reports were preserved.
- At point 1 closure, point 2 remained open and package/toolchain/runtime migration
  had not started. The point 2 evidence below records its subsequent implementation.

### Point 2 — packages, frozen tools and discovery (2026-10-26)

- Added inert package initializers and explicit customer/owner/external setuptools
  metadata. Core/application are one distribution; owner adds only its namespace;
  external stages canonical core into its separate artifact. Versions follow VERSION
  (still 0.5.0), with explicit resource lists and no runtime/developer requirements
  in delivered metadata. Private staging keeps build/egg metadata in `.runtime/python`.
- Added hash-locked pytest 9.1.1, Ruff 0.17.2, setuptools 84.2.1, pip 26.2.2,
  wheel 0.48.0 and their complete Linux Python graph; official Node Pyright 1.1.414
  uses a separate integrity lock with optional macOS fsevents omitted. Reviewed
  activity, licenses, compatibility, dependency tree/size, advisory history and
  overlap in [dependency review](../../tooling/python-rebuild/dependency-review.md)
  and the existing maintenance registry. Exact-version OSV results were empty for
  all ten selected distributions; this is dependency research, not final security PASS.
  No Composer/pnpm dependency or manifest changed.
- Added importlib pytest discovery with strict scopes/markers, recording runner,
  workspace/Git/config factories, physical projection/wheel/isolation scenarios and
  stable owner `test-python`/`python-tool` transports. Literal argv, host Compose
  delegation, owner/development guards, native child status, shared native-suite lock,
  signal restoration, process-group reaping and selection-specific reports are explicit.
  Default tests exclude live-resource and packaging markers; selected prerequisites
  fail normally rather than silently skipping. Old tests/gates remain callable.
- Added strict Pyright and Ruff rules over all first-party source, executable adapters,
  tests and fixtures. Wired Python setup/format/lint/types/unit/contracts/packages
  into the existing tooling group and legacy tooling selection with real graph
  prerequisites and testing-state serialization. Customer quality retains its own
  application tests without owner pytest requirements.
- Knowledge was current (183 documents, 2850 chunks); queried it before focused
  source work and opened original command/toolchain/ownership guidance. Updated
  public Python rules, private setup/testing/package guide, registry/quality/tools
  docs and migration ledger. Root/customer AGENTS and Docker references now link
  the public source of truth; CLAUDE already delegates to AGENTS. Other domain skills
  need no change: no Laravel/frontend/database/security capability or procedure was
  altered. Private test dependencies are not customer development requirements.
- Focused evidence: frozen tool setup succeeded in the existing app container
  (CPython 3.11.2), including pip-wheel bootstrap without ensurepip; six scoped
  adapter/argument/archive-refusal cases passed through the supported test transport.
  A collection-only packaging selection found exactly the intended wheel scenario.
  `quality-plan --group=tooling` resolved the new nodes and prerequisites without
  executing them. Source formatting covered only new Python files. Mandatory hooks
  and staged knowledge freshness remain required for this commit.
- No full suite, whole-tree lint/type analysis, wheel/image build, real installation
  or broad acceptance was run. Those remain point 10 work; service migration and
  full Python rule compliance are not claimed here. Temporary runtime/receipt API
  consumers and lifecycle/fake adaptation are tracked for points 3–4 in the ledger.
- No task-created containers, networks, volumes or image tags needed cleanup.
  Existing development services/data and Qdrant/model caches were preserved, along
  with dependency research and test reports under `.runtime/python`. Point 3 remains
  open; shared infrastructure implementation has not started.

### Point 3 — shared infrastructure and first runtime consumers (2026-10-26)

- Added immutable workspace/execution/release/Compose contexts, explicit process
  specs/results and runner Protocol, subprocess implementation, cooperative
  cancellation and clock seams, scoped SIGTERM ownership, safe command results/
  reporting and exact caller-supplied secret masking. Core imports stdlib/core only.
  Native child statuses, literal arguments, explicit copied environments, byte
  stdin/capture, inherited terminal streams and opt-in deadlines remain distinct.
- Added descriptor-anchored state directories, bounded validated JSON, duplicate/
  non-finite JSON refusal, unique private atomic writes with optional fsync and
  stable-inode shared/exclusive advisory locks. Domain services retain journal
  schemas, operation authorization, durable ownership/recovery and cleanup policy.
  Process groups terminate/reap before enclosing locks release; repeated interrupts
  are masked during cleanup and prior handlers are restored. Detached Docker or
  service resources still require their domain ownership journals.
- Migrated development Compose policy and the private Python tool executor/host
  delegation to the new APIs. Removed the executor's duplicate process-group,
  flock and signal mechanics; recording test support now implements the real core
  ProcessRunner interface. Independent workspaces/releases share no mutable context.
  Existing mode 644 suite locks retain their inode through explicit readable-lock
  policy. Workspace trust anchors accept the existing mode 775 host checkout and
  container bind mount while refusing unsafe state paths below that boundary.
  Existing report/cache directory permissions and unrelated artifacts are preserved.
- Reviewed baseline runtime/context/documentation consumers. Legacy script adapters
  still need their point 4 package bootstrap before they can use qualified imports
  from unrelated cwd; no new consumer extends that bootstrap. Their remaining
  quality/production/knowledge consumers migrate in points 5–7 and compatibility
  modules retire in point 9. Receipt-aware documentation ownership remains an
  application policy with an explicit root, not a core responsibility. The migration
  ledger records these staged dispositions and removal conditions.
- Knowledge was current (186 documents, 2882 chunks), queried before source work;
  original core/command/toolchain contracts were read. Loaded backend/security
  guidance. Updated the public Python source of truth, private workflow/ledger,
  physical wheel import scenarios and core dependency-boundary contract. Root and
  customer AGENTS already link that public guide; their rules and backend/security
  skills need no edits because no new customer command, permission or application
  workflow is introduced. Customer CLAUDE continues delegating to AGENTS.
- Focused container diagnostics through supported test-python: nine literal-input,
  independent-context, timeout/process-group, interruption, atomic publication and
  lock cases passed; eleven JSON refusal, trusted-anchor, clock, reporting and
  readable-lock cases passed. Repeated three affected context/cancellation/path
  cases after lifecycle review passed. Dependency-direction contract passed.
  Initial strict ancestor handling rejected the actual group-writable workspace;
  explicit workspace anchoring resolved it without chmod or weakening private
  state policy. Formatting/import ordering touched changed Python source only.
  Mandatory commit hooks and staged incremental knowledge refresh apply.
- No full suite, full-tree lint/type analysis, wheel/image build, scanner pipeline
  or broad acceptance was run; these remain point 10. No task-created Docker
  containers/networks/image tags/volumes were created or removed. Existing services,
  databases, Qdrant/model caches, backups and reports were preserved. VERSION stays
  0.5.0; point 4 is the next implementation point.

### Point 4 — application policy and host/development transport (2026-10-26)

- Introduced workspace-scoped development configuration, setup and command services, management service and immutable
  request-bound HostPlan, injected repository/source policies and an explicit
  application-to-core process policy adapter. Pure dependency composition and
  module version assessment remain functions; CLI parsing is guarded and module
  version reads accept an injected process function. Preserved receipt schema,
  owner/source exclusion rules, role checks, literal argv, credential-isolated Git,
  frozen dependency locks and module-version refusal semantics.
- Isolated host image/discovery, plan validation/execution and authenticated
  streaming worker lifecycle. Discovery now uses the core shared output bound
  and deadline with process-group cleanup. Host private atomic JSON no longer
  imports deployment mechanics. Socket binding does not change cwd; shutdown
  restores signal handlers and drains workers. Disconnect finishes accepted work,
  stores the result and never retries; exclusive receipt creation rejects replay.
- Bin/hook aliases bootstrap fixed modules, retaining alias identity and explicit
  hook target repository. Boost retains exec/stdio. Updated PHP dev/hook paths,
  Compose setup and devcontainer post-create together. Application readiness stays
  independent of optional AI. Container markers are resolved at composition roots;
  explicit locations and operator environments are injected into domain services.
  PHP catalogs remain authoritative public declarations; independent Python
  security allowlist and published schema have an equality regression contract.
- Qualified application imports and fixed-module bootstrap for legacy executor
  callers are required compatibility work, not migration of their domain policy.
  Delegation-only function facades, legacy test scaffolds and owner dispatch are
  tracked in the migration ledger for points 5–9. The owner composition and database alias bootstraps retain private dispatch/quality and database purpose behavior until their owning migration points. External version-separated
  acquisition and complete legacy test reconciliation remain points 6 and 8.
- Knowledge was current before work (186 documents, 2887 chunks); queried local
  sources and opened the relevant public/owner contracts. Loaded backend/security
  and Laravel/Docker guidance for the affected process and caller boundaries.
  Synchronized public Python/command/development/host documentation and private
  ledger. Root/customer AGENTS and skills already route Python work to the public
  source of truth; they need no changes because public commands, permission rules
  and customer application workflows are preserved. No module manifest bump is
  needed: changes are tooling/management transport outside module implementation.
- Focused supported test-python diagnostics passed: seven workspace/role/catalog/
  output-bound/profile cases, four preview/container/receipt/disconnect cases and
  eleven plan/authentication/pending-operation/state/manifest cases, two stable-setup-lock/named-container-cleanup cases and one inert-import contract. After separating configuration/setup and consolidating Compose construction, five focused configuration/setup/routing cases passed. A separate relative commit-message regression confirmed the hook reads the requested repository, independently of the tool cwd. The import diagnostic exposed eager evaluation of a subprocess type annotation when infrastructure was replaced; postponed annotations resolved it and the focused contract passed. A focused owner repository check confirmed the alias/bootstrap still selects the correct branch policy. Help-only bin
  probes retained their usage/exit behavior. No broad suite, full static analysis,
  scanner pipeline or installation acceptance was run. An attempted knowledge
  status refresh automatically built the changed management image; this accidental
  prerequisite build was outside the intended point checks and is not final-gate
  evidence. Its task-created tag `runa-management:797e8a6c0eab19e3e092f98b` was removed after the last consumer (no containers referenced it); shared build
  caches, all development services/data, Qdrant/model caches, backups and reports
  are preserved. Knowledge parser source identities remain unchanged.
- Incremental knowledge refresh passed (186 documents, 2896 chunks; 21 embedded and 12 removed; no stale/missing/orphan chunks and no parser change). Mandatory hooks accompany the point
  commit. VERSION stays 0.5.0; point 5 is next. Full compatibility/package/container
  acceptance and strict quality/security/AI gates remain point 10.

### Point 5 — shared quality, diagnostics, scanners and hooks (2026-10-26)

- Split YAML/frontmatter reading, immutable validated quality steps, pure graph
  selection, planning, scheduling, state/status and candidate-bound closure evidence
  into explicit application services. Owner composition supplies private paths and
  release metadata policy; removed quality monkey-patching/runpy, maintainer context
  path injection and duplicate private development diagnostics. Preserved selection,
  graph/command hashes, dependency/exclusive scheduling, native exits, timings,
  latest-run locks, background detachment and interruption/resource ownership.
- Reused core group reaping and atomic JSON. Safe existing operator-owned mode-644
  quality state is accepted through an explicit non-private file policy; new writes
  remain private. State reads are bounded, reject unsafe file identities and validate
  run/step status and child identity. Cleanup runs before lock release on errors.
- Added injected Diagnostics, SecurityService and shared strict SecretScanner.
  Repository/image findings, export/operational errors and both dependency audits
  are collected and remain blocking. Reports remain private; archives are unique
  and scoped. Isolated production discovery uses qualified imports and verifies
  dependency locks against scanned source. Actual content/report-bound review
  evidence remains mandatory; no synthetic project attestation was produced.
- Delivered the unchanged reviewed Python development locks, shared setup/executor,
  pytest/Ruff/Pyright configuration and public runa:python:setup/format/lint/typecheck
  commands. Root owner analysis/private suite selections add private scope. Updated
  PHP catalog, independently enforced host allowlist/schema, setup fingerprints,
  package allowlists and both quality definitions together. Customer configuration
  is composed under src/app; operational/cold Python remains stdlib-only.
- Hooks run pinned Ruff against staged Python/shebang bytes and publish formatting
  only after lint, secret, partial-staging and freshness checks. Private owner hook
  composition injects immutable-history checks; receipts always retain customer
  scope. Retired the completed 2026-10-22 backfill transition and RM-010 first-closure
  bypass plus exception-specific tests. Reviewed their completed/first-use removal
  conditions; resulting historical hashes and immutable records are preserved.
- Migrated quality/security regression entrypoints to supported private pytest
  selection. Preserved graph/catalogue/debug/evidence refusal contracts and added
  injected multi-context, scheduling, background locking/reaping, scanner aggregation,
  staged-Python atomicity, strict history and physical customer-import scenarios.
  Legacy compatibility launchers remain tracked for point 9; aggregate native/tooling
  test routing and comprehensive remaining legacy suite migration stay in point 8.
- Knowledge was current at entry (186 documents, 2907 chunks); queried the local
  index and opened the returned contracts. Loaded backend/security and Laravel
  guidance; Boost confirmed the existing Artisan signature/option conventions.
  Updated public Python/quality/commands guidance, owner quality/toolchain/ledger,
  root owner and separately authored customer AGENTS/backend guidance. Other domain
  skills need no change: no new Docker/database/frontend/application-domain rule.
- Necessary focused checks: staged-Python full/partial/lint publication and strict
  receipt/history cases passed (4); a focused closed-history deletion/rename
  refusal regression also passed. Scanner/audit/review cases passed (6); injected
  scheduler cases passed except initial legacy-state permissions (5 PASS, 1 FAIL).
  Fixed safe legacy-state compatibility and received PASS for that exact case.
  Candidate-bound closure regression, production-image/lock boundary and physical
  customer import without owner packages each passed their focused selection.
  Corrected an initial pytest cache path warning and repeated the exact closure
  check cleanly. Nonexecuting owner plan and public Python command help succeeded.
  Help/knowledge transports incurred management-image cache misses and prepared
  their isolated registry images; no application/production image acceptance ran.
  Knowledge refresh exposed a transitional bare-import collision with the legacy
  knowledge configuration module; renamed the shared parser to configuration_reader
  and repeated incremental indexing. Changed-file Ruff/formatting and mandatory
  hooks are part of commit preparation;
  broad tests, static analysis, scanner pipelines and full quality remain point 10.
  The first commit attempt exposed a byte-stdin backpressure deadlock in the core
  polling transport on the large roadmap sent to Prettier. Interrupted that exact
  hook in its container and reaped its child group. Replaced timed communicate
  retries for byte input with explicit selector-driven writes/reads and EOF; added
  a slow-reader/bidirectional large-payload regression and received its focused
  PASS before repeating mandatory hooks. Hook interruption now uses the scoped
  signal adapter and returns 130 after cleanup.
- Removed task-created unused management image tags
  `runa-management:797e8a6c0eab19e3e092f98b` and
  `runa-management:f63afca52162bcc1026e958f` after inspecting their exact labels and
  confirming no container references. Their discovery containers removed themselves.
  Preserved active development services, database volumes, Qdrant/model/builder
  caches, backups and verification reports; no prune or database cleanup ran.
- Point completion is recorded with its implementation commit. VERSION remains
  0.5.0; point 6 is next. No production operation or database mutation occurred.

### Point 6 — deployment and external acquisition/install/update (2026-10-26)

- Replaced production ROOT/STATE mutation with explicit OperationContext and
  immutable operator/release/Compose identities. Separated source acquisition,
  production config/images, runtime/data/health and deployment/rollback services;
  function facades remain only for caller migration through points 8–9. Production
  and operation executors are import-safe fixed module transports.
- Split external command/planning/Docker/runtime/file/customization/execution APIs;
  UpdatePlanner injects acquisition/runtime/lock/module probes and UpdateExecutor
  composes that planner with its runtime factory. Removed execution-to-CLI and
  runtime/customization cycles. External processes use core reaping/timeout/stdio
  mechanics; updater state/locks reuse durable bounded core infrastructure. Kept
  exact journal phases, source/file review, data completion and refusal boundaries.
- Application InstallationService owns host installation mechanics; external
  bootstrap/initialization/adapter start its fixed versioned gateway in a separate
  process. No customer Python is imported into the acquisition/updater process.
  Protocol-1 initial installs need no sibling helper next to a copied bootstrap;
  historical targets require their matching historical installer. Existing 0.5.0
  development upgrades use independent source/PHP/database probes without needing
  that older installation gateway. No host packages/dependencies were added.
- Canonical core rules now own manifest composition, literal raw environment
  parsing and delivery exclusions. Standalone bootstrap embeds its fixed policy
  with an equality contract; independent security validation remains. Application
  package resources include the gateway marker; external source projection contains
  core/external packages and PHP resources, without application/maintainer imports.
- Added focused regression cases for interleaved workspaces/releases, secret-copy
  boundaries, deployment build/data/health failure fencing, repeat and unsafe
  rollback, 0.5.0-to-0.6.0 updates, source-rename interruptions, committed-seed
  recovery, customer tooling configuration/frozen inputs, SMTP/TLS refusals,
  initial secret preservation and physical external import isolation. Existing
  mature native acceptance/legacy suites remain for point 8 migration and final
  execution, not claimed as passing for the refactored services.
- Knowledge was current at entry (186 documents, 2918 chunks). Queried deployment,
  updater and source contracts and opened their original guides. Loaded security,
  Docker and backend guidance. Updated current installation/bootstrap/update,
  deployment/commands/Python docs, package projections and migration ledger.
  Assessed root AGENTS/owner skills and the separately authored customer products:
  no reusable rule changed, no public Python extension API was added and existing
  source ownership, supported-command, production and recovery guidance remains
  valid; no instruction duplication or customer protected-edit permission is needed.
- Necessary focused checks received PASS for interleaved contexts (1), deployment
  failure/repeat/unsafe rollback (6), upgrade/source-rename recovery (3), standalone
  policy/gateway and physical external imports (2), SMTP/TLS/settings preservation
  (4), HTTPS redirect refusal (1) and physical application import isolation (1). Initial fixture errors (ProcessResult shape and private state-directory mode)
  were repaired and affected cases repeated successfully. Source syntax/import
  diagnostic passed. Changed-file Ruff exposed a missing moved HTTPS redirect handler;
  restored it and received its focused refusal PASS before commit. Ruff preparation initially removed implicit facade re-exports; made these
  explicit and repeated the exact committed-seed upgrade successfully. Nonexecuting
  production/updater/gateway help exposed an incorrect lifecycle import; corrected
  it and repeated all three help paths successfully. Changed-file formatting/lint and mandatory hooks are included
  in commit preparation. Broad suites/static analysis/build/install/deployment/
  scanners and all real 0.5.0 upgrade acceptance remain exclusively point 10.
- The initial knowledge-status transport prepared its isolated cached management
  registry image, without starting/building application or production images.
  Task pytest fixtures cleaned their temporary source trees; reports remain under
  .runtime/python/reports. Active development/database/Qdrant/model/builder state,
  backups and reports are preserved. No production command or database mutation ran.
- Completion is recorded with the implementation commit. VERSION remains 0.5.0;
  point 7 is next.

### Point 7 — development knowledge and owner composition (2026-10-27)

- Packaged knowledge configuration/corpus/provider/storage/index/retrieval/startup
  through qualified imports. Added validated typed configuration, payload/effect
  contracts, explicit workspace/process/clock context and injected store/provider/
  HTTP/retry dependencies. KnowledgeIndexer owns ingestion, rebuild and freshness;
  Retriever owns hybrid selection and uses injected time for evaluation evidence.
- Public configuration/golden loaders read delivered resources only. Excluded
  owner composition adds private corpus/golden inputs explicitly. Fixed source
  transports select the receipt-scoped composition root; corpus enforcement refuses
  private sources even under an explicit owner context when a receipt is present.
  Production/environment guards precede startup/network operations. Optional
  readiness remains bounded and initialization retries independently of app setup.
- Preserved model/collection/schema, source/chunk identities, pending-write repair,
  metadata filters, BM25/RRF behavior, golden expectations and controlled CPU fallback.
  Parser fingerprint remains tied to actual corpus source; migration marks it stale
  for incremental reparse while unchanged semantic vectors are reused. No rebuild,
  collection reset or model/cache deletion was performed. Source exclusions now
  inspect workspace-relative paths, so a fixture checkout under root .runtime is
  not incorrectly treated as entirely excluded.
- Moved all 14 offline ingestion contracts into unified discovery with injected
  indexing dependencies. Packaged live CPU/CUDA and retrieval scenarios behind
  fixed historical transports and migrated customer fixture imports. Added owner/
  customer isolation, injected HTTP/time, configuration refusal, production/startup
  and physical projected-package regressions. Aggregate integration remains point 8.
- Local knowledge was unavailable initially because app was stopped. Status transport
  prepared its isolated cached management registry image. Supported dev:up restored
  the existing development services after narrowing an inspected operator-owned
  regular .runtime/production/operation.lock from 0644 to 0600; no lock inode/data
  was replaced. After recovery, status reported 186 documents/2928 chunks and only
  parser staleness. Used focused source navigation rather than stale retrieval.
- Necessary focused diagnostics received PASS: parser reparse/pending recovery/model
  refusal (3), explicit owner/receipt/physical customer isolation/parser relocation
  (4), optional production/startup and HTTP retries (3), malformed remote manifest
  and GPU identity refusal (2). Production refusal was repeated after guard
  injection. Incremental knowledge refresh reached current state: 186 documents,
  2934 chunks, 24 changed chunks embedded, 18 obsolete chunks removed, zero
  missing/orphan chunks, without collection/model reset. The first isolation fixture
  exposed absolute-path exclusion of .runtime ancestors; corrected relative source
  filtering and repeated those four cases successfully. Syntax/import and mandatory
  changed-file preparation accompany commit. No broad suite/static analysis/image
  acceptance or real retrieval/AI gate was run; those remain exclusively point 10.
- Updated public knowledge/Python guides, migration ledger, owner RAG reference and
  the separately authored customer RAG reference. Root AGENTS and main skill files
  remain correct: no new public command, dependency, protected-edit permission,
  roadmap rule or production capability was introduced; references own the detail.
- Development/database/Qdrant/model/builder state, backups and reports are preserved.
  Focused pytest workspaces are disposable runner-owned fixtures; no task-created
  Docker simulation resources require removal. Active development services remain
  available. No production operation or business-data migration ran. Completion is
  recorded by the implementation commit; VERSION remains 0.5.0 and point 8 is next.

### Point 8 — shared tests, aggregate defaults and private discovery (2026-10-27)

- Delivered `tools.application.testing` composes application ownership; private
  `tools.maintainer.test_cli` composes Runa ownership over the same lifecycle.
  Default `runa:test` / `bin/test` and `tools/maintainer/test` select backend,
  frontend and tooling; browser/e2e remains explicit. Focused transports and
  scope/filter/path selection retain native engines and container transport.
- The shared runner acquires the suite lock once, preserves worker cleanup,
  process-group reaping, per-scope child/cleanup codes and reports, continues
  independent failures and records interruption/unstarted scopes honestly.
  Customer empty tooling is explicit; missing owner suites and prerequisites fail.
  Native `php artisan test` remains PHP-only and uses the same lock identity.
- Shared recording process, workspace/Git, clock/HTTP and Compose/image support
  ships independently of private suites. Application `src/app/testing.json` can
  register application/module Python roots without private imports or protected
  edits. Existing PHP bases remain delivered; reusable browser observation moved
  into `src/runa/Testing`. Projected customer/default/failure and native extension
  acceptance scenarios were added for final verification.
- Migrated the remaining useful unittest assertions into pytest discovery with
  qualified imports and domain context composition. Updater fixture extraction
  eliminates inherited test duplication and runpy fixture loading. Recorded 249
  baseline scenario dispositions in `tooling/python-rebuild/test-migration.json`;
  consolidated implementation-specific cases retain injected-service/subprocess
  successors. Live retrieval has an explicit knowledge marker and no import-time
  configuration read. No broad suite collection or acceptance was run here.
- Native acceptance helpers use importable modules and explicit process/Compose
  context; removed fixed development container naming. Preserved scoped cleanup,
  resource labels, ownership snapshots and recovery journals. SMTP/FastCGI/queue
  imports are inert; localization/runtime PHP fixtures resolve this checkout.
  The completed one-time workstation PostgreSQL ICU switch and transition-only
  tests were retired; current disposable collation/runtime probes remain.
- Test migration found a stale production composition member reference from point
  6; corrected it to `runtime.production`. Owner database/migration validation now
  rejects scope overrides before Docker delegation. Both quality definitions use
  scoped supported test commands and explicit tooling prerequisites.
- Synchronized public testing/command/Python guidance, private quality/migration
  records and both owner/customer AGENTS plus backend/Laravel references. Vue,
  Tailwind and presentation products require no changes because no UI behavior
  changed; the shared browser observer is covered by the public testing contract.
- Necessary focused diagnostic: supported `python3 tools/maintainer/test-python
--scope=unit --path=unit/test_test_runner.py`. Initial 9 cases passed; after
  adding cleanup/lock/symlink and independent-scope coverage, **13 passed in 0.09s**, without
  collection warnings. This resolves the new runner's failure/ownership/lifecycle
  uncertainty; it is not final suite, analysis, packaging or live-service evidence.
  Mandatory commit hooks apply. Full verification remains point 10.
- No disposable test Docker resources were created or removed. Incremental
  knowledge refresh rebuilt the development management registry image after its
  catalogue changed; that operational image is retained for current tooling.
  Active development/test data, Qdrant/model caches, backups and reports remain
  preserved. No new
  dependency, database migration or production deployment occurred.

### Point 9 — internal caller retirement (in progress, 2026-10-27)

- Removed application runtime/diagnostic/management/security/quality/production/
  deployment/receipt/customer-source facades and the obsolete executor package.
  CLI adapters, PHP management Actions, Compose, owner commands and native
  acceptance now select fixed package modules or explicit service APIs. Operation
  contexts supply receipt/customer-source policies using their injected process
  dependencies. Owner database/entrypoint adapters no longer rewrite `sys.argv`.
- Removed private filename suite transports, redundant toolchain/process support
  reexports and the ingest function facade. Gates use explicit pytest paths or
  qualified native scenario modules. Documentation live acceptance and quality
  subprocess fixtures no longer inject sys.path/PYTHONPATH. The baseline inventory
  and scenario identities remain historical; the per-file ledger names current
  replacements. Final alias/resource/non-Python/inline reconciliation remains open.
- Optional knowledge initialization still starts without PHP/vendor/application
  readiness. Its transport composition selects a fixed receipt-scoped initializer;
  customer source never imports owner packages. Previous-release daemon command
  paths remain only for exact owned-daemon recovery; the Boost executable retains
  its process-level cwd/exec operation to preserve stdio. These are deliberate
  transport/recovery boundaries, not service-global mutations or retained shims.
- Root package metadata contains only delivered packages/shared rules. Private
  Ruff/Pyright additions moved to excluded `tools/maintainer/python`, preserving
  full strict owner service/adapter/test/fixture scope. Frozen pins are unchanged.
  External packaging prepares canonical external/core source tarballs and the
  standalone bootstrap alongside wheels. Added physical all-module import,
  metadata-completeness and no-install external source-bundle contracts. Artifact
  builds and standalone/wheel acceptance remain for point 10.
- Devcontainer configuration selects the frozen Python interpreter/Ruff binary,
  strict Pylance and Python format/import actions. Public command/Python/editor/
  environment/production docs, owner packaging/quality docs and owner/customer
  backend/Docker references are synchronized. Root owner/customer AGENTS already
  require qualified imports, shared tooling and container ownership; they need no
  new authority rule. Laravel/security guidance retains the same public registry,
  receipt and security boundaries. No frontend/presentation guidance changed.
- Necessary focused diagnostics passed: two startup/health cases; two delivered
  quality/security/deployment import cases; interleaved release contexts;
  development receipt refusal; unknown/maintenance installation refusal;
  reviewed debug-output refusal; production image/lock binding; literal host-plan
  arguments; physical import of every delivered module; and four unrelated-cwd
  public alias help cases (15 selected tests). A stale test expected the installation
  CLI to raise a service exception despite its container refusal exit; it now tests
  the injected installation service's refusal before mutation and passed. Two
  initial keyword selections collected no matching tests; explicit selected nodes
  above supplied the intended evidence. This is not a full-suite/typing/packaging
  or live acceptance PASS. Changed-file Ruff formatting/import/lint preparation
  uses the pinned container binary, matching mandatory staged-hook rules.
- Remaining point 9 work: finish full-scope typed JSON/protocol/service and private
  fixture/test contracts, and finish the complete inventory/caller/resource audit.
  Keep point 9 open until these changes and their documentation/checklist are
  committed. Do not start full verification or release finalization yet.
- No business module behavior/contracts or independent module version changed.
  VERSION remains 0.5.0; proposed feature release remains 0.6.0. No new dependency,
  migration, production deployment or test-created Docker resource was introduced.
  No containers/networks/images/volumes were deleted. Incremental knowledge
  refresh rebuilt the operational management registry image
  `runa-management:836b79eb8f4145489eef9e10` after its PHP catalogue inputs changed;
  it is retained for current tooling, not disposable acceptance. Development/test
  data, knowledge/model caches, backups and reports are preserved.

#### Point 9 continuation — typed boundaries and remaining callers (2026-10-27)

- Added the overloaded `WorkspaceProcess` contract for workspace-bound source
  policies, preserving text/bytes results and explicit environment/stdio options.
  Annotated the configuration reader, source policy and customer Git service;
  decoded configuration/receipts now return validated recursive JSON mappings
  with fixed diagnostics for invalid/non-finite payloads. Typed host-plan payloads
  preserve omitted versus empty arguments and the existing independent validation.
- Source inspection found remaining nested bare imports in native installation,
  updater contracts and management contracts, plus the removed customer-source
  facade imported by the deployment contracts. Replaced them with qualified
  imports, explicit service composition and injected process outcomes. Preserved
  cleanup/failure assertions instead of retaining globals from retired runners.
  Added a nested-import package contract for final verification; it has not been
  executed as an early repository-wide analysis gate.
- Corrected `check-database-migrations`: it previously selected a nonexistent
  `test_database_guards.py`; it now uses the existing native backend runner with
  `--parallel --processes=1 --filter=DatabaseTest`, consistent with the current
  worker/quality documentation. Its focused regression records the command without
  starting PHP, Docker or a database suite. No quality prerequisite/severity changed.
- Removed unused private knowledge/database filename adapters, the one-off Docker
  inventory and obsolete PHP-only simulation shell helper. Removed the shell
  resource from owner package data and recorded retirement in the ledger. Original
  inventory/command fixtures remain baseline records; historical evidence is unchanged.
  Development setup now selects `tools.application.scanner_installation`; its
  import-safe implementation receives workspace/process dependencies and retains
  the same reviewed scanner version/checksum and download policy.
- Reconciled all 37 baseline aliases against their actual symlink targets: no
  differences or missing resolved files. Source searches across current callers
  found and corrected obsolete command/documentation paths; the old host-executor
  identity remains deliberately supported only for owned-daemon recovery. Complete
  ledger/resource metadata reconciliation and final semantic review remain open.
- Fixed the generated application-module test command and public module example to
  select `runa:test --scope=backend` before native path arguments. Updated owner
  and customer Laravel references together. The generator is platform framework
  tooling outside independently versioned modules; no module manifest/changelog
  bump is required. No business module contract changed.
- Updated public Python/module guides and current owner architecture, bootstrap,
  installation/update, production, test, maintenance and release command guidance.
  Root owner/customer AGENTS already specify qualified imports, aggregate defaults
  and explicit focused ownership; no authority change is needed. Backend/security
  skills retain their existing injection and boundary rules; the implementation
  adds no new customer extension API or security-policy exception.
- Twelve necessary focused cases passed through `tools/maintainer/test-python`:
  five decoder/receipt/frontmatter cases; native database routing; duplicate/literal
  host-plan handling; real Git branch/tag/SHA acquisition; interleaved scanner reuse;
  two native-runner cleanup/failure cases; and read-only module-plan mounts. The
  first Git case exposed the stale facade import, which was corrected before its
  successful rerun. No full suite, Pyright, wheel/build, scanner or live production
  acceptance was run. Changed-file pinned Ruff preparation passed after correcting
  the owner config's cache path from `../../../.runtime` (which resolved outside the
  workspace) to `.runtime/python/cache/ruff`.
- Point 9 stays open. Next work is full-scope typed JSON/protocol/service and private
  fixture/test completion across quality/host transport, deployment/installation,
  knowledge and external tooling, followed by the remaining ledger/resource audit.
  Strict scope/exclusions and frozen dependency pins are unchanged. Point 10 has
  not started; VERSION remains 0.5.0 and the agreed feature projection remains 0.6.0.
  No disposable Docker resource was created or removed by this continuation;
  active development/test data, knowledge/model caches, backups and reports remain.

#### Point 9 continuation — deployment data and process contracts (2026-10-27)

- Annotated deployment context/source, production policy, Compose/runtime and
  orchestration inputs/results, release callbacks and progress/identity records.
  Workspace-bound execution reuses `WorkspaceProcess`; forwarded `ProcessOptions`
  retain literal input, text/bytes results, default checked execution, explicit
  unchecked probes, noninteractive Compose stdin and isolated operator environment.
- Added typed journal/Compose/service-status/container-inspection decoders. They
  validate consumed fields before use, retain unknown historical/customer fields
  and keep existing serialization. The 0.5.0 producer uses the same release fields;
  an absent legacy `rollback_safe` remains unproven, never permission to roll back.
  Optional profiled services can omit an image until selected. Null Docker Env is
  handled as absent configuration, preserving the fixed knowledge-isolation refusal.
  Manifest versions are checked without imposing platform SemVer on customer data.
- Five focused boundary cases passed through the supported `test-python` runner,
  then passed again after extending explicit check/stdin/environment assertions.
  Three selected existing regressions passed: interleaved workspaces/releases,
  successful repeat without data replay, and unsafe rollback refusal before runtime
  work. These use scripted process outcomes; no Docker acceptance or data mutation
  was performed. Full suites, strict analysis, builds/scanners and acceptance remain
  exclusively point 10. Mandatory staged hooks apply to this continuation.
- Synchronized the public Python guide and owner migration ledger. Owner/customer
  AGENTS and backend/security products already require qualified typed boundaries,
  explicit contexts and safe journal/process handling; no new public extension API,
  authority, customer action or security exception requires changing those products.
  No independently versioned module, dependency, migration or production setting
  changed. No disposable Docker resource was created or removed; development/test
  data, knowledge/model caches, backups and verification reports are preserved.
- Point 9 remains open: quality/host transport, installation/external update,
  knowledge and remaining private fixture/test annotation completion, followed by
  the final complete alias/resource/non-Python/inline ledger reconciliation.
  Point 10 has not started; VERSION remains 0.5.0 and proposed_version is 0.6.0.

#### Point 9 continuation — typed quality planning (2026-10-27)

- Added typed definition/node/graph, roadmap and legacy/named selection contracts.
  Source decoders validate consumed fields and retain node/roadmap evidence used
  by existing hashes. Pure planning and the planner/service boundary now declare
  inputs/results; the `QualityPlanSource` protocol allows explicit test composition.
  Evidence resolution uses the same definition decoder as operational planning.
  Selection order, prerequisites, conditional dependencies, graph hashing and
  declared-scope refusal retain their behavior; no quality command was executed.
- Moved shared node/definition factories out of a test module into private support.
  Added a typed `FixturePlanner` and replaced dynamic namespace planners in preview
  and subprocess fixtures. The background/signal harness remains for final quality;
  it was not run as an early concurrency/acceptance check. Existing docs/catalogue
  consumers now explicitly decode configuration before typed graph selection.
- Five focused decoder/planner cases passed; three selected existing cases passed
  for dependency/scope handling, legacy interrupted status and nonmutating preview.
  The exact candidate/scope/command-binding closure regression also passed. The
  preview case passed again after replacing its dynamic planner fixture. No full
  suite, strict analysis, real graph preview, image build/scanner or live acceptance
  was run. Mandatory staged hooks apply; frozen pins and strict scope are unchanged.
- Updated the public Python guide and migration ledger. Owner/customer instruction
  products already cover explicit composition, typed boundaries and private/public
  test ownership; these internal planning types add no customer extension API or
  authority rule. Backend/security skill products require no new procedure.
- Point 9 remains open. Remaining annotation work includes quality scheduling,
  status/evidence records and private cases; host transport; installation/external
  tooling; knowledge; then complete alias/resource/non-Python/inline reconciliation.
  No module version, dependency, migration, production setting or disposable Docker
  resource changed. Development/test data, caches, backups and reports are preserved.
  Point 10 has not started; VERSION stays 0.5.0, proposed feature release 0.6.0.

#### Point 9 continuation — external runtime and source boundaries (2026-10-27)

- Added explicit text/bytes process overloads, runtime transport and source-change
  contracts, and annotated the external installation/gateway, compatibility/source,
  Compose/runtime and Docker service inputs/results. Shared dependency composition
  now declares recursive JSON inputs/results without changing composition policy.
  External document decoding retains duplicate-key refusal and additionally rejects
  non-finite/invalid recursive values with fixed diagnostics, preserving input files.
- Added consumed-field decoders for external Compose/container/service responses
  and migration/seed/module metadata. Unknown response fields remain available;
  old releases without module tables retain empty optional metadata. Optional
  profiled services and their tmpfs mounts remain readable, null Docker Env/Labels
  behave as absent values, and required service image/host/mount/ownership checks
  remain blocking. No installed Python release is imported.
- Fixed incomplete injection: DockerRuntime's runtime/database probes now use its
  selected command transport, and lock validation uses one process policy through
  image/probe execution and exact scoped cleanup. All added diagnostic scenarios
  use scripted processes; they start no Docker worker or database operation.
- Ten necessary focused cases passed through the supported test-python runner:
  six new process/decoder/transport/cleanup cases, the existing owned-worker client
  failure cleanup case, missing-service refusal, dependency/applied-migration refusal
  and shared manifest/version refusal. Changed-file pinned Ruff import/lint and
  formatting preparation passed. No full suite, strict analysis, build, scanner or
  live acceptance was run; mandatory staged hooks remain required.
- Synchronized the public Python guide, owner updater guide and migration ledger.
  Owner/customer AGENTS and backend/security products already require explicit
  contexts, typed boundaries, isolated processes and safe refusal/recovery. These
  internal external-bundle types introduce no customer extension API, authority or
  security exception, so those products need no new rule. Existing package source
  projection includes the new model module without changing resource exclusions.
- Point 9 stays open for quality scheduling/status/evidence, host transport,
  installation services, external bootstrap/planning/files/module/execution,
  knowledge and remaining private fixture/test annotations, followed by complete
  alias/resource/non-Python/inline reconciliation. Point 10 has not started.
  No module version, dependency, migration or production setting changed; VERSION
  stays 0.5.0 and proposed_version remains 0.6.0. No disposable Docker resources
  were created or removed; active development/test data, knowledge/model caches,
  backups and reports are preserved.

#### Point 9 continuation — delivered-file review and module plan records (2026-10-27)

- Typed delivered-file identities, ownership/differences, review/consent records,
  journaled actions and staging/rename callbacks. Resume now validates consumed
  file-action fields before staging use, preserving extra evidence and legacy
  journals without action lists. Inventory, serialization and consent fingerprint
  inputs are unchanged; no additional overwrite or recovery authority is added.
- Typed infrastructure comparison and validated Compose service objects without
  environment interpolation/resolution. The isolated module planner validates
  response fields and preserves PHP empty associative maps serialized as lists.
  Its selected injected process policy now owns both execution and scoped cleanup;
  the dynamic sys import is removed. External packaging already includes authored
  Python package sources, including the new file-record module.
- Four new focused cases passed through supported test-python: journal evidence/
  malformed fields, raw Compose review input, module response compatibility and
  exact planner cleanup on invalid response. The first cleanup fixture failed
  before its intended boundary because it omitted the platform manifest identity;
  the fixture was corrected and all four passed. Four existing focused cases also
  passed: read-only module-plan mounts, complete file/instruction review, reviewed
  add/replace/remove and interrupted file-renames/resume. These use temporary Git/
  file fixtures or scripted processes; no live Docker worker/data operation ran.
- Synchronized the public Python guide, owner updater guide and migration ledger.
  Owner/customer AGENTS and backend/security skills already require explicit typed
  boundaries, safe refusal/recovery and private/public ownership. These internal
  records introduce no public extension API, new customer action, security exception
  or authority rule; those instruction products need no changes.
- Point 9 remains open for quality scheduling/status/evidence, host transport,
  installation services, external bootstrap/planning/execution, knowledge and private
  fixture/test annotation completion, then full caller/resource/inline reconciliation.
  Point 10 has not started: no full suite, strict analysis, package build, scanner
  or live acceptance was run. Changed-file pinned Ruff preparation and mandatory
  commit hooks apply; dependency pins and strict analysis scope remain unchanged.
- No independently versioned module, dependency, migration or production setting
  changed. VERSION remains 0.5.0 and proposed_version remains 0.6.0. No disposable
  Docker resources were created or removed; active development/test data, knowledge/
  model caches, backups and verification reports are preserved.

#### Point 9 continuation — host transport and recovery records (2026-10-27)

- Declared typed host requests, credentials, operation records, input frames, binary
  worker factory and bounded stream reader. The server receives its service,
  credentials, image/registry identities and environment explicitly at construction;
  status is an ordinary service method rather than an attached closure. Discovery,
  plan validation and execution boundaries now declare inputs/results, with guarded
  execution retaining its independent plan validation.
- Transport decoding rejects non-object, duplicate, malformed and non-finite JSON
  before field use. Interactive/control fields validate bytes and native status;
  request Unicode failures produce safe refusal. Recovery validates positive integer
  PIDs before process inspection/signaling, preserves PID-only legacy recovery and
  optional old registry identity, and retains extra receipt evidence. Existing
  authentication, replay, disconnect and exact owned-process checks remain intact.
- Source review found registry-discovery failure cleanup omitted the required
  workspace argument after process migration. Added the selected workspace and a
  scripted regression asserting only the request's named container is targeted.
  This diagnostic executes no Docker operation. Private transport fixtures now use
  explicit typed process/server composition and validated JSON inputs.
- Thirteen distinct necessary focused cases passed through supported test-python:
  six new decoder/input/control/recovery/status cases; request refusal and literal
  binding; named validator cleanup; catalogue/allowlist agreement; local single-worker
  socket disconnect/replay; two owned-process recovery cases; and discovery failure
  cleanup. Decoder cases passed again after the final stdin-field refinement. No
  full suite, strict analysis, package build, scanner or live Docker acceptance ran.
  Changed-file pinned Ruff preparation passed; mandatory commit hooks remain in force.
- Synchronized the public Python guide and owner migration ledger. Root owner and
  customer AGENTS already require qualified composition, guarded tooling and private
  suite separation. Owner/customer backend/security products already route typed
  boundaries and recovery to the public guides; no new customer extension API,
  permission, infrastructure authority or security exception requires another rule.
  Package source projection already includes the new module; frozen pins/exclusions
  remain unchanged and no independent business-module version changes.
- Point 9 remains open for quality scheduling/status/evidence, remaining registry
  and installation services, external bootstrap/planning/execution, knowledge and
  remaining private fixture/test annotations, then complete caller/resource/inline
  reconciliation. Point 10 has not started. VERSION stays 0.5.0 and proposed_version
  remains 0.6.0. No dependency, migration, production setting or disposable Docker
  resource changed. Development/test data, knowledge/model caches, backups and
  verification reports are preserved; no containers/images/networks/volumes removed.

#### Point 9 continuation — registry and installation boundaries (2026-10-27)

- Typed registry orchestration, command/plan/process and browser lifecycle inputs
  and results. Source catalog decoding validates consumed name/tool/prefix fields,
  preserves metadata and leaves independent executable allowlists intact. Shared
  Compose status decoding handles both arrays and line responses and refuses a
  boolean/noninteger setup exit status before accepting development health.
- Typed installer/gateway settings, paths, journals, resources and process/service
  contracts. Private JSON reads retain the 64 KiB bound during reading and reject
  duplicate/non-finite fields with fixed diagnostics. Consumed settings fields
  validate before URL/TLS/SMTP use; recovery validates source/project/volume/label
  records while retaining additional evidence and legacy optional journal fields.
  Null/missing container labels establish no ownership. Explicit SMTP-only resume
  correction, raw literal values and generated application/database secrets remain
  preserved. Private service fixtures use explicit management composition rather
  than a dynamic namespace. No installed external release is imported.
- Eighteen distinct necessary focused cases passed through supported test-python:
  nine decoder/health/refusal cases, three unsafe-settings variants, preservation
  of production secrets/raw values, redirect refusal, SMTP correction, foreign
  pending-operation refusal, literal reverse-command transport and TLS input refresh.
  Two decoder/settings cases passed again after final field-check refinements.
  These use temporary files or scripted processes; no live Docker operation or
  database mutation was run. Changed-file pinned Ruff preparation passed; mandatory
  staged hooks remain required. No full suite, strict analysis, package/image build,
  scanner or live acceptance was run; these remain final-point work.
- Synchronized the public Python guide and migration ledger. Root owner/customer
  AGENTS and backend/security skill products already require typed injected
  boundaries, guarded execution, safe recovery and public/private test separation;
  these internal records add no customer extension API, authority or security
  exception requiring another rule. Existing source projection includes both new
  authored model modules without a resource exception. Frozen pins, strict scope
  and exclusions remain unchanged; no independently versioned module changed.
- Point 9 remains open for quality scheduling/status/evidence, external bootstrap/
  planning/execution, knowledge and remaining private fixture/test annotations,
  followed by complete caller/resource/inline reconciliation. Point 10 has not
  started. VERSION stays 0.5.0 and proposed_version remains 0.6.0. No dependency,
  migration, production setting or disposable Docker resource changed. No container,
  network, image or volume was created or removed; active development/test data,
  knowledge/model caches, backups and verification reports remain preserved.

#### Point 9 continuation — quality scheduling and run evidence (2026-10-27)

- Typed scheduler process/command boundaries, run/step records, status publication
  and closure configuration/policy inputs/results. Scheduling consumes the existing
  immutable step parser; literal transport arguments remain lists. Serialized graph,
  selection, candidate hashes, four-slot/exclusive scheduling and native exits keep
  their contracts. No real quality graph was started.
- Added consumed-field run-state decoding before status/evidence use. It validates
  exit codes without accepting booleans, positive PIDs, finite nonnegative timings,
  timezone-aware timestamps and selection metadata. Minimal legacy status records,
  optional/null timing/identity fields and unknown evidence remain preserved. A live
  coordinator's status stays read-only; invalid abandoned evidence is not rewritten.
- Eight distinct necessary focused cases passed through supported test-python:
  four new decoding/status/start-refusal cases; existing native failure/dependent
  blocking and host routing, abandoned log-tail status, nonmutating preview and
  exact graph/scope/candidate closure. The routing case initially exposed a changed
  list-to-tuple transport argument; restored its established list shape and received
  PASS. Start refusal uses an injected process, asserts exact workspace/environment,
  fixed diagnostic, closed log and restored signals; it starts no external worker.
- Synchronized the public Python guide and migration ledger. Owner/customer AGENTS
  and backend/security products already cover explicit typed composition, guarded
  processes/state and public/private ownership. These internal records add no public
  extension API, customer action, authority or security exception needing another
  rule. Existing package projection includes the authored module without exclusions
  or dependency changes. No independently versioned business module changed.
- Point 9 remains open for external bootstrap/planning/execution, knowledge and
  remaining private fixture/test annotations, followed by complete caller/resource/
  inline reconciliation. Point 10 has not started: no full suite, strict analysis,
  package/image build, scanner or live acceptance ran. Changed-file pinned Ruff and
  mandatory commit hooks apply. VERSION stays 0.5.0; proposed_version stays 0.6.0.
- No disposable Docker container, network, image or volume was created or removed.
  Active development/test data, knowledge/model caches, backups and reports remain
  preserved. No migration or production setting changed.

#### Point 9 continuation — external planning contracts (2026-10-27)

- Typed external preflight acquisition/runtime/lock/module dependencies, release
  identity projection, plan/report records, read-only lock observation and staged
  dependency composition. Per-request overrides retain the composed planner's
  dependencies. Consumed acquired identity fields validate before use; additional
  receipt metadata stays outside reports. Acquisition retains independent source
  verification. Serialized fields, partial blocked-plan evidence and download
  cleanup remain intact; same-release pending module work prevents a no-op result.
- Typed private updater workspace cleanup/Git/download/runtime/snapshot/plan
  boundaries and composed it through UpdatePlanner. Four new focused cases passed
  via supported test-python: selected dependencies/identity projection, invalid
  identity refusal before probes, same-release module work/request overrides and
  partial evidence on module refusal. Six existing focused cases also passed:
  ready-plan preservation, runtime/staging refusal, dependency/applied-migration
  refusal, lock/pending-state refusal, completed source replacement and changed-tag
  resume refusal. Fixtures use temporary Git/files and scripted runtimes; no live
  Docker operation or database mutation ran. Changed-file pinned Ruff preparation
  passed; mandatory commit hooks remain required.
- Synchronized the public Python guide, owner updater guide and migration ledger.
  Root/customer AGENTS and backend/security skill products already require typed
  injection, safe refusal/recovery and private/public ownership. These internal
  planning records introduce no customer extension API, authority or security
  exception requiring another rule. External source projection includes the new
  authored model module; frozen pins, exclusions and strict scope are unchanged.
- Point 9 remains open for bootstrap/initialization, update execution, knowledge
  and remaining private fixture/test annotations, then complete caller/resource/
  inline reconciliation. Point 10 has not started: no full suite, strict analysis,
  package/image build, scanner or live acceptance ran. VERSION remains 0.5.0 and
  proposed_version remains 0.6.0; no independently versioned module changed.
- No dependency, migration, production setting or disposable Docker resource
  changed. No container/network/image/volume was created or removed. Active
  development/test data, knowledge/model caches, backups and reports are preserved.

#### Point 9 continuation — external execution and recovery records (2026-10-27)

- Typed update runtime/factory, planner, consent/options, report, file-journal and
  consumed release/review/database/module recovery contracts. Planning and apply
  share report detail records without changing serialized fields or mode semantics.
  Per-operation state and runtime dependencies remain explicit; ready preflight
  inputs are checked before use and repeated under the existing exclusive lock.
- Resume validates consumed journal types before source/data mutation, including
  boolean schema-version refusal, source-transition proof, migrations/seeds,
  three-part database mount identities, review fields and module-completion rows.
  Fixed diagnostics retain malformed evidence without rewriting or re-fencing it.
  Unknown top-level/nested baseline/review evidence and legacy absent optional
  module/action/manual-reconciliation records remain supported.
- Found and corrected a concrete capture/storage mismatch: Docker metadata used
  tuples for database mounts, while the core JSON writer accepts lists. Capture now
  uses JSON lists, preserving serialized identities and refusal of a changed
  database. The recording-runner case covers actual nonempty bind/volume capture
  through the decoder and durable writer; it starts no Docker resource.
- Ten distinct necessary focused cases passed via supported test-python: three
  new malformed/legacy/mount journal cases, injected runtime capture/storage,
  existing source replacement/no-op, exact tag-object refusal, interrupted directory
  renames, repeated resume-fencing interruption, explicit customization consent and
  0.5.0-to-0.6.0 committed-seed non-replay. Initial fixture failures correctly refused
  unclaimed retained file staging and Compose/mount disagreement; fixed the fixtures
  to retain actual action records and separate database inspection from service
  inspection, then received PASS. The implementation's refusal checks remain intact.
- Synchronized the public Python guide, owner updater guide and migration ledger.
  Root/customer AGENTS and backend/security skill products already require typed
  injected boundaries, safe recovery, preserved evidence and private/public
  ownership. These internal records introduce no public extension API, authority or
  security exception requiring another rule. Package source projection includes the
  authored model module automatically. No independently versioned module changed.
- Point 9 remains open for bootstrap/initialization, knowledge and remaining private
  fixture/test annotations, then complete caller/resource/inline reconciliation.
  Point 10 has not started: no full suite, strict analysis, package/image build,
  scanner or live acceptance ran. Changed-file pinned Ruff preparation and mandatory
  commit hooks apply. VERSION remains 0.5.0; proposed_version remains 0.6.0.
- No dependency, migration, production setting or disposable Docker resource changed.
  No container/network/image/volume was created or removed. Active development/test
  data, knowledge/model caches, backups and reports remain preserved.

#### Point 9 continuation — acquisition and customer initialization contracts (2026-10-27)

- Typed standalone release-client/identity, projection/receipt, tree, HTTP callback,
  process and CLI boundaries. Consumed GitHub object/list shapes, string identities,
  positive non-boolean IDs and boolean release flags validate before use; unrelated
  API fields remain accepted and diagnostics never include response bodies.
- Kept bootstrap's independent stdlib process/Git/publication mechanism as the
  intentional single-file first-acquisition boundary; it has no adjacent core
  package. Customer repository initialization instead composes `CustomerSource`
  with a copied environment and injected core runner for every Git operation,
  including tree reads and closed-stdin installer handoff. Pure tree parsing and
  verified file publication remain shared; no installed Python is imported.
- Annotated private bootstrap/customer-clone fixture inputs/results and migrated
  obsolete installer-file expectations to the fixed protocol-1 module gateway.
  Mocked acquisition now creates the required gateway marker, so handoff tests
  exercise the actual command builder rather than replacing it with a legacy path.
- Sixteen necessary focused cases passed through supported test-python: five new
  metadata/runner/handoff cases, standalone copied-file help, real temporary Git
  acquisition, real customer clone/source equality, unsafe owner-source/ref refusal,
  annotated identity refusal, exact gateway commands, interactive postponement,
  reference selection, hidden token handling and closed-stdin install failure.
  No live GitHub request, Docker resource, image build, full suite, strict analysis,
  scanner or broad acceptance ran. Mandatory staged formatting/lint/secret/freshness
  hooks remain required; point 10 has not started.
- Synchronized the public Python guide, owner bootstrap guide and migration ledger.
  Corrected the owner guide's stale bootstrap native-exit claim: standalone first
  acquisition reports installer failure through its established safe boundary;
  customer initialization propagates the installer exit. Root/customer AGENTS and
  backend/security products already require typed injected boundaries and guarded
  process/source handling; these internal contracts introduce no customer extension
  API or authority requiring new instructions. Existing external package projection
  includes both modules automatically. No independently versioned module changed.
- Point 9 stays open for knowledge and remaining private fixture/test annotations,
  followed by complete alias/resource/non-Python/inline reconciliation. VERSION
  remains 0.5.0 and proposed_version remains 0.6.0. No dependency, migration or
  production setting changed. No container/network/image/volume was created or
  removed; active development/test data, knowledge/model caches, backups and reports
  remain preserved.

#### Point 9 continuation — knowledge provider and retrieval contracts (2026-10-27)

- Typed HTTP opener/response and transport factory, provider/store query, hybrid
  ranking, golden-case/evaluation and doctor records; CLI/owner composition,
  parser callbacks and corpus collections declare inputs/results. Collection,
  manifest, search diagnostics and serialized report fields remain unchanged.
- Provider limits reject booleans/nonintegers, require explicitly disabled
  truncation and turn missing consumed capability fields into fixed contract
  errors. Vector coordinates reject booleans/nonfinite values. Transient GPU
  failures still validate CPU before repeating the same request; model mismatch
  refuses without fallback. Extra service metadata remains accepted.
- Twenty-four distinct necessary focused cases passed through supported test-python:
  fourteen new capability/vector/query/ranking cases plus ten existing retry,
  production-guard, malformed-manifest/model refusal, parser refresh, interrupted
  write recovery, idempotent edit/delete and branch refresh cases. The scripted
  retrieval case exercises actual index status and composed storage/provider/Git
  boundaries, preserves both ranking signals and exact branch/source conditions,
  excludes foreign point IDs and refuses boolean top-k before another query.
  Initial collection failures exposed an out-of-order type definition and a
  deferred JSON-alias annotation; corrected both and received PASS. No live
  service acceptance or collection rebuild was run.
- Synchronized the public Python/knowledge guides and migration ledger. Root and
  customer AGENTS plus RAG/backend products already require injected typed
  boundaries, capability validation, source grounding and private/public ownership;
  these internal contracts introduce no extension API or authority requiring a
  new instruction rule. Package projection already includes the changed modules;
  no dependency, frozen pin, exclusion or independently versioned module changed.
- Point 9 remains open for remaining private fixture/test annotations and complete
  caller/alias/resource/non-Python/inline reconciliation. Point 10 has not started:
  no full suite, strict analysis, package/image build, scanner or broad acceptance
  ran. Changed-file pinned Ruff preparation and mandatory commit hooks apply.
  VERSION remains 0.5.0; proposed_version remains 0.6.0.
- No container, network, image or volume was created or removed. Development/test
  data, knowledge/model caches, backups and verification reports remain preserved.
  Only ordinary incremental knowledge refresh is needed for source freshness;
  no migration or production setting changed.

#### Point 9 completion — declarations, fixtures and complete reconciliation (2026-10-27)

- Completed remaining development/configuration/setup, diagnostics/security,
  dependencies/hooks/module versions, release/docs/localization and CLI signatures,
  including all private cases, callbacks and native SMTP/FastCGI/PostgreSQL peers.
  Source inventory now finds 1,349 declared functions with input/result annotations
  and no missing signatures. This is declaration evidence, not strict-analysis PASS;
  strict scope, rules, exclusions and frozen pins are unchanged.
- Workspace-bound diagnostic/security calls compose the existing overloaded
  process contract, retaining text/bytes and explicit option overrides. Private
  acceptance callbacks, report/Compose/resource records and fixture boundaries are
  typed. Shared private knowledge provider/storage/HTTP fakes live in support;
  suites no longer import another suite. GPU fallback now constructs the actual
  provider with scripted capabilities/responses rather than replacing its method.
- Completed source reconciliation for all 104 baseline components, 37 unchanged
  symlink aliases, 37 resources, 68 non-Python references and 58 inline candidates
  in `tooling/python-rebuild/reconciliation.json`. Preserved baseline inventories,
  scenario identities, immutable dated evidence and retained process/version/
  daemon-recovery boundaries. Added the final source reconciliation contract;
  broad source/package contracts remain scheduled for point 10.
- Removed remaining native acceptance assumptions about the owner's `runa-dev`
  project/container name. Management/host preservation uses exact checkout labels;
  model-cache reuse resolves the owner's configured embeddings service and inspects
  its actual volume. An explicit owner context reaches customer knowledge acceptance;
  customer collection isolation and cache preservation are unchanged. Corrected
  remaining package command examples and obsolete executor guidance.
- Eight necessary focused cases passed through supported test-python: configured
  owner cache discovery, existing credential-preserving development reentry,
  interrupted knowledge write recovery, parser relocation without collection/model
  reset, native temporary-Git module version checks, active release arithmetic,
  aggregate security audit collection/workspace routing and transient-versus-permanent
  provider failure. No live Docker/GitHub/service operation, full suite, strict
  analysis, image/wheel build, scanner or broad acceptance ran. Changed-file pinned
  Ruff preparation and mandatory staged hooks apply.
- Consolidated the migration ledger's obsolete incremental status into current
  architecture/disposition guidance; synchronized its README/toolchain, public
  Python/command guide, owner localization/command migration and tools ownership.
  Root/customer AGENTS and owner/customer backend/security/Laravel/RAG products
  already cover qualified typed/injected APIs, guarded effects, recovery and scope.
  Internal annotations/support and caller corrections introduce no new public
  extension API, authority or exception, so those instruction products need no
  further rule. No independently versioned business module changed.
- Point 9 implementation is complete; point 10 is the only open point. VERSION
  remains 0.5.0, projected feature release 0.6.0. No dependency, migration or
  production setting changed. No disposable container/network/image/volume was
  created or removed; active development/test data, knowledge/model caches,
  rollback resources, backups and verification reports remain preserved.

#### Point 10 completion — central test ownership (2026-10-27)

- Relocated 275 authored cases, fixtures and native acceptance resources into
  `tests/{backend,frontend,tools}/{app,runa}`. Public commands and scope names stay
  compatible. Updated PHP autoload/discovery, module generators and metadata,
  Python imports/packages and analysis targets, browser configuration, aliases,
  source checks and staged hooks. Shared reusable testing infrastructure stays
  with its owning source package; application test scaffolds remain minimal.
- Acquisition projection 2 excludes every private test root; exact projection 1
  receipts remain readable. Application test roots remain customer-owned during
  update. Existing customer cases are never moved implicitly; unreconciled legacy
  cases produce an explicit diagnostic instead of a successful empty run.
  Production excludes the whole test tree, including legacy source test roots.
- Auth, Audit and Permissions advance to 1.0.1 and Reference to 0.1.1 for relocated
  test contracts. Explicit no-data upgrade edges preserve installed-module upgrade
  continuity; runtime APIs remain unchanged. Both owner-scoped module version
  checks against main passed with no errors. Composer autoload regeneration passed;
  its module PSR-4 notices reflect the existing separate module autoloader.
- Eleven focused pytest cases passed through supported tooling entrypoints: ownership,
  exact old/current receipts, generator placement, dependency/config resolution,
  extension roots, legacy refusal, real-Git customer acquisition and runner/adapter behavior. The application
  welcome case passed in actual Chromium after relocation. Supported backend
  discovery lists the application and Reference cases without executing the suite.
  Initial path/resolution failures were corrected before these passing checks.
- Updated public testing/Python/module/command guides, documentation ownership,
  current migration targets and both owner/customer instruction products. Original
  inventory identities, command baselines and dated/closed evidence are preserved.
  Changed-file Ruff preparation and mandatory staged formatting/hooks apply.
- No full suite, strict analysis, production build, scanner pipeline or broad
  acceptance ran here; these remain in point 11. VERSION remains 0.5.0 with
  projected feature release 0.6.0. No dependency was added or data migrated.
  Normal host transport rebuilt its operational management image, retained for
  supported operations. No disposable Docker test resource was created or removed;
  development/test databases, rollback volumes, model caches, backups and reports
  remain preserved. Removed the now-empty old application/platform/module test
  directories and former private Python/native/browser acceptance directories; no
  obsolete directory facade remains.

### Source review planning extension (2026-10-27)

- Reviewed all 859 tracked src/runa files by area and traced representative public
  signatures, imports, integration composition, generator output and frontend
  replacement fixtures. Findings F01–F25 above record source evidence, intended
  outcomes, compatibility limits and the focused frontend collection failure.
- Proposed points 11–19 are all open; final verification is now point 20. No new
  refactor, application/runtime configuration, dependency or data change was made.
  Added one public API boundary guide and routing rules in owner/customer AGENTS
  and backend/Laravel/frontend skills, with source metadata. Final path-specific
  guidance remains point 19 work; no proposed layout is claimed as implemented.
- The early two-file customization diagnostic failed before any selected case ran.
  It is not acceptance; point 11 owns the repair and exact focused recheck. No more
  test/build/analysis operation is authorized by this planning task. No Docker
  resource was created or removed; development data, caches and reports remain.
- Changes are limited to the roadmap and authorized handbook/AGENTS/skill guidance.
  No additional tests were run for these guidance edits. VERSION stays 0.5.0;
  confirmed compatible feature projection remains 0.6.0. Breaking public/module
  changes still require a separate explicit owner/version decision.

### Point 11 completion — cold frontend dependency scanning (2026-10-27)

- Continued implementation under the owner's instruction to proceed. Committed
  the existing architecture review/roadmap rename and owner/customer boundary
  guidance separately as `5a8a68cc`; this preserves the planning boundary.
- Knowledge status initially reported stale sources and a renamed branch; used
  focused original sources, then staged and refreshed the corpus incrementally.
- Reproduced F25 with a cold optimizer cache. A warm run had misleadingly passed
  all five selected customization cases. The browser scanner visits every configured
  suite entry, including unselected files: lazy bare Test Utils/Vitest imports in
  account-preferences first broke pnpm resolution, and stale relative lazy imports
  in account-preferences/http then broke source resolution. Subsequent runtime
  optimization reloaded stateful Vitest modules and lost the current suite.
- Migrated the remaining lazy package imports to the existing shared testing
  facades and corrected relative source imports from the central suite directory.
  Retained package pins, central placement, configuration, assertions and native
  diagnostics. No alias workaround, duplicate installation or scanner suppression
  remains. Added a private regression checking lazy imports across central frontend
  sources, so an unselected file cannot silently reintroduce this scan failure.
- Focused supported diagnostic, with a cold cache after the final correction:
  `python3 tools/maintainer/test-frontend ../tests/frontend/runa/unit/auth-entry.test.ts ../tests/frontend/runa/unit/auth-account.test.ts --filter=customization:`
  returned exit 0: two files, five cases passed, 69 intentionally skipped. No scan
  failure, optimizer reload or missing-current-suite error remained. Evidence:
  `.runtime/artifacts/playwright/rm012-p11-cold-fixed.log`. Earlier failed diagnostic
  logs and optimizer caches remain preserved under `.runtime`.
- Focused new import-regression check through the supported owner Python runner
  returned exit 0: one passed, seven deselected. No broad suite, build, static
  analysis, acceptance or final gate ran. Browser diagnostics still report an
  upstream module-runner dynamic-import warning and reactive presentation-component
  warnings; these were not suppressed. Review component identity handling in the
  already planned presentation/orchestration points 15–16 before final verification.
- Updated the public testing guide for lazy imports, full-entry dependency scanning
  and cold-cache diagnostics. Owner/customer AGENTS and skills already route to
  these public test contracts; no additional instruction-product change is needed
  for correcting private suite imports. Public testing support APIs are unchanged.
- No disposable containers, networks or volumes were created by these test runs.
  The supported host knowledge transport prepared its operational management image
  `runa-management:7b4060d299cfd85922f842e3`; retain it for ongoing development/hook
  transport. Preserve active development services, all database/model/Qdrant data,
  cache backups and reports. No resource deletion or data operation was performed.
- Point 12 is next; points 12–20 remain open. VERSION remains 0.5.0 and the confirmed
  compatible feature projection remains 0.6.0.

### Point 12 completion — API audience, signature and placement map (2026-10-27)

- Refreshed the renamed active roadmap and completed point 11. Local knowledge
  status was current: 187 documents, 3060 chunks, no changed/deleted/missing/orphan
  chunks. Retrieved public API/module boundary guidance and opened the original
  public API, module, frontend, testing and seeding sources before recording rules.
- Added the owner-only [map specification](../../architecture/api-boundary/README.md),
  [file inventory](../../architecture/api-boundary/source-map.json) and
  [signature/consumer inventory](../../architecture/api-boundary/signatures.json).
  Baseline source objects record the exact pre-move commit. All 859 tracked
  src/runa files have explicit owner/audience/mode, customer/production disposition
  and exact targets; 165 private file relocations are assigned to owning points.
- Recorded 302 signature sources, 195 consumer/reference sources and exact named
  source callers for 17 reserved contracts. Complete Git source objects preserve
  PHPDoc, base/callback/value/exception signatures, SFC props/emits/slots and inferred
  frontend controller members; extracted declarations/reference matches are
  navigation evidence, not a semantic parser or a blanket import permission.
- Kept public Support adapters, WorkHandler/WorkContext, module contracts and
  frontend facade identities compatible. Consumer review additionally retained
  SeedGraph, ModuleSettings, ModuleFrontend, VersionCompatibility, the welcome
  route controller, public test bridges/browser/config entrypoints and the module
  Vite plugin's supported path. Private suite configs still move to owner tooling.
  Retained upgrade classes/edges/fingerprints and migration bytes deliberately;
  no historical-handler consolidation is authorized by directory cleanup.
- Froze ModuleFramework internals outside the guarded Modules namespace, named
  Core integration, provider responsibilities, module HTTP/work placement,
  frontend type/controller collaborators, generator/build/management ownership
  and source/image migration dependencies. These are migration targets, not
  claims that runtime or Docker projection has already changed.
- Focused inventory diagnostic PASS: exact 859/859 tracked-file coverage and
  source hashes, unique destination paths, unchanged public file/FQCN identities,
  and existing signature/named-caller source files. The diagnostic caught the
  public Vite plugin relocation proposal; corrected the map to retain its entry.
  No application implementation, runtime configuration, tests, builds, production
  operations or broad verification were executed in this point.
- Instruction-product assessment: root AGENTS and owner/customer backend,
  Laravel/frontend skills already route to the authoritative public boundary and
  require signature classification before moves. No new reusable customer rule
  or delivered capability changed here; those products remain valid unchanged.
  Exact delivered-layout updates belong to their implementation points and the
  point-19 reconciliation. Public boundary guidance now records the stable-entry
  compatibility constraint without publishing owner-only migration artifacts.

### Point 13 completion — platform internals and explicit composition (2026-10-28)

- Applied the point-12 map to 68 private PHP classes: module engines now live in
  ModuleFramework, named Core bridges in Integration/Core, and infrastructure in
  Identity, Configuration, Logging, Mail, Runtime and Http. Public Support adapter,
  value, exception and WorkHandler/WorkContext identities remain supported. The
  owner-approved correction retains ModuleJob's exact serialized class identity;
  no alias, copied engine or database reset was introduced.
- RunaServiceProvider remains the configured entrypoint and registers explicit
  ModuleFramework/CoreIntegration/Development providers. Core security bindings
  remain lazy and bindIf precedence is preserved. Root boot retains the existing
  logging, queue-admission and heartbeat callback order; AuthTransport retains
  Fortify/HTTP ownership. Module loading remains registry/hash guarded. Explicit
  Composer classmap exclusions remove module PSR-4 notices without bypassing that
  loader. Fixed the central private fixture loader's missing path separator, found
  by the focused registry diagnostic.
- Reconciled current PHP, test/native fixtures, configuration, platform preparation,
  release checks and external module planning consumers. Docker PHP source stages
  include the moved runtime homes. Version-separated planners and deployment/update
  capability probes retain both previous Support and current ModuleFramework target
  layouts; rollback source hashes cover both. No source/image acceptance ran.
- Auth, Audit and Permissions use compatible PATCH 1.0.2 for internal collaborator
  imports, each with a new source-only forward edge. Public contracts/dependency
  ranges stay compatible. Existing migration and Upgrade handler bytes, version
  edges and recorded data histories are preserved; Permissions initialization now
  imports the relocated collaborators. Pending-source/fingerprint refusal remains
  intact. No business-data migration or production operation ran.
- Focused evidence: four new PHP composition/autoload/public-signature/persisted
  envelope/isolated target-planner cases passed (310 assertions across the two
  selections); existing registry-loader case passed (2 assertions); the real queue
  envelope case passed (15 assertions) in one disposable worker. Two updater layout
  cases and one deployment failure-boundary case passed through supported Python
  transports. Three module version checks against main returned no errors.
  Composer regeneration/package discovery passed without module PSR-4 notices.
  A targeted stale-symbol search found no old relocated FQCNs in current consumers.
- Initial diagnostic failures were resolved: the autoload test now respects Core
  contracts' guarded loading instead of expecting unconditional module imports;
  the planner fixture checks its actual public pending_upgrades field. The registry
  probe exposed the private fixture loader separator bug. A mistaken queue filter
  collected no tests and was replaced by the exact existing case. Running that case
  on the preserved base test database correctly refused its unrelated Core state;
  the supported single-worker selection passed without changing those records.
- Knowledge was initially unavailable because app was stopped, then available but
  stale after these documentation edits; focused original sources supplied context.
  Supported dev:up attempted normal setup, whose existing frontend typecheck failed
  at CustomerEntry.vue:31 before Vite/image acceptance. This incidental setup check
  is not final verification; point 15 now explicitly owns that fixture correction.
  No full suite, full static analysis, scanner pipeline or intentional broad build
  ran. Mandatory staged hooks and incremental knowledge refresh apply to the commit.
- Updated the public API/module guides, placement-map status and owner/customer
  backend references with local provenance. Root AGENTS and Laravel/security/Docker
  products already require the same public boundary, guarded loading, container
  ownership and scoped checks; they need no duplicate rule. Frontend/RAG rules are
  unchanged; layout-specific remaining reconciliation stays in points 14–19.
- The runner showed and removed only its disposable runa_test_test_1 database.
  Preserved base runa_test, development data/services, Qdrant/model/builder caches,
  backups and reports. No disposable Docker simulation resources were created or
  deleted. Supported startup/tool transports retain their operational resources.
  Incremental knowledge refresh reached 189 documents/3133 chunks with no stale,
  missing or orphan chunks (21 embedded, 13 removed). Its supported transport
  prepared operational management tag runa-management:bb12fe0c186bd993ba0a8fee
  after Dockerfile changed; retain it for current tooling, not acceptance evidence.
  VERSION remains 0.5.0, confirmed feature projection 0.6.0; point 14 is next.

### Point 14 completion — built-in contract audiences and private execution (2026-10-28)

- Applied all 54 point-14 relocations from the frozen map: Auth executable work
  handlers/policy live in Work, HTTP adapters in Http/Controllers and projections
  in Http/Resources. EntryWork and HTTP descriptors retain their identities and
  stable operations, routing/access metadata and payload semantics. No aliases or
  duplicate engines remain; public and reserved Contracts identities are unchanged.
- The public API guide now distinguishes Auth/Audit/Permissions guarded APIs and
  values, extension declarations/traits, local Audit suppression and reserved
  identity/authority/writer/development integration. Private Actions, Queries,
  Models and Proxies retain their cohesive existing homes. Combined settings
  declaration/application handlers deliberately retain their frozen identities.
  Historical initialization/Upgrade classes stay at module roots; no historical
  handler consolidation or migration rewrite was introduced.
- Auth advances compatibly from 1.0.2 to PATCH 1.0.3 with the source-only
  execution_layout edge and central changelog. Audit/Permissions implementation
  is unchanged at 1.0.2; required ^1.0.0 dependencies remain compatible. Updated
  all three module guides' obsolete version/dependency claims. Platform VERSION
  remains 0.5.0 and the approved feature projection remains 0.6.0.
- Added a pre-relocation history fixture anchored to dada9275: 86 successful
  upgrade fingerprints and 13 migration hashes across the built-in modules.
  The isolated target-planner regression validates retained fingerprints from
  installation, suppresses completed work, plans only Auth's new edge from 1.0.2,
  and refuses tampered history. The previous composition planner case includes
  the additional compatible Auth edge. Added source-owned work resolution through
  the actual guarded declarations without executing or enqueueing handlers.
- Necessary focused diagnostics passed through supported test-backend: two
  history/target-planner cases (32 assertions), and three single-worker Auth cases
  for work resolution, guarded login/logout and actual CSRF refusal (45 assertions).
  Scoped module version checks against the point baseline returned no errors for
  all three modules. Exact moved-symbol search found no stale FQCNs in current
  source/test/tool consumers, and all 54 frozen relocation targets exist. No full
  suite, static analysis, image build, scanner pipeline or broad acceptance ran;
  these remain exclusively final point 20. The first mandatory hook attempt
  detected eight generic-api-key false positives where migration filenames
  containing token/key words were JSON keys beside source hashes. A focused
  redacted scanner report identified only those public source hashes. Stored
  migration path and SHA256 as separate named fields, preserving the same values
  without any scanner exception or policy change; repeated the affected history
  case and mandatory hooks. The private report remains under .runtime/security.
  Incremental indexing reached 189 documents/3138 chunks before final hook
  formatting; staged freshness remains mandatory.
- Local knowledge was current (189 documents, 3133 chunks); queried boundary and
  upgrade guidance and read original public API/module/security sources. Loaded
  backend/Laravel/security skills. Updated the public boundary and module guides,
  Auth changelog and owner placement-map delivery status in this point. Root
  AGENTS and owner/customer backend/Laravel/security products already require
  public signature preservation, guarded module APIs and source-owned placement;
  this adds no authority, extension entrypoint or new operating procedure, so
  those instruction products need no duplicate changes. Frontend contracts remain
  point 15 work. Incremental knowledge refresh and mandatory hooks accompany commit.
- The supported runner showed and removed its disposable runa_test_test_1 worker.
  Base runa_test, development data/services, Qdrant/model/builder caches, backups
  and reports are preserved. No task-created Docker containers, networks, image
  tags or volumes required deletion. No production operation or business-data
  migration ran. Point 15 is next; points 15–20 remain open.

### Point 15 completion — explicit frontend consumer contracts (2026-10-28)

- Published complete AuthEntryController/AuthAccountController/AuthWelcomeController/
  ApplicationSessionController aliases through the retained Auth facade, with the
  navigation callback, entry/account/application/history/security component props,
  slots/events and cropper handle. Aliases preserve every inferred return member;
  customers can name nested result/argument types without private imports. Existing
  Vue Component presentation definitions remain compatible with wrappers that
  forward attributes or consume only part of a contract.
- Supplied wrappers/security components use the same named declarations. Public UI
  layouts expose macro-compatible props/slot interfaces and translator keys; module
  pages/slots expose reactive prop/fallback contracts plus metadata types. Static
  errors retain HttpErrorLayoutProps and expose HttpErrorContentData. No facade or
  documented subpath was renamed. The existing entryPage resolver already owns
  Auth path matching; the minimal document dispatcher remains separate from
  customer routing. No additional router, navigation registry or state dependency
  was introduced.
- Corrected CustomerEntry's untyped template-ref callbacks with explicitly typed
  public consumer bindings. Typed application-owned examples cover controller
  composition, forms, layouts, full shells, module pages/slots and static errors.
  New regressions cover nested/reactive provider inheritance and defaults, keyed
  page/account replacement, private-draft isolation without write replay, and
  reactive module page/slot fallback. Provider composition unwraps component proxies
  while retaining map reactivity; the nested consumer asserts no Vue warning.
- Auth advances from 1.0.3 to compatible MINOR 1.1.0 for the additional public
  contracts. Reference advances from 0.1.1 to PATCH 0.1.2 for its corrected public
  HTTP facade import. Each has a new source-only forward edge and central changelog;
  retained handlers/migrations/fingerprints are unchanged. Audit/Permissions remain
  1.0.2; Permissions' required Auth ^1.0.0 accepts 1.1.0, and Auth's required
  Audit ^1.0.0 remains satisfied. Platform VERSION stays
  0.5.0; confirmed feature projection remains 0.6.0.
- Necessary focused diagnostics: nine customization cases passed in Chromium through
  test-frontend across auth-entry/auth-account/modules/error-customization. Other
  tests were excluded by the explicit name filter, not accepted as passing. A
  focused strict vue-tsc consumer project under .runtime/frontend/point15 compiled
  all customization fixtures and their public type dependencies through bin/pnpm;
  no scoped typecheck command exists, so this was the documented one-off diagnostic
  through the tool transport, not a full static-analysis gate. The final rerun
  passed after supplying Vite ambient types and preserving optional-prop defaults
  in the typed layout wrapper. Two existing history/target-planner cases passed
  through test-backend (32 assertions); scoped module version checks against the
  point baseline returned no errors for both owners.
- The initial account-switch fixture bound a new field to the proof-only section
  map; corrected it to the controller's actual editable fields and waited for
  readiness before asserting draft isolation. Selected browser runs initially
  exposed reactive-component warnings; provider unwrapping resolved them. Vite
  still reports its upstream generated Vitest module-runner dynamic-import analysis
  warning; no source suppression or dependency change was added. Broad suites,
  browser acceptance, builds, scanners and full analysis remain final point 20.
- Local knowledge was current (189 documents, 3143 chunks); queried frontend
  contracts and opened the original adoption/architecture sources. Loaded frontend/
  Vue and backend/Laravel guidance for module-version edges. Synchronized the public
  adoption, components/layout and API guides, owner placement map, Auth and Reference
  changelogs, and owner/customer frontend references with local provenance. Root
  AGENTS and Vue/Laravel/backend/security products already route to these public
  contracts and preserve ownership/lifetimes; no authority or duplicate procedure
  change is needed. Incremental indexing and mandatory hooks accompany the commit.
- No disposable Docker container/network/image/volume was created or removed.
  Supported backend cleanup reported an empty worker list; base runa_test,
  development data/services, Qdrant/model/builder caches, backups and reports are
  preserved. No production operation, database migration or business-data mutation
  ran. Point 16 is next; points 16–20 remain open.

### Point 16 completion — shared account orchestration and Auth preference ownership (2026-10-28)

- Extracted private profile/avatar conflict reconciliation, paginated sessions/
  history/trust and history-detail cancellation, task/proof validation, and delivery
  preference reads/writes into account collaborators. They receive the mounted
  controller's identity, signal/liveness, shared refs and mutation gate; no second
  account store, independent security flow or copied session controller exists.
  Public useAuthAccount arguments, returned members and named type aliases remain
  compatible. Existing AccountTab/AccountTask/AccountSection exports are retained.
- Kept coordinated refresh, sequential section/task writes, partial-completion
  handling, proof/secret cleanup and safe-draft lifetime in the parent controller.
  Further splitting that pipeline would hide shared versions and ordering. Entry
  and supplied presentation already compose feature components; they need no extra
  layers solely for line counts. Public-only replacements continue using the same
  controller; no application scaffold or visual redesign was introduced.
- Generic preference storage/contracts no longer import Auth. Auth owns the
  relocated accountPreferences transport plus the single preferences/session
  composition and initial session probe. Updated internal consumers and public
  facade reexports together, without compatibility shims or duplicate adapters.
  Welcome catalogue presentation moved into Auth with its all-module manifest glob
  and existing metadata/icons. Removed both former private lib implementation files.
- Auth advances compatibly from 1.1.0 to PATCH 1.1.1 with the source-only
  account_orchestration edge and central changelog. Historical handler/migration
  bytes and fingerprints are unchanged. Audit/Permissions remain 1.0.2; required
  ^1.0.0 constraints remain satisfied. Platform VERSION stays 0.5.0 and the owner-
  confirmed feature projection remains 0.6.0. No dependency or data change occurred.
- Twelve distinct necessary focused frontend cases passed in Chromium through
  test-frontend: shared pending refresh/save revision and unrelated-draft retention;
  trusted-read disposal/account replacement; profile/draft merge and overlapping
  conflict; history-detail cancellation; display/delivery revision ordering;
  delayed hydration; password validation/CSRF no-replay; public replacement;
  welcome registration/catalogue; one delivery read per invalidation; and trailing
  focus invalidation. Other cases were excluded by name, not accepted as passing.
  The scoped public-consumer vue-tsc diagnostic through bin/pnpm passed. No scoped
  typecheck command exists; this uses the point-15 temporary consumer project,
  not the full source/static-analysis gate. Two supported backend history/target-
  planner cases passed (32 assertions); scoped module version checks against this
  point's HEAD baseline returned no errors for all three built-in modules.
- The initial type diagnostic found two mixed imports still requesting generic
  exports from the new session composition; split their imports and received PASS.
  A delivery-count diagnostic failed because synthetic tab activation first focused
  the browser document at heading.focus(), producing a separate legitimate focus
  refresh. Traced both reads to the existing refresh pipeline; the fixture now
  focuses Details before measuring tab entry and separately asserts one more read
  on explicit window focus. The exact delivery and trailing-focus cases passed;
  no refresh suppression, assertion removal or runtime retry was introduced.
  Temporary tracing was removed. Vite retains the previously observed generated
  Vitest module-runner dynamic-import analysis warning; no suppression was added.
- Local knowledge was current (189 documents/3155 chunks); queried refresh/delivery
  rules and opened original account/preferences/throttling guides. Loaded frontend/
  Vue/security guidance and backend/Laravel guidance for the version edge. Updated
  public account/preference/module guidance, owner placement-map delivery status,
  and owner/customer frontend references with local provenance. Root AGENTS and
  Vue/backend/Laravel/security products already enforce public ownership, shared
  lifetimes, cleanup and explicit retries; no new authority or procedure requires
  duplicate rules. Incremental indexing and mandatory staged hooks accompany commit.
- No broad suite, full static analysis, build, scanner pipeline or installation/
  production acceptance ran; these remain final point 20. No disposable Docker
  containers, networks, image tags or volumes were created or deleted. Supported
  backend cleanup reported an empty worker list. Base runa_test, development data/
  services, Qdrant/model/builder caches, backups and reports remain preserved.
  Point 17 is next; points 17–20 remain open.

### Point 17 completion — developer support, generators and build ownership (2026-10-28)

- Moved isolated PHP management adapters/catalogues/schema to Tooling/Management,
  retaining the Runa\Management namespace, explicit cold loader and single Artisan
  registry. Updated bin-facing PHP paths, image inputs, fingerprint/source catalogue
  readers and host/acceptance fixtures together. Production remains an isolated
  operator bundle; ordinary application startup does not load management providers.
- Grouped internal PHP runner adapters under Testing/Backend and the database guard
  under Testing/Database; the fixed worker child lives in testing/backend. Retained
  Tests\TestCase, browserProblems/frontend-runtime and all public testing aliases.
  Native/supported runners retain the same suite lock, status and cleanup. Outside
  testing, destructive commands refuse before resolving excluded developer support;
  production does not require the test guard just to reject a destructive command.
  Static PHP analysis includes the relocated development/testing responsibilities.
- Private Vitest/Playwright configurations now live under tools/maintainer/frontend,
  resolving the existing src packages explicitly without another installation or
  directory facade. Kept the delivered module Vite plugin in its supported location.
  Moved generator/templates together into Development/Modules. Generated controllers
  use Http/Controllers, API execution uses Handlers/Proxies, and components use
  frontend/components/<snake_case>.vue. Opt-in discovery binds generated keys to
  their manifest hash; generation never registers pages/slots or activates modules.
  Public API consumers, central tests/docs and exact hash overwrite guards remain.
- Moved static-error executables into delivered build/errors, updated renderer
  imports, Vite, setup/updater/test callers and explicit image build-stage copies.
  Runtime messages/templates, native mail bridges, assets/licenses, public output
  and workspace .runtime placement remain intact. Branding scripts moved to private
  owner assets tooling with documented cwd/endpoint/output ownership; no raster or
  generated production asset was regenerated. Historical receipt exclusion paths
  for removed InstallCommand/ProjectCommand remain exact, preserving old receipts.
- Necessary focused diagnostics passed through supported transports: four backend
  generator/hash/cold-registry cases (186 assertions), one production-refusal case
  (10 assertions), three tooling cases for config resolution, generated component
  discovery/hash and cold PHP error finalization, plus catalogue/schema/allowlist
  agreement. The finalizer fixture has no Laravel bootstrap or env file and checks
  six escaped customer-branded pages plus English JSON. The public headless entry
  CSRF/refusal case passed in Chromium through the relocated Vitest config. Composer
  autoload regeneration succeeded without scripts or dependency changes.
- Initial dynamic import of Playwright's require-selected module lacked its named
  devices export; use its existing CommonJS exports via src-scoped createRequire.
  Repeated config resolution passed. An initial frontend name filter selected no
  cases; the explicit existing headless-entry name above supplied executed evidence.
  Vite retains the previously observed generated Vitest runner
  dynamic-import warning, without suppression or dependency changes.
- Local knowledge was current (189 documents/3162 chunks); queried placement and
  generator/build contracts and opened the original guides. Used backend/Laravel,
  frontend/Vue, Docker/security guidance and version-aware Boost provider/command
  documentation. Synchronized public commands/tests/modules/errors/assets guides,
  owner placement/migration targets and owner/customer Vue references/provenance.
  Root AGENTS and backend/Laravel/frontend/Docker/security rules already route to
  these guides and preserve public ownership; no duplicate procedure or authority
  change is needed. Independent module source/contracts are unchanged, so no module
  version/changelog bump applies. Incremental indexing and mandatory hooks accompany
  the implementation commit.
- No broad suite, full static analysis, image/wheel build, scanner or live
  installation/production acceptance ran. No disposable Docker resource was created
  or removed; backend cleanup reported an empty worker list. Development/test/base
  data, active services, Qdrant/model/builder caches, backups and reports are
  preserved. VERSION remains 0.5.0; confirmed feature projection remains 0.6.0.
  Points 18–20 remain open; delivery enforcement and final acceptance are not claimed.

### Draft preparation validation (historical)

Planning validation is limited to metadata, ownership, source links, point order,
compatibility coverage and final-only broad verification scheduling. At draft
preparation all checklist items remained open and only the draft changed. No
quality PASS or implementation completion was claimed by that planning validation.
