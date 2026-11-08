# Auth module changelog

## 6.0.1 — Unreleased

- Admit the verified platform0.8.0 release in the supplied>=0.7.0 <0.9.0
  compatibility range; module/API versions and runtime behavior are unchanged.

- Use distinct semantic single/bulk action-family and final-command icons. Treat
  shared HTTP document cancellation as canceled protected-activity observation,
  preserving real proof/network/availability refusal.

- Use the shared semantic action/feedback icons with visible labels and preserved
  busy, keyboard, proof and operation behavior; no backend contract/storage change.

- Use native structured management reads for password policy, addresses, sessions,
  trusted devices, exceptions and preference collections. Preference writes accept
  delivery arrays only; preserve grants, original revisions, proof and atomic Audit.
  Require Framework API ^2.1.0. These changes join the unreleased Auth 6 MAJOR.

- Initialize directly at the current schema/version; remove historical development
  upgrade scaffolding. Retain the 6.0.0 → 6.0.1 current-schema update,
  reserved declaration/Audit participation and native history/recovery refusals.
  Published Core databases with replaced migration history require explicit
  development reconciliation; no historical schema conversion is provided.

## 6.0.0 — Unreleased

- Use action-driven business reads: remove passive account/resource reads and
  same-session preference/branding/Admin cascades while retaining security checks
  and actual identity/access invalidation. Reconcile recognized stale-state refusals
  only, preserve drafts/original submission tokens, and stop delivery prefetch from
  silently replacing its reviewed revision.

- Start the own-account trusted-device list collapsed using public Accordion;
  retain mounted table state and independent trust-browser/prerequisite controls.

- Remove the redundant login introduction in Polish/English and the repeated
  registration instruction; retain activation, recovery and security guidance.
  Keep the existing optional-description presentation contract.

- Replace AuthPolicies publication/preview generation integers with semantic review identities (API 4.0.0). Require Framework API ^2.0.0; keep current grants, action-specific password proof, policy consequences and atomic history/Audit. Publish the current settings review contract without data conversion.

## 5.0.2 — Unreleased

- Consume Framework API ^1.2.0 native settings access/proof/publication contracts.
  Declare current policy read/write grants and one-use password confirmation on
  IdentitySettings; persist policy consequences/history through its owning hook.
  Remove Core Audit dispatch to Auth and the separate proof consumer. Preserve
  guarded API signatures, current encrypted settings, credentials, exception
  invalidation, native recovery and supported history validation.

## 5.0.1 — Unreleased

- End user-dialog reads on close and isolate subsequent bulk editing; retain dirty
  drafts when closing is refused. Use administrator activation terminology in both
  registration locales.

- Group synchronous admission, eligibility, factor-restriction and session-lifetime
  settings reads under short registry-locked transactions. Reuse the current
  admission's user/session locally; recheck every subsequent operation, expiry,
  revocation and rollback. Public guarded APIs and data remain compatible.
- Retain the Laravel remember-token column deliberately; Runa's remembered
  sessions continue to use the tracked operational session.

## 5.0.0 — Unreleased

- Retain in-progress authenticator enrollment when refreshed actions replace Begin with Confirm. Retain the shared full-page password-confirmation flow.

- Align protected-authority bulk tools with existing aggregate counts and omitted-account warnings; remove individual change listings and place Superadministrator last in both action toolbars.

- Replace the Auth user superadministrator checkbox section with grouped grant/revoke actions for one account or up to 100 explicitly selected accounts. Add guarded PermissionsAuthorityBatch 1.0.0 with complete-set lockout protection, stale review refusal, one password proof and atomic per-target Audit; preserve ordinary Auth bulk exclusions.

- Simplify Permissions to a readonly permission catalogue, roles and presets; combine details/editing in one dialog. Move account copy/preset and separately granted protected-authority workflows to Auth user details. Reuse guarded search, compact member selection, native source fingerprints, reviewed password-confirmed writes and atomic Audit; remove obsolete access/effective page adapters and exports.

- Use quiet outlined action buttons, including Save and confirmations. Preserve operation colors before modal admission and use purple for mixed-effect tools; final confirmation reflects the selected operation. Document the shared policy for future views.

- Adopt shared action button intents: green commit, neutral safe cancellation, yellow discard/caution, blue reads/previews, purple mixed-effect tools and red destructive effects, independent of feedback context or password proof.

- Simplify module settings Save to a confirmation question; omit the repeated differences and policy consequence messages while preserving native validation, password proof and Audit.

- Use the full workspace width for module settings and pack group cards in independent responsive columns, removing large gaps below shorter groups.

- Compact the shared module settings editor into bounded label/control rows and responsive group columns; widen the snapshot selector. Preserve source technical keys and separate PL/EN names/descriptions through authorized Admin projection, with keyboard/touch-accessible help. Clarify Auth descriptions and units without changing setting behavior.

- Remove guarded revision-history restore (AuthPolicies 3.0.0) and the local policy history viewer. Use framework-owned named settings snapshots with committed-value capture, draft preview/cancel, password-confirmed deletion and transactional Audit. Group Auth settings semantically and publish the full policy once.

- Remove redundant selected-user rule instructions and the local change-history panel; retain active rules and Account status, with history available through Audit.

- Auth 4.0.0 / AuthStepUp 2.0.0: use password-only confirmation for sensitive operations and protected places. Reserve factor challenges for login; retain new-authenticator enrollment validation. Remove factor proof modes/storage and password_change_factor. Share the existing Auth page across Admin and own account, preserving cancelled drafts, native proof consumption and Audit. Trusted-device issuance reuses the factor verified at login.

- Auth 4.0.0 / AuthPolicies 2.0.0: allow optional account-rule expiry, with null meaning indefinite and no maximum horizon for a future date. Update effective policy reads, session cleanup, status/history and Audit handling; correct the original pre-1.0 schema and current Auth consumer constraints. Space operation fields and show explicit optional/indefinite copy.

- Move selected-user rules into User with named bounded editors, separate active/history presentation and live custom rules in Account status. Add guarded AuthPolicies 2.0.0 active exception reads independent of the latest-50 history cap; preserve native proof, revisions and Audit.

- Keep directory filter controls aligned within each responsive row when field labels wrap.

- Complete own/Admin security table capabilities: chronological session sorting, local search/column filters, and separately authorized bounded Admin exports/print. Extend AuthAdministration to 2.1.0 with authorized directory ordering before cursor paging, nulls last, stable UUID ties and query-bound cursors.

- Enrich new-browser sign-in notices in Polish/English with captured safe browser/system labels, event time and guidance for unrecognized activity, preserving native multipart mail and reference-only delivery.

- Auth 4.0.0: identify trusted browsers with allowlisted browser/system labels, cookie-bound current-device state and last successful use. Share columns in Admin and the inline own-account table, including empty results. Extend AuthSecurity to 1.2.0 and correct the original pre-1.0 development migration.

- Present selected-user sessions and trusted devices with the shared account Table, authorized dates, remembered sign-in and native revocation; rename Trust to Trusted devices and retain bounded cursor navigation.

- Hide single/bulk action separators at wrapped row starts while preserving same-row separators and unclipped keyboard focus.

- Refine single/bulk action grouping into inline buttons with vertical separators, accessible category names and no visible group headings.

- Auth 4.0.0: share thematic single/bulk action grouping, ordered account-status groups and compact own/Admin preference defaults. Add AuthProfiles 2.1.0 existing-address confirmation with native bypass configuration/grants, both-factor proof, ownership/version checks and atomic Audit.

- Auth 4.0.0: share secondary-email list/inline editing through public AuthSecondaryEmailFields. Preserve occupied identifier and password-reset refusal codes, distinguish business refusals from stale revisions, and give actionable account/preference/proof feedback while retaining anonymous non-enumeration.

- Remove generic operation-scope filler, show factor-discovery skeletons and explicit action progress, and expose account refreshing for retained read feedback.

- Preserve inactive name drafts, show selected-avatar bytes/initials, consolidate account facts and rebuild email management. Hide recipient selectors with disabled secondary addresses and apply the complete review preset during explicit development seeding.

- Auth 4.0.0: align selected-user details with creation, bulk action tones and own-account identity/preferences/avatar cropping; export shared presentation fields with independent controller ownership.
- Auth 4.0.0: rebuild user details around User and Security with named user rules; add permissioned target preference reads/atomic edits and individual all-device/exception revocation. Preferences API advances additively to 1.1.0, Security to 1.2.0 and Policies to 2.0.0; preserve self-service binding, native proofs, delivery constraints and Audit.

- Omit operational editors for configuration-disabled capabilities. Keep authorized account facts read-only, hide disabled verification/factor/avatar editors and notification recipients, and dismiss withdrawn editors without losing unrelated drafts. Document the shared visibility rule in owner/customer frontend guidance. Preserve native backend enforcement.

- Simplify account creation copy and remove its optional security controls. Keep a concise outcome summary. Enable administrative email verification by default, retain its editable Auth policy control and omit creation verification entirely when unavailable. Preserve native permissions, stronger proof and Audit.

- Deliver comprehensive atomic account creation through AuthLifecycle 2.1.0 with initial password/invitation choices, independent activation/deactivation schedules, administrative email verification, initial locks and forced password change. Supply a conditional PL/EN form and access/delivery summary. Send and accept invitations before activation without issuing a session; show account_inactive only after correct login credentials. Preserve owning policy/grants/proofs, Audit and token security guards.

- Exclude all protected superadministrators from every Auth bulk user/session/trust action, including inactive or locked members. Show skipped counts, mutate only ordinary targets and fingerprint current protected membership through a bounded trusted Permissions read. Retire blocked-whole-batch preview flags. Advance AuthBulkSessions to 2.0.0 and update current consumers; individual native last-administrator guards remain unchanged.

- Refine bulk modals with compact width, paired selection/impact counts, localized loading and right-aligned confirmation.

- Group related Auth bulk controls into primary outlined buttons with explicit modal operation selection. Add atomic per-account activity/deletion/security-lock inversion with native grants, review fingerprints, Audit and deletion password proof; rename the one-way bulk action to Force password change. No periodic-policy column or account opt-out is added.

- Default Auth password expiry to 30 days on fresh initialization; retain zero as explicit disabling and preserve the independent one-time forced-change action.

- Remove independent account approval: current activity and scheduled activation/deactivation govern lifecycle admission. Remove approved/approve from storage, eligibility, creation, directory and administration; registration_activation selects automatic activation or inactive accounts awaiting ordinary administrator activation. Preserve historical Audit reads. Update the unreleased Auth 3.0.0 and Lifecycle/Administration/BulkUsers 2.0.0 contracts and all current consumers.

- Add optional locally configured automatic superadministrator fixture seeding after database rebuilds. Keep credentials outside Git, preserve existing passwords, enforce production guards and retain normal runtime password validation; only the initial development fixture permits an owner-approved shorter password.

- Remove independent account expiry in favor of scheduled deactivation, remove users.expires_at and its command/fact/filter, and preserve historical Audit reads. Reminder mail/settings/categories now use scheduled deactivation, including administrator-copy routing. Consolidate unpublished platform, Audit, Auth and Permissions migrations into final per-table CREATE definitions; initialize technical rows through seeds/initializers and recreate the disposable development database. Remove the unnecessary expiry conversion migration and settings rename machinery. Auth moves to 3.0.0 and Lifecycle/Administration/BulkUsers APIs to 2.0.0; current dependent constraints/consumers are synchronized.

## 2.1.0 — 2026-10-31

- Extend AuthBulkUsers to 1.1.0 with actual affected-account counts. Refine bulk parameter/review/password-step presentation, translate session revocation and apply semantic action order/colors. Owner-approved batch policy requires fresh password proof only for delete/restore, password/2FA reset and exception revocation; other batches retain native review, grants and Audit without extra proof. Replace the state column with deleted-row colors and a default Not deleted filter.

- Match Auth Users filters to Audit compact controls, shorten account/security labels and expose each authorized bulk command as its own button below filters. Retain selected targets and native reviews/proofs.

- Consolidate date/datetime ranges into one labelled compact field with a shared endpoint popup, valid atomic Apply and Clear/Cancel. Replace expanded Auth range filters and Audit from/until controls; preserve wire keys, timezone/DST and open endpoints.

- Publish guarded AuthAdministration 1.1.0 field-authorized account facts and AuthBulkUsers 1.0.0 with immutable BulkUserChange. Update all exact-version consumers and the source public API boundary catalogue.

- Complete Users filters across authorized facts and date ranges; show effective 2FA/enrollment/recovery, retained activity, invitation and credential state. Move creation into the directory header and all native reviewed bulk user operations below filters. Share locked owning phases, enforce current grants/features, atomic Audit/proof rollback and safe restore subject locks.

- Complete configuration-aware Users administration: authorized account/session/security dates and state, accessible detail tabs, native avatar editing, and exact table-selected session/trust revocation. Remove the separate bulk-session page and retain native grants, proofs, Audit and shared Save.

- Replace standalone content circles in history details, image cropping, entry forms and welcome navigation with shared content placeholders. Preserve specialized account geometry and Table loading.

- Hide premature empty collection messages while modal record details load; shared Admin sections supply animated loading feedback.

- Collapse long bounded collection values with the shared escaped ValuePreview; preserve explicit pagination and timestamp formatting.

- Use the shared lowercase localized choice presentation for directory account states and read-only administrative dictionaries; keep wire/export codes unchanged. Align own login/account-history outcome labels with lowercase PL/EN presentation in rows and safe details.

- Return the authorized filtered directory total before cursor/limit and forward it to Admin; retain deleted-state and profile-search authority.

- Use shared timezone-independent epoch parsing for account/session/device dates and session deadlines; format known history date changes through the application timezone. Remove misleading UTC wording from datetime field labels.

- Add guarded AuthStepUp 1.1.0 protected-section idle assertion/activity, with
  independent server-owned deadlines, session/action/security/factor isolation,
  atomic rollback and expiry-safe cleanup. Preserve fixed-age and consumed proofs.
- Deliver public useProtectedActivity with scoped trusted input, bounded event
  batching, visible-document handling, server expiry rechecks and disposal/identity
  fencing. Section owners retain current endpoint authorization and presentation.
- Add the forward section_activity edge and nullable technical timestamps without
  resetting existing credentials, identities, sessions or development data.

## 2.0.0 — 2026-10-30

- Reuse validated settings inside the current locked read for entry/security,
  password rules, session and profile projections. Batch selected session/trust
  inventories and reminder history/exception reads while retaining per-account
  Audit, current dispatch authority, limits and deadline/fingerprint checks.

- Delete successfully cleaned avatar candidates in one bounded metadata write;
  retain failed storage candidates and the current linked avatar.

- Batch own-account presentation preferences and delivery categories instead of
  querying/revalidating every entry independently. Preserve current declarations,
  account isolation, migrations/defaults and mandatory recipient restrictions.

- Keep reset beside filter fields in the shared filter region. Render declared
  event timestamps and known Auth entry dates through the application locale/
  timezone formatter, preserving canonical instants in transport and exports.

- Use shared table refresh/reset icons with Tooltips, compact automatic filters
  and dictionary Selects; open Details in guarded dialogs. Supply bounded
  CSV/XLSX/PDF/Print behind the shared `runa.permissions.tables.export` grant
  and current owning read authorization.

- Use shared FormNotifications for operation proof, history-detail/sign-in-option reads, session operation failures and administrative collection errors; retain session expiry and admission state displays.

- Consume application defaults without overwriting account/browser choices; format supplied account/session/history instants in the application timezone and reuse the public raster container inspection helper.

- Include bounded initial Admin-entry advertisement in shared shell readiness; keep content/menu coherent with account preference hydration and hide temporary theme/language controls until navigation settles. Preserve background menu revalidation and identity fences.

- Compose shared breadcrumbs from the active layout page context, with linked area/module ancestors and the current Admin/account subview, including query navigation.

- Share Welcome module presentation through the public UI source catalogue and correct the shared session shell's Admin breadcrumbs for dashboard and owner pages.

- Publish account navigation loading in the top bar, remove obsolete content tab placeholders and expose shell contentLoading/navigationLoading for initial page discovery.

- Move account subview navigation into the shared top bar via UI PageNavigation, with query deep links, browser history and pending-write protection. Keep standalone presentation usable. Included in the existing unreleased candidate; no migration or version-only upgrade edge.

- Coordinate initial application content/avatar placeholder completion inside the immediately rendered layout; retain the readiness latch across background reads and bound image failures/timeouts.

- Render layout frames immediately and keep loading placeholders local to dependent content/avatar elements; public branding and profile/image reads never hide the page frame.

- Share identity-scoped initial view/image readiness, bounded decoded-avatar fallback and nondestructive same-session refresh through the public UI contract.

- Keep the known Admin menu advertisement during same-session revalidation, ignore unchanged activity observations and hide on denial/failure or immediate identity invalidation. Preserve stale-response fencing without a second session or polling loop.

- Match operation-proof page heading focus to existing Auth screens and give shared proof cancellation its red danger/error outline button. Preserve reading focus and normal keyboard indicators on controls.

- Follow the shared application language in administrative defaults and entry proof, retain translated policy setting names, and notify guest preference subscribers after write/reset so all mounted locale consumers stay synchronized.

- Supply replaceable AuthOperationProofPage in MinimalLayout and PL/EN inline/page controls/errors; share the existing proof controller, support password/factor/both, and fence aborted factor reads even for an unchanged identity. Expose typed presentation contracts and an optional session-shell admission slot without starting another session.

- Name the account-menu Admin entry `Panel administratora` / `Administrator panel` consistently with the Admin catalogue.

- Supply private source-declared ProfilePage/ProfileEditor for native atomic naming/login replacement. Default record reads select the actor; explicit authorized reads use public UUIDs. Add complete Auth-owned Users, policy/history/restore and selected/all session administration. Supply guarded AuthAdministration 1.0.0 with bounded cursor directory, grant-filtered profile and lifecycle reads; preserve native independent action/Audit/proof protocols. Include these additive changes in the unreleased 2.0.0 candidate without a new migration or version-only upgrade edge.

- Add public useOperationProof/AuthOperationProof for source-declared administrative action proof. Reuse native security/step-up/email-code endpoints and current eligible methods; erase password/code on settlement/invalidation and never replay an owning business write. The caller retains session admission and lifecycle identity.
- Extract public ApplicationSessionShell; AuthApplication accepts a shared session plus typed navigation/current/locale/account-menu inputs without another session/activity owner.
- Retain account/loading replacement and native session security; advertise Admin entry only in the account dropdown through the fixed proof-free availability read.
- Expose authorityRevision for successful session refreshes and share the application language with Admin controls. The unreleased 2.0.0 target keeps its existing native_transactions edge; no extra version or migration is introduced.

- Use native owner lock/validate/apply phases for standalone and shared transactions; all owner reviews validate before any business write.
- Intentionally replace ambient nesting of AuthProfiles.edit with source-owned ModuleTransactions composition; the guarded write API version is 2.0.0 and standalone calls require no ambient transaction. Other guarded API versions remain unchanged.
- Require platform >=0.7.0 <0.8.0 for the new framework protocol. Preserve strong action proof, current native authority and same-transaction Audit; add a retained-source native_transactions forward edge with no schema change.

## 1.1.5 — 2026-10-30

- Widen platform compatibility to <0.8.0, preserving the >=0.4.2 lower bound and existing API contracts.
- Append the source-only admin_platform forward edge; no schema or data transformation.
- Include Admin in the manifest-owned welcome catalogue with the existing adjustments icon; rendered acceptance remains pending.

## 1.1.4 — 2026-10-28

- Preserve registration privacy and session behavior while completing typed request, model, settings and mail contracts; handle nullable profile names safely.
- Add the source-only verification_contracts forward edge without schema or data changes.

## 1.1.3 — 2026-10-28

- Resolve writable controller refs explicitly in supplied account views; preserve controller signatures, reactive lifetimes and writes.
- Correct supplied Vue attribute order and local names for the enforced source checks.
- Add the source-only frontend_source_checks forward edge without schema or data changes.

## 1.1.2 — 2026-10-28

- Support platform 0.6.x explicitly while retaining public APIs and all historical upgrade identities.
- Record the source-only platform_delivery edge; production images exclude developer implementations and raw module frontend sources. No schema or data transformation.

## 1.1.1 — 2026-10-28

- Separate internal account profile/conflict, list/history/trust, validation and delivery collaborators behind the unchanged public controller; retain shared cancellation, revisions, draft reconciliation and explicit writes.
- Own account preference transport/session composition and the welcome catalogue in Auth; generic preference storage remains independent.
- Add the source-only account_orchestration forward edge without schema or data changes.

## 1.1.0 — 2026-10-28

- Publish complete named frontend controller, replacement props/slots/events and cropper handle types through the existing public facade; preserve all existing members and Vue component replacements.
- Type supplied wrappers and security components from those contracts; retain keyed lifetimes and subtree fallback behavior.
- Add the source-only frontend_contracts forward edge without schema or data changes.

## 1.0.3 — 2026-10-28

- Separate private work handlers/policy and HTTP controllers/resources from declarations; retain public contract identities and guarded behavior.
- Preserve historical upgrade handlers, edges and migration bytes; add the source-only execution_layout edge without schema or data changes.

## 1.0.2 — 2026-10-28

- Reconcile internal platform collaborators with the module framework and Core integration homes; public contracts and runtime behavior remain compatible.
- Add a source-only forward edge with no schema or data transformation.

## 1.0.1 — 2026-10-27

- Relocate module test metadata and cases to the central owner test tree; runtime APIs and data remain unchanged.

## 1.0.0

Prepare the first stable Auth release for verified closure of RM-2026-011.
Preserve the delivered public contracts and add the source-only 0.23.1 -> 1.0.0
update edge; no schema or data transformation. Align required Core module dependencies to ^1.0.0.

## 0.23.1

Preserve validated password confirmation through the registration adapter so the
shared credential validator receives the complete confirmed input. Correct the
typed development-account listing contract. Source-only forward edge; no schema change.

## 0.23.0

Complete concrete non-display preference writes and per-setting resets, factor and verification state, account lifecycle and policy exception history. Show precise event labels; password summaries do not open value details.
Source-only forward edge; no schema migration.

## 0.22.0

Open details for every account-history event through a replaceable public dialog.
Capture approved profile/address/avatar/notification changes atomically with Audit;
security operations describe the event without credential values. Historical events
without captured pairs remain explicit about missing details. Requires Audit ^0.6.0;
source-only forward edge, no storage migration.

## 0.21.15

Label notification preference history as Preferences changed, with distinct complete
and administrative reset labels in PL/EN. Source-only forward edge; history remains
a safe summary projection without a detail endpoint.

## 0.21.14

Include recorded email notification preference changes, complete preference resets
and administrative resets in own-account history. Existing Audit entries become
visible without backfilling events. Language/theme changes remain outside history.
Source-only forward edge; no schema or Audit projection/authorization changes.

## 0.21.13

Target request limits at declared entry/proof operations, with independent source
buckets. Ordinary preferences, profile/history and session activity do not consume
the entry cap. A neutral optional adapter supports exclusive future Throttling
ownership without disabling intrinsic Auth protections. Consolidate preference tab
entry/refresh into one delivery read while preserving external invalidation/drafts.
Source-only forward edge; no schema or permission changes.

## 0.21.12

Remove the redundant account dropdown caption. Show account deactivation actions
in warning outline and deletion in danger outline, including their confirmations.
Source-only forward edge; no behavior, schema or security-policy changes.

## 0.21.11

Respect disabled welcome policy in authenticated logo destinations and breadcrumbs,
and in public Auth layout branding. Until public navigation policy is available,
use /app and omit Home. Make the avatar skeleton fill its exact circular bounds
and pulse over a transparent loading background, matching content skeletons.
Source-only forward edge; no schema or session/security-policy change.

## 0.21.10

Keep a shared pulsing avatar placeholder through optional profile discovery and
image loading in the application header and user dropdown. Add localized
Home / Application / User panel breadcrumbs to the authenticated entry/account views. Initials appear only
for absent/failed images; optional profile reads still never block the shell.
Source-only forward edge; no schema, identity or security-policy change.

## 0.21.9

Replace the welcome placeholder with the .runa heading, a concise PL/EN platform
introduction and source-owned module icons with localized, keyboard-accessible
tooltips. The platform catalogue reads public module manifests at build time;
installation state, private grants and application modules are not exposed.
Existing welcome presentation replacement and session orchestration are retained.
Source-only forward edge; no schema or security-policy change.

## 0.21.8

Use one account refresh pipeline when entering session/history table tabs instead
of reading rows both before and during refresh. Keep one continuous loading state
and center the shared table spinner without visible text, retaining its accessible
label. Source-only forward edge; no schema or security-policy change.

## 0.21.7

Show the shared Table loading spinner while sessions, account history or trusted
devices are being read. Empty results appear only after the request completes.
Independent read indicators do not treat mutations as table reads. Source-only
forward edge; no schema or security-policy change.

## 0.21.6

Keep ApplicationLayout mounted from the first session read, with structural account
Card skeletons through account initialization and skeleton notification rows.
Expose replaceable account loading presentation and a loading slot for custom
application content. Protected forms still mount only after session validation.
Source-only forward edge; no schema or security-policy change.

## 0.21.5

Repair strict TypeScript compilation of Auth presentation and its tests. Bind
controller form/heading refs explicitly through callbacks and type the exposed
Form instance used by delivery preferences. Preserve validation focus, public
contracts and security behavior. Source-only forward edge; no schema change.

## 0.21.4

Place secondary-address status badges beside the email, wrapping when necessary
on narrow screens. Source-only forward edge; no behavior/schema change.

## 0.21.3

Remove the redundant trusted-device heading inside the two-factor Card. Retain
trust actions and dialog titles. Source-only forward edge; no behavior/schema change.

## 0.21.2

Remove trailing padding from the final factor row so the following section uses
only the shared Card spacing. Source-only forward edge; no behavior/schema change.

## 0.21.1

Remove redundant account helper/status paragraphs and byte counts from password
hints while retaining dynamic minimum/complexity requirements and all validation.
Center the avatar preview. Show device management only while the guarded current
trusted-device list has at least one active item; refresh that list at account
initialization and after security changes. Source-only forward edge; no schema,
security policy, public API or dependency change.

## 0.21.0

Add lifecycle-fenced automatic account read refresh on visible return/tab changes
and rejected conflicts, preserving dirty fields/files/proofs and merging disjoint
changes. Expose refresh and local detail/avatar conflict resolution to replacement
presentations. Serialize delivery and presentation writes with one preference
revision queue; refresh rejected writes without replay. Use shared FormConflict
for overlapping edits instead of page reload actions. Source-only forward edge;
no database, security policy or external dependency change.

## 0.20.1

Stack Preferences Cards at every width, remove redundant display/save helper text
and the required-message summary, and repair multiple recipient selection with an
explicit Boolean Select prop. Mandatory security delivery remains primary-only.
Source-only forward edge; no schema, dependency or public API change.

## 0.20.0

Add explicit own-account reset_all for presentation and delivery defaults in one
revision-checked transaction with Audit. Preserve existing scoped/presentation
reset semantics. Put confirmed full reset in the Preferences header; use direct
language/theme choices and independent inline delivery drafts with Save/Revert.
Source-only forward edge; no schema or external dependency change.

## 0.19.1

Place Preferences beside Account and security and rebuild its default view with
shared appearance/language, defaults and email delivery Cards. Optional delivery
categories use independent modal drafts, verified recipients and explicit saves;
mandatory/sensitive categories stay primary-only. Reset requires modal confirmation.
Reuse existing preference persistence, cover, revision and recovery contracts.
Source-only forward edge; no data, policy, backend API or dependency change.

## 0.19.0

Move all two-factor actions into guarded dialogs; group trusted devices under the
factor card with usable-enrollment gating. Add independent trustedRows/trustedCursor,
canTrustDevice and explicit load/revoke controller methods; keep legacy trust tab
seams for custom presentations. Preserve fresh factor proof and ephemeral secrets.
Source-only forward edge; no data/security policy change.

## 0.18.2

Use concise End actions for all own sessions and the global default table sorting
contract. Sort created/expiry/history dates chronologically using original instants,
independently of localized presentation. Trusted-device actions remain Revoke.
Source-only forward edge; session authorization, revocation and data stay unchanged.

## 0.18.1

Apply the shared native Runa Markdown email layout to direct entry, profile/address
and security-notice delivery. Reuse configured branding, footer, locale and automatic
plain text; retain complete fragment URL fallbacks and readable OTP codes. Preserve
private recipient rechecks, synchronous single-attempt delivery and the optional
SecurityMailDelivery adapter contract. Source-only forward edge; no data change.

## 0.18.0

Integrate secondary notification addresses into the Account details card and remove
its separate default navigation tab. Add status badges, compact add/edit fields,
explicit verification reissue and modal-confirmed removal. Expose saveSecondary
and secondaryErrors for isolated versioned writes that preserve identity,
credential and avatar drafts throughout pending requests and failures. Retain
legacy addresses/choose/submit controller seams for customer presentations.
Source-only forward edge; existing backend policy, verification and routing remain.

## 0.17.0

Use public AuthRecoveryCodesDialog for both first TOTP enrollment and recovery-code
replacement, including required entry flows. Require explicit saved acknowledgement;
keep codes private, print-hidden and modal-bound. Move avatar deletion confirmation
copy into the modal body. Source-only forward edge; no authority/data changes.

## 0.16.0

Add a locally rendered, private SVG QR image to the existing no-store TOTP
begin response, using Fortify's installed BaconQrCode dependency. Keep the manual
key/otpauth URI and activation proofs unchanged. Expose ephemeral qrCode/enrollmentQr
controller refs and public AuthTotpSetup for account and required enrollment;
clear images with secrets on completion/cancel/switch/logout/disposal and omit
setup material from print. No external QR service, new dependency or stored changes.

## 0.15.1

Stack account details/password independently of avatar/security card heights.
Replace the two-factor operation select with explicit method status and setup/disable
actions. Require shared modal confirmation before avatar deletion. Independent
password saves ignore unrelated factor drafts. Source-only forward edge; local
manual-review feature settings remain outside module/production defaults.

## 0.15.0

Expose optional guarded profile presentation through useApplicationSession and
render private avatars in the shell trigger/dropdown using shared Avatar. Refresh
both from same-account nonstale profile reads after save/remove; clear on session
pause/logout/switch and fall back to initials on image errors. Source-only forward
edge; no backend API, data/settings or session-authority change.

## 0.14.2

Unify account, entry and avatar form outcomes through public top-right
FormNotifications. Remove duplicate success/error banners, retain linked field
validation, persistent failures and explicit recovery actions. Modal notifications
remain inside their focus boundary. Source-only forward edge; no stored changes.

## 0.14.1

Open avatar cropping in the shared modal with a bright circular frame and dimmed
surrounding image. Replace sliders with dragging, wheel/pinch zoom and keyboard
alternatives. Apply/Cancel remain local; only Save uploads the accepted crop.
Preserve public crop() output and bounded-file cleanup; source-only forward edge.

## 0.14.0

Add four shared account cards with independently scoped submits/credential drafts,
shared success alerts/toasts and local avatar cropping (20 MB originals, 512px PNG
output). Add compatible saveSection/AccountSection headless contracts; retain the
previous task and combined-submit APIs. Fix avatar false positives by inspecting
container metadata and boundaries instead of scanning compressed pixels for code.
Source-only 0.13.7 -> 0.14.0 edge; no settings, schema or external dependency change.

## 0.13.7

Consolidate account details, avatar, password and verification into one fluid-width
editor with visible fields and one save action. Preserve guarded individual APIs,
validate the complete edit before writes and stop on failure without replay;
report partial completion explicitly. Keep sessions/preferences unchanged.
Source-only forward edge; existing headless task APIs remain compatible.

## 0.13.6

Recognize explicit shared-HTTP document cancellation in entry/account/welcome/session
flows without false failure alerts on the departing page. Preserve live failures,
secret cleanup and no automatic write replay. Source-only forward edge.

## 0.13.5

Leave default application content empty, remove sidebar account navigation and name
self-service User panel / Panel użytkownika. Refine dropdown identity/action rows
with icons and accessible native navigation/sign-out. Source-only forward edge.

## 0.13.4

Use outlined entry/account form actions and shared quiet TextLink navigation for
welcome and Auth forms. Preserve semantics, policy, proof and keyboard interaction.
Source-only 0.13.3 -> 0.13.4 edge; no data/settings change.

## 0.13.3

Remove the interactive focus outline from programmatically focused entry/account
headings while retaining heading focus announcements, tabindex=-1 and visible
keyboard focus on actual controls. Source-only 0.13.2 -> 0.13.3 edge.

## 0.13.2

Render welcome header entry actions as quiet text anchors with hover/focus and
44px touch targets, retaining location, session and registration policy.
Source-only 0.13.1 -> 0.13.2 edge; no data/settings change.

## 0.13.1

Move welcome sign-in, conditional registration and authenticated entry links into
the default header beside locale/theme controls through the public MinimalLayout
header-actions slot. Preserve session/policy behavior and responsive wrapping.
Source-only 0.13.0 -> 0.13.1 edge; no data/settings change.

## 0.13.0

Add public subtree presentation replacement, headless entry/account/welcome/session
controllers and default application composition. Reuse the existing CSRF, restrictions,
private fragments, session fencing and account cleanup for customer-owned views.
Keep unchanged defaults available. Source-only 0.12.0 -> 0.13.0 forward edge;
no storage/settings change or new required dependency.

## 0.12.0

Add explicit local DevelopmentAccounts/contributor scenarios, owned transactional provenance, safe account discovery and hidden initial password setup using real first-administrator authority. Preserve existing credentials and security policy; production creates only empty ledger storage and refuses fixture execution. Add forward 0.11.0 -> 0.12.0 preparation without a Permissions dependency.

## 0.11.0

Integrate trusted first-administrator/recovery authority with the actual Permissions system role and guarded Permissions-selected bulk sessions. Add resumable encrypted initial invitations alongside private no-mail CLI handoff, safe identity/session/policy metadata, and source-only 0.10.1 -> 0.11.0 preparation. Audit ^0.5.0 supplies metadata; public API signatures remain unchanged and no reverse required Permissions dependency is added.

## 0.10.1

Correct neutral authority participation to distinguish actual deactivation/deletion,
factor reset and forced reverification from non-removing creation/activation/restore
and profile/invitation/avatar work. Integrate the trusted Permissions invariant
without a reverse dependency. Scheduled last-admin refusal records independent
Audit and continues other due accounts; self-service persists its refusal after
root rollback. Add source-only 0.10.0 -> 0.10.1 edge; public Auth API signatures,
storage and policies remain compatible. Security locks/time expiry remain independent.

## 0.10.0

Add source-owned localized administrative permission declarations and the trusted non-recursive AuthorizationIdentity eligibility contract for the framework authorization bridge. Add source-only 0.9.0 -> 0.10.0 upgrade and require Audit ^0.4.0. No reverse Permissions dependency, new schema, credential exposure or automatic grant.

## 0.9.0

Add public welcome/application/account composition, exact named application/account
GETs, session-aware welcome navigation and post-security internal returns. Add
public_welcome_enabled (true by default) and the 0.8.0 -> 0.9.0 defaults upgrade.
Compose profile/sign-out in the existing user dropdown and deliver versioned own
profile/email/avatar/security/sessions/trust/preferences/private-history views.
Add throttled real-activity/idle warnings, 401/restricted reauthentication, safe
account-bound memory drafts with explicit review, and logout/late-response cleanup.
No migration, new dependency, permission shortcut or Admin UI.

## 0.8.0

Add lazy public AuthEntry composition and exact named entry pages, safe public form
options, compatible own-security flags, login/registration/recovery/invitation/
verification/address confirmation and restricted factor/password screens. Reuse
MinimalLayout and shared PL/EN themed form controls. Consume fragments without GET
writes or browser persistence; fence late responses, validate redirects and require
explicit retry after CSRF/conflict errors. Add the source-only 0.7.0 -> 0.8.0 edge;
no migration, settings change, dependency or Admin UI. Account UI remains separate.

## 0.7.0

Add AuthPolicies/AuthBulkSessions 1.0.0, mandatory transactional policy history
and pre-publication step-up authorization, comparison/consequence previews, new-version
restore, future-only/force-change complexity handling and bounded versioned account
lifetime exceptions with immediate expiry/revalidation. Add atomic preview-bound
bulk revocation, idempotent scheduled lifecycle materialization and trusted local
operator initialize/recover/restore-policy commands with private single-use handoff,
external disablement and pending-recovery login fencing. Add the explicit 0.6.0 ->
0.7.0 storage/defaults edge and neutral OperatorAuthority integration contract;
actual Permissions grants and installer wiring remain in their integration point.
No new dependency, infrastructure service or Admin UI.

## 0.6.0

Add AuthPreferences/AuthDelivery 1.0.0, source-owned versioned preference/category
declarations, PostgreSQL account revisions, bounded validation/migration, own
snapshot/write/reset/explicit guest adoption and audited permissioned admin reset.
Adapt display/navigation/statistics/table consumers to an account cache with
revision-ordered writes, guest separation, reset tombstones, late-response fencing
and visible failure/reload feedback. Add email-only category/address routing,
mandatory primary recipients, reference-only security notices, bounded idempotent
expiry reminders, new-browser HMAC inventory and single-attempt mail with a future
trusted delivery adapter/direct fallback. Add forward storage/settings upgrade
0.5.0 -> 0.6.0. Existing API signatures/framework API/Audit requirement stay unchanged;
policy/UI/Permissions integration remains assigned to later roadmap points.

## 0.5.0

Add private versioned profile, configurable naming/login self-edits, primary-address
confirmation with optional old consent and captured session policy, independently
verified notification-only secondaries, force-reverification, authenticated private
avatar storage/cleanup, optional guarded self lifecycle and independently enabled
safe own login/account history. Add permissioned AuthProfiles 1.0.0 administrative
contracts with specific both-factor email step-up/bypass permission and old-primary
notice; administrative replacement revokes all sessions/trust. Add profile forward
migration and reserved settings upgrade, current/new-account verification policy,
address ownership arbitration across create/restore/change, and credential-driven
pending-address cancellation. Require Audit ^0.3.0 for OwnHistory; existing public
API signatures and framework API remain compatible. UI/preferences/Permissions
integration remain separate roadmap points.

## 0.4.0

Add shared password complexity/history/expiry/change/recovery, TOTP with transactional
replay counters and hashed single-use recovery, optional session-bound email OTP,
required-method enforcement, revocable device trust and AuthStepUp 1.0.0 with
session/action-bound TTL or every-action proofs. Add protected AuthSecurity 1.0.0
for email-only administrative reset, privileged factor recovery and device reads/
revocation. Reset requires current authority plus every-action both-factor step-up;
ordinary administrative access remains denied until Permissions. Add the forward
credential migration and reserved 0.3.0 -> 0.4.0 settings upgrade. Existing public
identity/lifecycle/invitation/session signatures and framework API stay compatible.

## 0.3.0

Add transactional tracked session admission, unlimited/default or capped concurrency
with oldest eviction, captured absolute/current idle deadlines, optional persistence
of the same remembered session cookie, session proof/generation checks on ingress,
own list/revoke/activity APIs and protected AuthSessions 1.0.0 admin contract.
Add technical session/trust/login-window storage, lifecycle revocation, bounded
cleanup and configurable privacy-preserving account/source abuse windows with
bounded temporary locks and optional review escalation. Add explicit 0.2.0 -> 0.3.0
settings-default upgrade; untracked older sessions require reauthentication.
Existing APIs remain compatible. Trust issuance, passwords/2FA, UI and Permissions
remain separate following points.

## 0.2.0

Add pinned Fortify integration, guarded named cookie/session/CSRF entry transport,
registration with independent approval/verification, single-use hashed invitations
and verification, protected invitation resend/invalidation API 1.0.0 and queued
eligibility-aware single-attempt mail with bounded optional reminders. Add owned
entry-token migration and explicit 0.1.0 -> 0.2.0 settings-default upgrade with
transactional Audit. Preserve existing identity/lifecycle API signatures; add native
verification interface without an unaudited mark-verified shortcut. No Auth UI,
operational session controls, remember-me, password recovery/2FA or Permissions yet.

## 0.1.0

Initial Core identity/lifecycle foundations requiring Audit ^0.2.0. Owned PostgreSQL
users, immutable UUIDv7 references, compatible Authenticatable model, exclusive
naming alternatives, normalized email/login claims with race-safe reservation/reuse
and conflict-safe inactive restoration. Independent current-time eligibility,
versioned audited lifecycle Actions, security revocation generations and neutral
authority integration. Permission-protected AuthIdentities/AuthLifecycle APIs 1.0.0,
initial identifier settings and transactional fenced initialization without users.
No authentication HTTP transport, credential flow, operational sessions or UI yet.
