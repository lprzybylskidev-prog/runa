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

## Renewed actual readiness — 10v

Runtime source `9ed1b543d8960a0dbda5b020f6b647384b0d9575` is committed. Supported
operational preparation passes: `pnpm --dir src run build`, development-mode Vite
build and standalone native-error finalization, each exit 0. Existing chunk-size
and ineffective-dynamic-import warnings remain recorded. These builds prepare the
manual runtime; they do not constitute full frontend/backend/release verification.
Unchanged backend/services/storage retain their prior 10t diagnostic evidence;
actual current login, password confirmation and application settings API succeed.

The bounded real Nginx browser observation exits 0. It confirms the reference
widget on Modules, its absence after switching to application settings, its return
on Modules and absence on direct `/admin?section=application` entry. The settings
form exposes the two nonempty localized groups; source extension fields retain
coverage in the focused readonly/default test. Explicit refresh returns HTTP 200
and retains a dirty name draft; restoring the name to its source default works
without a configuration write. The unchanged final draft disables Save. Browser
observation sends zero application configuration writes and preserves existing data.

Desktop 1440px/light and mobile 390px/dark were observed in PL and EN; direct desktop
dark entry was also observed. No horizontal page overflow, page error or unexpected
API refusal occurs. The existing guest 401 and initial step_up_required/403 remain
expected and are followed by successful native entry. Desktop/mobile screenshots
were visually inspected. The owner must repeat affected acceptance; these agent
observations do not accept the design on the owner's behalf.

Current SPA is `/build/assets/index-Dd2HJQm4.js`; index SHA-256:
`0ae079af07cd0451fef22affde9391af28a70867c48d0e278f72ebbb94be73e6`.
Source/branch/time and full asset/native-error hashes are retained in
`.runtime/artifacts/playwright/RM-2026-017-point10v/build-identity.json`.
The same directory owns settings-layout.json, the runnable browser observation and
PL/EN desktop/mobile screenshots. Build/browser logs live under
`.runtime/dev/RM-2026-017-10v/`. Older 10t evidence remains unchanged and identifies
its own older assets. No database/module update, grant or credential change,
Docker resource creation/deletion or host transition occurs. Existing volumes,
base test/development data, model/Qdrant caches, backups and reports remain;
the browser context and supported focused runner shut down their own processes.

The current acceptance sheet now identifies this candidate and both dashboard /
application-settings URLs. Point 11 remains open; full automated verification,
security/AI gates, release and merge remain point 12 after owner acceptance.
