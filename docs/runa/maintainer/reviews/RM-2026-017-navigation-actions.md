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
