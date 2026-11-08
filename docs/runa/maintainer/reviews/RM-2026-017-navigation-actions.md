# RM-2026-017 reference order, action identity and navigation warnings

Owner manual feedback, 2026-11-08. Baseline: 9ed1b543 / built index-Dd2HJQm4.js.
Points 10w–10x precede repeated manual acceptance 11. Final verification/release
remains 12; no owner acceptance is inferred from agent observation.

## Reproduction and owning corrections — 10w

A real Nginx Chromium observer persists warning insertions and beforeunload events
across actual native link navigation. Ordinary reference → Admin → reference →
Auth users → reference navigation captures the yellow text:
“Nie udało się wykonać żądania. Spróbuj ponownie. Ponów”. Warning insertions follow
beforeunload by approximately 50–85ms. A controlled real successful read, briefly
held at its response boundary before navigation, captures the same warning.
The original observer also touched sessionStorage in an opaque initial document;
that harness exception is retained separately from captured real page evidence.

Shared HTTP already converts departure cancellation to RequestCancelledError and
honors synchronous dirty guards. Admin bootstrap failure, Admin page-read failure
and Auth protected-activity failure had not retained that distinction: departure
could manufacture a failed/unavailable warning or section expiry before unload.
Those owners now recognize only the existing typed cancellation. No global warning
filter, delay, debounce, HTTP retry, reload or replay is introduced. Live network,
proof/availability refusal, current-time expiry and account/access loss still follow
their normal failure/withdrawal paths. Unknown write completion remains unknown.
Reference workspace/resource consumers already recognized document cancellation.

ReferenceManagement explicitly assigns its inspect landing page order -10; the
other existing pages retain order 0 and their deterministic key order. This uses
the supported descriptor and backend ordering, without sorting translated labels
or hard-coding a reference exception in platform navigation.

Auth launchers previously used the same arrows-exchange glyph. A private semantic
family map now joins single and bulk tools, and concrete command icons distinguish
opposing effects. Review remains eye; execution reflects the chosen operation.
Sixteen additional unchanged outline SVG originals come from the existing pinned
Tabler revision/MIT license, with recorded hashes and typed decorative Icon support.
No package dependency, remote runtime source, grant/proof/color change or API
command dispatch is added. Public presentation signatures remain unchanged.

Inventory: supplied Auth directory bulk tools and account-detail tools were the
two concrete grouped-action consumers with this uniform icon. Analogous avatar
removal and session/device revocation row controls in Admin/account views now use
the same photo/session/device identities as the grouped tools and confirmations. Reference workspace,
Permissions and Audit use their own meaningful save/inspect/revoke/delete/read
controls. Generic AdminAction already distinguishes create/save/delete; unspecified
commands retain a generic fallback. Generic TableActions similarly retains its
caller-agnostic fallback, but no delivered App/Core business BulkProvider uses it;
its only providers are private executable fixtures. No unrelated provider API or
business workflow was added merely to invent icon meanings for unknown actions.

## Bounded evidence and documentation

Seven native Chromium cases pass, exit 0 / cleanup 0:
`python3 tools/maintainer/test-frontend --filter='protected activity distinguishes document|Admin document cancellation|Auth tools keep distinct semantic|cancels departing-document reads'`.
Coverage distinguishes typed cancellation from network, proof and availability
failure; preserves admitted Admin page/bootstrap on departure; retains genuine 503
refusal; checks shared read/write cancellation without success/replay; verifies
sixteen distinct tool identities and opposite concrete effects. Initial fixture
failures reused an already-consumed Response and asserted before browser JSON body
completion; corrected fixtures use fresh responses and wait for native completion.
No product error path was relaxed to resolve those fixture issues. Full suites
remain unexecuted during this source point.

Evidence: `.runtime/dev/RM-2026-017-10w/focused{,-corrected}.log`, pinned asset
retrieval script and `.runtime/artifacts/playwright/RM-2026-017-point10w/warning-before.json`.
The latter retains ordinary and controlled beforeunload/warning events plus its
separate observer-origin failure. Current rebuilt runtime observations belong to 10x.

Public semantic icon/cancellation guidance, Auth administration, reference narrative,
asset provenance, module/central changelogs and both instruction products are
synchronized. Existing owner Vue guidance already states the cancellation rule;
root owner/customer AGENTS delegate these exact contracts and need no policy edit.
The customer Vue/frontend selectors point to the canonical consumption guidance;
private engine corrections remain owner work. These corrections join unreleased
Auth 6.0.1, Admin 2.0.0 and reference 0.4.1 candidates. Framework API 2.1.0 and
platform 0.7.0 / approved feature-0.8.0 remain unchanged. No storage, migration,
settings, dependency, credential or infrastructure change is required.

## Renewed actual readiness — 10x

Committed runtime source: `bb5abb29c1cf9055a943e6a751adf580c49fc52b`, followed by
this documentation-only handoff. Supported operational build, development-mode
Vite build and standalone error finalization all exit 0. Existing build chunk-size
and ineffective-dynamic-import warnings remain recorded. No full suite or image /
installation/security/AI/release gate is claimed by this manual-runtime preparation.
Unchanged backend/services/data retain valid previous readiness evidence; actual
current login/password confirmation and relevant Nginx APIs work.

The corrected same-origin observer repeats the four ordinary native navigations
and controlled pending-read/departure sequence against current assets, exit 0:
**zero warning insertions**, compared with five in the preserved old observation.
Beforeunload events remain recorded, so the absence is not merely a missing observer.
The opaque initial document is excluded from sessionStorage access in the harness;
no product storage or unload behavior was changed to repair that harness exception.

Actual top navigation is reference landing, history, samples, settings, work.
The account directory exposes **16 distinct bulk launcher glyphs** and the inspected
account exposes **15 distinct single-account launcher glyphs**. Every button retains
its visible label and one decorative icon. Common families have matching glyphs
across both contexts; PL/EN changes retain icon identity. Desktop/light and
390px/dark mobile samples have no horizontal page overflow or page errors.
Reference-order and Auth launcher screenshots were visually inspected.

A separate deliberate browser transport fixture returns availability_changed/503
for the page read while the document stays open: the warning remains visible,
protected page data is withdrawn and explicit Retry recovers through the real
healthy Nginx endpoint. This is a controlled transport refusal, not an actual server
incident or permission bypass. The first negative probe targeted the page endpoint
but clicked section refresh, so it did not inject a refusal; that failed harness
attempt is preserved. The corrected native reload targets the intended page request
and the complete order/icons/refusal observation then exits 0 against unchanged
product source. No production response, configuration or server guard was altered.
The source regressions separately retain real network/proof/availability failure
handling and shared cancellation without write success/replay.

Current SPA: `/build/assets/index-BdXz_X9Q.js`; index SHA-256:
`a69468b1df6cd17ccb15dfe31a71af9f90b2b29c039b9840655ece5b2c661e07`.
Full source/branch/time and built/native-error hashes:
`.runtime/artifacts/playwright/RM-2026-017-point10x/build-identity.json`.
That directory retains warning-after.json, order-icons.json, the failed negative
probe, scripts and screenshots. Before evidence remains in point10w. Build/browser
logs live under `.runtime/dev/RM-2026-017-10x/`; explicit health preparation reports
healthy through the owning native command. No storage reset, migration, account,
credential or business mutation is performed. Normal login/activity/preferences
retain their owning behavior. Browser contexts and native runners close their own
processes; no Docker resources are created or removed. Existing volumes, data,
Qdrant/model caches, backups and evidence remain preserved.

The current acceptance sheet now identifies this source/build and the three affected
manual scenarios. Owner acceptance 11 stays open; final automated verification,
release and merge remain 12. No further module/API version or instruction change is
needed for this operational handoff.
