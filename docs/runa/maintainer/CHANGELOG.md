# Changelog

All release entries are maintained by the agent from roadmap facts.

## Unreleased — RM-2026-017

- Simplify built-in initialization into null-to-current steps, retain explicit
  current-schema updates and published table-free Admin preparation, and remove
  76 historical Upgrade classes/122 edges. Core candidate patches: Auth 6.0.1,
  Audit 1.1.2, Permissions 2.1.2, Admin 1.1.1. Reference 0.4.1 owns its revision in
  the original CREATE; recorded old migration histories still refuse and require
  identified development/test reconciliation. Native engine/recovery is unchanged.

- Use action-driven business reads: remove passive account/resource reads and
  same-session preference/branding/Admin cascades while retaining security checks
  and actual identity/access invalidation. Reconcile recognized stale-state refusals
  only, preserve drafts/original submission tokens, and stop delivery prefetch from
  silently replacing its reviewed revision.

- Start the own-account trusted-device list collapsed using public Accordion;
  retain mounted table state and independent trust-browser/prerequisite controls.

- Complete the interface-copy review: remove login filler, shorten registration
  and root introductions in PL/EN, and retain actionable/security instructions.
  Record source dispositions, bounded Chromium evidence and the current Nginx
  503 limitation; combined runtime readiness and owner acceptance remain open.

- Correct native settings review/publication and snapshot admission in Framework
  API 2.0.0: separate semantic `review_id` from operational generation, retain
  exact health/authority/proof fences and reuse native prepared scopes. Update
  AuthPolicies 4.0.0 / Auth 6.0.0, Audit 1.1.1, Permissions 2.1.1 and current
  consumers, CLI and source-only forward edges. Complete reference 0.4.0 backend
  examples for audited editing, grants, settings/snapshots and protected work;
  presentation is now delivered through four Admin pages and a typed standalone
  workspace, using the additive Admin 1.1.0 native lifecycle seam. Combined runtime
  readiness, owner acceptance and final checks remain.

- Absorb the approved RM-2026-018 interface-copy, trusted-device disclosure and
  action-driven data-refresh scope into this roadmap's feature/0.8.0 plan; remove
  its redundant unactivated draft and separate 0.8.1 projection. Record owner
  approval of the native settings generation/snapshot-admission correction.
  Trusted-device disclosure and refresh implementation remain open, followed by one renewed
  owner acceptance and one final automated verification/release.

- Establish the reference module as executable customer documentation: new Runa
  modules and customer-facing capabilities extend working examples, guidance and
  tests in their owning point. Reopen reference completeness through repair point
  10a before owner frontend acceptance; backend examples are delivered; their expanded frontend journey is delivered; reconciliation/readiness/acceptance remain.

- Record the approved common-settings refactor and ordered repair points 10a–10e:
  framework contracts, Auth migration, executable reference backend/UI and renewed
  acceptance preparation. Framework API 1.2.0 now supplies source-owned access,
  field grants, password-proof orchestration and transactional settings hooks in
  the native runner. Preserve encrypted values/revisions/recovery and use existing
  Audit/history/snapshots. Auth 5.0.2 now uses this common source contract; Core
  Audit no longer dispatches Auth policy authorization/history. Current grants,
  one-use proof, domain consequences and atomic Audit remain enforced. Expanded
  reference runtime examples remain pending in points 10c–10e.

- Define agent-directed verification continuity and bounded manual SIGSEGV handling;
  preserve valid unrelated evidence and require explicit owner acceptance of
  exhausted confirmed interruptions, replacing standing automatic acceptance.
  No runner, retry automation or customer recovery-policy change.

- Keep Vite caches under workspace `.runtime`; invalidate development setup after
  shared/module frontend, registry or native error build changes while preserving
  nondestructive reopen. Add hash-bound RM-016 decision retrieval cases without
  rewriting closed history or changing knowledge runtime/model dependencies.

- Add Framework API 1.1.0 exact-call Admin read-health planning, preserving separate
  on-use checks before the locked read, current grants/proof/Policies and consumed
  source/expiry/rollback/reconnect evidence. Update the reference and generated
  customer example without changing Ping/AdminAccess guarded signatures.

- Align the application import rule with the already public Admin facade; keep
  private Admin implementation imports refused.

- Prepare app.reference 0.3.0's read-only Admin page/widget using its existing
  guarded Ping API; align panel feedback/read retention and document customer
  capability reproduction. Native integration uses the owner-approved exact-call health preparation; final
  runtime/browser acceptance remains pending. Keep optional development-only source and stored data.
- Auth: clear closed user-dialog state before later reads/bulk editing and use
  administrator activation terminology in Polish and English registration copy.

- Admin 1.0.1: scope lazy record-section activation to its selected editor subject;
  stop closed sections from refreshing. Keep dismissed form outcomes absent across
  language changes and align Wizard actions with the shared spacing standard.

- Auth 5.0.1: bound synchronous admission and policy reads without retaining
  authorization/eligibility decisions. Group trusted permission evaluation and
  management declaration discovery under their existing registry lock contract;
  retain current availability, expiry, grants, rollback and reconnect checks.
- Preserve `runa_auth.users.remember_token` for deliberate Laravel integration
  compatibility while retaining Runa's operational session/revocation guarantees.

## [0.7.0] - 2026-11-06

Feature release — RM-2026-016. Core Admin 1.0.0 with source-owned administration, native security/Audit and customer presentation contracts. Backend accepted under the standing owner SIGSEGV/139 exception; retained native failures and interrupted coverage remain disclosed.

- Prepare Admin 1.0.0 as its first stable creation release, with explicit install/update history and synchronized current module contracts.

- Allow a bounded PostgreSQL disconnect grace period before guarded test-worker cleanup; retain ownership/activity refusal, native failures and non-forced deletion. Correct backend acceptance fixtures for native login/proofs, feature prerequisites, current projections, scheduler boot and production seeding refusal.

- Repair final-verification findings: restore customer Vitest module-component discovery, reconcile controlled table columns and preference notifications, coalesce header positioning before paint without suppressing browser warnings, retain authenticator enrollment across action refresh, and include Settings/Images implementation in production images. Update four locked Symfony packages to 8.1.11 for reported security corrections; no new dependencies.

- Retain the required eligible administrator during bulk superadministrator removal and apply the remaining reviewed changes. Reuse the existing protected-account warning/count, fingerprint the retained set and preserve native single-write lockout protection, password proof and atomic Audit.

- Align protected-authority bulk tools with existing aggregate counts and omitted-account warnings; remove individual change listings and place Superadministrator last in both action toolbars.

- Replace the Auth user superadministrator checkbox section with grouped grant/revoke actions for one account or up to 100 explicitly selected accounts. Add guarded PermissionsAuthorityBatch 1.0.0 with complete-set lockout protection, stale review refusal, one password proof and atomic per-target Audit; preserve ordinary Auth bulk exclusions.

- Keep the access import mode selected by omitting its clear action. Add optional AdminField.clearable presentation control, retaining existing defaults for other fields.

- Open selected-user copy/preset tools in separate shared dialogs and remove the redundant source/system readonly notice from Permissions details. Preserve native review, proof, drafts and definition protection.

- Simplify Permissions to a readonly permission catalogue, roles and presets; combine details/editing in one dialog. Move account copy/preset and separately granted protected-authority workflows to Auth user details. Reuse guarded search, compact member selection, native source fingerprints, reviewed password-confirmed writes and atomic Audit; remove obsolete access/effective page adapters and exports.

- Use quiet outlined action buttons, including Save and confirmations. Preserve operation colors before modal admission and use purple for mixed-effect tools; final confirmation reflects the selected operation. Document the shared policy for future views.

- Unify shared action buttons with explicit semantic intents across supplied views: green commit, neutral cancellation/navigation, yellow discard/caution, blue reads/previews, purple mixed-effect tools and red destructive effects. Separate hierarchy from meaning and preserve color while busy; align snapshot previous-value help below the padded Changed marker.

- Simplify module settings Save to a confirmation question; omit the repeated differences and policy consequence messages while preserving native validation, password proof and Audit.

- Use the full workspace width for module settings and pack group cards in independent responsive columns, removing large gaps below shorter groups.

- Compact the shared module settings editor into bounded label/control rows and responsive group columns; widen the snapshot selector. Preserve source technical keys and separate PL/EN names/descriptions through authorized Admin projection, with keyboard/touch-accessible help. Clarify Auth descriptions and units without changing setting behavior.

- Auth 5.0.0 / AuthPolicies 3.0.0: remove guarded revision-history restore and the local history viewer. Deliver reusable named module snapshots, committed-value capture, draft-only loading with previous-value markers/cancel, password-confirmed deletion and atomic Audit. Reorganize Auth settings into semantic groups with one native reviewed Save.

- Remove redundant selected-user rule instructions and the local change-history panel; retain active rules and Account status, with history available through Audit.

- Restrict second factors to login and use the existing full-page Auth password confirmation for all sensitive Admin/account operations and protected places. Remove factor-based operation modes, inline credential forms and the password-change factor setting. Deliver AuthStepUp 2.0.0 with password-only proofs, retained drafts on cancel and unchanged transactional authorization/Audit.

- Auth 4.0.0 / AuthPolicies 2.0.0: allow optional account-rule expiry, with null meaning indefinite and no maximum horizon for a future date. Update effective policy reads, session cleanup, status/history and Audit handling; correct the original pre-1.0 schema and current Auth consumer constraints. Space operation fields and show explicit optional/indefinite copy.

- Move selected-user rules into User with named bounded editors, separate active/history presentation and live custom rules in Account status. Add guarded AuthPolicies 1.2.0 active exception reads independent of the latest-50 history cap; preserve native proof, revisions and Audit.

- Align shared table and Auth directory filter controls within each row when labels wrap onto multiple lines.

- Complete table sorting, filters/search and separately authorized exports/print across supplied account/Admin views. Add SQL ordering before cursor paging for users, permission catalogues and Audit through AuthAdministration 2.1.0, PermissionsAccess 2.2.0 and AuditReads 1.1.0; retain loaded-page export scope and native authority.

- Enrich new-browser sign-in notices in Polish/English with captured safe browser/system labels, event time and guidance for unrecognized activity, preserving native multipart mail and reference-only delivery.

- Identify trusted browsers with allowlisted browser/system labels, cookie-bound current-device state and last successful use. Share columns in Admin and the inline own-account table, including empty results. Extend AuthSecurity to 1.2.0 and correct the original pre-1.0 development migration.

- Present selected-user sessions and trusted devices with the shared account Table, authorized dates, remembered sign-in and native revocation; rename Trust to Trusted devices and retain bounded cursor navigation.

- Omit Auth action-group separators and leading indentation at the beginning of each wrapped row; retain same-row separators during resizing and locale changes.

- Present Auth single/bulk action groups inline without visible category headings, using subtle vertical separators and responsive wrapping.

- Organize Auth single/bulk actions with one thematic order and subtle separators, group account facts and compact own/Admin preferences. Add explicit primary/secondary administrative confirmation through AuthProfiles 2.1.0 with native grants, fresh proof, concurrency and atomic Audit.

- Complete access-editor interaction states: pointer/hover/pressed/focus on custom buttons, inherited checkbox cursors and truthful disabled row/page affordances.

- Refine access-editor proportions with compact bounded search, label-sized category controls, quieter filters/counters and lighter row/pagination chrome across desktop/mobile themes.

- Replace sprawling role/direct-permission forms with a dedicated compact access editor: one searchable catalogue, assigned filtering, retained category state and bounded pages without duplicate selected lists. Preserve native review, proof and draft reconciliation.

- Unify own/Admin secondary-email presentation and audit supplied error flows. Preserve business refusals through profile, secondary, security and aggregate adapters; show specific localized field/operation feedback for identifiers, limits, preferences, proof and access changes.

- Remove redundant operation scope/instruction copy and unify visible progress across shared forms, Admin/Auth/Permissions actions and the reference panel. Submit Buttons inherit local Form busy; independent reads/actions keep explicit progress owners and existing proof/draft/security boundaries.

- Refine user details with retained naming drafts, real avatars, consolidated account facts, email management and searchable assignments. Apply a complete development review preset and hide notification recipients when secondary addresses are unavailable in both own/Admin preferences.

- Align Auth user details with creation and account forms using public shared identity and preference controls, bulk action tones, native avatar cropping and form-local Save/Revert. Keep guarded target operations and post-submit proof.

- Rebuild user details into at most three task-based tabs with individual actions, independent editable forms and target-account presentation/notification preferences. Hide technical revisions/UUIDs, request proof after submit and preserve all native grants, reviews, concurrency and Audit. Auth 3.1.0 / Preferences, Security and Policies APIs 1.1.0; Admin 0.2.0 supports native standalone participant forms alongside complete aggregate Save.

- Unify horizontal wheel scrolling and directional dotted overflow cues across tables, content/modal tabs and application top navigation. Preserve table-body vertical scrolling, native Ctrl+wheel, keyboard/touch access and existing public component APIs.

- Omit operational editors for configuration-disabled capabilities. Keep authorized account facts read-only, hide disabled verification/factor/avatar editors and notification recipients, and dismiss withdrawn editors without losing unrelated drafts. Document the shared visibility rule in owner/customer frontend guidance. Preserve native backend enforcement.

- Simplify account creation copy and remove its optional security controls. Keep a concise outcome summary. Enable administrative email verification by default, retain its editable Auth policy control and omit creation verification entirely when unavailable. Preserve native permissions, stronger proof and Audit.

- Deliver comprehensive atomic account creation through AuthLifecycle 2.1.0 with initial password/invitation choices, independent activation/deactivation schedules, administrative email verification, initial locks and forced password change. Supply a conditional PL/EN form and access/delivery summary. Send and accept invitations before activation without issuing a session; show account_inactive only after correct login credentials. Preserve owning policy/grants/proofs, Audit and token security guards.

- Repair editor reopen with private development seed settings: accept and preserve the documented overlay across profile/device changes, materialize explicit entries into Laravel's local environment and keep credentials out of profile output and generated Compose overrides. Reopen still never seeds or migrates data.

- Skip ineligible accounts in Auth bulk invitations/password resets, report their counts separately from protected superadministrators and fingerprint eligibility through execution. Preserve specific business refusal messages instead of presenting every refusal as a stale write; review all bulk operation previews and owning execution regressions.

- Exclude all protected superadministrators from every Auth bulk user/session/trust action, including inactive or locked members. Show skipped counts, mutate only ordinary targets and fingerprint current protected membership through a bounded trusted Permissions read. Retire blocked-whole-batch preview flags. Advance AuthBulkSessions to 2.0.0 and update current consumers; individual native last-administrator guards remain unchanged.

- Refine bulk modals with compact width, paired selection/impact counts, localized loading and right-aligned confirmation.

- Group related Auth bulk controls into primary outlined buttons with explicit modal operation selection. Add atomic per-account activity/deletion/security-lock inversion with native grants, review fingerprints, Audit and deletion password proof; rename the one-way bulk action to Force password change. No periodic-policy column or account opt-out is added.

- Default Auth password expiry to 30 days on fresh initialization; retain zero as explicit disabling and preserve the independent one-time forced-change action.

- Remove independent account approval: current activity and scheduled activation/deactivation govern lifecycle admission. Remove approved/approve from storage, eligibility, creation, directory and administration; registration_activation selects automatic activation or inactive accounts awaiting ordinary administrator activation. Preserve historical Audit reads. Update the unreleased Auth 3.0.0 and Lifecycle/Administration/BulkUsers 2.0.0 contracts and all current consumers.

- Add optional locally configured automatic superadministrator fixture seeding after database rebuilds. Keep credentials outside Git, preserve existing passwords, enforce production guards and retain normal runtime password validation; only the initial development fixture permits an owner-approved shorter password.

- Remove independent account expiry in favor of scheduled deactivation, remove users.expires_at and its command/fact/filter, and preserve historical Audit reads. Reminder mail/settings/categories now use scheduled deactivation, including administrator-copy routing. Consolidate unpublished platform, Audit, Auth and Permissions migrations into final per-table CREATE definitions; initialize technical rows through seeds/initializers and recreate the disposable development database. Remove the unnecessary expiry conversion migration and settings rename machinery. Auth moves to 3.0.0 and Lifecycle/Administration/BulkUsers APIs to 2.0.0; current dependent constraints/consumers are synchronized.

- Refine Auth bulk actions with parameter inputs, actual affected-account previews, ordered semantic button tones and localized session revocation. Require fresh password proof only for delete/restore, password/2FA reset and exception revocation; Confirm opens that step directly. Remove the directory state column, color deleted rows and default the Deleted filter to current records. Extend AuthBulkUsers to 1.1.0 and public Admin table/review presentation seams.

- Translate 22 built-in setting/exception choice codes into readable PL/EN labels while retaining API values. Complete shared PL/EN defaults for validation, file actions, tag errors, feedback/loading accessibility, navigation, footer, module availability and static error request labels. Preserve invalid numeric/temporal drafts when changing language and keep static locale propagation browser-independent.

- Translate the shared Admin unfiltered All choice to Polish Wszystkie, including Auth boolean filters; preserve English copy and wire values.

- Match Auth Users filters to Audit compact controls, shorten account/security labels and expose each authorized bulk command as its own button below filters. Retain selected targets and native reviews/proofs.

- Consolidate date/datetime ranges into one labelled compact field with a shared endpoint popup, valid atomic Apply and Clear/Cancel. Replace expanded Auth range filters and Audit from/until controls; preserve wire keys, timezone/DST and open endpoints.

- Complete Users filters across authorized facts and date ranges; show effective 2FA/enrollment/recovery, retained activity, invitation and credential state. Move creation into the directory header and all native reviewed bulk user operations below filters. Share locked owning phases, enforce current grants/features, atomic Audit/proof rollback and safe restore subject locks.

- Complete configuration-aware Users administration: authorized account/session/security dates and state, accessible detail tabs, native avatar editing, and exact table-selected session/trust revocation. Remove the separate bulk-session page and retain native grants, proofs, Audit and shared Save.

- Restore module-state-to-card spacing after loading composition; keep ready ContentLoading slot children directly under their layout owner.

- Separate the JSON continuation ellipsis from payload tokens in shared previews.

- Standardize content loading across all supplied views and 17 modal instances: shared ContentLoading geometry, Dialog/AsyncContent/ViewBoundary defaults, retained-content refresh pulses and lint-enforced circular-indicator ownership. Audit report and both frontend instruction products are synchronized.

- Right-align preview expansion links and animate pending Audit detail-modal reads/continuations with the shared Spinner; use the same initial-read feedback in Auth/Permissions record sections.

- Link correlation and actor UUIDs directly to filtered Audit history, move correlation into Event and remove the related-entity card. Add shared theme-aware JSON syntax colors and inline expansion links across module previews.

- Group Audit event details, format/collapse long JSON with explicit expansion, clarify verified entity-history navigation and share long-value/review previews across Auth, Permissions and Admin.

- Unify lowercase localized dictionary labels across Admin filters, table cells and read-only details, including Audit actor/outcome and Auth account state; retain canonical identifiers, wire values and exports.

- Vertically center table header labels with balanced scrollbar spacing; scroll wide tables horizontally on header/rail wheel input while preserving native Ctrl+wheel zoom and page scrolling over rows or fitting tables.

- Align datetime table filters with compact controls, remove deferred header/rail scroll updates and show exact authorized filtered totals independently of cursor pagination across Auth, Permissions and Audit.

- Unify application timezone formatting, shared datetime defaults and calendar today; remove input timezone annotations and browser-dependent backend timestamp parsing. Maintain the temporal contract in both frontend instruction products.

- Keep shared table headers and a below-header horizontal scrollbar visible during page/dialog scrolling. Use timezone-aware date/time pickers for declared module filters, including Audit from/until.

- Add reusable Auth-owned protected-section idle access (Auth 2.1.0, AuthStepUp
  1.1.0) and migrate Admin admission/aggregate Save. Renew only on deliberate
  visible-section input, preserve fixed-age/single-use operation proof, and ship
  public scoped frontend composition plus forward storage and cleanup.

- Extend performance review across application startup, public/account reads,
  settings/assets, module work, bulk sessions, reminders and frontend request
  ownership. Bound repeated settings/declaration reads and collection queries,
  reduce asset transfer and coalesce presentation refresh bursts with trailing
  freshness; preserve authority, rollback isolation and current source checks.

- Remove registry/permission read amplification across Admin module projections
  and batch Auth preference/delivery snapshots. Add query-growth, transaction/
  rollback and authorization freshness regressions, plus preventive review
  guidance in owner/customer backend skills.

- Unify module record Details dialogs and compact automatic dictionary filters.
  Supply shared refresh/reset table controls, preferences and bounded
  CSV/XLSX/PDF/Print under `runa.permissions.tables.export`, preserving owning
  reads. Permissions advances to 2.1.0 with transactional declaration preparation.

## [0.6.2] - 2026-10-29

Maintenance release — RM-2026-015.

- Add explicit narrow delivery/source-security quality scopes while preserving mandatory checks, strict selected scans and existing broad group behavior.
- Share one exact production fixture across selected production acceptance and healthy-image scanning, retaining independent phase evidence, interruption recovery and scoped cleanup.
- Group compatible immutable PHP-image probes and expose preparation, build, assertion, scanning and cleanup costs in existing reports.
- Synchronize owner/customer execution guidance and repair private fixture report placement, strict Python types and coordinator environment contracts. All 47 selected final checks pass, including combined and standalone native fixture qualification.

Production plus scanning measured 874.928 seconds combined versus 1204.183 seconds separately on the same candidate: 329.255 seconds (27.3%) saved, with logged Compose creations reduced from 85 to 64. Cache/order differences limit this single-run comparison. The complete qualification graph took 3368.879 seconds and intentionally includes both extra standalone runs. Excluded backend/frontend/database/browser/knowledge-runtime groups are not claimed as PASS. Application/module versions remain unchanged; no migration or real production deployment occurred. Disposable fixtures were scoped-cleaned while preserving development/test data, caches, backups and reports.

## [0.6.1] - 2026-10-29

Maintenance release — RM-2026-013.

- Deliver bounded source-verified knowledge evidence reads, shared reader admission, explicit applicability/decision metadata and current/history/audit retrieval intents.
- Improve semantic passage retrieval and bounded local citation navigation; preserve original required evaluation sources, unknowns and customer corpus isolation.
- Reconcile current module/version guidance and consolidate owner/customer instructions around canonical public procedures while preserving customer extensions and immutable history.
- Correct Python typing, delivery acceptance expectations and evidence-bound queue lifetime handling. All 47 selected final checks pass, including real knowledge, installation/update, transport, production and strict security acceptance.

The owner excluded the full backend suite; this release makes no backend-suite PASS claim. Native segfault diagnosis remains a separately planned follow-up. Two Polish retrieval probes remain visible limitations of the English model. Platform module/application versions are unchanged; no real production deployment occurred. Harnesses scoped-cleaned their disposable resources and preserved development/test data, Qdrant/model/build caches, backups and reports.

## [0.6.0] - 2026-10-28

Feature release — RM-2026-012.

- Refactor Python packages and owner/customer/distribution tooling into typed, explicit composition with supported guarded commands and central test ownership.
- Clarify and enforce public PHP/frontend extension contracts, separate platform/module internals and preserve replaceable presentation, generators, source projection and runtime isolation.
- Synchronize handbook, maintainer guidance and both instruction products; deliberately patch existing test dependencies and Symfony Mime 8.1.10 security fixes.
- Preserve independent Auth 1.1.4, Audit 1.0.4 and Permissions 1.0.4 upgrades and migration history; keep the example application version 0.0.0 compatible with 0.6.x.

Owner-authorized closure exception: intermittent backend SIGSEGV remains deferred to RM-014 (fix 0.6.2 after RM-013). Raw final graph: 53 passed, one SIGSEGV, six blocked; separate supported checks completed the blocked scopes and full backend passed 588 tests/6117 assertions. This is accepted verification with a disclosed exception, not a fabricated complete graph PASS. Strict security and browser checks passed. No real production deployment occurred; disposable resources were scoped-cleaned and persistent data/caches/reports preserved.

## [0.5.0] - 2026-10-26

Feature release — RM-2026-011.

- Deliver Audit, Auth and Permissions Core modules at independent version 1.0.0: transactional redacted history, secure identity/session/account flows and native action grants with protected authority.
- Ship Polish/English authentication and account views, application entry/navigation, customer presentation contracts and extensible development fixtures; keep Admin management UI deferred.
- Complete resumable operator/first-administrator and module installation/update integration, forward migrations and explicit stable-version upgrade edges.
- Patch PHP, curl/Brotli and gosu runtime findings; remove temporary scanner policies, bypass callbacks and dedicated exception tests. Build nonroot PostgreSQL 18.6 with gosu 1.20/Go 1.27.3 while preserving its data layout.
- Repair verification fixture isolation, concurrency/browser races and resource lifecycle defects. All 54 selected final gates pass, including 579 backend tests, 386 browser tests, real installation/update/production acceptance and strict security/AI checks.

Platform 0.5.0 is independent of the supplied application version 0.0.0. No real production deployment occurred. Disposable acceptance resources were scoped-cleaned; active development/test data, model/build caches, backups, rollback material and reports were preserved. Native crash observations do not establish a root cause; the final verified candidate passed normally. Downstream PostgreSQL packaging retirement remains tracked by MT-2026-001.

## [0.4.2] - 2026-10-23

Maintenance release — RM-2026-010.

- Add manual --group=NAME/--all quality selection for owner and customer plan/run/final commands, including native Artisan and host transport validation.
- Resolve mandatory checks and true prerequisites with scope/command evidence, explicit exclusions, timings and closure validation; preserve legacy flag-free behavior.
- Synchronize owner/customer instructions, quality catalogues and release workflows. All 55 full final checks passed; the owner approved focused regression verification for the subsequent narrow closure-link metadata correction.

No new dependencies, product migrations, Docker topology changes or real production deployment. Scoped acceptance resources were cleaned; development/test data, caches, backups and verification evidence were preserved.

## [0.4.1] - 2026-10-23

Fix release — RM-2026-009.

- Deliver separate owner/customer agent instructions and verified customer skills through acquisition, reviewed updates and ordinary customer clones; exclude authoring templates from owner retrieval.
- Review complete delivered-file differences and apply exact consent-bound add/replace/remove actions with durable recovery, preserving application source, dependency composition, private settings and local instruction extensions.
- Enforce safe development environment-file ownership and credential consistency, including pre-generation symlink refusal; document environment creators, consumers and backup preservation.
- Deliver missing public frontend contracts and complete fresh customer knowledge, real update/team/production propagation and security acceptance. All 42 final quality checks pass.

No new dependency, product database migration/reset or real production deployment. Disposable acceptance containers, networks, image tags and volumes were scoped-cleaned; active development/test data, rollback material, Qdrant/model/build caches, backups and verification reports were preserved. Existing owner-only PostgreSQL/gosu risk applicability remains unchanged. Manual quality selection and the Core-module follow-up remain separate drafts.

## [0.4.0] - 2026-10-23

Feature release — RM-2026-006.

- Deliver module manifests, persistent registry/discovery, dependency planning, atomic lifecycle transitions, technical health and effective maintenance with guarded runtime entrypoints.
- Add PostgreSQL/storage boundaries, typed public PHP/HTTP contracts, events, optional integrations, encrypted settings, queue/work fencing, commands/schedules and structured module logging.
- Provide opt-in frontend navigation/slots, versioned management metadata, independent module versions/changelogs, guarded generators and module-aware resumable install/update execution.
- Deliver the development-only reference module, customer source selection and module developer guide; keep reference/probe and AI tooling excluded from production.
- Consolidate delivered customer testing support, repair parallel database isolation and maintenance-paused worker health, and expose per-stage durations through shared Python/Artisan quality status.
- Pass all 54 final quality gates, including real customer/native/generated-test acceptance, team propagation, production isolation, image scanning and strict AI checks; total elapsed 32 minutes 48 seconds.

The bounded owner-only PostgreSQL/gosu accepted risk MT-2026-001 remains unchanged and scanner-validated; customer security defaults remain fail-closed. Application version stays 0.0.0. New platform module registry/lifecycle/work/settings/update migrations are forward-only; no real production deployment or development/test database reset occurred. Disposable acceptance resources were scoped-cleaned; active development/test data, existing test services, rollback resources, shared/model caches, backups and verification reports were preserved.

## [0.3.1] - 2026-10-21

Fix release — RM-2026-008.

- Update development, production and management PHP to official digest-pinned 8.4.27 images for CVE-2026-109618 and CVE-2026-109619; synchronize runtime checks and rebuild/recreation guidance.
- Correct relocated dependency-tree scanner exclusions while retaining first-party source, composed locks and development dependency audits. Pin and scan the maintained Redis fixture used only for real additional-service acceptance.
- Bind the owner-approved PostgreSQL/gosu pending-upstream decision to exact current artifacts/findings/evidence/expiry; retain raw reports and fail-closed customer defaults. Track MT-2026-001 for reassessment by 2026-11-20 or earlier upstream/artifact/trust changes.
- Repair native updater fixture version selection, restore recreated-container Boost registration through existing setup and pass all 45 selected final checks, including real customer update/team/deployment, security and strict AI gates.

PostgreSQL/gosu remains an explicitly accepted owner-only pending-upstream risk, not a corrected binary or blanket customer production acceptance. No application version change, migration, database reset or real production deployment occurred. Task fixtures/images were scoped-cleaned; development/test data, rollback resources, model/shared caches, backups and verification evidence were preserved.

## [0.3.0] - 2026-10-21

Feature release — RM-2026-005.

- Separate protected platform code, customer application code, commands, migrations/seeds, tests and documentation; retain independent application/platform manifests and versions.
- Deliver verified external acquisition, resumable development/production initialization, restricted host command transport and journaled development-only platform updates with customization review and recovery.
- Deploy complete customer repository revisions with ordered forward data steps for both owners; verify teammate propagation, custom services, TLS/SMTP, failure fencing and production AI isolation.
- Complete functional, security-review and release contracts, including dedicated customer production fixtures and repaired owner test routing.

Known security risk: the owner explicitly accepted only the recorded RM-008 deferrals for local/non-public use. Raw security scan FAIL is retained: nested dependency/scanner findings and PostgreSQL gosu findings remain unresolved; PHP advisories and Redis fixture remediation also remain RM-008. This release does not establish external production readiness. Remediation and fresh verification are required before external exposure. No runtime vulnerability suppression or dependency/image update was introduced to obtain acceptance.

No real production deployment occurred. Task fixtures were scoped-cleaned; development/test data, existing images, rollback resources, Qdrant/model/build caches, backups and verification reports were preserved.

## [0.2.1] - 2026-10-20

Fix release — RM-2026-007.

- Recognize the generated emergency error document when checking development setup readiness, so an unchanged valid environment skips redundant Composer installation and frontend builds.
- Cover valid setup, missing current output, stale fingerprints and existing failure propagation with startup regression contracts; synchronize development guidance.
- Verify containerized full/fast setup, host startup/health and the complete selected local quality/strict AI gate.

No dependency, PHP/image, application schema or production change. Development/test data, knowledge/model caches, backups and reports were preserved. The observed native Composer SIGSEGV did not recur during recovery; its cause was not established by this repair.

## [0.2.0] - 2026-10-19

Feature release — RM-2026-004.

- Deliver optional Vue UI controls, feedback, forms, selection, dialogs, tables and browser-local preferences through a typed public component library.
- Provide reusable MinimalLayout/ApplicationLayout and branded HTML/text email layouts, with independent application/platform identity and versions.
- Add localized native HTTP errors, favicon and PHP-independent Nginx emergency responses with preserved API/status/security contracts.
- Support table personalization, cross-page selection, operation-provider contracts and bounded real CSV/XLSX/PDF/print exports using the approved pinned adapters.
- Verify responsive PL/EN light/dark behavior, accessibility semantics, reduced motion, interrupted interactions and independent consumers; retire temporary application previews while retaining isolated tests.
- Correct verification preparation, browser-specific diagnostic APIs and scan/build ordering; pass the complete local quality/security/production/AI gate.

No business modules or server export jobs were added. No application schema change or real production deployment occurred. Disposable simulation resources were removed; development/test data, knowledge/model caches, backups and verification reports were preserved.

## [0.1.2] - 2026-10-18

Maintenance release — RM-2026-003.

- Support durable native-Linux host/devcontainer handoffs and explicit selective build/recreation commands.
- Decouple application startup and diagnostics from optional knowledge services, with independent retrying first-index initialization and bounded degradation reporting.
- Keep development services running across editor transitions, use stable `:dev` image tags and pin the approved same-version official Nginx slim runtime consistently.
- Retire the verified unused Bookworm PostgreSQL volume, preserve active ICU data and clarify scoped preproduction operations and resource sizing.
- Synchronize current development guidance and complete host fault/recovery, editor, production simulation and security verification; remove disposable test resources.

No application schema, dependencies or numerical resource presets changed. Development data, knowledge/model caches, backups and verification reports were preserved. No real production deployment occurred.

## [0.1.1] - 2026-10-18

Fix release — RM-2026-002.

- Reconcile current-state documentation with the delivered foundation and preserve historical acceptance evidence.
- Make documentation synchronization part of implementation and require a recorded current-state review during roadmap finalization.
- Validate README version, active roadmap and production-deployment status through existing docs/release gates, with regression coverage and a narrow draft-planning exception for published release tags.
- Classify knowledge roadmap metadata from actual lifecycle files and frontmatter; correct historical roadmap search filters and refresh the development index.

No application behavior, database schema, runtime configuration or dependencies changed. No real production deployment occurred.

## [0.1.0] - 2026-10-18

Feature release — RM-2026-001.

- Establish Laravel 13, PostgreSQL with UTF8/ICU/pl-PL, and the Vue 3/TypeScript/Tailwind application foundation with Polish/English and light/dark support.
- Provide reproducible Docker development, local quality gates, Git hooks, browser tests, Laravel Boost and development-only knowledge retrieval.
- Provide isolated production containers, guided deployment, coordinated backups/restores, guarded rollback, health checks and security scanning.
- Complete host verification and final security review; preserve the exact approved gosu not-affected decision and require scoped cleanup of temporary Docker resources.

This pre-1.0 environment foundation contains no business modules and has never been deployed to real production.
