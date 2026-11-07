---
id: RM-2026-018
title: Purposeful interface copy and compact trusted devices
status: draft
branch: roadmap/RM-2026-018-interface-copy-and-trusted-devices
created_at: 2026-11-07
activated_at: null
closed_at: null
release_type: fix
proposed_version: 0.8.1
quality_groups: docs,frontend,e2e
quality_rationale: Proposed scope covers interface copy, trusted-device disclosure behavior and rendered regressions, plus mandatory checks and real prerequisites. Reassess against actual changes before final execution; no unrelated infrastructure acceptance.
impact_areas:
  - frontend
  - documentation
---

# RM-2026-018 — Purposeful interface copy and compact trusted devices

## Overview

### What and why

Revisit low-value interface text left after RM-2026-017 and reduce the initial
visual weight of the account security view. The login instruction "Podaj dane
swojego konta." repeats the form's obvious purpose. The trusted-device table
exposes filters, export/view controls and pagination before the user needs them.

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
- Synchronize affected current-state guidance, presentation contracts, meaningful
  regression cases and independent module version/changelog assessments.

### Out of scope

Broad visual redesign; new table, accordion or authentication systems; new
dependencies; changes to account linking, security eligibility, authorization,
trusted-device lifetime or persistence; migration/reset; SIGSEGV tooling;
production deployment. Do not remove necessary instructions just to reduce text.
Do not expand the disclosure change to all platform tables without agreement.

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

### Decisions

- 2026-11-07 — Owner requested a separate roadmap for 0.8.1 covering low-value
  copy review and collapsing the table shown earlier in account security.
- 2026-11-07 — Owner explicitly confirmed the target as Trusted devices and
  approved `release_type: fix` / `proposed_version: 0.8.1`: PATCH after the planned
  0.8.0 predecessor, for interface corrections rather than a new capability.
- 2026-11-07 — Draft preparation only. No activation or implementation approval
  is inferred; the separate RM-2026-017 point-9 execution deferral is unchanged.

## Dependencies and ownership

Predecessor: [RM-2026-017](../active/RM-2026-017-platform-quality-and-workflows.md).
Refresh its link after closure, reconcile actual release order and target version
before activation, and inspect current code instead of assuming today's paths or
rendered state remain unchanged. Do not reopen closed evidence.

Platform presentation belongs in `src/runa`; retain supported customer replacement
and composition seams. Reuse existing shared components rather than exporting new
ones solely for this correction. Assess Auth's independent correction version,
dependent ranges and central/module changelogs from the actual changed contract.
Review owner/customer instruction products in the owning point; use references
to canonical copy/component guidance instead of duplicating it.

## Implementation plan

Implementation points deliver source, meaningful tests and synchronized docs.
Only small focused checks resolving concrete uncertainty belong there; broad
suites, static analysis, acceptance matrices and quality gates remain in point 5.
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
- [ ] 3. Review dispositions and prepare the manual acceptance runtime.
  - Reconcile scope against actual delivered changes; repair gaps in explicit
    points before manual acceptance. Prepare current assets, compatible runtime,
    authorized access and actual working page/API responses without data reset.
  - Provide concrete URLs and a short PL/EN, theme, viewport and keyboard sheet,
    including login copy and collapsed/expanded/empty trusted-device states.
- [ ] 4. Owner manual frontend acceptance (penultimate point).
  - Record the owner's explicit result and tested source/environment. Agent
    observations or silence do not complete this point. Place repairs before it
    and repeat affected owner acceptance after frontend changes.
- [ ] 5. Final verification, release finalization, merge and report.
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
