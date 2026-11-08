# RM-2026-017 post-refactor runtime readiness

Point 10r completed, 2026-11-08. This renews the manual handoff after 10k–10q.
Source/runtime/build observations identify `5903593ccde714088b3816fbe8493889a76067c9`
on `roadmap/RM-2026-017-platform-quality-and-workflows`; the point-10r commit
changes documentation only. Point 11 requires explicit owner acceptance; complete
selected automated verification and release remain point 12. The dated 10i review
and its original evidence remain historical and unchanged.

## Audit disposition and current contracts

| Finding | Completed owner | Current implementation, consumers and evidence                                                                                                                                                                                                                                                        |
| ------- | --------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P01     | 10l             | Reference's original CREATE owns the positive bigint revision/default/CHECK; the ALTER is removed. Current initializer, reference contracts/examples and focused tests use that schema. The identified live sample storage/provenance is rebuilt below.                                               |
| P02     | 10l             | Five direct current initializers and honest current-schema update paths replace historical built-in chains. Core patch steps execute on both actual databases below; retired success records remain evidence. Generic customer upgrade authoring/engine stays.                                        |
| P03     | 10m             | Current scoped guest/account display preferences replace flat imports/dual writes and legacyKey. Current consumers/browser fixtures and focused coverage are recorded in the completed point.                                                                                                         |
| P04     | 10m             | Built-in table v1 conversion is removed; current v2 named/default views and generic migration hooks retain their current roles. Obsolete concrete formats select defaults.                                                                                                                            |
| P05     | 10n             | Button exposes iconOnly; all four language/theme branches use it. Existing icon-only controls retain their contract. Current entry/error keyboard and square dimensions are observed below.                                                                                                           |
| P06     | 10p             | ApplicationTestCommand launches Pest for serial/parallel PHPUnit and Pest cases; global argv adaptation and Collision composition are removed. Native flag/discovery/refusal parity remains in its review; current bounded public backend execution passes below.                                     |
| P07     | 10q             | Retired bin/check and bin/operations are absent; six recovery links target bin/command. Live Artisan/cold/host transports remain; current fixture inventories and command guidance match.                                                                                                             |
| P08     | 10o             | Explicit bounded json fields carry native snapshot/Auth values and delivery writes. Backend/frontend validation, specialized controls, generic readonly previews and reference consumers use the current representation; text stays scalar. Current snapshot/password projections are observed below. |
| P09     | 10k             | Owner decision provenance and pre-1.0 guidance qualify history restrictions. Controller comments preserve real replacement/composition boundaries. No adapter or compatibility exemption is added.                                                                                                    |
| P10     | 10q             | MT-2026-002 is resolved against the real 0.5.0 release. MT-2026-001 remains planned without a proven corrected PostgreSQL replacement. Historical scanner/release evidence and actual pins are preserved.                                                                                             |

The owning audit, completion records, initialization/structured-read/native-test
reviews and current owning sources were rechecked. P01/P02 require actual storage
reconciliation, not acceptance of stale installed metadata. P08's corrected native
json contract supersedes the audit's initial projection-only premise. K01–K16's
retained architectural boundaries remain; no unrelated fallback/transport is removed.

Platform VERSION/platform manifest stay 0.7.0, approved feature projection 0.8.0;
Framework API is 2.1.0. Both actual databases now match these source candidates:

| Module           | Before | Installed/source after |
| ---------------- | ------ | ---------------------- |
| runa.audit       | 1.1.1  | 1.1.2                  |
| runa.auth        | 6.0.0  | 6.0.1                  |
| runa.permissions | 2.1.1  | 2.1.2                  |
| runa.admin       | 1.1.0  | 2.0.0                  |
| app.reference    | 0.4.0  | 0.4.1                  |

Admin's breaking snapshot consumer change and Framework API increment were assessed
in 10o. Auth/Audit/Permissions/reference public API candidates retain their recorded
versions. No dependency or runtime version override was introduced. Reference's
manifest already requires Admin ^2.0.0; its README's remaining ^1.1.0 statement is
corrected in this point. Full current-state documentation review remains point 12.

## Identified storage preparation and preservation

Read-only inspection confirmed local database/role/owner `runa` and isolated testing
database/role/owner `runa_test`. The schema marker is exactly
`runa.module.v1:app.reference`; its sole table is `app_reference.samples`.
No queued jobs, active managed work/transitions or update reservations were present.
Core has real supported current-schema paths. Reference's old 0.4.0 installation
has no current upgrade path and its old CREATE/ALTER fingerprints cannot establish
today's schema. Inspection did not publish a reservation or pretend update success.

The exact reset scope was disclosed before execution: only `app_reference.samples`,
its eight `shared.module_update_steps` rows and its installed-version provenance in
each identified database. Development held one generated welcome sample; testing
held two disposable example rows. Standing pre-1.0 authority applies. No entire
schema/database, application migration repository or Core table was wiped.

The operation held existing production-operation/test-suite file locks and its
own manual maintenance. It first ran the supported forward platform primitive;
all four Core update/preparation steps completed through native ModuleUpdates.
Then a bounded owner reset held the native update advisory lock and singleton/
module row locks, rechecked ownership/no active reservation/work, and transactionally
ran DROP TABLE app_reference.samples RESTRICT, removed only reference completion
rows and cleared only its installed_version to null. Registry generation advanced.
This was an actual destructive development reset, including an explicit provenance
edit; it was not an ordinary native update or an invented upgrade conversion.
Old step names/hashes/statuses are retained in the operation journal.

Native ModuleUpdates::apply('app', exact source) then executed the current CREATE
and null-to-0.4.1 initializer. Its actual native succeeded records and installed
publication replace the reset provenance; no success/version was fabricated to
bypass validation. Existing settings/registration/enabled flag were retained.
Both bases contain one newly initialized welcome sample. Both owner plans have
zero pending steps, installed/source versions match, update reservations are empty
and maintenance reasons are empty. No stale migration was marked current.

Development retains 15 users; testing retained its two pre-operation users.
Before/after hashes of complete user rows and encrypted module settings/revisions
match per database. Development settings hash remains
`89a1b1151e3d1200d7bbc7f7240c60d396e417dbe310d173ba12d101f7a333f5`.
No password, assignment, account identity, Core history or settings value was reset.
The test environment additionally passed TestDatabaseGuard. Safe evidence and local
one-off operation scripts are under `.runtime/dev/RM-2026-017-10r/`: inspect-local/
inspect-test, after-local/after-test JSON and runa/runa_test JSONL. They are private
operational evidence, not a new supported command or customer reset interface.
The first Python invocation failed on a nonexistent module before running any PHP;
the corrected invocation used the existing root tools imports and locks.

## Assets, actual pages and bounded interactions

Operational preparation completed with exit 0:

- pnpm --dir src run build (including TypeScript and native error rendering).
- pnpm --dir src exec vite build --mode development (includes opted-in reference).
- php src/runa/build/errors/build.php (standalone emergency assets).
- php artisan runa:doctor:development (config, database/migrations, queue/scheduler,
  real Nginx/Mailpit and resources; optional knowledge availability reported separately).

Existing chunk-size and ineffective-dynamic-import warnings remain visible in the
build logs. These builds prepare the manual application; they are not full frontend,
image, installation or security verification. No container image was built.
Current SPA entry is `/build/assets/index-CaW1iAUP.js`; build index SHA-256 is
`1698ce679ee57f4760384f2188d477b9a753186eebfee764cc487ab9f5d00368`.
Exact source/branch/time and asset hashes are in
`.runtime/artifacts/playwright/RM-2026-017-point10r/build-identity.json`.

The owner origin is [localhost:8080](http://localhost:8080). Chromium traversed
actual sibling Nginx at http://nginx:8080 using existing private development admin
credentials and normal full-page Admin password proof. No credentials were changed
or included in reports. Observed account, Admin dashboard, user directory and all
five reference views reached actual content and successful dependent APIs. The
1440px sample has no page errors, toasts or horizontal page overflow. Initial
session 401/Admin step-up 403 are expected login/proof transitions, not bypassed.

Bounded current interactions additionally establish:

- Trusted-device disclosure starts collapsed, opens with Enter and collapses again;
  the current table row remains mounted.
- Returning from another browser tab sends zero business GETs and preserves the
  reference settings draft; explicit refresh also preserves it.
- Review succeeds; publication requests normal password proof. Cancel returns to
  the draft with zero publication requests. The stored label is unchanged.
- Reference settings render without page overflow at 390px in PL and EN.
- Auth policy snapshots arrive as a native array, user creation's password_policy
  as a native object. Policy/user creation/details/security/account preference
  views open without JavaScript errors. Lazy sections not actually read by these
  observations are not certified; remaining collection/mutation states stay in 11/12.
- Entry language/theme controls expose localized names and square 44px dimensions;
  keyboard activation works at narrow width. The ordinary nonexistent
  /account/preferences path returns native 404; its language/theme GET controls
  retain the companion preference, including EN/dark navigation. No route or
  destructive error trigger was introduced.

The structured-read diagnostic's earlier attempts used insufficient settling or
incorrect cancel/tab locators; the control diagnostic initially activated during
preference transition. Failed attempts are preserved separately. Corrected
settling and semantic locators establish only the final recorded bounded scope.
No application code change was needed. Current screenshots were visually sampled;
these observations are not a complete viewport/theme/state matrix or owner acceptance.

The supported public command
`php artisan runa:test:backend --filter='keeps two independent users snapshots'`
passed on the prepared base: 1 case, 14 assertions, exit 0 / cleanup 0. This resolves
the actual independent-session stale-write readiness uncertainty after rebuilding
reference storage and replacing the native launcher. It is not a complete backend
suite, TTY/coverage acceptance, SIGSEGV disposition or final PASS.

Browser evidence is under `.runtime/artifacts/playwright/RM-2026-017-point10r/`:
combined-readiness, interactions, structured-readiness and controls-readiness JSON,
their scripts and current screenshots. Build/doctor/native-test logs are under the
10r development evidence directory. Raw earlier attempts remain separate.

## Manual handoff, instruction products and resources

The [current acceptance sheet](RM-2026-017-current-acceptance.md) replaces the stale
10i identity and renews current preference formats, Button/native-error controls,
structured snapshots/Auth projections, reference workflows, dirty/proof/conflict
behavior and PL/EN/themes/viewports/keyboard scenarios. Existing authorized access
works; safe account discovery remains runa:seed:accounts. Standalone reference
composition is a component/composable contract, not a fictional extra route.
Only the owner's explicit tested result completes point 11.

Owner AGENTS and backend/frontend/Laravel/PostgreSQL/security skills already select
the applicable public contracts and pre-1.0 reset/verification boundaries. Customer
AGENTS/CLAUDE/skills remain application-scoped. This point adds no reusable rule,
public signature, runtime behavior or new capability; neither instruction product
needs a change. Public reference documentation receives only its corrected actual
Admin range, not owner operation history. Final documentation/quality review stays 12.

Removed: the two identified reference sample tables' old contents and eight old
reference completion rows per database, followed by real native recreation.
Preserved: all other tables/data/history, accounts/credentials/settings, enabled
flags, databases/volumes, active services, all model/Qdrant/shared caches, backups,
old and current reports and recovery evidence. No Docker container/network/image/
volume was created or removed. Browser processes/contexts closed; the focused
backend command reports no disposable worker databases to remove. The one-off
operation scripts/journals remain evidence under the single workspace runtime tree.
No host transition, production/customer operation, acceptance, release or full gate
is claimed. Point 11 and point 12 remain open.
