---
id: RM-2026-012
title: Python Tooling Architecture & Unified Test Infrastructure
status: draft
branch: roadmap/RM-2026-012-python-tooling-architecture
created_at: 2026-10-26
activated_at: null
closed_at: null
release_type: feature
proposed_version: 0.6.0
quality_groups: all
quality_rationale: The rebuild crosses customer operations, acquisition/update, host transport, hooks, quality coordination, security, knowledge, containers and every existing test integration; all groups and their real prerequisites are needed for compatibility and isolation evidence.
impact_areas:
  - tooling
  - development
  - production
  - security
  - ai_environment
  - documentation
---

# RM-2026-012 — Python Tooling Architecture & Unified Test Infrastructure

## Overview

### What and why

Rebuild the Python tooling as a cohesive internal engineering platform with
explicit package APIs, injected infrastructure, typed contracts and unified tests.
This is a redesign of responsibility and dependency boundaries, not a directory
move or a mechanical conversion of functions into classes. Preserve existing
customer and owner behavior while removing duplicate implementation mechanisms.

The predecessor is [RM-2026-011](../closed/RM-2026-011-audit-auth-permissions-core.md),
released as 0.5.0. The owner explicitly requested **0.6.0 after this roadmap**.
`feature` is the proposed release classification because Runa uses it for a MINOR
increment with PATCH reset. Explicit classification confirmation remains pending;
obtain it before activation. Creating this draft does not change VERSION, approve
the architecture for execution, activate a roadmap or create its branch.

At authoring time the checkout is clean `main`, no roadmap is active or paused,
and the next unused identifier is RM-2026-012. Recheck these facts and the release
order at activation; a changed predecessor requires reconciliation with 0.6.0.

### Scope

All tracked Python source and executable launchers; application/maintainer and
external distribution tools; Python tests and fixtures; PHP/JS/shell/Compose/
Devcontainer callers; package/resource projection; tool pins; local hooks and
quality integration; documentation and both agent instruction products.

### Out of scope

No application feature, schema redesign, business-data migration, new UI, module
framework rewrite, production Python service, remote tooling platform, CI,
general plugin/DI framework, new host PHP/Node requirement, Docker socket inside
the devcontainer, or replacement of Laravel Artisan as the customer registry.
Do not rewrite mature Python engines in PHP or move PHP Actions into Python.

## Specification

### Decisions

- 2026-10-26 — The owner selected projected platform version 0.6.0. Proposed
  `release_type: feature` follows the existing 0.5.0 baseline and release rules;
  confirmation is pending, independently of roadmap activation.
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
API or third-party plugin namespace. Explicit packaging includes prevent discovery
of unrelated repository directories. Core and application form **one customer
distribution**, so customer tooling never needs to install a second core package.
Owner tooling is a separate private distribution adding `tools.maintainer`; it
does not ship a second `tools/__init__.py` or overwrite customer files. External
distribution is a separately prepared stdlib bundle containing its required core
from canonical source, not private maintainer code. There is one authored core;
generated bundle copies are artifacts, not separately maintained implementations.

Root `pyproject.toml` describes only deliverable core/application metadata and
shared tool rules. Private build/test dependency groups, collection roots and
owner package manifests live under `tools/maintainer`, outside customer projection.
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
  classified from private pytest tests and developer dependencies.

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

Keep existing public arguments, validation precedence, exit codes, streaming,
stdin/TTY behavior and plan-only guarantees. Translate expected failures once at
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
in containers. Reopen/recovery must work before installing development packages.

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

### Unified test infrastructure and regression matrix

Private Python tests live in `tools/maintainer/tests/python/{unit,contracts,
integration,e2e}` with importable `support` factories/fakes. Use `test_*.py`, pytest
`--import-mode=importlib`, strict registered markers and explicit collection roots.
Mark Docker/network/knowledge/database consumers and schedule them through existing
quality prerequisites/exclusive groups. Do not add generic host pytest as the
documented owner workflow: extend the existing maintainer entrypoint mechanism
with a justified `test-python` adapter supporting explicit scope/filter options,
container transport, reports and native exit codes. Public Artisan remains the
customer interface; add no owner Artisan aliases or competing test coordinator.

Support includes a recording fake process runner with literal argv/cwd/env/stdin
assertions and injected failures; fake Compose/image inspection; isolated Git
repository/customer receipt factories; typed config/journal factories; controllable
clock/HTTP provider; temporary workspace fixtures; output/result assertions; and
owned Docker resource journals. Avoid one universal fixture that starts the stack.
Unit tests cannot start Docker, network services or real databases implicitly.

Move existing useful unittest assertions under unified discovery first; pytest
can collect retained TestCase classes. Convert when fixtures/parameterization
remove actual duplication. Replace test inheritance used as a fixture library
with support objects so importing `Updater` does not collect inherited tests twice.
Preserve a mapping from old test/scenario to retained, migrated, strengthened or
deliberately retired behavior. A test count reduction requires explanation, not
necessarily a one-for-one replacement. Do not freeze incidental dict layout or
private method call order unless it is a recovery/security protocol.

| Surface                   | Required final regression evidence                                                                                                                                                                                                                |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| All public transports     | Every bin alias, application hook adapter, fixed-purpose maintainer command, documented direct Python command and external acquisition/install/update entry: help, valid/invalid args, no-arg behavior, exit/output/stdin/TTY and independent cwd |
| Host/devcontainer         | Host stdlib cold operation, configured container execution, no container Docker socket, correct context/root and paths containing spaces; mixed Python 3.11/3.12 contexts                                                                         |
| Management/host executor  | Unknown/duplicate/oversized/request-mismatched plans refused, immutable artifact binding, receipt roles, permissions, disconnected operation, cancellation, stale executor recovery and nonexecuting preview                                      |
| Install/update/team clone | Verified projection, clean/reference variants, optional customer example preservation, explicit remote ownership, development initialization, 0.5.0 upgrade to 0.6.0, preflight/apply/resume, source tampering and no replay after committed data |
| Production                | Separate complete customer fixture, actual image build/deploy/health, exact image and source identity, TLS/SMTP refusal/resume, forward data semantics and unsafe rollback refusal                                                                |
| Quality                   | Manual/legacy selection, prerequisite union, unchanged hard failures, cycle/conflict detection, exclusivity/concurrency, candidate/hash binding, timing, background/status, interrupted coordinator and surviving child refusal                   |
| Security                  | Reusable direct/quality scanner results, pinned artifacts, lock consistency, all selected image reports collected despite earlier failure, secret redaction, scanner errors/findings remain nonzero, real review evidence                         |
| Knowledge                 | Freshness/source filtering, owner/customer corpus split, model/input/dimension refusal, pending recovery, incremental reuse, exact golden cases, real CPU/CUDA fallback where supported, production refusal                                       |
| Hooks                     | Real installed safe temporary hooks, secrets/env/key rejection, invalid message, partial staging, formatter input from index, owner/customer policy and mandatory knowledge freshness                                                             |
| Packaging/isolation       | Customer without owner/external directories, owner wheel layered on customer wheel, standalone external bundle, declared resources, private test/dependency exclusion; production contains no tooling/AI imports, services or artifacts           |
| Lifecycle                 | Lock contention, symlink/ownership refusal, signal restoration, kill/reap before cleanup, inactive worker ownership, automatic vs manually owned browser endpoints, task-only Docker cleanup and preservation of durable resources                |
| Interop                   | PHP Actions and generators, shell hooks, Compose setup/knowledge jobs, Devcontainer startup, Docker build inputs, both gate definitions and JS/package script integrations                                                                        |

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

This draft alone introduces no delivered capability or reusable operating rule,
so root/customer instruction products and current-state documentation need no
changes now. Closed roadmaps and dated audit evidence remain immutable.

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
- Public transports, Artisan contracts, PHP/JS/shell/Docker integrations, journal
  compatibility, native exits, cancellation and scoped cleanup remain correct.
- Transitional adapters, duplicate helpers, obsolete test runners/configuration,
  dead imports and resolved owner-specific exception machinery are retired.
  Retained public compatibility launchers contain transport only and are documented.
- Documentation and instruction products match delivery. Final evidence records
  the actual selected graph/candidate, semantic docs review and resource disposition.

## Implementation plan

All points remain open. Each implementation point produces code, test code,
synchronized documentation, a logical commit and checklist update. Refresh the
active roadmap, relevant decisions, local knowledge and relevant skills before
each point. Do not routinely run suites at point boundaries. Only necessary small,
fast checks resolving a concrete uncertainty belong before the final point;
mandatory commit hooks always apply. No broad baseline, installation acceptance,
static analysis, image build or scanner pipeline is a prerequisite for later
implementation. Build their harnesses/scenarios first and execute them at the end.

- [ ] 1. Capture executable contracts and the migration ledger.
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

- [ ] 2. Establish packages, reproducible development tooling and test discovery.
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

- [ ] 3. Implement shared context, process, state and lifecycle infrastructure.
  - Prerequisites: point 2 packages and test support.
  - Implement the core contracts above, preserving explicit timeout/stdio/env and
    strong filesystem/lock policies; add typed error/result/reporting boundaries.
  - Add unit/failure/cancellation tests, including two workspaces/releases in one
    process and process reaping before lock/resource release.
  - Migrate first reusable runtime/Compose consumers; remove their duplicate
    mechanics only after all callers in that slice use the canonical service.
  - Document API ownership, dependency direction and runtime artifact locations.

- [ ] 4. Migrate application guards, development and management/host transport.
  - Prerequisites: point 3 infrastructure.
  - Migrate repository receipt/source policies, dependency composition and module
    version checks, development config/setup and host/container resolution.
  - Separate discovery/image identity, validated plans, request transport and
    executor lifecycle into injected services, preserving fixed allowlists and
    existing PHP Action ownership. Make imports inert.
  - Update bin/hook bootstrap adapters, PHP paths and Compose/Devcontainer startup
    together. Add cold/host/container, invalid-plan, role/refusal, disconnect and
    compatibility regression scenarios. Keep application startup independent of AI.
  - Synchronize public commands, host executor/development docs and affected products.

- [ ] 5. Rebuild shared quality, diagnostics, scanners and hook services.
  - Prerequisites: point 4 guards and transports.
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

- [ ] 6. Migrate deployment and external acquisition/install/update.
  - Prerequisites: points 4–5 shared services and policies.
  - Replace global production ROOT/STATE mutation with explicit release contexts;
    separate source acquisition, Compose/image validation, data transitions,
    health/isolation and deploy/rollback orchestration.
  - Rebuild external installation/update as package services using the explicit
    application gateway, separating contracts/customization/files/runtime from CLI
    orchestration. Break updater import cycles and preserve durable journal phases.
  - Preserve standalone bootstrap, verified sparse projection, external tool bundle
    operation, remote/history boundaries and PHP module/database gateways.
  - Add 0.5.0 upgrade, two-release, interrupted source/data switch, TLS/SMTP,
    unsafe rollback and production/customer distribution regression scenarios.
  - Synchronize installation/update/production/security docs and package projections.
    Do not run real installation/build/deployment acceptance yet.

- [ ] 7. Migrate development-only knowledge and owner extensions.
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

- [ ] 8. Consolidate private Python tests and isolated acceptance support.
  - Prerequisites: points 4–7 migrated domain APIs and their test code.
  - Move remaining unittest cases into unified discovery; retain useful assertions,
    replace fixture inheritance/runpy with support factories and qualified imports,
    and reconcile the old-to-new coverage ledger.
  - Consolidate host acceptance process/Git/Compose/resource helpers; retain unique
    project labels, ownership snapshots, journal recovery and suite exclusivity.
    Parameterize container identity instead of fixed dev container names.
  - Migrate localization/service fixtures, error/SMTP/FastCGI helpers and still-live
    PostgreSQL probes. Assess and document obsolete historical probe retirement.
  - Keep supported backend/frontend/e2e runners and PHP worker lifecycle intact;
    route Python scopes through the existing quality scheduler without a second
    lock/report coordinator. Document exact scoped commands and prerequisites.

- [ ] 9. Complete caller migration and retire the old architecture.
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
  - Prepare the final graph definitions and acceptance scenarios; do not run an
    early full gate, static analysis, image build or acceptance phase here.

- [ ] 10. Full verification, release finalization, merge and final report.
  - Prerequisites: points 1–9 complete and committed, migration/coverage ledgers
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
  - Include actual host/container/standalone/package/customer upgrade and production
    acceptance, scanners, hooks, knowledge golden/freshness and supported PHP/JS/
    database/browser suites. Collect complete candidate-bound results and artifact
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

### Draft preparation validation

Planning validation is limited to metadata, ownership, source links, point order,
compatibility coverage and final-only broad verification scheduling. All checklist
items stay open. No quality PASS or implementation completion is claimed here.
The only current file change is this draft under the permitted planning directory.
