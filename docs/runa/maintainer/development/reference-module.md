# Reference module maintenance

Owner decision, 2026-11-07: `app.reference` is executable customer documentation
for Runa's supported application/module APIs. A customer developer must be able to
perform an operation, locate its application-owned implementation and tests, and
learn how to consume the supplying Runa module without copying private engines.

## Delivery obligation

For every new Runa module and every new customer-facing capability in an existing
module, update the reference example, its owning documentation and meaningful
tests in the same roadmap point. Include representative integrations with other
supplied modules where they are part of correct use. A dependency, manifest entry,
permission declaration, screenshot or catalogue of CLI commands alone does not
complete an interactive example.

Use a coherent small scenario rather than unrelated demonstration controls. For
each supported capability identify the visible or executable action, public
entrypoint/version, declaration, owning Action/Query, data, presentation, expected
result and success/refusal tests. Provide source links in the application-owned
guide. Show existing behavior in action rather than claiming that an unimplemented
stub is a working reference. A genuinely noninteractive capability may use an
executable backend/CLI example with reproduction and observable results; explain
why a UI example is inapplicable. Internal platform-only mechanisms are excluded
with a specific reason, not exposed as customer APIs.

## Current Core integrations

- Audit: an actual safe input change produces transactional history. Show an
  approved single-model Auditable policy and explicit events for operations that
  need them; failure rolls the business change back. Exclude secrets and private
  payloads, and link the authorized history view.
- Permissions: separately authorized fields/sections/actions demonstrate omission
  and read/write differences. Server projection and guarded writes independently
  refuse unauthorized access; frontend hiding never supplies authority.
- Auth: a real owning operation requires action-specific password confirmation
  through the shared full-page flow. Backend proof verification/consumption shares
  the actual mutation transaction; Admin entry proof is a separate prerequisite.
- Admin: working module-owned pages, settings, data editing and operations use
  declared guarded APIs, shared presentation/controller contracts and existing
  revisions/review/history mechanisms. Metadata without an executable workflow
  does not complete the example.

Keep a capability-to-example matrix in `docs/app/modules/reference/README.md`.
Its detailed source/test map is `docs/app/modules/reference/core-capabilities.md`,
linked from that reproduction section.
Reconcile it against all current Runa modules when adding one, changing public
capabilities or performing a platform quality review. Update affected instruction
products without granting customers authority over protected platform source.
Customer guidance describes how to read/adapt the example; this maintenance
obligation belongs to the Runa owner.

## Boundaries and acceptance

Keep source in `src/app/modules/reference`, documentation in
`docs/app/modules/reference` and tests in central application test roots. Use only
supported public contracts and preserve existing example data. Retain opt-in,
default-disabled acquisition and production source/build/runtime exclusion. Do
not install it into the minimal scaffold or create fake integrations merely to
populate a screen. No new dependency or private API bypass is implied.

New examples must have usable PL/EN presentation, light/dark themes, responsive
layout, keyboard/focus behavior and documented replacement/composition contracts.
Record role/grant prerequisites and concrete acceptance URLs. Author meaningful
success, denial, validation, stale-revision, proof and rollback regressions with
their owners. Ordinary implementation uses only necessary focused diagnostics;
complete affected-area verification remains final, and changed UI returns to the
owner's manual acceptance before release.
