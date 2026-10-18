---
id: RM-2026-004
title: UI foundations, layouts and reusable components
status: draft
branch: roadmap/RM-2026-004-ui-foundations
created_at: 2026-10-18
activated_at: null
closed_at: null
release_type: feature
impact_areas:
  - frontend
  - backend
  - production
  - security
  - documentation
---

# RM-2026-004 — UI foundations, layouts and reusable components

## Overview

### Goal and release

Deliver optional, individually reusable Vue UI primitives, three layouts, branded HTTP errors and integration contracts for future applications/modules. Preserve the current `/` welcome dashboard's purpose, main content, appearance and preference interactions, except for the explicitly superseding dual-version footer requirement (USER 254). This is a planning-only draft; activation requires the owner's explicit declaration.

Planned release: **0.2.0**, from audited `VERSION` **0.1.2**. This is a feature roadmap under `docs/workflows/releases.md`, not a stable API/1.0 declaration. Pre-1.0 API additions and documented evolution justify a minor release; no major bump is authorized. Recompute from actual main at activation if an intervening release occurs and synchronize the two successor drafts.

Execution order: this roadmap → [RM-2026-005](RM-2026-005-platform-installation-updates.md) → [RM-2026-006](RM-2026-006-module-framework.md). Neither successor may start implementation before its predecessor is closed, merged and released. No successor infrastructure is a prerequisite of this UI release.

### Scope and exclusions

Include minimal/application/email layouts; branding/version interfaces; errors and static emergency 503; preferences; components; forms; tables; exports/bulk-operation adapters; temporary visual review pages; tests and durable documentation.

Exclude Auth, Permissions, Audit, Admin, business workflows/record-detail screen composites, backend export jobs, account preference synchronization or shared saved views, notification history/center, server-side form drafts, address datasets, permanent showcase infrastructure and module lifecycle/discovery. Business applications may consume public backend APIs with their own frontend and may import individual components without either shell.

### Audited baseline and source refresh

Audit date: 2026-10-18, `main` at `725f75b`, clean worktree, no active roadmap, closed RM-2026-001/002/003. `docs/project/state.yml` says production never deployed. Local knowledge status was current (96 documents/883 chunks); a focused architecture/install query was checked against original sources. Refresh status before implementation; use focused source searches on unavailability/staleness, never bootstrap knowledge merely for navigation.

Read root `AGENTS.md`, applicable nested contracts if added, `docs/workflows/{roadmaps,git,releases}.md`, `docs/README.md`, `docs/architecture/{frontend,application-architecture,api,configuration,database}.md`, `docs/development/localization.md`, `docs/testing/`, `docs/quality/gates.yml` and relevant Runa frontend/Vue/Tailwind/Laravel/backend/security/Docker skills. The only applicable source-tree AGENTS found is root; `.runtime` simulation copies are not source policy.

| Existing source                                                                                     | Finding and disposition                                                                                                                                     |
| --------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resources/js/App.vue`, `layouts/AuthLayout.vue`                                                    | Root is an accepted welcome view, not authentication. Extract/generalize structure, retain compatible AuthLayout facade and root rendering.                 |
| `components/AppButton.vue`, `AppTooltip.vue`, `AppIcon.vue`, `AppForm.vue`, `AppSelect.vue`         | Real primitives already exist. Extend them, preserve useful contracts/tests; do not create parallel competing controls.                                     |
| `AppFooter.vue`, `RunaLogo.vue`, `AppBackdrop.vue`, `AppConstellation.vue`, `AppLoadingOverlay.vue` | Reuse supplied logo, accepted backdrop, transitions and footer structure. Footer currently imports root VERSION and hardcodes author/repository.            |
| `lib/preferences.ts`, `composables/useTheme.ts`, `useLocale.ts`                                     | Safe browser-local storage and PL/EN/theme behavior exist; generalize persistence without losing current keys/behavior.                                     |
| `lib/http.ts`                                                                                       | One `/api` wrapper, CSRF/error handling, unknown payload validation. Extend for encoded query parameters and typed caller validation; retain one transport. |
| `routes/spa.php`, `SpaController`, `bootstrap/app.php`                                              | Only named `/` SPA route; no Vue Router dependency. API errors already prefer JSON. Add explicit page resolution without catch-all HTTP-200 errors.         |
| `package.json`, `composer.json`, `vite.config.mjs`, `tsconfig.json`                                 | Vue 3.5, Tailwind 4.3, strict TS, Laravel 13.37, Vitest/Vue Test Utils/Playwright already present. No UI framework, router or export library installed.     |
| `tests/frontend/{components,http}.test.ts`, `tests/e2e/foundation.spec.ts`                          | Reuse real-browser component and root preference tests; extend risk-based behavioral coverage.                                                              |
| `docker/nginx/production.conf`, `docker/php/Dockerfile`, production Compose                         | Existing Nginx→PHP-FPM build has explicit copies and no emergency branded fallback. Extend copies/config in this roadmap, not RM-005.                       |

Inspect `docs/design/visual-direction.md`, `color-palette.md`, `color-palette-preview.svg`, `assets.md`, references README, REF-001 and REF-002. The two raster references were inspected. REF-003's source image is absent; its approved textual navigation decisions suffice. Do not reconstruct the missing reference or claim pixel equivalence.

## Specification

### Decisions

- 2026-10-18 — Preserve IBM Plex Mono, semantic palette, approximately 4px ordinary corners, moderate density, supplied logo and licensed local Tabler subset. Extend icons from reviewed pinned upstream assets with attribution/hash updates, without assuming approval for a package.
- 2026-10-18 — The latest accepted backdrop uses grid, diffuse corner gradients and constellations; older circle/ring instructions in the same design document are superseded. Preserve the current accepted implementation.
- 2026-10-18 — New requirements supersede older workspace/footer guidance. Application workspace order is heading/description/actions → breadcrumbs → optional statistics → optional toolbar → mandatory content, with optional supplementary panel. Sidebar is fixed/independently scrollable; top navigation and toolbar scroll normally. Update current design/frontend guidance during the responsible implementation point; never rewrite closed roadmap evidence.
- 2026-10-18 — The later explicit USER 254 requirement supersedes footer-only preservation from USER 187: minimal and application layouts, including `/`, default to `ApplicationName vX.Y.Z | Powered by Runa vA.B.C`, with an email equivalent. Do not invent a legacy footer exception. Preserve the root's body, purpose, layout and preference interactions; present the scoped footer change at its visual gate.
- 2026-10-18 — Use a stable `resources/js/runa/ui/index.ts` export facade and matching Vite/TS `@` alias. Keep existing App-prefixed implementations; export documented public names without exposing private file paths. RM-005 relocates ownership behind the same import facade. Public components do not require router, shell, registry or global state installation.
- 2026-10-18 — Add a small typed route/page resolver for known routes, history and navigation metadata, with server-known named entry paths. Browser back/forward, direct URLs and refresh reconstruct active module/area from route descriptors; do not add a routing dependency without separate approval. RM-006 later supplies the same descriptors.
- 2026-10-18 — Server-rendered Blade/PHP views are a narrow exception for email and HTTP errors, not application UI. Share branding/token inputs with Vue, not Vue rendering. All Laravel API choices must be checked using Boost version-aware docs before implementation.

### Requirement authority and audit corrections

The original conversation was read in full (messages 35–271), then checked again against the corrected drafts. Explicit USER requirements and later corrections take precedence over ASSISTANT summaries. An ASSISTANT saying “agreed” without a recorded USER answer is not proof of approval. Requirements also explicitly present in the owner-supplied roadmap prompt remain in scope; ordinary accessibility/testing choices follow repository rules and USER 239. Specific paths, route resolver and adapter mechanics below are planning choices, not quoted owner decisions.

Key source anchors: USER 148/168 (three layouts and roadmap sequence), 174/177/179/229/231/249–250 (existing design/skills/Tabler), 183/185 (native Laravel mail/error integration, defer Auth mail), 187/189 (retain `/`, temporary previews plus 404), 191/193/195/199 (optional regions, widths, two-level navigation, statistics above toolbar), 202/204 (both statistics modes and mandatory user switch), 209/214/222/227/233 (catalog, inputs, four exports, primitives only, extraction), 241/243/270 (small visual phases, remove catalog, no global workflow change), 247/256 (fixed independent sidebar, scrolling top/toolbar), 254/258/262/268 (dual-version footer everywhere, defer account preferences, overridable print defaults, static 503).

USER 199/212 requires design documentation to incorporate the newer specification. At activation, point 1 must reconcile `docs/design/visual-direction.md` and related current guidance before visual implementation: mark new decisions as planned until delivered, replace obsolete root-removal intent, and resolve footer/workspace order. Do not rewrite dated closed-roadmap evidence or claim unimplemented layouts are already available. This audit changes planning drafts only.

### Layout and identity contracts

Minimal layout offers named header/content/illustration/actions/footer regions and a translator-compatible default slot. Preserve AuthLayout as the root's split-panel composition, including preference cover timing, focus return, responsive illustration and skip link. Other minimal screens can omit illustration without copying markup.

Application layout consumes typed navigation descriptors (stable entry/route/module/area keys, label, icon, href, children and availability). Active state comes from route context, not stored selection. Sidebar supports dropdown groups, collapsed icon tooltips and phone drawer with focus restoration/Escape; persist collapse and expansion. Top navigation supports links/dropdowns and overflow on narrow screens. Both are visible by default and can independently be hidden; sidebar stays on the left and top navigation above the workspace, with no relocation API; only the sidebar is viewport-fixed, while top navigation scrolls normally. Expose contained/fluid width and named workspace slots. In both web layouts, each footer element and the entire footer can be configured/hidden without source edits. Footer remains in document flow, naturally near viewport bottom on short pages. Shared print CSS hides navigation/footer/unnecessary controls and formats content; views may override explicitly.

A branding/identity provider exposes application ID/name/logo/version and platform version; defaults use actual root VERSION for the bundled Runa application and platform. Configuration overrides are supported now, with no settings database/UI. RM-005 replaces the backing application metadata with its manifest. Do not use example numbers or automatically couple future application versions to Runa. Validate image URLs/assets and accessible fallback names.

Register the base theme/views with Laravel's normal Markdown mail and notification rendering so standard non-Auth messages actually use Runa branding without callers having to opt into a separate custom mail engine. Do not modify Auth-specific messages. Email uses Laravel mail rendering, a light-only, responsive, centered ~600px table-based structure, inline compatible CSS and safe fallback fonts. Header/body/actions/footer are overridable through namespaced views/contracts without editing Runa. Support CID branding by default, public HTTPS URLs, alt text/blocked-image readability and text equivalents. Prefer Laravel Markdown/text rendering for automatic plain text; allow explicit text content for custom HTML where automatic conversion is lossy. Test actual multipart/alternative MIME and CID relationships. No Auth messages or user preference models.

HTTP errors cover at least 403/404/419/429/500/503, preserve status/retry headers and JSON API behavior, use localized PL/EN presentation with English technical codes, and reveal no production trace. Error rendering must tolerate database failure and missing JS/build assets. A static Nginx-owned emergency 503 is shipped in the production Nginx image, independently of Laravel/PHP/database. Explicit maintenance and unavailable upstream paths return 503 without replacing unrelated API status/body contracts. Use an internal location and local static styles compatible with CSP; do not redirect errors into successful SPA pages. Verify the existing deployment stop/start path still leaves a serving fallback when maintenance is required; adapt that narrow serving path here while retaining rollback/health behavior.

### Shared state and component contracts

Preferences use a versioned schema keyed by application/view/control identity, validated JSON values, migrations/default reset and a replaceable storage adapter. Retain existing locale/theme keys through compatible reading/migration. Fail to in-memory session behavior on denied/quota/corrupt storage; preferences are never security state. Account sync is only a future adapter seam.

Use typed props/events/slots and composables; document size/variant/state semantics, including success/info/warning/error feedback, modal sizes and configurable icon name/size/color. During layout-first phases reuse/extract existing controls and the minimum shell composition needed; do not preimplement entire later component groups or duplicate their markup ahead of their visual gates. Include buttons/icon/group/dropdown buttons; alerts/toasts/badges/tooltips/popovers/status; cards/accordions/tabs/dividers/dropdowns/breadcrumbs/pagination; progress/spinners/skeleton/empty states; async-content loading/error/empty/success slots; alert/confirmation/custom dialogs; icons/avatars. Dialogs/layers own focus trapping/return, Escape/outside-click rules, stacked-layer handling and accessible titles. Toasts appear top-right, close explicitly, pause timeout on focus/hover and preserve important errors inline. Avoid mandatory shell dependencies.

Forms extend AppForm with sections/descriptions, responsive columns, collapse, actions, busy state and dirty baselines. Optional route and beforeunload guards preserve entered values; use browser unload behavior only for navigation warnings, never as field validation UX. Inputs share labels, IDs, helper/warning/error descriptions and default/focus/disabled/read-only/required/invalid states. Preserve semantic types/autofill and use `novalidate`. Map Laravel 422 errors (including nested keys), handle 409 conflict recovery and prevent known invalid/duplicate submissions without business rules in primitives.

Input inventory: text/textarea/password/email/URL/telephone; integer/decimal/currency/percentage; date/time/datetime/date range; checkbox/radio/switch; select/multiselect/searchable variants; single/multiple files; color/hidden/tags. Numeric values must avoid binary rounding surprises (decimal strings plus explicit display formatting); date-only values stay date-only and moments specify UTC/timezone conversion. File controls select files and expose validation UX; they do not implement the deferred Filesystem module.

Selection composes local/remote options, stable IDs, search, debounce/cancellation, incremental paging, loading/error/empty/clear states and bounded rendering. Document a provider contract rather than fetching whole large datasets. Masks expose raw/formatted values separately from validation; cover cursor movement, paste, deletion, composition/IME and nullable input. Cascading dependencies clear invalid descendants, cancel stale requests and support multilevel local/remote graphs with cycle rejection. Wizard state preserves values, validates steps, focuses errors and supports optional progress; no server drafts.

Tables use explicit client/server modes and stable record keys (public IDs for backend domain data). Provider input includes normalized filter/sort/page state and cancellation; output includes rows, total/matching count and capabilities. Add safe transport query encoding to the existing HTTP client. Use existing API list conventions (`page`, `per_page`, `sort`, `filter[...]`, `data` plus `meta`), explicit filter/sort allowlists and maximum page sizes for Laravel adapters; custom providers translate their own wire format. Unknown/stale totals are represented explicitly. Per-column render/filter/sort adapters and row/bulk action slots do not mandate a backend table engine.

Selection is a discriminated contract: explicit included IDs, or all-matching query snapshot/token plus excluded IDs and matching count. Preserve across pages; changing the query invalidates or explicitly reconfirms all-matching selection. Backend resolves final membership and permissions at execution; never download all IDs. Column reordering supports drag-and-drop plus an accessible keyboard alternative. Named saved views include filters, sort and column order/visibility/width; support create/select/edit/delete/default/reset with schema migration and invalid-column handling.

Expose XLSX/CSV/PDF/print operations with all/filtered/selected scopes and capability negotiation. Ship bounded client CSV (quoting, UTF-8, configurable separator and spreadsheet-formula handling) and print, plus working default adapters for small client-side XLSX/PDF exports. XLSX preserves headers, cell types and formatting; PDF supports readable headers, page breaks and orientation. Applications can replace adapters and choose export columns/formats. Never rename CSV/HTML to those formats. Browser-local datasets can be supplied to an application-owned adapter; server data always uses backend export providers. A provider returns a download or an operation handle with queued/running/progress/completed/failed/partial results, accessible status, retry semantics and optional cancellation. All four actions must work with real downloadable/printable output for bounded client datasets and deterministic server-provider contract tests. A fixture-only XLSX/PDF adapter is not completion. Document production server-provider integration; no fake success or backend business jobs. Print remains a separate action rather than an asynchronous file-export format. A new binary-generation dependency requires explicit approval after activity/license/security/size/overlap review; native generation is not an excuse to invent fragile XLSX/PDF engines. Evaluate available maintained adapters early and obtain any required dependency approval before their implementation point; if no approved implementation is available, keep that point open rather than silently downgrading to a demo.

### Visual approval protocol (this roadmap only)

Each visual point below is a separate approval boundary: finish implementation and relevant tests/docs, commit coherent work, supply reachable preview URL(s) and applicable PL/EN desktop/tablet/mobile evidence (web in light/dark, email in its light-only theme), then **stop and wait for explicit owner acceptance before starting the next point**. Record acceptance date/scope in the active roadmap. Silence is not approval. Feedback is resolved/rechecked at that point. Keep its acceptance subcondition open until accepted; do not equate a technical commit with point completion.

Temporary development-only named routes under a reserved review prefix provide minimal, application, email, actual 404 and interactive categorized component catalog previews. Register only in development/testing, never production; no production flags can enable them. Catalog supports variants/sizes/state controls and responsive inspection. Email preview includes actual rendered HTML/text/MIME fixtures. Preserve these until the owner accepts all visual work, then remove demo routes/pages/catalog in point 19. Permanent tests use isolated fixtures/harnesses, not shipping preview pages. This is not Admin or Storybook.

## Implementation plan

Every point refreshes relevant sources/knowledge/skills, synchronizes affected current docs/catalog entries, runs proportionate checks and creates a scoped Conventional Commit. Dependencies below are sequential unless stated otherwise.

- [ ] 1. Refresh audit, root regression baseline and public contracts.
  - Inspect the audited files; capture `/` at 1440×900, 1024×768 and 390×844 in both themes/PL/EN, preference timing and reduced motion. Define export/identity/navigation/preferences types and inventory exact reuse/extension decisions. Reconcile planned design documentation as specified above, and set up temporary review routing/catalog skeleton without changing `/`. Check production exclusion and import-boundary smoke tests. No new dependency is preapproved.
- [ ] 2. Minimal layout and configurable branding/footer.
  - Requires 1. Extract slots/identity provider, preserve AuthLayout/root body and existing interactions while applying the later dual-version footer requirement. Add layout/short-page/long-page tests and direct imports. Update frontend/design/component/page references. **Visual gate: minimal preview and root regression, with only the explicit footer change.**
- [ ] 3. Application layout, navigation and shared preferences.
  - Requires accepted 2. Implement typed route resolver, deep-link server entries, independent nav visibility, collapsible/drawer sidebar, nonsticky top/toolbar, all workspace regions, contained/fluid and print rules. Extend storage adapter with malformed/denied-storage tests. Test back/forward/refresh, overflow, keyboard focus and scroll independence. **Visual gate: application preview using synthetic navigation only.**
- [ ] 4. Email layout and extension contracts.
  - Requires accepted 3. Add native mail views/text/CID/public-image interfaces, correct build copies and test mailable fixtures without Auth. Test a standard non-Auth Laravel Markdown/notification message using the default Runa theme, MIME, PL/EN, long content, blocked images and explicit/automatic text. Review representative Outlook/Gmail/Apple Mail rendering where available; report limits. **Visual gate: email preview and sample rendered message.**
- [ ] 5. HTTP errors and emergency 503 integration.
  - Requires accepted 4. Implement six server-rendered statuses, localized safe output, retry headers, no-JS/DB-failure behavior and static production fallback. Extend exact production image copies/Nginx/maintenance integration and tests. Preserve API JSON and `/`. **Visual gate: actual 404 plus 403/419/429/500/503 samples.**
- [ ] 6. Buttons, icons and avatars.
  - Requires accepted 5. Extend current controls with variants/sizes/busy/disabled, icon-only labels, groups and dropdown buttons; review icon provenance and avatar fallback. Component/event/keyboard tests. **Visual gate: category variants/states.**
- [ ] 7. Feedback and floating layers.
  - Requires accepted 6. Alerts/toasts/badges/status/tooltips/popovers, shared positioning/dismissal and live-region behavior. Test timeouts, focus/hover pause, Escape and narrow-screen placement. **Visual gate: feedback catalog.**
- [ ] 8. Containers and navigation primitives.
  - Requires accepted 7. Cards/accordions/tabs/dividers/dropdowns/breadcrumbs/pagination with controlled state and keyboard behavior. Reuse in shell where appearance is preserved. **Visual gate: containers/navigation catalog.**
- [ ] 9. Loading, progress, async content and statistics.
  - Requires accepted 8. Reuse spinner/overlay; add skeleton/empty/progress/async slots. KPI label/value/icon/trend/semantic/custom-visualization slots and global/filtered mode toggle, per-view default/persistence. Calculations/providers stay external. Test all states and switching. **Visual gate: loading/KPI catalog.**
- [ ] 10. Form container, common field wrapper and text/numeric inputs.
  - Requires accepted 9. Sections, columns, collapse/actions, validation messages and text/numeric inventory. Explicit dirty/busy/value contracts, Laravel error mapping and decimal serialization tests. **Visual gate: field states and responsive form.**
- [ ] 11. Date/time, logical, file and additional inputs.
  - Requires accepted 10. Date/time/datetime/range, checkbox/radio/switch, file/multiple file, color/hidden/tags; localize values/display and test DST/date-only/null boundaries, keyboard and autofill. **Visual gate: input catalog.**
- [ ] 12. Composable selection controls.
  - Requires accepted 11. Extend AppSelect with multiselect/searchable and local/remote providers; incremental large-data handling, clear/loading/error/empty states and cancellation races. Test stale responses and keyboard/touch selection. **Visual gate: selection catalog.**
- [ ] 13. Masks, dependent fields, dirty guards and wizards.
  - Requires accepted 12. Raw/formatted masking, cursor/paste/delete/IME tests, cascading dependency graph and multistep navigation/validation/preservation. Test cancel/retry, descendant invalidation and leaving a dirty form. **Visual gate: advanced forms.**
- [ ] 14. Dialog interactions.
  - Requires accepted 13. Alert/confirm/custom modals with shared layer ownership, stacking, focus return and busy/error behavior. Test keyboard traps, nested layers and mobile scrolling. **Visual gate: dialogs.**
- [ ] 15. Table foundation and client/server providers.
  - Requires accepted 14. Sorting/filtering/pagination, row actions/selection, async states, responsive rendering and typed query transport. Use deterministic providers, public keys and request race tests. **Visual gate: both processing modes.**
- [ ] 16. Table personalization and saved views.
  - Requires accepted 15. Column visibility/reorder/resize/reset and full named-view lifecycle/default. Accessible keyboard alternatives to dragging, corrupt/old storage migration and unavailable-column tests. **Visual gate: personalization and saved views.**
- [ ] 17. Cross-page selection, bulk operations and exports.
  - Requires accepted 16. Implement explicit/all-matching/exclusion contracts, counts, query-change invalidation, export scopes/capabilities, four format actions and synchronous/async progress/partial-failure UX. Deliver real small-dataset XLSX/PDF adapters after any required dependency approval; document server providers and validate actual MIME/file signatures, XLSX headers/types and PDF pagination/orientation. Test no whole-dataset fetch, authorization failure presentation, formula-safe CSV, print and retry. **Visual gate: exports/bulk workflow.**
- [ ] 18. Integrated accessibility, responsive and public-consumer review.
  - Requires accepted 17. Exercise components in a frontend that imports no layout, test all optional workspace/nav combinations, WCAG 2.2 AA applicable behavior, 200% zoom, keyboard/screen-reader critical interactions, reduced motion, print and console/network cleanliness. Refine inconsistent states, update docs. **Visual gate: final complete catalog; explicit approval to retire previews.**
- [ ] 19. Remove temporary visual review infrastructure.
  - Requires explicit final visual acceptance in 18. Delete preview/catalog routes/pages, fixture-only production registrations and demo navigation. Preserve library/layouts/docs/test fixtures and `/`. Prove preview URLs absent in production and no demo assets in shipped graph; tests use nonshipping harnesses. Publish durable import/extension/provider examples in existing frontend docs/catalog references.
- [ ] 20. Full verification, release finalization, merge and final report.
  - Requires 1–19 complete, all visual acceptance recorded. Review current-state README, design/frontend/API/config/localization/testing/tooling/production docs and affected skills; record sources/findings/disposition. Run the full applicable local gate per `docs/quality/gates.yml` including production fallback/isolation and security. Insert fixes before this point and rerun the entire final gate on any substantive failure. Follow roadmaps/releases workflows for metadata, knowledge refresh, docs/release checks, closure/merge/main sanity, annotated tag/GitHub Release and branch deletion. Background gates end the agent turn without polling. Planned version 0.2.0 is finalized from actual VERSION; do not activate RM-005 automatically.

## Validation, risks and completion criteria

Use existing `./bin/test frontend`, `./bin/test e2e --critical`, backend Pest, `./bin/typecheck`, `./bin/pnpm run build`, production self-test/isolation and relevant offline production contracts. No mandatory CI. Tests must exercise behavior (focus, MIME, status, races, storage, selection membership), not just mirror component markup. Include production preview-absence and no-JS/error tests.

Risks: generalizing AuthLayout can alter root geometry/timing; use rendered regression, allowing only the explicitly superseding footer change. Email client engines and blocked fonts differ; use conservative markup and report actual client coverage. Static fallback can mask API errors; test negotiated responses and upstream failure paths. Large tables/masks/selects require measured rendering and race tests. Binary export engines and optional testing/router packages require separate dependency decisions; provider contracts do not waive delivery of the four real client export/print operations or authorize unapproved packages.

Acceptance: all required component/input categories and four export integrations have documented, tested contracts; layouts work independently of future modules/Auth; navigation reconstructs from URLs; preferences degrade safely; actual errors preserve statuses; fallback serves without PHP; `/` retains its purpose/body/behavior, with only the explicitly required dual-version footer change; owner approvals are recorded; temporary previews are removed; docs and reusable pattern references match code; full gates and release workflow pass. No production data or Docker resources are changed during this planning task. During execution inspect/show/clean exact task-created disposable resources after their last use, preserving development/test data, rollback volumes, knowledge/model caches and reports.
