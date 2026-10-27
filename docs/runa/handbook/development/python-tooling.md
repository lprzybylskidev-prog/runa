# Python tooling

Runa's operational Python source uses the standard library and CPython 3.11
language features. Native Linux with Docker Engine/Compose is the supported host
environment. The maintained runtime contexts are container CPython 3.11 and host
CPython 3.12; `requires-python >=3.11` is a language floor, not a claim that every
future interpreter has passed acceptance. Host operation needs no pip, virtual
environment or package installation. PHP, Node and development tooling stay in
the project containers.

The repository-local `tools` package separates reusable infrastructure in
`tools/core` from delivered application tooling in `tools/application`. Core may
import only the standard library and core; application may additionally import
application modules. Neither may require owner tools or external installer code.
Package initializers perform no operations. Internal integrations use qualified
imports and explicit workspace/process arguments. The public command registry remains
Laravel Artisan; Python package names are not a customer extension API.

Root `pyproject.toml` defines the core/application distribution and shared analysis
rules. Private owner analysis additions live outside customer delivery. Delivered configuration and frozen development locks live under
`tools/application/python`; both ownership contexts use the same reviewed versions.
It declares no runtime or developer dependencies. Package versions follow root
`VERSION`; Python packages do not receive independent releases. Private wheel
preparation is an engineering check, not an operational installation requirement.
The production images contain none of these Python tools or development packages.

## Shared infrastructure

`tools.core.context` owns immutable `WorkspaceContext`, `ExecutionContext`,
`ReleaseContext` and `ComposeContext`. Tool source, requested workspace and release
source are explicit identities; constructing them does not detect an installation
role, authorize an operation, follow symlinks or change the working directory.
Application services own receipt, development/production and environment policy.
`tools.application.development_runtime` supplies the development Compose file/env
selection and app transport; production selection remains a separate policy.

`tools.core.process` exposes `ProcessSpec`, `ProcessResult`, `ProcessRunner` and
`SubprocessRunner`. Arguments are literal tuples; cwd is absolute and environment
is an explicit copied mapping. Stdin is explicitly inherited, closed or bytes;
output is inherited or captured as bytes. Timeouts are opt-in finite positive
durations; no timeout means the caller deliberately allows a long operation.
Results retain native return codes. Byte stdin and captured output use concurrent
selector-driven exchange, preserving input across slow readers/backpressure and
closing stdin explicitly at EOF. `checked()` raises a safe failure without
including argv, environment or captured output. Inherited streams preserve the
caller's terminal; no shell or pseudo-terminal is added.

The runner owns a new process group. Timeout, cooperative cancellation and raised
exceptions terminate and reap the direct child, kill remaining group members and
close pipes before returning control to an enclosing resource/lock scope. Children
that intentionally detach from that group require a domain ownership journal;
this primitive does not replace Docker endpoint/worker recovery. Cleanup masks
repeated interrupt signals until reaping finishes. `interrupt_signals()` temporarily
maps SIGTERM to interruption in the command's main thread and restores the prior
handler; services do not install handlers at import. `Cancellation` and `Clock`
provide explicit cancellation/time seams without a global registry.

`tools.core.state` supplies bounded JSON and stable-inode advisory locks. Directory
traversal uses no-follow directory descriptors and an explicit trusted anchor
(normally the selected workspace). Below that anchor, ancestors require safe
ownership/modes; leaf directories are operator-owned. This accepts a group-writable
workspace bind mount without permitting writable shared state beneath it. No
component, including the anchor, may be traversed through a symlink. Private state files must be regular, mode 600
or stricter, operator-owned and singly linked. JSON rejects duplicate keys and
non-finite values; services validate their own journal schemas after decoding.
Writes use a unique private temporary, atomic replacement and optional file/directory
fsync. Failed publication preserves the old file and removes only its temporary;
a durability error after replacement is an uncertain commit for the domain to
reconcile, never permission to replay a data operation. State is never cleared
automatically. Callers serialize read/modify/write operations with their domain lock.

Locks are never truncated, replaced or unlinked. The default is a private file;
an explicit non-private lock policy permits existing mode 644 coordination files
while still rejecting foreign ownership, hardlinks, symlinks and writes by other
users. This preserves shared native-suite lock identities during migration.
`tools.core.reporting` offers native command results, fixed safe error boundaries
and caller-supplied exact secret masking. Report only authored safe diagnostics;
masking is not permission to print arbitrary subprocess or exception payloads.

Application bootstrap now starts fixed package modules from the resolved tool
checkout. Bin aliases retain their invoked basename before resolving symlinks;
Boost replaces its process and preserves MCP stdio. Hook adapters carry a separate
requested repository through PHP and Python instead of treating the tool cwd as
the target. Compose setup and devcontainer post-create use the same
`tools.application.development` module before Composer is available.

`DevelopmentService` receives workspace, process policies and execution location; it composes separate `DevelopmentConfig` profile/secret preparation and `DevelopmentSetup` dependency/bootstrap services.
`RepositoryPolicy` receives the operator environment and process function.
`CustomerSource` keeps credential-isolated Git and exact source/projection checks.
Source policies and workspace-bound diagnostics/security adapters consume the
typed `WorkspaceProcess` callable contract;
captured text and bytes remain distinct, and workspace/environment selection
stays at their composition root. Configuration and receipt readers validate decoded
JSON before returning recursive `JsonValue` mappings. Host-plan payload types retain
the distinction between omitted development arguments and an explicit empty list;
the request binding, bounds and source-owned operation allowlist remain enforced.
Dependency composition remains pure and never resolves packages or edits locks;
module checks accept their process dependency without importing module code.
Container detection happens at composition roots, recognizes Docker/Podman marker
files and never authorizes an installation role. Services can represent distinct
workspaces and host/container locations in the same interpreter.

`ManagementService` separates immutable request-bound `HostPlan` parsing, isolated
registry/image discovery and allowlisted execution. Source-owned PHP catalogs and
the independently enforced Python allowlist/schema have an explicit consistency
contract; plan data cannot register an executable. Discovery uses a shared 64 KiB
stdout/stderr bound and 30-second timeout through the core runner. `HostExecutor`
receives the selected workspace/process dependencies and a typed binary worker
factory. Its server receives credentials, registry identity and environment through
explicit construction. Transport frames validate JSON objects, duplicate/non-finite
values and consumed request/input/control fields before use; interactive input
retains its decoded-byte limit. Recovery validates a positive integer PID before
checking exact process ownership, while older records may omit registry identity.
Its bidirectional streaming protocol owns a distinct child lifecycle; disconnect
continues accepted work and persists completion, while request IDs prevent replay.
Host state uses the core durable private JSON writer, independently of deployment.
Socket binding uses a directory descriptor without changing the process cwd.
Failed registry discovery removes only its named container in the selected workspace;
recovery and operation receipts retain additional historical evidence fields.
Registry orchestration declares its command/plan/process contracts. Source catalog
readers validate command names, tool identities and literal prefix lists before
reverse lookup; the execution allowlist remains independent. Development health
shares the consumed Compose status decoder and requires an integer setup exit
code, accepting both array and line-oriented responses.

Command adapters compose the explicit development, management, diagnostics,
quality, security and deployment services. Internal PHP/Compose callers start fixed
package modules using `python3 -m` from the selected tool checkout. Bin, Git-hook
and standalone external launchers retain their documented file interfaces.
Do not reintroduce legacy process/Compose/state mechanics or import-path injection.
Development setup selects the import-safe `tools.application.scanner_installation`
module, which receives the workspace and process policy and retains the reviewed
scanner version/checksum. Its preparation is separate from scanner execution.

All generated private Python state belongs under the workspace-root
`.runtime/python`: `venv`, `cache`, `build`, `pycache`, `node` and `reports`.
Do not create source-local virtual environments, bytecode, wheel/egg metadata or
pytest caches. Development containers already set `PYTHONPYCACHEPREFIX`; preserve
that setting in new transports. Reports, caches and unrelated operation state are
preserved during cleanup. See [artifact placement](../architecture/project-principles.md#generated-tooling-and-build-state).

Application developers continue using the [supported commands](../tooling/commands.md)
and [testing workflows](../testing/strategy.md). Private owner pytest suites and package assembly are excluded from customer delivery.
The shared development toolchain, locks and pytest configuration are delivered.
Host startup/recovery transports remain stdlib-only; container development setup
prepares the frozen tools.

## Delivered development tools and composition

`./bin/artisan runa:python:setup` installs the reviewed, hash-locked pytest/Ruff/
Pyright tools in the app container under `.runtime/python`. Normal development
setup includes this preparation; a current reopen skips it. Host execution needs
only Python/Docker/Git. Help, recovery and production operations do not install or
import third-party Python packages. Production images exclude these tools.

Use `runa:python:format [--check]`, `runa:python:lint` and
`runa:python:typecheck` through Artisan (host `./bin/artisan`, container
`php artisan`). Default analysis covers delivered core/application tooling and
`src/app` and `tests/tools/app`. Application and private owner suites share the delivered test lifecycle with
explicit separate roots; default Runa test commands include tooling. The shared pytest configuration supplies importlib discovery, strict
markers and configuration; runners supply exact suite paths, workspace root,
locks, cache, temporary paths and reports. Configuration is not a test suite.

Applications may pass `--config=src/app/<file>` to Python format/lint/typecheck.
Ruff configuration can extend `../../tools/application/python/ruff.toml` from a
file directly under `src/app`; Pyright can extend the adjacent
`pyrightconfig.json` with the equivalent relative path. Include application-owned
module/tooling paths explicitly. Preserve strict typing/markers and workspace-root
`.runtime/python` caches/reports. Keep custom configuration in `src/app` and leave
protected tooling unchanged. The owner adds its private source/test and packaging
scope through excluded maintainer configuration and its private test selector.

## Quality, diagnostics and staged checks

`QualityPlanner` receives a configuration reader, roadmap directory and definition;
`QualityService` adds the latest-run directory and command prefix;
`QualityExecutor` receives workspace, location, environment and process launcher.
The owner composes these same services with private paths, without replacing
module globals or loading command files as implementation. Configuration parsing,
graph selection, execution, status and closure evidence have separate modules.
Definition/frontmatter decoders validate source-owned planning fields; typed graph
and scope contracts preserve definition order, conditional dependencies and command
hashes. `QualityPlanSource` permits an explicitly injected planner without mutating
the scheduler. Status rendering accepts legacy scope metadata and validates named
group selections. Run-state decoding validates native exit codes, positive child
identities, finite nonnegative durations and timezone-aware timestamps before
rendering or marking abandoned work interrupted. Unknown historical evidence and
minimal legacy step records remain preserved. The scheduler receives a typed
process launcher and command transport; closure receives a separate configuration
reader contract. Parsing a definition does not execute its commands.
The private release validator supplies allowed closure metadata; customer closure
uses application manifest/changelog defaults. Serialized scope, graph hashes,
commands, timing and candidate checks retain their existing contracts.

The concurrent scheduler owns its distinct multi-child lifecycle and uses core
process-group cleanup. It preserves native exits, four ready-node slots,
exclusive groups, dependency blocking, inherited latest-run locks and background
handoff. Child groups are reaped on errors and interruption before releasing the
run lock. New state uses atomic private JSON; safe operator-owned legacy mode-644
state remains readable without permitting symlinks, hardlinks or foreign writes.

`Diagnostics`, `SecurityService` and `SecretScanner` receive explicit workspace /
process dependencies. Scanner findings and operational failures remain blocking;
repository/image reports and both locked dependency audits are collected before
aggregate refusal. Gitleaks uses the same strict default/redaction policy for
whole-repository and staged snapshots. Review evidence still requires an actual
report bound to current source hashes; tools never attest on an agent's behalf.

Hooks format and lint staged Python bytes with pinned Ruff, including Python
shebang launchers. Missing tools fail with the setup command. Dirty/partial staged
files are never overwritten; lint, secret and knowledge checks precede publication.
Closed history deletion/renaming is refused as well as content changes.
Private immutable-history rules are injected by the owner hook composition and
are excluded from customer tooling. Retired one-time history/closure exceptions
have no active bypass code; immutable historical records remain preserved.

## Deployment and external version boundaries

`OperationContext` binds the operator workspace, execution location, process policy
and production journal store. `SourceAcquisition`, `ProductionRuntime`,
`DeploymentRuntime` and `DeploymentService` separate verified source, Compose/image
policy, health/data mechanics and orchestration. Compose uses an immutable release
identity and the operator's TLS overlay; it never replaces a module root. Safe
operator-owned legacy coordination directories/lock modes remain accepted; newly
written journals are private, bounded and durable through core state primitives.
Deployment source, runtime and orchestration services carry explicit input/result
types. Their process adapters preserve text/bytes results, literal input, default
checked execution and explicit unchecked probes. Journal readers validate release
fields before use, retain additional historical evidence and treat a missing
rollback-safety flag as unproven. Compose/Docker readers validate consumed response
fields with fixed diagnostics; optional profiled services can omit an image until
that service is selected. These are internal contracts, not a new customer API.
Secrets remain literal raw values. Core owns dependency composition and fixed
delivery exclusions; standalone acquisition independently checks the same policy.

Standalone acquisition declares its release client, exact release identity, tree
entries, projection and receipt contracts without importing another package. Its
GitHub consumers check object/list shapes, string identities, positive non-boolean
IDs and boolean release flags before use; diagnostics do not echo response bodies.
The standalone file retains its own isolated Git/process and atomic publication
primitives because first acquisition has no adjacent package. Customer-repository
initialization composes `CustomerSource` with an explicit environment and the core
process runner; tree parsing and file verification stay shared with bootstrap.
Its CLI composes the same runner for closed-stdin installation and preserves that
installer's native exit status. Neither path imports customer application Python.

The external installation adapter selects protocol 1 and starts the acquired
application's fixed installation module as a separate process. The gateway checks
its own source/workspace identity before importing installation services; it is
internal tooling, excluded from runtime images. Historical first installs use their
matching historical tool release. Installer services declare settings, journal,
resource and process inputs/results. Private JSON reads remain bounded at 64 KiB
and reject duplicate/non-finite fields. Settings types are checked before semantic
TLS/SMTP validation or raw environment use; recovery validates consumed source,
project, volume and container-label fields while retaining additional evidence.
Absent container labels establish no workspace ownership. Resume still permits
only explicit SMTP corrections and preserves generated application/database secrets.
Upgrading an existing 0.5.0 development workspace
does not require the old installation gateway: the separately supplied updater
never imports customer application Python. It retains explicit source identities,
file review consent, journal phases, forward data completion and fencing/resume.
Its process and read-only runtime probes have typed injected boundaries, including
validated Compose/Docker and database metadata. Delivered-file inventories, exact
review decisions, action transitions and infrastructure comparisons also have
explicit typed records. Resume validates consumed file-action fields before using
retained staging, preserving additional journal evidence and legacy absent action
lists. The isolated module planner validates its returned records, including PHP's
empty associative maps, and shares one injected process policy with its cleanup.
These contracts remain internal to the separately supplied external bundle and
canonical core.
Execution declares its runtime factory, planner/consent inputs, report and recovery
records separately from read-only planning. Resume validates consumed source,
review, database baseline and module-completion fields before source/data changes;
unknown evidence and legacy absent optional records remain preserved. Database
mount identities use JSON lists throughout capture and durable storage, so the
same mounts remain comparable after restart. A different database still blocks
resume, and committed data completion is never silently replayed.
External preflight also declares its acquisition/runtime/lock/module probe contracts,
release identity projection, plan and report fields. Request-local probe overrides
leave the planner's composed dependencies intact. Blocked reports retain completed
checks and any partial plan; handled failures still clean external downloads without
writing to the customer installation. Same-release plans include pending module
work before reporting that no update is needed.
Customer Python configuration/tests/frozen inputs under `src/app` remain customer
owned; delivered defaults change only through the existing reviewed file plan.

## Development knowledge services

The delivered `tools.application.knowledge` package composes corpus/configuration,
provider/store, incremental indexing, retrieval and optional startup using explicit
workspace/process/clock and HTTP dependencies. Private corpus and golden additions
are composed by excluded owner tooling; customer loaders never import it. Typed
configuration, HTTP opener/response, provider, ranked-hit and evaluation contracts
preserve the existing collection schema and diagnostic fields. Provider metadata
requires literal integer limits and disabled truncation; embedding coordinates
must be finite numbers, with booleans refused. Transient GPU failures still validate
the CPU service before retrying the same request; incompatible model metadata is
refused without fallback. See
[knowledge lifecycle and parser freshness](knowledge.md#package-and-ownership-composition)
for migration, pending-write recovery and nondestructive incremental refresh.

`runa:test:python` discovers only application-owned Python roots; the default
`runa:test` includes it alongside PHP and frontend scopes. Public fakes/workspace
support lives in `tools.application.testing.support`, separately from private
suite roots. See [testing and extension configuration](../testing/strategy.md#python-tooling-and-public-test-support).
