# RM-2026-017 combined runtime readiness

Point 10i, 2026-11-07. Development preparation is observed; the point remains open
while the base test database's inconsistent installation provenance is unresolved.
Owner acceptance 11 and final automated verification/release 12 remain open.

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
replaced; no reset, reseed, source rollback or production operation occurred.
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

## Test provenance blocker and bounded native evidence

Base database and role are runa_test, owned by runa_test, with no other active
connections when inspected. Core forward updates completed to the same current
versions as development. Existing app.reference was marked installed 0.4.0 but
its samples table (four rows, including revision) had no module-update ledger at
all. Applying the real app update therefore correctly refused duplicate table
creation, transactionally rolling back that attempted migration. Its exact
app-owner reservation is retained for reconciliation, not cleared manually:
operation c2cf8bd889c3bc1e05a8b06adcf315a6c218fdef,
fingerprint a4eea3e546ca34a766a5f261966ce943a841483b85fbcb82d31eda472b55e801.
All existing test rows were preserved. The local operation removed only its own
manual maintenance artifact, shared by the CLI environment, so working development
was not left down; the test reservation still fences its affected module.

The canonical reset procedure requires coherent platform/module/application storage
and ledgers; application migrate:fresh alone cannot repair this state. Point 10i
explicitly says no reset, so the owner was asked whether to amend that constraint
for rebuilding only runa_test. No reset is authorized by silence. Preserve runa,
all Docker volumes, backups/caches and evidence. Do not invent migration success,
change installed versions by SQL, skip migration bodies or clear the reservation
as a substitute for reconciliation.

A fresh supported disposable worker allowed the requested two-user diagnostic to
execute without touching this inconsistent base. Its first real run failed with
401 because the fixture reused a browser session; point 10h2 separates clients.
The corrected supported application backend selection passed 14 assertions in one
parallel worker, native exit 0 and cleanup 0; runa_test_test_1 was removed by its
own runner. These scoped results establish actual backend stale/removed admission
for independent users, not complete backend, SIGSEGV acceptance or final PASS.

## Access, manual handoff and resources

Use the existing administrator scenario through normal login; safe account discovery
is php artisan runa:seed:accounts. Its existing role is
runa.permissions.super_admin. No new role, assignment or password was created.
Admin entry still requests native full-page password confirmation. The
[current acceptance sheet](RM-2026-017-current-acceptance.md) lists exact routes,
independent reference grants and intended mutation/proof scenarios. Owner acceptance
has not been requested as complete while 10i's test prerequisite remains unresolved.

Only the supported diagnostic's disposable worker database was removed. No Docker
containers, networks, tags or volumes were created or deleted. Development/base
test data, all existing services, Qdrant/model/pnpm caches, backups, reports and
recovery/operation state remain preserved. The base-test repair decision is the
remaining blocker for completion of 10i; 11/12 remain open.
