# Per-file migration ledger

Baseline source snapshot and semantics are explained in [README](README.md). Rows retain baseline source identities. Their dispositions identify current replacements; final typing and complete resource/caller reconciliation remain open in point 9. Target names identify responsibilities, not a required one-file/one-class transformation.

| Current source                                           | Point | Target responsibility                                                                                             | Disposition                                                                                                                                                                                         |
| -------------------------------------------------------- | ----: | ----------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `bin/artisan`                                            |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `bin/boost-mcp`                                          |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `bin/command`                                            |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `bin/passthrough`                                        |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/bootstrap.py`                              |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/initialize.py`                             |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/installation.py`                           |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update.py`                                 |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_contracts.py`                       |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_customization.py`                   |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_execution.py`                       |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_files.py`                           |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_modules.py`                         |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `distribution/update_runtime.py`                         |     6 | Release/install/update state-machine services                                                                     | migrated in point 6; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/bin/git-hook`                         |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/checks.py`                            |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/diagnostics.py`                                                                                                                                                         |
| `tools/application/customer_source.py`                   |     4 | Application guard/development/transport APIs                                                                      | retired; `tools/application/customer_source_service.py`                                                                                                                                             |
| `tools/application/dependencies.py`                      |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/docs_checks.py`                       |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/documentation.py`                     |     3 | Shared context/process/state APIs                                                                                 | migrated in point 3; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/executors/dev.py`                     |     4 | Application guard/development/transport APIs                                                                      | retired; `tools/application/development.py`                                                                                                                                                         |
| `tools/application/executors/doctor.py`                  |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/doctor_cli.py`                                                                                                                                                          |
| `tools/application/executors/knowledge.py`               |     7 | Development-only knowledge APIs                                                                                   | retired; `tools/application/knowledge/cli.py`                                                                                                                                                       |
| `tools/application/executors/operations.py`              |     6 | Release/install/update state-machine services                                                                     | retired; `tools/application/deployment_cli.py`                                                                                                                                                      |
| `tools/application/executors/production.py`              |     6 | Release/install/update state-machine services                                                                     | retired; `tools/application/production_cli.py`                                                                                                                                                      |
| `tools/application/executors/quality.py`                 |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/quality_cli.py`                                                                                                                                                         |
| `tools/application/executors/quality_check.py`           |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/source_checks.py`                                                                                                                                                       |
| `tools/application/executors/security.py`                |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/security_cli.py`                                                                                                                                                        |
| `tools/application/git_hook.py`                          |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/hooks.py`                             |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/host_executor.py`                     |     4 | Application guard/development/transport APIs                                                                      | retired; `tools/application/host_transport.py`                                                                                                                                                      |
| `tools/application/install-scanners.py`                  |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/scanner_installation.py`; development setup selects its fixed package module                                                                                            |
| `tools/application/knowledge/configuration.py`           |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/corpus.py`                  |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/ingest.py`                  |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/provider.py`                |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/retrieval.py`               |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/startup.py`                 |     7 | Development-only knowledge APIs                                                                                   | retired; `tools/application/knowledge/startup_cli.py`                                                                                                                                               |
| `tools/application/knowledge/store.py`                   |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/knowledge/transport.py`               |     7 | Development-only knowledge APIs                                                                                   | migrated in point 7; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/management.py`                        |     4 | Application guard/development/transport APIs                                                                      | retired; `tools/application/management_service.py`                                                                                                                                                  |
| `tools/application/module_versions.py`                   |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/operations.py`                        |     6 | Release/install/update state-machine services                                                                     | retired; `tools/application/deployment_service.py`                                                                                                                                                  |
| `tools/application/production.py`                        |     6 | Release/install/update state-machine services                                                                     | retired; `tools/application/production_runtime.py`                                                                                                                                                  |
| `tools/application/quality.py`                           |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/quality_service.py`                                                                                                                                                     |
| `tools/application/repository_boundary.py`               |     4 | Application guard/development/transport APIs                                                                      | retired; `tools/application/repository_boundary_service.py`                                                                                                                                         |
| `tools/application/runtime.py`                           |     3 | Shared context/process/state APIs                                                                                 | retired; `tools/application/development_runtime.py`                                                                                                                                                 |
| `tools/application/security.py`                          |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | retired; `tools/application/security_service.py`                                                                                                                                                    |
| `tools/application/test_resources.py`                    |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/application/testing/hooks.py`                     |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/checks.py`                             |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/context.py`                            |     3 | Shared context/process/state APIs                                                                                 | migrated in point 3; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/database`                              |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/docs_checks.py`                        |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/entrypoint`                            |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/localization/snapshot.py`              |     8 | Private test/support and native-runner orchestration                                                              | migrated in point 8; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/make-migration`                        |     4 | Application guard/development/transport APIs                                                                      | migrated in point 4; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/owner_tests.py`                        |     8 | Private test/support and native-runner orchestration                                                              | retired; `tools/maintainer/test_cli.py`                                                                                                                                                             |
| `tools/maintainer/release_checks.py`                     |     5 | Injected quality/diagnostic/scanner/hook services                                                                 | migrated in point 5; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/service_fixtures.py`                   |     8 | Private test/support and native-runner orchestration                                                              | migrated in point 8; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/testing/bootstrap.py`                  |     8 | Private test/support and native-runner orchestration; preserve 27 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_bootstrap.py`                                                                                                                                |
| `tools/maintainer/testing/customer-deployment-host.py`   |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/customer_deployment_host.py`                                                                                                                                     |
| `tools/maintainer/testing/customer-deployment.py`        |     8 | Private test/support and native-runner orchestration; preserve 9 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_customer_deployment.py`                                                                                                                      |
| `tools/maintainer/testing/customer-initialization.py`    |     8 | Private test/support and native-runner orchestration; preserve 5 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_customer_initialization.py`                                                                                                                  |
| `tools/maintainer/testing/customer-knowledge.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/customer_knowledge.py`                                                                                                                                           |
| `tools/maintainer/testing/database-guards.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/test-backend --parallel --processes=1 --filter=DatabaseTest`                                                                                                             |
| `tools/maintainer/testing/dependencies.py`               |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_dependencies.py`                                                                                                                             |
| `tools/maintainer/testing/development-startup.py`        |     8 | Private test/support and native-runner orchestration; preserve 20 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_development_startup.py`, `tools/maintainer/tests/python/contracts/test_knowledge_services.py`                                                |
| `tools/maintainer/testing/docker-test-inventory.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; unused one-off inventory, no runtime/suite consumer; scoped resource lifecycle remains in native acceptance                                                                                |
| `tools/maintainer/testing/docs.py`                       |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_docs.py`                                                                                                                                     |
| `tools/maintainer/testing/documentation-live.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/documentation_live.py`                                                                                                                                           |
| `tools/maintainer/testing/documentation.py`              |     8 | Private test/support and native-runner orchestration; preserve 12 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_documentation.py`                                                                                                                            |
| `tools/maintainer/testing/documentation_fixture.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | migrated in point 8; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/testing/error-fallback-host.py`        |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/error_fallback_host.py`                                                                                                                                          |
| `tools/maintainer/testing/error-fastcgi-fixture.py`      |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/error_fastcgi_fixture.py`                                                                                                                                        |
| `tools/maintainer/testing/fixtures/installation-smtp.py` |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | migrated in point 8; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/testing/host-executor-host.py`         |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/host_executor_host.py`                                                                                                                                           |
| `tools/maintainer/testing/host-executor.py`              |     8 | Private test/support and native-runner orchestration; preserve 8 directly defined test methods                    | retired; `tools/maintainer/tests/python/unit/test_application_transport.py`, `tools/maintainer/tests/python/contracts/test_host_recovery.py`                                                        |
| `tools/maintainer/testing/installation-host.py`          |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/installation_host.py`                                                                                                                                            |
| `tools/maintainer/testing/installation.py`               |     8 | Private test/support and native-runner orchestration; preserve 17 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_installation.py`                                                                                                                             |
| `tools/maintainer/testing/knowledge-live.py`             |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/knowledge_live.py`                                                                                                                                               |
| `tools/maintainer/testing/knowledge-retrieval.py`        |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | retired; `tools/maintainer/tests/python/integration/test_knowledge_retrieval.py`                                                                                                                    |
| `tools/maintainer/testing/knowledge.py`                  |     8 | Private test/support and native-runner orchestration; preserve 14 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_knowledge_ingestion.py`                                                                                                                      |
| `tools/maintainer/testing/maintenance.py`                |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_maintenance.py`                                                                                                                              |
| `tools/maintainer/testing/management-host.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/management_host.py`                                                                                                                                              |
| `tools/maintainer/testing/management.py`                 |     8 | Private test/support and native-runner orchestration; preserve 22 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_management.py`, `tools/maintainer/tests/python/unit/test_application_transport.py`, `tools/maintainer/tests/python/unit/test_test_runner.py` |
| `tools/maintainer/testing/module-development.py`         |     8 | Private test/support and native-runner orchestration; preserve 2 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_module_development.py`                                                                                                                       |
| `tools/maintainer/testing/operations.py`                 |     8 | Private test/support and native-runner orchestration; preserve 21 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_operations.py`, `tools/maintainer/tests/python/unit/test_core_process.py`                                                                    |
| `tools/maintainer/testing/postgres-alpine-contracts.py`  |     8 | Private test/support and native-runner orchestration; preserve 4 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_postgres_alpine_contracts.py`                                                                                                                |
| `tools/maintainer/testing/postgres-alpine.py`            |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/postgres_alpine.py`                                                                                                                                              |
| `tools/maintainer/testing/postgres-switch-contracts.py`  |     8 | Private test/support and native-runner orchestration; preserve 3 directly defined test methods                    | retired one-time transition in point 8; current collation contracts retained                                                                                                                        |
| `tools/maintainer/testing/production.py`                 |     8 | Private test/support and native-runner orchestration; preserve 9 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_production.py`, `tools/maintainer/tests/python/unit/test_application_transport.py`                                                           |
| `tools/maintainer/testing/quality.py`                    |     8 | Private test/support and native-runner orchestration; preserve 19 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_quality_compatibility.py`, `tools/maintainer/tests/python/contracts/test_quality_services.py`                                                |
| `tools/maintainer/testing/release.py`                    |     8 | Private test/support and native-runner orchestration; preserve 12 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_release.py`                                                                                                                                  |
| `tools/maintainer/testing/repository-boundary.py`        |     8 | Private test/support and native-runner orchestration; preserve 7 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_repository_boundary.py`                                                                                                                      |
| `tools/maintainer/testing/security.py`                   |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_security_services.py`                                                                                                                        |
| `tools/maintainer/testing/services.py`                   |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | migrated in point 8; final typing/caller reconciliation pending in point 9                                                                                                                          |
| `tools/maintainer/testing/switch-postgres-icu.py`        |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired one-time transition in point 8; current collation contracts retained                                                                                                                        |
| `tools/maintainer/testing/team-update-host.py`           |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/team_update_host.py`                                                                                                                                             |
| `tools/maintainer/testing/tool-ownership.py`             |     8 | Private test/support and native-runner orchestration; preserve 6 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_command_adapters.py`                                                                                                                         |
| `tools/maintainer/testing/update-execution.py`           |     8 | Private test/support and native-runner orchestration; preserve 17 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_update_execution.py`                                                                                                                         |
| `tools/maintainer/testing/update-host.py`                |     8 | Private test/support and native-runner orchestration; migrate scenario/fixture without inventing generic CLI help | retired; `tools/maintainer/testing/update_host.py`                                                                                                                                                  |
| `tools/maintainer/testing/update.py`                     |     8 | Private test/support and native-runner orchestration; preserve 30 directly defined test methods                   | retired; `tools/maintainer/tests/python/contracts/test_update.py`                                                                                                                                   |
| `tools/maintainer/testing/wrappers.py`                   |     8 | Private test/support and native-runner orchestration; preserve 4 directly defined test methods                    | retired; `tools/maintainer/tests/python/contracts/test_application_bootstrap.py`                                                                                                                    |

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

## Point 6 deployment and external versions

- Deployment separates operator/process/state context, source acquisition, production
  Compose/image policy, runtime/data/health mechanics and deployment orchestration.
  Compatibility function facades create services without ROOT/STATE mutation. The
  two executor files are inert fixed-module transports; existing legacy tests that
  patch globals are retained for point 8 reconciliation, not claimed as passing.
- External `update_planning`, `update_docker`, contracts/customization/files and
  `UpdateExecutor` depend downwards; execution never imports the CLI. Common process,
  durable state/lock, raw environment and manifest composition use canonical core.
  Standalone bootstrap retains its necessary self-contained Git/network transport
  and embedded exclusions with an explicit canonical-policy consistency contract.
- Installation mechanics live in application `InstallationService`; external
  launchers negotiate the fixed protocol-1 process gateway and do not import
  customer code. Gateway marker is a declared application package resource. A
  historical initial-install target uses its matching historical installer; an
  existing 0.5.0 development installation updates through version-independent
  external probes, source verification and PHP/database gateways.
- New contract cases cover interleaved releases, pre/post-data deployment failure,
  repeat/rollback, 0.5.0-to-0.6.0 committed-seed and source-rename recovery, preserved
  customer Python files, settings/TLS/SMTP and physical external import isolation.
  Actual production, package wheels and installation/scanner acceptance remain the
  final gate. Package source projection ships shared frozen development resources;
  customer-owned configuration/tests remain under src/app and private suites stay
  excluded. Broad legacy-test/support migration remains point 8.

## Point 7 knowledge slice

- Qualified `tools.application.knowledge` modules now own typed configuration and
  payload/effect contracts, workspace/process/clock context, provider/HTTP storage,
  index/rebuild/freshness, hybrid retrieval and optional startup. Configuration
  identity and limits are validated before network operations. Store/HTTP/provider
  factories and retry waiters are injected; production code does not patch globals.
- Excluded `tools.maintainer.knowledge_cli` composes private corpus/golden resources.
  The fixed executor selects the receipt-scoped composition module. Customer loaders
  read only delivered resources; explicit owner context cannot override a receipt
  in corpus discovery. Physical customer package imports need no maintainer tree.
- Existing model/collection/schema, document/chunk identities, golden cases and
  pending manifests retain their format. Parser hash still follows corpus source:
  migration reparses incrementally and reuses unchanged semantic embeddings.
  Source policy evaluates relative paths, including isolated fixtures under the
  root runtime tree; authoring-template/secret/symlink exclusions remain enforced.
- The 14-case offline knowledge suite now lives in unified contracts. Live CPU/CUDA
  and retrieval scenarios have ordinary importable private modules; historical
  filename transports remain for existing gate callers. Customer scenario imports
  are qualified. Point 8 owns aggregate/native test integration, and point 9 reviews
  remaining delegation-only ingest facades and fixed script transports after their
  last callers migrate. Real-service scenarios execute only in point 10.

## Point 8 shared test lifecycle and discovery

- Delivered `testing.runner` / `testing.cli` compose explicit application roots;
  excluded `maintainer.test_cli` supplies Runa roots. Aggregate defaults include
  backend/frontend/tooling; native engines remain Pest/Vitest/pytest/Playwright.
  One suite lock encloses the selected run, backend pre/post worker cleanup and
  native child reaping. Independent failures continue; interruption stops scheduling.
  Reports retain per-scope native/cleanup statuses, empty/blocked states and ownership.
- Public stdlib recording process, workspace/Git, clock/HTTP and Compose/image
  support is packaged independently from private cases. Optional source-owned
  `src/app/testing.json` registers Python roots; protected/private roots are refused.
  PHP guarded TestCase/ModuleContractSuite remain public; reusable browser problem
  observation moved out of private cases into `src/runa/Testing/browserProblems.ts`.
- Retained unittest cases now use qualified pytest discovery, explicit domain
  contexts and separate fixture objects. Updater execution no longer inherits the
  planning TestCase; `support.updater.UpdaterWorkspace` contains no tests. Old
  module-global/runpy fixture cases were consolidated into the injected-service,
  subprocess and native-acceptance contracts. The exact source/scenario retention
  map is `test-migration.json`; it is not a passing-test claim.
- Native acceptance has importable underscore modules and common explicit
  `AcceptanceContext` process/Git/Compose handling. Development app/container
  identity is resolved through Compose, with checkout-label ownership snapshots.
  Run-specific cleanup/journal paths and preserved persistent resources remain.
  SMTP/FastCGI/queue helpers import without starting listeners or sending messages;
  PHP localization/runtime fixtures resolve the actual repository root.
- Retired only the completed one-time workstation ICU volume-switch tool and its
  transition-specific contracts. Existing historical evidence stays unchanged.
  Pinned disposable PostgreSQL collation/resource probes and current production
  health/isolation acceptance remain available. No database reset was performed.
- Public PHP source-check adapters delegate tests to the shared lifecycle; focused
  quality nodes retain exact ownership. Owner database/migration transports refuse
  scope overrides before container delegation. Production command composition now
  follows `runtime.production`, repairing an outdated point-6 member reference.
  Remaining compatibility filename transports and final annotation/caller review
  belong to point 9; they delegate rather than implement a second engine.
- Owner/customer AGENTS and backend/Laravel guidance, public testing/Python/command
  docs and private quality docs reflect the accepted aggregate default. Vue/Tailwind
  and presentation skill products need no change: no rendered UI or component
  behavior changed. The browser observer contract is documented in the public test
  guide. Frozen dependency inputs and production build inputs are unchanged.

## Point 9 caller retirement (in progress)

- PHP management Actions, host plans, Compose and development recovery now start
  fixed package modules. The former `tools/application/executors` package and
  domain compatibility facades are removed. Callers construct explicit services;
  deployment policy and customer-source work reuse injected operation processes.
- Private filename test transports and support reexports are removed. Gates select
  explicit pytest scopes/nodes or ordinary native acceptance modules. Source and
  scenario baseline identities remain preserved in the inventory/test map.
- Knowledge startup remains stdlib-only and independent of PHP/vendor readiness.
  Its composition boundary chooses a fixed receipt-scoped initializer process;
  no delivered module imports private owner code. Live scenarios use explicit
  indexers; no sys.path/PYTHONPATH injection or ingest function facade remains.
- Private analysis configuration is excluded under `tools/maintainer/python`.
  Shared rules remain in the root/application configuration. External packaging
  additionally prepares canonical external/core source and standalone bootstrap
  artifacts, with no host install. Actual artifact builds/acceptance remain final.
- Bin/Git-hook/external acquisition entrypoints retain their documented transport
  purpose. Legacy daemon path recognition is retained only for stopping an exact
  previously owned daemon. Existing journal/receipt/evidence compatibility is
  data recovery behavior, not a second implementation.
- Remaining work: complete full-scope typed service/test contracts and reconcile
  every baseline alias/resource/non-Python/inline record against final sources.
  This table is source disposition evidence, not full acceptance or annotation PASS.

Deployment context/source/production/runtime/orchestration now declare their
workspace, release, callback, process-option and return contracts. The new
`deployment_models` boundary validates journal and consumed Compose/Docker fields
without altering serialized schemas or discarding extra evidence. Missing legacy
rollback proof remains refusal, and optional profiled services retain their
existing deferred image requirement. Private boundary cases cover malformed
records, exact evidence preservation, response formats and text/bytes/stdin/check
semantics. External update orchestration, quality, knowledge and remaining
private fixture annotations still require their own completion; this entry does
not attest full-scope strict-analysis or acceptance success.

Quality definitions, roadmap inputs, graphs, legacy/named selections and planner
outputs now have typed contracts and explicit decoders. Planner/evidence composition
uses the same source decoder. Private quality factories live in the support package
instead of importing another test module; `FixturePlanner` implements the injected
plan source without a dynamic namespace or service-global replacement. Command
order, dependency expansion, graph hashing, scope refusal and candidate binding
retain focused regression coverage. Scheduler/state/evidence annotations and the
remaining private cases are still open; no early full analysis/acceptance was run.

External installation/gateway adapters, process policy, source/compatibility helpers,
runtime/Compose probes and Docker ownership/data mechanics now declare inputs and
results. Shared dependency composition returns validated recursive JSON contracts.
`distribution/update_models.py` checks consumed external Compose/container/status
and database migration/seed/module fields; unknown response fields stay available.
The Docker transport propagates through every runtime/database probe, and one
injected process policy owns lock validation and worker cleanup. Focused cases use
the delivered recording runner and preserve closed stdin, credential/environment
isolation, old-release metadata and exact cleanup identities. External packaging
already projects every authored `.py` in the declared distribution package, so the
new model modules need no resource exception. Delivered-file inventory, review,
consent, staging and action-transition functions now declare typed boundaries through
`distribution/update_file_models.py`; journal loading validates consumed action
fields while preserving extra evidence and absent legacy lists. Infrastructure
comparison validates Compose service records without interpolation/environment
resolution. Isolated module results validate PHP's associative-map serialization;
one selected process policy owns execution and exact named-worker cleanup.
Bootstrap/initialization, update planning/execution and remaining private fixture
annotations stay open; there is no full strict-analysis or artifact acceptance claim.

Host transport now declares request, credential, operation-record, binary worker,
stream-reader and server/service contracts. `host_transport_models.py` validates
finite object frames, consumed input/control fields and positive recovery PIDs;
legacy credential identity omission and PID-only recovery records remain supported.
The server receives all dependencies in its constructor rather than attaching
service attributes or a status closure after binding. Status preserves additional
receipt fields, and worker execution still reparses its plan at the guarded service
boundary. Management discovery/plan/process entrypoints and private transport
fixtures now declare their inputs/results. Fixed failure cleanup's missing workspace
argument, with a scripted regression proving exact named-container removal without
Docker. Focused socket coverage preserves disconnected completion and replay
refusal. Package source projection already includes the new authored model module;
no resource allowlist exception or runtime dependency is added. Strict full-scope
analysis and broad/native acceptance remain final-point work.

Registry orchestration now declares source catalog, process, command/plan and browser
lifecycle contracts. Source command records validate consumed name/tool/prefix fields
and retain metadata; lookup accepts literal sequences without authorizing executable
families. Development health reuses the shared array/line Compose decoder and checks
native integer setup exit status. Installer/gateway services declare settings,
recovery, resource, path and process input/result contracts. Private settings/state
decoding rejects duplicate/non-finite objects with fixed diagnostics, and keeps the
64 KiB bound during reads. Consumed settings, journal, Compose volume and Docker label
fields validate before use; extra evidence and legacy optional journal fields stay
available. Null/missing labels establish no ownership. Focused recording-runner and
file cases preserve refusal before mutation, literal secrets and explicit SMTP-only
resume correction. Private service cases use explicit management composition instead
of a dynamic namespace. Existing source package projection includes the new authored
model modules; no resource/dependency exception is needed. Remaining private fixture
annotations, quality scheduling/evidence, external planning/execution and knowledge
completion remain open, followed by full caller/resource/inline reconciliation.

## Retired implementation-era resources

`tools/maintainer/testing/php-alpine-host.sh` was an unused PHP-only acceptance
helper for an existing historical simulation. Its inline Python and package-data
entry are removed. Current final production acceptance creates an owned customer
fixture and checks the complete runtime image set through the supported native
installation/security graph. Historical PHP/PostgreSQL evidence is preserved.
The unused Docker inventory script and private knowledge/database filename
adapters are also removed; the ledger above records their dispositions.

Quality scheduling, reporting and closure evidence now declare run/step records,
process-launch/command-transport and configuration-reader inputs/results. The
`quality_state` decoder retains minimal historical records and extra evidence,
validates consumed exit/PID/timing/selection fields and refuses malformed state
before abandoned-status publication. Scheduler command parsing uses the existing
validated immutable step while preserving the transport's literal list arguments.
Closure still binds the exact graph, scope, candidate and permitted metadata;
boolean exit codes cannot satisfy successful evidence. Focused private cases cover
these boundaries and fixed start-failure diagnostics; background/concurrency/full
quality acceptance and strict analysis remain point 10. Remaining full-scope
annotations and complete caller/resource/inline reconciliation are still open.

External preflight now declares acquisition/runtime/lock/module probe contracts,
consumed release identities and plan/report records in `distribution/update_plan_models.py`.
The planner keeps request-local overrides separate from its composed dependencies;
partial plan/check evidence remains present when later module planning refuses.
Acquisition receipt metadata beyond the three consumed identity fields stays outside
the report. Private updater workspace support declares its cleanup, Git, download,
runtime, snapshot and plan contracts and uses the composed planner. Focused cases
cover download cleanup, selected probes, same-release pending modules, override
isolation and partial blocked reports without live Docker/data operations. Existing
external source projection includes the authored model module automatically. Owner/
customer instruction products already document typed injected boundaries and guarded
recovery; these internal types introduce no public extension API or new authority.
Bootstrap/initialization, update execution, knowledge and remaining private case
annotations stay open, followed by full caller/resource/inline reconciliation.
