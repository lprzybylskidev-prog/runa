# RM-2026-017 dashboard scope and application settings layout

Owner manual feedback, 2026-11-08. Source baseline a7d29c8b. Points 10u–10v repair
the unintended widget placement and flat application settings layout before repeated
owner acceptance 11. Full automated verification/release remains 12.

## Source correction — 10u

AdminDashboard previously appended widgets after the complete AdminOperations
renderer regardless of its selected subview. AdminOperations owns reactive selection
and now supplies its optional named dashboard slot only in the Modules subview.
AdminDashboard forwards its isolated module widgets there. The private
AdminDashboardWidgets renderer retains the same module component resolution,
boundaries, DTO props and standalone no-controller rendering. No window listener,
second navigation owner or application-specific widget suppression was added.
The additive named slot is documented for supported operations replacements; a
full default-slot replacement still owns the complete presentation.

Application settings keep their existing Card, one Form/controller and write lane.
Shared FormSection groups source-authorized fields into application identity,
default language/theme/timezone and additional settings only when present. Identity
places name above logo/favicon, while the settings sections sit beside each other
on wide screens and stack on narrow screens. Named compact restore buttons replace
repeated full-text controls; refresh/save share the final action row. The private
AdminApplicationSettingField only presents existing field types, authorized readonly
states, previews, validation and local conflict resolution. Draft merging, image
preparation, proof, grants, current revision and save behavior are unchanged.

Four focused native Chromium component cases pass, exit 0 / cleanup 0:
`python3 tools/maintainer/test-frontend --filter='application settings group identity|dashboard contributions leave|application settings defaults compose|application settings retain dirty'`.
They verify slot withdrawal/return on real subview navigation, grouped fields and
named compact resets, authorized readonly fields/default reset, and dirty/conflict
reconciliation. The first slot fixture incorrectly searched for anchors in the
unhosted PageNavigation fallback, which uses buttons; its failure is retained and
the corrected fixture exercises those actual buttons. No product navigation rule
was changed to satisfy the fixture. Full suites remain unexecuted here.
Evidence: `.runtime/dev/RM-2026-017-10u/focused-layout{,-corrected}.log`.

Public Admin composition and source placement are documented in the owning frontend
guide; reference guidance identifies its dashboard-only working widget. Owner and
customer frontend selectors point to the same contract. Existing AGENTS policies
already cover page composition and protected-source boundaries and need no change.
Admin 2.0.0 is already this roadmap's unreleased breaking candidate; the additive
slot and default-layout correction are included there. Framework API 2.1.0 and
other module candidates need no further increment. No dependency, backend/schema,
configuration or data change is introduced. Platform stays 0.7.0 with approved
feature/0.8.0 projection. Current assets and actual UI readiness are recorded in 10v,
not inferred from these source checks. Only the owner accepts the rendered result.
