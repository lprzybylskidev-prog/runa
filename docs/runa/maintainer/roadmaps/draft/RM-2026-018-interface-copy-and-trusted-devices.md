---
id: RM-2026-018
title: Purposeful interface copy, compact trusted devices and simpler data refresh
status: draft
branch: roadmap/RM-2026-018-interface-copy-and-trusted-devices
created_at: 2026-11-07
activated_at: null
closed_at: null
release_type: fix
proposed_version: 0.8.1
quality_groups: docs,backend,frontend,e2e
quality_rationale: Proposed scope covers interface copy, trusted-device disclosure, removal of passive view refresh and existing backend stale-write contracts, including action/conflict regressions and security-boundary preservation. Include mandatory checks and real prerequisites; reassess actual changes before final execution. No unrelated infrastructure acceptance.
impact_areas:
  - frontend
  - backend
  - documentation
---

# RM-2026-018 — Purposeful interface copy, compact trusted devices and simpler data refresh

## Overview

### What and why

Revisit low-value interface text left after RM-2026-017 and reduce the initial
visual weight of the account security view. The login instruction "Podaj dane
swojego konta." repeats the form's obvious purpose. The trusted-device table
exposes filters, export/view controls and pagination before the user needs them.
Remove proactive data-freshness reads on browser-tab return/focus and the indirect
refresh cascades they trigger. The backend validates an operation against current
state; the frontend sends the user's original version/review token, displays a
meaningful refusal and only then refreshes the affected data without replaying
the rejected operation or losing the draft.

This is a separately planned correction after the projected RM-2026-017 release
0.8.0. Current platform VERSION remains 0.7.0; neither this draft nor its target
changes runtime versions. RM-2026-017 remains the only active roadmap.

### Scope

- Review the supplied root, Auth entry/account, Admin, Permissions, Audit and
  optional reference interfaces for redundant introductions, descriptions and
  helper text in both Polish and English. Include shared components and native
  error/mail presentation where they contain equivalent task introductions.
- Remove text that adds no actionable information, starting with loginHelp.
  Preserve meaningful task labels, constraints, security explanations and outcomes.
- Keep trusted devices in the existing table, inside an initially collapsed
  disclosure in the user's account security view.
- Simplify supplied view controllers and their shared session/presentation
  consumers so a passive return/focus does not refresh business data, preferences
  or branding. Keep security/session admission separate from data freshness.
- Preserve and verify existing backend concurrency and integrity checks for
  inserts, updates and deletes, with reason-specific PL/EN conflict feedback and
  affected-data refresh after an actual conflict.
- Synchronize affected current-state guidance, presentation contracts, meaningful
  regression cases and independent module version/changelog assessments.

### Out of scope

Broad visual redesign; new table, accordion or authentication systems; new
dependencies; changes to account linking, security eligibility, authorization,
trusted-device lifetime or persistence; migration/reset; SIGSEGV tooling;
production deployment. Do not remove necessary instructions just to reduce text.
Do not expand the disclosure change to all platform tables without agreement.
No new freshness cache, polling interval, age threshold, refresh coordinator or
concurrency framework. Retain current initial loads, explicit reads and security
checks; this scope does not authorize weakening backend write admission.

## Specification

### Interface copy review

Apply the existing [interface copy standard](../../../handbook/design/visual-direction.md#interface-copy-and-preference-controls).
Inventory view families and shared text owners; for each candidate record the
rendered location, purpose, PL/EN text and removal/retention rationale. A source
count or a blanket "no defect" conclusion is insufficient. Inspect relevant
rendered contexts using bounded observations; full browser matrices remain final.

Remove the login introduction "Podaj dane swojego konta." / "Enter your account
credentials." and its unused owning catalogue/description path as appropriate.
Do not replace it with another sentence restating the heading or field labels.
Review analogous entry and account descriptions, rather than fixing this string
alone. Keep security consequences, recovery/refusal guidance, unusual input
requirements, validation, accessible names and genuine results.

### Trusted-device table disclosure

The target is the existing "Zaufane urządzenia" / "Trusted devices" section in
`src/runa/modules/auth/frontend/AuthAccountSecurity.vue`. The owner explicitly
confirmed that "linked accounts" in the initial request meant this table.

- On initial entry, show a clearly named disclosure control with its table
  collapsed. Reuse the public Accordion contract from
  [content containers](../../../handbook/development/frontend/components.md#content-containers-and-navigation).
- Expanding exposes the existing table and its permitted controls. Preserve
  filters, pagination, view configuration, refresh, authorized exports and
  revocation behavior. Closing/reopening during the same view must preserve
  existing table state; ordinary refresh must not force it open.
- Scope the disclosure to the device list. Keep the trust-browser action and
  meaningful two-factor prerequisite explanation outside the hidden list. Do
  not change the security rules or duplicate session/security orchestration.
- Preserve configuration-based omission of the entire disabled capability. Keep
  keyboard operation, expanded/control semantics, hidden-content focus handling,
  reduced motion, mobile/tablet/desktop and both themes/locales usable.
- Preserve existing loading/readiness and identity/access-loss clearing. Hidden
  content does not authorize retaining protected data after access loss.

### Backend-owned consistency and action-driven refresh

The owner explicitly replaces the current blanket requirement to refresh visible
views on return/focus. A displayed snapshot may remain older than database state.
The frontend does not continuously determine whether that snapshot is current.
For a mutation it still sends the original expected version/revision or reviewed
fingerprint; it must not silently fetch and substitute a newer token to make an
old draft pass validation.

At execution, the backend validates current authority, availability, expected
version/review and business constraints within the owning transaction/locks.
Cover inserts, updates and deletes according to their semantics: insertion may
require uniqueness/reference checks rather than a preexisting record version;
bulk actions must revalidate the reviewed set/effects. Do not add versions
mechanically to idempotent actions. Reuse and correct existing owning safeguards
where necessary; no new concurrency system is planned.

On an actual stale-state rejection, return a stable specific error code. The
frontend shows a clear PL/EN explanation that the action was not performed and
refreshes only the affected data. Preserve dirty values and offer existing local
conflict resolution where necessary; new server values must not silently replace
the draft or advance its submission token without reconciliation. Never replay
the write, hide the refusal behind successful refresh, or retry it with a fresh
version. Treat removed targets explicitly. Business refusals sharing HTTP 409
retain their own messages and do not imply a data-version conflict.

Retain initial reads on view/section entry, user-requested refresh, filter/sort/
pagination reads, explicit operation previews and updates after the user's own
successful action. Preserve refresh-in-place presentation and draft retention for
these real reads; the reported loading placement is not itself a demonstrated
defect. Do not replace removed passive reads with timers or delayed equivalents.

#### Source-grounded removal and separation targets

The 2026-11-07 read-only investigation identified these owners; recheck actual
source after the predecessor closes rather than freezing today's implementation:

- `auth/frontend/useAuthAccount.ts`: remove broad account/list refresh on
  visibilitychange, focus and pageshow, plus trailing-read state used solely by
  those triggers. Retain action/conflict/explicit reads and their cancellation.
- `admin/frontend/useAdminResource.ts`: remove the visibility-triggered last-query
  read. Review blanket 409 invalidation alongside `useAdminForm.ts`; route actual
  stale-state errors to targeted conflict handling instead of refreshing on every
  unrelated business refusal.
- `auth/frontend/useApplicationSession.ts` and Admin's `useAdmin.ts`,
  `useAdminPage.ts`, `useAdminResource.ts`: separate security checks from the
  unconditional authorityRevision-to-bootstrap/page/resource refresh cascade.
  A successful check of the same session must not itself reload business views.
  Retain real identity/access-change invalidation and late-response fences.
- `auth/frontend/preferences/accountPreferences.ts`: avoid reloading an unchanged
  account's preference snapshot merely because a session check succeeded.
- Shared `resources/js/runa/ui/createApplication.ts`: remove focus/visibility and
  incidental history/session-driven public presentation refresh. Keep initial
  hydration, real identity transitions and refresh following an applicable own
  settings mutation. Session reads must not become branding refresh triggers.

These are paths under `src/runa/modules` except the explicitly shared resources
path. Review supplied App/reference consumers and analogous controllers too.
Keep necessary session/protected-place expiry and access checks, 401/403 handling,
and clearing of protected content on detected identity/access loss. Do not delete
all visibility/focus listeners indiscriminately: protected activity, modal focus
and decorative animation have independent purposes. No data-freshness reload may
be reintroduced through a security check's successful completion.

#### Guidance and acceptance

Update the owning public forms/reconciliation guide, affected loading/module/API
guides and root owner/customer instruction products and skill references in the
same implementation point. Replace obsolete passive-refresh rules rather than
appending contradictory exceptions. Keep one canonical procedure and retain only
reconciliation machinery needed for explicit reads, own actions and real conflicts.

Author regressions proving that focus/tab return does not fetch business data
through either direct listeners or session/preference/presentation cascades;
filters, pagination, manual refresh and own-action results still work. Include
two independent users/sessions: X changes or removes data while Y keeps the old
snapshot; Y's operation receives the correct backend refusal, shows the specific
message, refreshes affected data and preserves the draft without replay. Cover
insert uniqueness/reference conflicts, reviewed bulk changes, non-stale 409
refusals, actual access loss and security checks that do not reload business data.
Existing conflict tests are evidence to inspect, not a fresh PASS. Complete
backend/frontend/E2E verification remains the final point.

### Decisions

- 2026-11-07 — Owner requested a separate roadmap for 0.8.1 covering low-value
  copy review and collapsing the table shown earlier in account security.
- 2026-11-07 — Owner explicitly confirmed the target as Trusted devices and
  approved `release_type: fix` / `proposed_version: 0.8.1`: PATCH after the planned
  0.8.0 predecessor, for interface corrections rather than a new capability.
- 2026-11-07 — Draft preparation only. No activation or implementation approval
  is inferred; the separate RM-2026-017 point-9 execution deferral is unchanged.
- 2026-11-07 — Owner approved adding the data-refresh simplification after a
  read-only source review. Backend owns consistency at operation execution;
  frontend supplies the original version/review, reports actual conflicts and
  then refreshes affected data. Remove proactive view-freshness behavior while
  retaining separate session/access safeguards. This is draft scope approval,
  not activation. The agreed fix/0.8.1 projection is unchanged.

## Dependencies and ownership

Predecessor: [RM-2026-017](../active/RM-2026-017-platform-quality-and-workflows.md).
Refresh its link after closure, reconcile actual release order and target version
before activation, and inspect current code instead of assuming today's paths or
rendered state remain unchanged. Do not reopen closed evidence.

Platform presentation belongs in `src/runa`; retain supported customer replacement
and composition seams. Reuse existing shared components rather than exporting new
ones solely for this correction. Assess changed modules' independent versions,
dependent ranges and central/module changelogs from the actual changed contract.
Review owner/customer instruction products in the owning point; use references
to canonical copy/component guidance instead of duplicating it.

## Implementation plan

Implementation points deliver source, meaningful tests and synchronized docs.
Only small focused checks resolving concrete uncertainty belong there; broad
suites, static analysis, acceptance matrices and quality gates remain in point 6.
Mandatory commit hooks remain in force.

- [ ] 1. Review supplied interface text and remove demonstrated filler.
  - Read the active scope, query local knowledge and load relevant frontend
    guidance. Produce the view/candidate disposition inventory and bounded
    rendered evidence; correct login and analogous PL/EN text owners.
  - Preserve useful instructions, accessibility and security messages. Update
    affected contracts, meaningful regressions, documentation and version records;
    assess both instruction products and commit the coherent correction.
- [ ] 2. Collapse the trusted-device table on initial account entry.
  - Reuse the established Accordion and existing native table/security owners.
    Author regression coverage for initial collapse, keyboard expansion,
    close/reopen state preservation, refresh and access/configuration boundaries.
  - Synchronize presentation guidance, module assessment and tests in the same
    committed point. No new runner, state library or dependency.
- [ ] 3. Simplify data refresh and preserve backend-owned conflict handling.
  - Remove the direct passive refresh triggers and indirect cascades specified
    above; remove obsolete state/tests rather than retaining alternate refresh
    paths. Keep normal explicit reads, own-action updates and security boundaries.
  - Inspect owning backend insert/update/delete and bulk validation, correct any
    demonstrated gaps, and preserve original version/review submission. Display
    specific refusals and refresh affected data after actual conflicts without
    losing drafts or replaying writes.
  - Author the specified direct/cascade, two-user conflict and security regressions.
    Synchronize canonical guidance, both instruction products, affected module/API
    consumers and version/changelog assessments in the committed point. Broad
    suites stay final; no dependency or new freshness infrastructure is authorized.
- [ ] 4. Review dispositions and prepare the manual acceptance runtime.
  - Reconcile scope against actual delivered changes; repair gaps in explicit
    points before manual acceptance. Prepare current assets, compatible runtime,
    authorized access and actual working page/API responses without data reset.
  - Provide concrete URLs and a short PL/EN, theme, viewport and keyboard sheet,
    including login copy, collapsed/expanded/empty trusted-device states, passive
    tab return without reload, manual refresh and conflict/draft presentation.
- [ ] 5. Owner manual frontend acceptance (penultimate point).
  - Record the owner's explicit result and tested source/environment. Agent
    observations or silence do not complete this point. Place repairs before it
    and repeat affected owner acceptance after frontend changes.
- [ ] 6. Final verification, release finalization, merge and report.
  - Review current-state docs, select and explain final quality groups and preview
    the exact supported graph. Run complete affected-area checks, real
    prerequisites and mandatory gates; excluded areas are not PASS.
  - Use then-current canonical owner procedures for failures and evidence
    validity. Insert repairs before manual acceptance and repeat affected
    verification/acceptance as required. Preserve native reports and data.
  - Finalize the actual platform/module versions and changelogs, synchronize
    closure and knowledge, commit closure, merge with a merge commit, run main
    sanity and perform the canonical tag/publication/branch cleanup sequence.
    Record scoped task-resource cleanup and preserved evidence; no global prune.
