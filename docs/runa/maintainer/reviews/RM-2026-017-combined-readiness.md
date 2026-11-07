# RM-2026-017 combined runtime readiness

Point 10i completed, 2026-11-07. Development runtime/assets and bounded interactions
are observed; the base test database's inconsistent provenance is repaired through
a guarded rebuild. Owner acceptance 11 and final automated verification/release 12
remain open.

## Source, runtime and data preparation

Runtime code/assets: c2cf8bd889c3bc1e05a8b06adcf315a6c218fdef, followed by the
test-only independent-session correction ce02c0b31ef72d3968aedf742e95145c0fda6a85.
Branch remains roadmap/RM-2026-017-platform-quality-and-workflows. Platform VERSION
and protected manifest remain 0.7.0, projected feature release 0.8.0. Current Core
ranges support this runtime; no early candidate version preparation was required.

The initial real Nginx HTTP 503 was derived Core maintenance, not failed Nginx or
PHP-FPM. Stored 1.x framework constraints/declaration signatures disagreed with
current Framework API 2.0.0 and settings/lifecycle source. Read-only ModuleUpdates
plans admitted ordinary forward updates, including reverse dependency checks and
immutable applied-migration hashes. No guard, constraint or installed version was
manually bypassed.

Development database identity and role were both runa. Queued jobs, active managed
work/transitions and update reservations were absent. The local readiness operation
held the existing production-operation and test-suite locks, entered its own manual
maintenance, used the supported narrow src/runa/database/prepare.php primitive,
then explicitly invoked the documented app-owner ModuleUpdates apply contract.
Laravel maintenance prevented new ordinary queue work; operational schedules retain
native maintenance admission and no running work required draining. Native module
publication retained its advisory lock, reservation, transactional steps and Audit.
This was an explicit local operation, not a new command or startup behavior.

| Module           | Development before | Installed/source after |
| ---------------- | ------------------ | ---------------------- |
| runa.audit       | 1.1.0              | 1.1.1                  |
| runa.auth        | 5.0.1              | 6.0.0                  |
| runa.permissions | 2.1.0              | 2.1.1                  |
| runa.admin       | 1.0.1              | 1.1.0                  |
| app.reference    | 0.3.0              | 0.4.0                  |

Platform migrations had no pending work. Reference's forward migration added the
positive bigint revision to retained samples. Existing source-only upgrade edges
completed normally; all module enabled flags were retained. Before/after counts
remain 15 users and one reference sample. A hash of encrypted settings/revisions
is identical before/after. No password, grant, credential or settings value was
replaced in development; no development reset/reseed, source rollback or
production operation occurred. The separate test-base rebuild is recorded below.
The owned manual maintenance was removed; development derived reasons are empty.

Local safe operational evidence: .runtime/dev/RM-2026-017-10i/runa.jsonl and
runa_test.jsonl. Generated one-off scripts remain there, outside delivered source.
They are point evidence, not supported new operational interfaces.

## Assets and observed application

Readiness build initially failed on two unsupported destroy intents and generic
Table test-prop inference. Point 10h1 corrects these existing-contract violations.
Repeated pnpm --dir src run build passed TypeScript, production registry and native
error rendering; the separate development-mode Vite build includes the opted-in
reference. php src/runa/build/errors/build.php finalized standalone emergency
assets. Chunk-size and ineffective-dynamic-import warnings remain unsuppressed;
this preparation is not final frontend/image/delivery verification.

Current SPA entry: /build/assets/index-3bYqUnwF.js, SHA-256
33909b285da0bcb1e2938452e858ce3417eda8ebb7e8650940251121b0537db7.
Build index SHA-256: 0567017a2ba4ea7e1599d3fbc77fe06aa0140d42098b2f54903d20f32b1e0349.
The complete safe identity record is
.runtime/artifacts/playwright/RM-2026-017-point10i/build-identity.json.

Actual local origin is [localhost:8080](http://localhost:8080); bounded Chromium
observations traverse the real sibling Nginx at http://nginx:8080. Existing app,
Nginx, PostgreSQL, queue, scheduler and Mailpit services were healthy; setup exited 0. No service recreation, new endpoint or image build was needed. Existing optional
Qdrant and embedding services/caches were preserved.

The final combined-readiness observation reached account, native Admin dashboard
and user directory, reference inspect/explicit refresh, samples, settings/snapshots,
history and work. Their dependent reads returned successful responses and actual
content; no page errors, visible toasts or horizontal page overflow in the 1440px
sample. Initial guest session 401 and Admin password-confirmation 403 are expected
security transitions, resolved by normal login/full-page proof. The first combined
attempt additionally recorded profile availability_changed/503; the clean repeat
without concurrent test preparation reached all views without that refusal. Both
attempts remain local evidence; the initial failure is not a PASS.

Evidence: .runtime/artifacts/playwright/RM-2026-017-point10i/combined-readiness.json,
combined-readiness-attempt1.json, initial-readiness.json and route screenshots.
The earlier point-10 readiness JSON is preserved separately from these results;
the initially reused screenshot filenames were regenerated by the first 10i
observation and do not establish their older source identity.

## Bounded current interactions

The real account's trusted-device section starts collapsed, opens with Enter and
collapses again; its existing row remains mounted. A real background-tab return
caused zero business GETs while retaining a reference settings draft. Explicit
header refresh retained that draft. Native settings review succeeded; requesting
publication opened the shared full-page password host, and Cancel returned to
the unchanged draft with zero publication requests. The original label remained
committed. No credential, snapshot, work or sample write was sent by these browser
observations.

Settings were sampled at 390px in PL and EN, returning language to PL afterward.
This is a bounded readiness sample, not the full themes/viewports/state matrix.
The keyboard/control and screenshot diagnostic's first attempts had ambiguous
locators, raced initial proof rendering or captured responsive/preference motion;
those attempts remain interactions-attempt*.json. Corrected semantic locators,
normal proof and settled-frame captures establish the scoped interactions, not
owner acceptance. Final observations are interactions.json under the same local
evidence directory; full manual scenarios (including empty-device fixtures,
secret changes/snapshots and two-browser rendered conflicts) remain 11/12.

## Qualitative and instruction review

Rechecked reference workspace/controller/wire projections and native SettingsAccess
against the approved generation/admission design and Core capability matrix.
Independent read/write/credential grants, original semantic review identities,
full-page operation proofs and current authorization stay with native owners.
Credential controls expose set state only; current development has no secret set.
Stored-secret redaction under mutation remains a source/test assertion until the
final selected backend/browser coverage; empty runtime state cannot certify it.

The sample workspace uses current public controls, local conflict resolution and
native revisions; no passive freshness loop or write replay was introduced. Settings
snapshot load/publication/secret handling and work admission remain separate
operations. Public replacement/disposal stays within existing Admin/Auth/UI facades.
The source/UI contract corrections in 10h1 and native diagnostic correction in
10h2 are recorded as separate repair points, not hidden inside final verification.

Reviewed current reference README/Core matrix, public components action semantics,
forms/loading, native settings publication, Auth session/account/preferences,
roadmap/release readiness and both instruction products' existing selectors.
These operational preparations add no reusable capability or policy: no changes
to owner/customer AGENTS/skills are needed. Do not copy the private incident or
readiness state into customer instructions. The full current-state documentation
and quality review remains point 12.

## Test provenance correction and bounded native evidence

The initial base test database's app.reference table had four rows and a revision
column, but no module-update ledger despite installed version 0.4.0. The ordinary
update correctly refused duplicate creation and retained its reservation. This
native failure and the initial operation journal remain evidence; no migration
success or installed version was fabricated to suppress it.

The agent-authored no-reset clause incorrectly contradicted the standing pre-1.0
owner authorization. The owner identified that error; the active roadmap corrects
it as an existing-authority correction, not a request for new reset approval.
Both owner and customer instruction products already state their applicable scope;
no new reusable exception or rule is needed.

Before rebuilding, TestDatabaseGuard verified APP_ENV/testing, host, runa_test
role and the actual database identity. Database owner was runa_test, no other
connections were active, and the app-test/nginx-test siblings were stopped. The
exact disclosed scope was all 47 application/platform/module tables in shared,
runa_auth, runa_audit, runa_permissions and app_reference within runa_test. Their
schema claims matched the retained module identities; no unrelated table, database
or volume was selected. The existing production-operation and test-suite locks
covered the sequence:

1. APP_ENV=testing php artisan db:wipe --force --no-interaction: application tables.
2. APP_ENV=testing php distribution/platform-database.php migrate:fresh --force --no-interaction:
   platform and claimed Runa-module tables, including installation/version ledgers.
3. APP_ENV=testing php src/runa/database/prepare.php b681794b0e7998649ebfadfa50ab4d3c78556022:
   required initialization and real Core module migrations/upgrades.
4. Native application migrate/db:seed and documented app-owner ModuleUpdates apply:
   real reference migrations and initializer, preserving its disabled test default.

Every phase exited 0. All installed/source versions match the current development
candidate; both reference migrations and every actual initialization/upgrade step
have native succeeded ledger records. The stale update reservation is absent.
No database/schema CASCADE, fake ledger, direct installed-version edit, new
migration, adapter or compatibility path was introduced. The base database object
and Docker volume were retained; its old rebuildable test rows were intentionally
removed. Safe inventory and operation evidence:
.runtime/dev/RM-2026-017-10i/base-reset-inventory.json and base-rebuild.log.

The previous disposable-worker two-user diagnostic passed 14 assertions after
10h2 separated independent browser sessions (native exit 0 / cleanup 0).
After rebuilding, the same supported command without parallel arguments,
php artisan runa:test:backend --filter='keeps two independent users snapshots',
passed on the repaired base: 1 test, 14 assertions, native exit 0 / cleanup 0,
no disposable workers removed. This resolves the concrete base admission question;
it is not a complete backend suite, SIGSEGV acceptance or final PASS.

Development was checked after the rebuild: database runa, 15 users, one sample,
unchanged encrypted-settings/revision hash
89a1b1151e3d1200d7bbc7f7240c60d396e417dbe310d173ba12d101f7a333f5,
no maintenance reasons, actual reference settings page HTTP 200. Its data,
credentials, flags and installed versions remain intact.

## Access, manual handoff and resources

Use the existing administrator scenario through normal login; safe account discovery
is php artisan runa:seed:accounts. Its existing role is
runa.permissions.super_admin. No new role, assignment or password was created.
Admin entry still requests native full-page password confirmation. The
[current acceptance sheet](RM-2026-017-current-acceptance.md) lists exact routes,
independent reference grants and intended mutation/proof scenarios. This working
candidate is ready for point 11. Only the owner's explicit tested result completes
that point; agent diagnostics do not establish owner acceptance.

The repaired base's rebuildable test rows and stale installation state were removed
within the disclosed scope. The earlier supported diagnostic removed only its own
runa_test_test_1 worker. No Docker containers, networks, tags or volumes were created
or deleted. Development data, all existing services, Qdrant/model/pnpm caches,
backups, reports and historical operation evidence are preserved. Point 10i is
complete; owner manual acceptance 11 and final automated verification/release 12
remain open.
