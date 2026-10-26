# Python tooling migration baseline

This is the owner-only contract and migration ledger for
[RM-2026-012](../../roadmaps/README.md).
It describes the existing 0.5.0 implementation and its intended replacement;
it does not claim that packages, pytest or the new services have been delivered.
The baseline source commit is `32d55d2f` (roadmap activation; runtime source is
unchanged from the released 0.5.0 tree).

## Evidence and use

- [inventory.json](inventory.json) records all 104 tracked Python source/shebang
  files, 37 transport aliases, 37 integration resources, 68 non-Python reference
  files and 58 internal/inline integration candidate files. Each source has its
  hash, ownership, context, responsibilities, imports, candidate local dependencies,
  path contracts, argparse declarations, migration point and source-level test links.
- [migration-ledger.md](migration-ledger.md) gives a per-file target responsibility
  and point assignment. Update each row's disposition in its owning migration point.
- [Python command contract fixtures](../../../../../tools/maintainer/testing/fixtures/python-command-contracts.json)
  bind 178 command/engine/test entry surfaces to 17 named regression scenarios,
  including the 72 exact management JSON declarations and their signatures/prefixes.
  They are declarative test inputs, not a runner or an executable command allowlist.

No target modules were imported or commands under test executed to collect this
baseline. Import references can be ambiguous: both owner and application have
`checks`/`docs_checks`, for example. Candidate lists deliberately preserve this
ambiguity instead of pretending to reproduce runtime sys.path resolution. A direct
source reference is evidence to inspect, not measured coverage. The 289 recorded
test methods are definitions, not a count of collected, unique or passing tests.
The inventory lists historical documentation references separately; their bytes
must not change when current paths are migrated.

Source discovery covers tracked Python extensions and Python shebangs anywhere
in the repository, plus non-Python references to Python/tool paths. Relevant
non-Python integration resources are explicitly included even when they do not
spell a Python filename. Inline candidates include Python `-c` programs, shell
heredocs and internal runpy/import-path harnesses; source path insertion itself
is not proof that a second interpreter is involved.

During migration, preserve the baseline identities and update the ledger's
disposition/evidence. Do not regenerate the original snapshot against partially
migrated code and thereby erase unmigrated contracts. Add newly discovered callers
and their resolution deliberately. Exact filenames below identify current source;
target responsibilities do not prescribe one class or file per existing script.

## Public surface and process contracts

| Surface                                           | Arguments, output and exit behavior                                                                                                                                                                                                   | Context and effects                                                                                                                                                                                      |
| ------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `bin/artisan`                                     | Empty args and `--help` print usage, exit 0. Native/management arguments remain literal; a `CalledProcessError` retains its child code (existing test injects 7). Recovery flags are distinct fixed operations.                       | Resolve tool root from launcher. Normal host native execution delegates into app; cold management uses isolated artifact. `--recover-host`, `--stop-host`, `--operator` have explicit lifecycle effects. |
| `bin/command`, `check`, `operations`              | Discovery transports select the management command list; they do not accept a second action registry.                                                                                                                                 | Management discovery may run a disposable isolated container. Do not assert that cold discovery creates no process or image; it must execute no requested domain operation.                              |
| `bin/{format,lint,typecheck,analyse,test,deploy}` | Preserve alias name through symlink resolution and forward to the corresponding exact Artisan command. Child status propagates. `test` means backend.                                                                                 | Empty args can perform real work; never probe these generically during inventory. Format/deploy are mutating commands.                                                                                   |
| `bin/{composer,pnpm}` and `passthrough`           | Help/no args produce usage for supported names; arguments preserve their order and literal content. Exceptions use existing stderr/error status handling. Raw `passthrough` is an implementation adapter, not another tool registry.  | Host delegates with Compose `exec -T`; container uses installed tool. Composer selects `src`, pnpm uses `--dir src`.                                                                                     |
| `bin/logs`                                        | No args selects development logs; `--help` only prints usage. Preserve streaming and native failures.                                                                                                                                 | Default is a read operation, not the passthrough help default.                                                                                                                                           |
| `bin/boost-mcp`                                   | Help exits 0; unexpected args refuse with status 1; normal execution uses exec replacement and transparent stdio.                                                                                                                     | Development only; preserve MCP stdio without adding informational stdout.                                                                                                                                |
| `tools/application/bin/{pre-commit,commit-msg}`   | Select purpose by basename. Commit-message takes a message file; invalid invocation fails 1. Preserve index contents and scoped `GIT_INDEX_FILE`.                                                                                     | Tool root and target Git repository can differ; host path translations and container transport are explicit.                                                                                             |
| `php artisan runa:*` management definitions       | Fixture records exact source-owned command, signature, helper prefix and development/production catalog. Keep PHP parsing and plan validation; internal Python parsing remains defensive.                                             | Normal PHP terminal is primary. `--plan` is nonexecuting; development operations reject prod receipts even with copied source/APP_ENV override.                                                          |
| Owner `check-*`                                   | Only no args or `--help`; reject foreign action/options before dispatch. Preserve per-check result and stderr behavior; do not assume all uncaught diagnostic exceptions preserve child codes.                                        | Fixed owner scope, no owner Artisan alias; relevant checks delegate into development container.                                                                                                          |
| Owner `quality-*`                                 | Repeated `--group=NAME` or standalone `--all`; background only for roadmap/final. Status has no flags, plan cannot background. Invalid combinations refuse before changing runner/lock/evidence state.                                | Owner roadmap/root/definition/evidence separate from customer. Background launch hands off; never poll as part of an active agent turn.                                                                  |
| Owner `test-backend`, `test-frontend`, `test-e2e` | Preserve dedicated native arguments/configuration, backend parallel worker default and e2e `--critical` compatibility. Return native failure; cleanup failure replaces success.                                                       | Native runners stay PHP/JS, suite lock and worker/browser lifecycle stay mandatory.                                                                                                                      |
| Owner database aliases                            | `migrate*`, `wipe`, `seed` are fixed-purpose; forbid path/database/realpath/env/schema overrides. Seed needs one explicit `Runa\\Database\\Seeders\\...` class. Validation string SystemExit is 1; child result otherwise propagates. | Development guard and platform-only PHP database gateway; no test or database changes in this point.                                                                                                     |
| Owner `make-migration`                            | Name required; path/realpath override refused. Fixed `runa/database/migrations` output; retain native status.                                                                                                                         | Host delegates before help handling; do not assume this help is container-free.                                                                                                                          |
| Direct source helpers                             | Preserve `dependencies.py --check`, `module_versions.py --owner app                                                                                                                                                                   | runa [--base ...]`, scanner provisioning and owner localization subcommands using extracted declarations.                                                                                                | They are documented narrow source workflows; do not add them to a new public Python command registry.    |
| External `bootstrap.py`, `initialize.py`          | Preserve all extracted flags, hidden/file credentials, explicit identity/reference selection and standalone acquisition. Argparse help is 0, parse errors 2; expected operational failures 1, handled interruption 130.               | Host stdlib, no PHP/Node or maintainer installation required; verify source before publication, no existing destination replacement.                                                                     |
| External `installation.py`                        | `dev                                                                                                                                                                                                                                  | prod target` plus settings/profile/simulation/resume/noninteractive flags. Parse failures 2; handled failure 1, interrupt 130.                                                                           | Explicit isolated customer target, external helper source and existing journal/settings/data boundaries. |
| External `update.py`                              | Exactly one preflight/plan/apply/resume; installation and target required. JSON/human output retains report semantics. Ready/completed returns 0; blocked/refused result 1; parser 2; handled interrupt 130.                          | Read-only modes never create installation operation state or ask write consent. Apply/resume use reviewed fingerprint and explicit recovery.                                                             |
| Internal engines and private test/fixture scripts | Extracted grammar/current caller protocol is authoritative; no blanket `--help` or no-argument invocation contract. Test scripts retain meaningful scenarios under future discovery.                                                  | Some no-argument helpers run Docker, provision endpoints or mutate isolated data. They cannot be auto-invoked from a manifest.                                                                           |

Error messages must remain English, actionable and secret-safe. Preserve semantic
help/output contracts and machine-readable schemas, not obsolete roadmap prose,
absolute workspace paths or traceback formatting. Existing native status and
interruption contracts take precedence over an invented universal failure code.
The new reporter must not print secrets or alter Boost's protocol stream.

## Ownership and target dependency map

The approved target package layout is specified in the active roadmap. In short,
core has no domain imports; application consumes core; owner consumes supported
application/core APIs and injects private policy; external distribution uses its
own verified bundle/core plus a version-checked customer gateway. Runtime images
receive none of these developer/operator packages. Preserve the distinct trust
boundary between a trusted operator environment and the application runtime.

| Migration slice                      | Inputs and current dependency problems                                                                                 | Required output                                                                                               |
| ------------------------------------ | ---------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Context/process/storage (3)          | Bare `runtime` imports, import-time ROOT/STATE, duplicated execution and atomic-state helpers                          | Explicit immutable contexts and focused shared mechanisms; policy remains with consumers                      |
| Guards/development/transport (4)     | Receipt/source role checks, PHP command catalogs/schema, Python allowlists, hooks/bin and Compose/Devcontainer callers | Inert qualified APIs, consistent metadata, independent security validation and cold stdlib transports         |
| Quality/checks/security/hooks (5)    | Owner function/global mutation; quality/checks/docs cycles; staged-content checks; scanner result paths                | One injected coordinator, owner validators outside delivery, reusable scanners and unchanged strict evidence  |
| Operations/external distribution (6) | Operations/production ROOT swapping, CLI imported by updater services, duplicate composition/projection rules          | Explicit release state machines, standalone acquisition and compatible version-separated gateway              |
| Knowledge (7)                        | Bare corpus/provider/store imports; owner resource discovery inside delivered config                                   | Injected owner additions, typed config/state, unchanged embedding/model/parser semantics                      |
| Private tests/support (8)            | runpy fixture loading, inherited fixture cases, patched global roots, duplicated acceptance transports                 | Unified discovery, shared effect fakes and isolated real scenarios retaining native PHP/JS runners            |
| Caller cleanup (9)                   | Constructed PHP paths, schema/catalog copies, inline shell/Python, package/build/editor/docs references                | No remaining old-only consumer or temporary internal adapter; deliberate public compatibility transports only |

Point 2 supplies packaging/configuration/discovery before point 3 core. Point 4
depends on 3; point 5 on 4; point 6 on 4–5; point 7 on 3–5; point 8 on migrated
domain APIs; point 9 reconciles all callers. Test definitions are delivered with
their domain migrations; point 8 consolidates remaining support, rather than
deferring all test design to the end. Point 10 alone executes full verification.

## Source/documentation disagreements and disposition

| Evidence                                                                                                                                                                | Resolution in this roadmap                                                                                                                                                         |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `make-migration --help` says reset isolation is implemented in a future roadmap point 9c; current database gateway/guards already exist                                 | Stale delivery prose, not missing authorization. Correct help during point 4 adapter migration, retaining current isolation behavior.                                              |
| Production helper help mentions removed `install-prod` transport                                                                                                        | Use current public acquisition/deployment guidance; correct stale names in point 6. Do not resurrect a removed alias.                                                              |
| `tools/application/git_hook.py` help suggests the generic bin git-hook accepts actions, while that adapter requires a dedicated basename                                | Preserve documented dedicated hook entrypoints; internal adapter help is not a public action-selector contract. Reconcile point 5.                                                 |
| Public doctor-development section describes the command as its own alias                                                                                                | Remove tautological prose when synchronizing point 4 docs; retain the command and behavior.                                                                                        |
| `MT-2026-002` still says the strict runtime will be published in a pending release; 0.5.0 is already released and remediation source reports delivered strict artifacts | Reconcile this current maintenance record in point 5 after inspecting its closure evidence. No runtime rollback, new exception or historical rewrite is authorized.                |
| Static source inventory finds some components without direct test import/path references, including scanner provisioning and Boost transport                            | This is an evidence gap, not proof of zero coverage. Add explicit transport/provisioning and package scenarios in owning points, tracing indirect coverage before replacing tests. |

No disagreement changes the accepted architecture, public behavior or security
premise. None requires broad verification before point 10. Material new premises
still follow the roadmap stop-and-discuss rule.

## Temporary integrations and exception ledger

| Current mechanism                                             | Consumers                                                                      | Removal/replacement condition                                                                                                                                      |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `sys.path` and bare imports                                   | All recorded flagged sources and their local import candidates                 | Replace each slice with qualified package imports; no flags remain by point 9                                                                                      |
| Owner quality globals/function replacement and runpy executor | `tools/maintainer/entrypoint`, quality contracts                               | Inject owner context/planner in point 5; remove old wiring in that point                                                                                           |
| Operations changes `production.ROOT`                          | Release Compose/build/deploy and distribution callers                          | Explicit ReleaseContext in point 6, two-context regression; delete swapping mechanism                                                                              |
| External install runpy/customer cwd imports                   | Bootstrap installation handoff and host acceptance helper                      | Explicit version-separated gateway and standalone bundle in point 6; no package import from unverified source                                                      |
| Private fixture runpy/dynamic file imports                    | Update/install/PostgreSQL/wrapper/ownership acceptance files                   | Ordinary support imports, subprocess boundary tests and explicit scope in point 8                                                                                  |
| `approved_rm010_closure_fix` in customer quality              | Owner closure of historical RM-010; exact commit checks                        | Review archived evidence in point 5; remove obsolete live callback and exception-specific tests if no current release needs it, while preserving the closed record |
| `approved_version_backfill` in customer hooks                 | One-time 2026-10-22 closed-roadmap metadata authorization and immutable hashes | Review in point 5; retire completed write authorization and dedicated callback tests while retaining normal historical immutability enforcement and audit hashes   |
| Temporary PostgreSQL derivative (MT-2026-001)                 | Docker build/runtime security baseline                                         | Preserve until the existing corrected-official-image condition is met. Python cleanup does not authorize a runtime replacement or loosen scanning.                 |
| Migration adapters introduced by later points                 | None yet                                                                       | Each owning point records its exact consumers and removal point; point 9 permits only documented external compatibility transports                                 |

No new security exception or suppression is introduced by point 1. Retiring live
exception code does not mean deleting immutable evidence or its historical hashes.

## Documentation and instruction assessment

Current runtime claims remain unchanged: packages, dependencies and test runners
are not yet rebuilt. The maintainer README alone changes its active-roadmap state.
Root AGENTS and owner/customer skills remain correct: this point captures existing
contracts and approved targets, not a new reusable developer procedure. Customer
instruction files were treated as product data. No customer update is needed until
the implementation points change delivered tooling or public instructions.

This baseline's machine-readable fixture definitions and inventory stay excluded
from customer distributions. They are not a new supported command or a quality
policy change. Existing quality/hook policy remains authoritative. Structural
inventory/fixture checks may run here to resolve omissions and dangling references;
no broad test, analysis, build, scan or installation acceptance is claimed.
