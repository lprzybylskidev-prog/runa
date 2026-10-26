# Per-file migration ledger

Baseline source snapshot and semantics are explained in [README](README.md). Every row starts `planned`; implementation updates disposition and evidence in the owning point. Target names identify responsibilities, not a required one-file/one-class transformation.

| Current source                                           | Point | Target responsibility                                                                                             | Disposition                                                   |
| -------------------------------------------------------- | ----: | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| `bin/artisan`                                            |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `bin/boost-mcp`                                          |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `bin/command`                                            |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `bin/passthrough`                                        |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `distribution/bootstrap.py`                              |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/initialize.py`                             |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/installation.py`                           |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update.py`                                 |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_contracts.py`                       |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_customization.py`                   |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_execution.py`                       |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_files.py`                           |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_modules.py`                         |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `distribution/update_runtime.py`                         |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `tools/application/bin/git-hook`                         |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/checks.py`                            |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/customer_source.py`                   |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/dependencies.py`                      |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/docs_checks.py`                       |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/documentation.py`                     |     3 | Shared context/process/state APIs                                                                                 | retained application ownership policy; typing/imports point 4 |
| `tools/application/executors/dev.py`                     |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/executors/doctor.py`                  |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/executors/knowledge.py`               |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/executors/operations.py`              |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `tools/application/executors/production.py`              |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `tools/application/executors/quality.py`                 |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/executors/quality_check.py`           |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/executors/security.py`                |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/git_hook.py`                          |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/hooks.py`                             |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/host_executor.py`                     |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/install-scanners.py`                  |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/knowledge/configuration.py`           |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/corpus.py`                  |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/ingest.py`                  |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/provider.py`                |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/retrieval.py`               |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/startup.py`                 |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/store.py`                   |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/knowledge/transport.py`               |     7 | Development-only knowledge APIs                                                                                   | planned                                                       |
| `tools/application/management.py`                        |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/module_versions.py`                   |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/operations.py`                        |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `tools/application/production.py`                        |     6 | Release/install/update state-machine services                                                                     | planned                                                       |
| `tools/application/quality.py`                           |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/repository_boundary.py`               |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; compatibility adapters tracked below     |
| `tools/application/runtime.py`                           |     3 | Shared context/process/state APIs                                                                                 | staged; adapter bootstrap point 4, final retirement point 9   |
| `tools/application/security.py`                          |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/application/test_resources.py`                    |     4 | Application guard/development/transport APIs                                                                      | planned                                                       |
| `tools/application/testing/hooks.py`                     |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/maintainer/checks.py`                             |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/maintainer/context.py`                            |     3 | Shared context/process/state APIs                                                                                 | staged; adapter bootstrap point 4, final retirement point 9   |
| `tools/maintainer/database`                              |     4 | Application guard/development/transport APIs                                                                      | planned                                                       |
| `tools/maintainer/docs_checks.py`                        |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/maintainer/entrypoint`                            |     4 | Application guard/development/transport APIs                                                                      | planned                                                       |
| `tools/maintainer/localization/snapshot.py`              |     8 | Private test/support and native-runner orchestration                                                              | planned                                                       |
| `tools/maintainer/make-migration`                        |     4 | Application guard/development/transport APIs                                                                      | planned                                                       |
| `tools/maintainer/owner_tests.py`                        |     8 | Private test/support and native-runner orchestration                                                              | planned                                                       |
| `tools/maintainer/release_checks.py`                     |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | planned                                                       |
| `tools/maintainer/service_fixtures.py`                   |     8 | Private test/support and native-runner orchestration                                                              | planned                                                       |
| `tools/maintainer/testing/bootstrap.py`                  |     8 | Private test/support and native-runner orchestration; preserve 27 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/customer-deployment-host.py`   |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/customer-deployment.py`        |     8 | Private test/support and native-runner orchestration; preserve 9 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/customer-initialization.py`    |     8 | Private test/support and native-runner orchestration; preserve 5 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/customer-knowledge.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/database-guards.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/dependencies.py`               |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/development-startup.py`        |     8 | Private test/support and native-runner orchestration; preserve 20 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/docker-test-inventory.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/docs.py`                       |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/documentation-live.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/documentation.py`              |     8 | Private test/support and native-runner orchestration; preserve 12 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/documentation_fixture.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/error-fallback-host.py`        |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/error-fastcgi-fixture.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/fixtures/installation-smtp.py` |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/host-executor-host.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/host-executor.py`              |     8 | Private test/support and native-runner orchestration; preserve 8 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/installation-host.py`          |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/installation.py`               |     8 | Private test/support and native-runner orchestration; preserve 17 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/knowledge-live.py`             |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/knowledge-retrieval.py`        |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/knowledge.py`                  |     8 | Private test/support and native-runner orchestration; preserve 14 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/maintenance.py`                |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/management-host.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/management.py`                 |     8 | Private test/support and native-runner orchestration; preserve 22 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/module-development.py`         |     8 | Private test/support and native-runner orchestration; preserve 2 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/operations.py`                 |     8 | Private test/support and native-runner orchestration; preserve 21 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/postgres-alpine-contracts.py`  |     8 | Private test/support and native-runner orchestration; preserve 4 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/postgres-alpine.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/postgres-switch-contracts.py`  |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/production.py`                 |     8 | Private test/support and native-runner orchestration; preserve 9 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/quality.py`                    |     8 | Private test/support and native-runner orchestration; preserve 19 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/release.py`                    |     8 | Private test/support and native-runner orchestration; preserve 12 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/repository-boundary.py`        |     8 | Private test/support and native-runner orchestration; preserve 7 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/security.py`                   |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/services.py`                   |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/switch-postgres-icu.py`        |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/team-update-host.py`           |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/tool-ownership.py`             |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | planned                                                       |
| `tools/maintainer/testing/update-execution.py`           |     8 | Private test/support and native-runner orchestration; preserve 17 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/update-host.py`                |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | planned                                                       |
| `tools/maintainer/testing/update.py`                     |     8 | Private test/support and native-runner orchestration; preserve 30 directly defined test methods                   | planned                                                       |
| `tools/maintainer/testing/wrappers.py`                   |     8 | Private test/support and native-runner orchestration; preserve 4 directly defined test methods                    | planned                                                       |

The inventory separately records every alias/resource/caller and its planned disposition. Aliases remain transports; their implementation family owns behavior and point 9 reconciles every remaining caller. Recorded direct test references require manual review and do not attest coverage or PASS.

## Point 2 additions and transitional consumers

The original 104-file inventory remains the immutable migration baseline. Point 2
adds inert initializers across declared packages, root/owner/external setuptools
metadata, the frozen toolchain adapter and packaging service, stable `test-python`
and `python-tool` transports, pytest configuration, private support factories and
new adapter/projection/wheel contracts. These are new infrastructure rather than
retirements of existing rows; all original test families retain their current gates.

`python_cli` initially consumed application `runtime.compose/inside` and
`repository_boundary.assert_development_context` through qualified imports. Point 3
replaces its lifecycle/process/Compose mechanics and adapts the recording fake to
core process contracts; point 4 replaces environment/receipt composition with the
migrated public APIs. No new sys.path/runpy compatibility harness was introduced. Package root
imports are inert; legacy service imports and full wheel transport acceptance are
completed by points 4–9 and executed at final verification. The unchanged standalone
acquisition launcher retains its single-file behavior until point 6.

## Point 3 shared infrastructure and first consumers

Added canonical context, process, state, lifecycle, clock and reporting modules
under `tools/core`; application development Compose policy lives in
`tools/application/development_runtime.py`. The private `PythonToolExecutor` and
`python_cli` now use these contracts for suite execution and host delegation.
Removed their duplicate subprocess-group cleanup, flock and signal machinery.
Recording support uses the same `ProcessSpec`/`ProcessResult` interface as services;
the original literal-input/failure test assertions remain covered.

The baseline `runtime.py` and maintainer `context.py` rows are infrastructure
consumers with **staged migration**: their existing script bootstrap cannot import
qualified packages from unrelated cwd until the point 4 bin/owner adapter migration.
They remain callable for their existing consumers; point 4 removes the bootstrap
and switches development consumers to the new API, points 5–7 switch their remaining
quality/production/knowledge families, and point 9 retires the compatibility module
after its last consumer. No new consumer uses bare `runtime` or `context` imports.
`documentation.py` already accepts an explicit root, has no process/context globals
and stays the application-owned receipt-aware ownership policy; it does not belong
in core. Its qualified-import/typing completion follows point 4 ownership guards.

New core tests are private unified unit tests, not replacements for the legacy
domain suites. Their refusal/failure and lifecycle scenarios are available to the
final gate; only specifically recorded focused diagnostics run in this point.

## Point 4 transport and policy slice

- `development.py`, `development_config.py`, `development_setup.py`, `management_service.py`, `host_plan.py`,
  `host_transport.py`, `repository_boundary_service.py` and
  `customer_source_service.py` own the migrated domain implementation.
  `processes.py` translates explicit application stream/timeout policies into
  core process specs. Root package metadata already includes these regular modules.
- `artisan_cli.py`, `command_cli.py`, `passthrough_cli.py`, `boost_cli.py`,
  `hook_cli.py` and guarded `git_hook.py` are fixed adapter modules. Bin aliases
  and hook aliases retain their original public purposes. Hook target repository
  is an explicit argument independent of tool cwd. Compose/devcontainer/PHP dev
  callers use the fixed module; no new public customer command is introduced.
- Existing `management`, `host_executor`, `repository_boundary` and
  `customer_source` function APIs remain delegation-only compatibility surfaces.
  Remaining quality/deployment/knowledge/external/test callers migrate in points
  5–8; remove unused compatibility exports in point 9. The direct dev/host source
  launchers remain fixed module transports for old callers, not parallel engines.
- Application root imports were qualified where these services cross families.
  Old executor source paths bootstrap their fixed modules before those imports.
  `owner_cli.py`, `database_cli.py` and `migration_cli.py` temporarily retain the prior private dispatch and fixed database purposes behind an alias
  bootstrap; point 5 replaces its quality patching and point 9 removes remaining
  global argument manipulation. No owner policy moved into customer services.
- Point 4 pytest contracts replace global ROOT/subprocess patch assumptions for
  the migrated slice: request/role refusal, immutable literal arguments,
  catalog/schema/allowlist agreement, two workspace execution, pending production
  fence, private state, bounded discovery, explicit container routing, preview,
  child-failure receipt cleanup, stable setup lock, named-container cleanup, inert imports and disconnected single execution/replay refusal.
  Existing legacy management/startup/wrapper/source unittest files retain their
  historical assertions for reconciliation in point 8; their old module-patching
  scaffolds are not passing-test claims for the new services.
- Knowledge parser/configuration source identities are unchanged. The knowledge
  executor receives only a fixed module bootstrap; sibling service migration and
  parser identity compatibility remain point 7. Production/global deployment
  state and quality policy migration remain points 5–6.

## Point 5 quality, development tools and diagnostics

- `configuration_reader`, `quality_models`, `quality_planning`, `quality_service`,
  `quality_execution`, `quality_reporting` and `quality_evidence` now own distinct
  parsing/validation, planning, scheduling, latest-run and closure responsibilities.
  `quality_cli` is the shared import-safe composition root; owner paths and closure
  metadata policy are passed explicitly. Removed owner monkey-patching/runpy and
  the maintainer context path injection. Compatibility `quality` exports delegate.
- `Diagnostics`, `SecurityService`, `SecretScanner` and staged hooks compose core
  process/state policies. Both dependency audits and all image scans retain strict
  aggregated failure. Security executors no longer assign an audit-root global;
  isolated production-image discovery uses qualified imports across its actual
  version/process boundary. Deployment operation engines remain point 6.
- Shared Python execution/toolchain/configuration and unchanged frozen locks moved
  to `tools/application`; public Artisan Python commands and setup/quality/hook
  callers use them. The private selector adds its suite paths and root analysis
  scope; it consumes shared pytest configuration with explicit root/cache locations.
  Package projection includes the shared resources, never private test sources.
- Owner hooks inject immutable-history protection. Retired RM-010 first-closure
  bypass and completed metadata-backfill transition code/tests were removed after
  reviewing their first-closure/completed-migration conditions; historical records
  and exact resulting hashes remain immutable. Ordinary closure evidence, strict
  history refusal and candidate/command binding retain regression scenarios.
- Migrated quality/security suites use qualified pytest contracts. Old direct test
  paths remain fixed supported-transport launchers. Legacy documentation tests no
  longer import the retired transition exception; broader legacy-suite discovery
  and aggregate backend/frontend/tooling routing remain points 8–9.
