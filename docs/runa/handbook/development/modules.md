# Developing modules

Use the [module framework contracts](../architecture/module-framework.md) for runtime interfaces and ownership. Ordinary application code remains supported; modules are optional. Module source is trusted PHP/Vue in the existing Laravel application, not an untrusted plugin sandbox.

## Generate and review

Run in local/testing development, from the repository root:

```bash
./bin/artisan runa:make:module app.documents
./bin/artisan runa:make:module app.documents --write
./bin/artisan runa:make:module app.documents controller ReadDocuments
./bin/artisan runa:make:module app.documents model Document
./bin/artisan runa:make:module app.documents migration Document
./bin/artisan runa:make:module app.documents service DocumentStorage
./bin/artisan runa:make:module app.documents action CreateDocument
./bin/artisan runa:make:module app.documents api DocumentLookup
./bin/artisan runa:make:module app.documents component DocumentSummary
./bin/artisan runa:make:module app.documents test DocumentContractTest
```

The default previews complete candidate contents, repository-relative paths and current/candidate SHA-256 hashes as JSON Lines. Add `--write` to apply. Names are simple PascalCase identifiers; the module ID is explicit and owner-qualified. New skeletons are Optional, version `0.1.0`, initially unregistered. Their minimal lifecycle policy is stateless and accepts null/current installed versions. Replace that policy before introducing persistent data, asynchronous work or external effects. No business feature or Core/Auth/Permissions mock is generated.

A skeleton writes `module.json`, an owned lifecycle policy and a contract test into `src/<owner>/modules/<code>/`, with README and CHANGELOG in `docs/app/modules/<code>/` or `docs/runa/handbook/modules/<code>/`. Elements derive backend namespace, schema, storage and test identities. Components use `resources/js/`; they have an empty static slot wrapper and no page/slot registration or styling. Compose existing UI components when adding behavior. There is no animation or visual acceptance claim for an unrendered skeleton.

Controllers are invokable 501 stubs with a manifest-registered `HttpProvider`; the framework derives named `<owner>.<code>.<operation>` routes under `/api/<owner>/<code>/...` and centrally guards ingress. API generation supplies a typed public interface, guarded proxy, refusing implementation and registered `ApiProvider`. Implement business behavior and server authorization before use. Substantial writes need Form Requests/Actions/JsonResources. Models extend ModuleModel with an explicit owned schema/table. Migration stubs use ModuleSchema identity/UUIDv7 primitives, UTC timestamps/soft deletion and refuse destructive rollback without a module recovery plan. Generation never runs migrations, synchronization, activation or installed-version publication.

Existing files require an explicit repeated `--overwrite=repository/path=SHA256`, using the current hash from the reviewed preview. This includes `module.json` when a controller/API adds a declaration. Every existing target needs its own authorization; unknown paths and stale hashes fail before writes. There is no blanket force option. Skeleton roots can never be overwritten. The generator serializes its own writers, rejects collisions/symlinks and restores its generated files if complete-layout validation fails. As with all trusted source tooling, do not concurrently replace source directories using other processes.

Runa-owned generation additionally requires a receipt-free owner checkout with its real `.git` directory, maintainer tooling and owner contract. Copied owner files in a customer acquisition do not grant permission. Production refuses generation/check commands; no module-management HTTP write endpoint exists.

## Contract checks and tests

```bash
./bin/artisan runa:modules:check app
./bin/artisan runa:modules:sync app
./bin/artisan runa:modules:check app --runtime
./bin/artisan runa:test app/modules/documents/tests
python3 tools/application/module_versions.py --owner app --base main
```

Static mode does not need registry synchronization or execute module classes. It validates manifests/discovery/owned paths and required dependency/version graphs, then checks literal module imports, direct paths/schema references and common raw Route/Schedule/Queue/Event/Command entrypoints. Public foreign Contracts imports are allowed; runtime access still uses guarded public adapters. Other module source must not be imported. Optional dependencies remain independent. A source-invalid owner scan fails explicitly.

`Runa\Support\Modules\Development\ModuleContractSuite::source(moduleId)` is reusable in module tests. Runtime `runtime(ModuleRecord)` checks synchronized declaration ownership/interfaces/schema versions through normal resolvers, lifecycle plans, health definitions, typed APIs, HTTP endpoints, jobs/commands/events, settings defaults, frontend pages, data paths and upgrade descriptors. It loads trusted pure metadata only; it never invokes lifecycle preparation, health checks, business APIs/jobs, settings application or upgrade handlers. Runtime descriptor failure returns a bounded safe code. Slot/schedule/management/integration providers are interface-checked and their metadata read; full contextual validation still occurs in their normal runtime adapters and dedicated platform regression tests.

Generated app tests explicitly use the delivered Tests\TestCase; owner tests use the owner PlatformTestCase. Run owner module tests with the owner bootstrap/configuration and their explicit path. Module tests are not automatically added to ordinary app test discovery; wire the relevant module paths into your application's test command/configuration. The generated source test does not replace module-specific health/lifecycle/work/storage/API/settings/event/frontend/update behavior tests. Existing platform regressions cover framework guarantees; a new module needs its own observable success/failure cases and concurrency checks where applicable.

Literal checks are deliberately pragmatic. Aliases, dynamic class/path/SQL construction, raw fully-qualified facade calls, reflection and external effects can evade them. They cannot prove semantic isolation, SQL safety, complete entrypoint classification or backwards compatibility. Review changed module entrypoints and data/contract boundaries; use runtime fences/adapters, PostgreSQL constraints and module-specific tests together. Never describe these checks as a sandbox.

## Independent module versions

The agent assesses and updates each changed module's stable `X.Y.Z` manifest version and central `CHANGELOG.md`, independently of platform VERSION and the application manifest. PATCH covers compatible corrections, MINOR compatible new behavior/contracts, and MAJOR incompatible public API/event/settings/behavior/data expectations. Record the chosen impact and check required dependents and enabled optional integrations. Pre-1.0 does not make silent contract breaks acceptable; state compatibility decisions explicitly. The platform's user-controlled major release rule remains separate.

`module_versions.py` compares tracked and untracked module implementation against the specified existing Git ref (default `main`), requires a monotonically increased version and a `## X.Y.Z` or `## [X.Y.Z]` central changelog heading for changed implementation, and rejects downgrades. New modules require their initial entry. Test-only edits do not force a bump. Missing baseline/shallow history, malformed versions or unsafe changelog paths fail; provide an available deliberate baseline rather than silently skipping checks. It does not infer semantic breaks, select the bump, prove the entry's accuracy or authorize deletion of a retained module. Human/agent review owns those decisions and must review dependent constraints. Backend-impact local quality graphs include these scoped checks; no CI or universal pre-commit gate is introduced.

## Working reference and application integration

The optional first-development example is `app.reference`, version 0.1.0. Choose `--reference-module=include|exclude` in the external source installer, or answer its interactive question. Automation defaults to exclude. Source selection persists on reopen; protected-platform updates preserve customer example edits and never add declined code. Production sparse staging, Docker context and frontend registry exclude it; backend discovery refuses copied reference source. Its central guide is `docs/app/modules/reference/README.md` when selected. The clean variant retains this guide and all public module contracts without needing the example.

Production builds copy both owners' module source into the frontend build for component discovery and into PHP for runtime discovery. The Vite module plugin is a build input only. The tracked Runa module root contains the Core Audit, Auth and Permissions source; clean customer builds need no pre-existing local directories. Docker context exclusions remove development examples and tests before these copies.

Module anatomy follows the generated skeleton: strict manifest; owned `backend/Contracts` DTOs/interfaces and guarded implementations; explicit `database/migrations`; local frontend components and module tests; central owner docs. Registration, enabled flags, health, source version and successfully installed data version are separate. Inspect `ModuleUpdates::plan(owner)` and apply under the existing trusted application operation journal/maintenance boundary before enabling data-dependent modules. Application update execution is explicit. `ModuleTransitions` previews and atomically applies lifecycle changes; diagnose critical health before enabling. Health policies and work policies belong to the module, including retry/checkpoint/cancellation/compensation decisions.

An ordinary application consumer can resolve a module API without becoming a module itself:

```php
use App\Modules\Reference\Contracts\Ping;
use Runa\Support\Modules\Api\ModuleApis;

$result = app(ModuleApis::class)->resolve('app.reference', Ping::class, '1.0.0')->read();
```

Use only a foreign module's public Contracts through these adapters, exchange public UUIDv7 references and handle operation rejection explicitly. Required dependencies participate in activation/health; optional integrations do not auto-enable or disable either side. Use the shipped Core Audit contracts for manual history and isolated fixtures for dependency policies whose real modules remain undelivered. Never add phantom Core/Admin registrations to make an example boot. Use the [Permissions declaration and grant contract](../modules/permissions/README.md) for source-owned permissions/predefined roles; ordinary action grants do not replace record policies or security steps.

Use ModuleSchema/ModuleModel and ModuleStorage for owned data/files; migrations run only in explicit install/update. No cross-module SQL/models/relations/FKs/path traversal. Settings providers declare typed defaults/validation and immediate/reinitialize handlers; use revision/generation checks and private runtime reads for sensitive values. Facts use ModuleEvents after commit and independent versioned listeners; coordinated critical operations use guarded APIs. Jobs/commands/schedules use classified adapters and cooperative work checkpoints. Frontends opt into ModulePage/navigation/ModuleSlot, validate runtime/build identity before loading and use the shared HTTP client; custom shells remain supported. Logs carry bounded origin/correlation and never raw secrets/payloads.

Troubleshoot in this order: source/manifest synchronization, installed version, administrative state, own/effective health and independent maintenance reasons, active work/fences, then declaration/build compatibility. Inspect `runa:modules:management`, `runa:modules:health` and redacted settings. Source checks cannot prove semantic isolation or replace behavior tests. Test module-specific failure/retry cases and retained-data disable/unregister, and review dependent public contracts whenever the independent module version changes.

## Replaceable module views

Every present/future Runa module view follows [frontend adoption and
replacement](frontend/customization.md): ship working default views under src/runa
and documented public props/slots/controllers for unchanged use, application
composition and complete customer presentation replacement. Reusable orchestration
and security remain platform-owned; do not require customer private imports,
protected edits or copied session/restriction flows. A module-specific facade and
typed local seams are sufficient; no universal renderer/registry is required.

Keep the supplied src/app minimal: essential wiring/extensions and the selected
reference module. Additional platform examples belong in owner fixtures; customer
business screens belong in their application only when requested. Synchronize the
public contract, independent module version/changelog, owner/customer skills and
meaningful default/custom consumer/update/clone regressions in the owning point.
When a view participates in native/emergency errors, preserve the shared static
CSP-constrained presentation build as well as normal browser composition.
