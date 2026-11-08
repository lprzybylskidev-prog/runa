# RM-2026-017 — Point 10p native test integration

## Source-owned design recorded before implementation — 2026-11-08

P06 concerns `ApplicationTestCommand`, its development provider registration and
`TestRun`; it does not authorize a new scheduler or crash recovery. Composer pins
Laravel 13.37.0, Pest 5.3.4 and pest-plugin-laravel 5.0.1; the lock resolves
PHPUnit 13.4.5, ParaTest 7.27.4 and Collision 8.9.5. No dependency change is needed.
The version-aware Boost search was consulted; installed source establishes the
exact CLI contract, including details absent from the returned documentation.

The current sequential branch composes Collision's internal Laravel command and
mutates global argv. The parallel branch launches Pest separately because
Collision parses ParaTest options before Pest can consume its central test root.
Collision's command also slices raw argv by assumed positions. This split is
integration complexity, not a demonstrated engine defect.

The owning replacement is one Runa Artisan command launching the required Pest
binary for both serial and parallel tests. Pest is an explicit pinned development
dependency, not an optional detected engine. PHPUnit test classes continue through
Pest's PHPUnit engine, as in the existing shared Python application/owner runner.
There is no new fallback engine, Collision command composition, inherited command
definition, global argv mutation or second parallel launcher. The provider binds
the command registration identity used by Collision's package provider directly
to the Runa command; the package's exception/printer integration remains installed.

Owner: Runa development testing. Audience: application developers and owner agents.
Entrypoints: `php artisan test [native arguments]`, application `runa:test*` and
private maintainer test commands. Implementation: `src/runa/Testing/Backend` and
existing shared application Python testing package. Delivery: development only;
no production capability or new public module/API signature.

| Input/behavior                                                        | Owning contract                                                                                                                                                                                        |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| CLI input                                                             | Symfony ArgvInput literal tokens after command name; refuse non-CLI input rather than read unrelated process globals.                                                                                  |
| Native engine                                                         | One Process argument list, PHP_BINARY + vendor/bin/pest, application configuration and central application test root. PHPUnit classes and Pest contributions both remain supported.                    |
| Filter/path/configuration, groups/suites, reports and native coverage | Forward literal native arguments without shell interpolation; Pest owns parsing/refusal. Native --coverage/--min, --compact and --profile remain native.                                               |
| Parallel                                                              | Forward --parallel/-p and --processes; Pest owns ParaTest and Laravel ParallelRunner. No Runa workers.                                                                                                 |
| Laravel lifecycle flags                                               | Explicit child environment for recreate/drop/without-databases/without-cache; preserve upstream recreation/drop handling. Clear stale parallel flags for serial/discovery.                             |
| Artisan flags                                                         | Consume --without-tty and --env (testing identity stays mandatory); translate ANSI selection to native colors and omit Artisan interaction/verbosity controls.                                         |
| Discovery                                                             | list-tests/list-tests-xml/list-suites/list-groups run serially without suite lock/DB cleanup, removing worker-only options. Artisan handles help/version without launching tests.                      |
| Execution                                                             | Existing TestRun config guard, lock, testing environment and cleanup before/after; no application/base reset.                                                                                          |
| Terminal/process                                                      | Available TTY unless explicitly disabled, raw output, inherited input, unbounded native run, native failures and 128+signal status, scoped forwarding of parent SIGINT/SIGTERM with restored handlers. |
| Evidence                                                              | No recovery/retry/skip logic. Existing manual owner SIGSEGV evidence and acceptance procedure remains unchanged.                                                                                       |

Tests will exercise the command against isolated subprocess fixtures to prove
literal forwarding, lifecycle cleanup/lock/config refusal, environment isolation,
discovery, exits and signals without starting a nested real suite. Bounded actual
application discovery and a tiny selected Pest/PHPUnit execution can resolve native
engine parity; complete serial/parallel suites, coverage generation, static analysis
and delivery acceptance remain final point 12.

## Implementation, source review and bounded evidence

The single command now owns its definition and reads `ArgvInput::getRawTokens`;
it no longer imports, creates or runs Collision's command. The provider directly
binds the package registration identity. One process construction/launch handles
serial/parallel modes, raw streams/stdin and available TTY; scoped parent signal
forwarding restores handlers and returns native signal statuses. PHPDBG retains
its required `-qrr` invocation. Existing `TestRun` and the shared Python runner are
unchanged. There are no dependency, module/API version, schema, ledger, runtime
installation or asset changes. Platform remains 0.7.0 / approved feature-0.8.0;
Framework API 2.1.0 and existing Core/reference candidates remain unchanged.

Literal options after `--` retain positional meaning. Explicit configuration or
no-configuration suppresses the default, so PHPUnit does not receive duplicate
configuration options. Discovery removes parallel worker controls and bypasses
lock/cleanup; execution preserves config/lock/cleanup refusal and nonzero cleanup.
The guarded testing environment applies equally to serial and parallel children;
Laravel parallel/cache flags never leak stale serial values.

Focused diagnostics received:

- `python3 tools/maintainer/test-backend --filter=NativeTestCommandTest`: **20 cases,
  82 assertions, exit 0 / cleanup 0**. Isolated engine/cleanup fixtures verify
  literal filter/path/report/coverage forwarding, native stderr/failure exits,
  unchanged global argv, both launch modes, configuration forms, all four
  discovery forms, cached-config/lock/non-CLI refusal, environment isolation,
  delimiter semantics, child SIGTERM (143), forwarded parent SIGINT (130), restored
  handlers and cleanup refusal/failure. Their task-created fixture directories are
  removed by test teardown.
- `php src/artisan test --without-tty --parallel --processes=2 --list-suites`:
  exit 0, actual application suite discovery (16 tests), without executing cases.
- `php src/artisan test --without-tty --configuration=../tests/backend/runa/Fixtures/native-command/phpunit.xml --compact --no-ansi`:
  **2 cases / 2 assertions, exit 0**, actual Pest contribution and PHPUnit class.
- Same fixture with `--parallel --processes=2 --no-ansi` instead of compact:
  **2 cases / 2 assertions, exit 0**, actual native two-process mode. Both native
  executions reported scoped worker cleanup `[]` before and after.
- Same native fixture with `--runa-unsupported-native-option`: expected native
  refusal, **exit 2**, both cleanup phases `[]`; no option is silently accepted.

Retained failed attempts: the first command-fixture run had 12 passing cases and
one invalid `--version` expectation (JSON decode error, exit 2 / cleanup 0).
Symfony handles that global option itself; the fixture expectation was removed.
The initial real serial parity run executed both cases but exited 1 because an
explicit configuration duplicated the injected default. The owning command now
omits its default for all supported explicit forms; the passing reruns above and
five permanent configuration cases cover that correction. Neither failed attempt
is represented as PASS.

The public testing/command guides replace obsolete split-transport claims. The
owner testing guide owns the private parity fixture commands; the central changelog
records the behavior. Owner Laravel reference guidance and the separate customer
Laravel reference route to the public contract. Root AGENTS and skill selectors
need no change: no cross-task invariant, activation rule or new capability-selection
trigger changed. Customer guidance gains no protected-platform editing authority.
The temporary manual owner SIGSEGV procedure is unchanged; no recovery automation
or corrected-runtime stability claim is introduced.

These are bounded integration diagnostics. Full serial/parallel backend coverage,
coverage-driver generation, interactive TTY acceptance, static analysis and delivery
acceptance remain point 12. Combined installed storage/assets/runtime reconciliation
remains 10r, explicit owner manual acceptance 11. No final PASS or release is claimed.
Development/base test data, caches, backups and verification reports are preserved.
No Docker containers, networks, images or volumes were created or removed.
