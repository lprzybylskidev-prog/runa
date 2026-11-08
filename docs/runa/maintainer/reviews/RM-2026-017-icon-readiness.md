# RM-2026-017 icon runtime readiness

Point 10t remains open, 2026-11-08. Icons and current assets are prepared, but
ordinary-timing activity refusals block a completed readiness handoff. Current runtime/assets identify
`e69a3b5f7c1741da9392d20827bc638fa8b00091`, following icon implementation 9cd43769
and direct readiness repair 10s1. The handoff commit changes documentation only.
[Source contracts and choices](RM-2026-017-icons.md) remain the owning source review.
The [current acceptance sheet](RM-2026-017-current-acceptance.md) records current scenarios and the blocker for point 11;
only the owner's explicit result completes it. Complete automated verification
and release remain point 12. Older 10i/10r assets and reports retain their identities.

## Preparation and source repair

The first operational pnpm build on 9cd43769 failed TypeScript on missing actionIcons
imports in the four generic SFCs Select, Table, TableActions and TablePreferences.
No assets were replaced by that failed type stage. Point 10s1 imports the map in
those owning components directly, without compiler suppression/config changes or
runtime workarounds. It also completes native Home/Retry icon composition and its
public vocabulary. The original failed build is retained at
.runtime/dev/RM-2026-017-10t/build.log; source inventory confirms no remaining
supplied actionIcons consumer without its import. Commit hooks additionally enforce
formatting, quick lint, secrets, repository sanity and staged knowledge freshness.
The initial source commit's attribute/default lint repairs and reference component
name correction are ordinary source fixes, not relaxed rules.

From corrected committed source, all required operational preparation exited 0:

- pnpm --dir src run build (TypeScript, application assets and native error renderer).
- pnpm --dir src exec vite build --mode development (opted-in reference source).
- php src/runa/build/errors/build.php (standalone emergency assets).
- php artisan runa:doctor:development (config, database/migrations, queue/scheduler,
  actual Nginx/Mailpit/resources; optional AI availability is reported separately).

Build logs preserve existing chunk-size and ineffective-dynamic-import warnings.
These are manual-runtime preparation and its actual prerequisites, not full native
installation, frontend/backend/security/image/AI or release PASS. No image build,
service recreation, host transition, storage reset, migration or grant change occurs.

Platform VERSION/platform manifest remain 0.7.0, approved feature projection 0.8.0;
Framework API stays 2.1.0. Actual dashboard reports source/installed matches for
Admin 2.0.0, Auth 6.0.1, Audit 1.1.2, Permissions 2.1.2 and reference 0.4.1. No new
module upgrade is required for these presentation-only additions. Existing 10r
accounts, credentials, settings, enabled flags and storage provenance remain.

Current SPA entry: `/build/assets/index-B-Ok1op7.js`. Build index SHA-256:
`92d803ec4af3ac64f44c4ee78affa4fdedec447129191b2734c2c0bdd61de472`.
Exact source/branch/time and asset hashes are retained in
.runtime/artifacts/playwright/RM-2026-017-point10t/build-identity.json.
Operational build/doctor logs are under .runtime/dev/RM-2026-017-10t.

## Actual pages and bounded observations

The application remains at [localhost:8080](http://localhost:8080); browser
observations traverse real sibling Nginx at http://nginx:8080. Existing privately
configured administrator access and normal full-page Admin password proof work.
No password reset, substitute token, new user/role or guard bypass was introduced.

The ordinary combined observation reaches native Admin/dashboard, account, user
directory and all five reference views with successful dependent APIs. It records
zero page errors, toasts or horizontal page overflow at 1440px. Initial session 401
and Admin step-up 403 are normal entry/proof transitions. The current module cards
and sidebar display the same source-owned icons, including reference flask.

One ordinary-timing icon-specific run checks actual login/refresh/create/review/Home
controls, reference samples/settings and module cards. Its account capture is an
initial loading frame; separate account-ready evidence checks settled content. Current PL/EN
reference settings render at 390px, with real system-theme resolution to light/dark
via browser media emulation. Language is returned to Polish; media emulation writes
no account theme preference. The separate account capture waits for the Save details control;
the earlier account loading frame is retained separately and certifies only loading.
User creation opens through Enter and closes through Escape without a creation write.
Native /account/preferences is deliberately nonexistent: its normal EN/dark 404
renders the Home icon without an added route or destructive failure trigger.
The ordinary reference sample refresh returns its real HTTP 200.

Shared Button's native busy/label geometry and icon replacement are established by
the six focused source-point Chromium cases (including three sizes), not by a
claim that a fast native request necessarily exposed every busy frame. The working
reference catalogue case also passes. Those 7 focused cases are not full suites.
Current source/asset observations and tests do not constitute owner acceptance.

Two explicit experiments delaying the sample request captured busy geometry but
then received native `/api/admin/activity` HTTP 503 `availability_changed` and lost
the expected control. They remain icons-readiness-attempt1/attempt2.json; neither
is PASS, and no successful delayed native interaction is claimed. The ordinary combined flow and one icon-specific run succeed with unmodified timing;
a further ordinary-timing repeat fails waiting for the sample response after
session/activity and Admin/activity receive availability_changed/503. That real
failure remains icons-readiness-attempt3.json. It is not a successful delayed-only
experiment or proof of ordinary stability.

Source inspection establishes that OperationGuard::assertCurrent refuses registry-
generation/maintenance drift, and ModuleHealth publishes a new registry generation
when it writes a new observation, including successful checks. Reference has hybrid
on-use health. Their overlap is a source-grounded candidate explanation; these
observations do not trace the exact generation/transaction that caused each refusal.
No availability/health/security guard, policy, retry or recovery behavior was
changed. Repeating until a green-looking sample cannot settle this blocker.

The icon implementation and native build are complete, but point 10t remains open:
normal interactive readiness is not established reliably. Correcting health/
availability/activity behavior may change an agreed architectural/security premise
and therefore needs owner discussion under the root AGENTS roadmap execution gate.
No backend correction or new policy is inferred from icon-selection authorization.
The owner can inspect current icons, but point 11 cannot yet be recorded as ready.
A concrete next discussion scope is to trace generation publication versus activity
admission, preserve real lifecycle/authority/health-loss fences and choose an owning
correction without guard suppression, blind retries or a UI workaround.

Renewed interactions additionally observe trusted-device collapse/Enter/collapse,
zero business reads after background-tab return, dirty draft retention through
explicit refresh, successful review and native password-proof cancellation with
zero publication requests. PL/EN mobile reference settings have no page overflow.
These extend the prior scenarios to current assets; no business publication,
credential mutation, sample edit, snapshot or dispatch is sent for visual readiness.
Normal login/activity and deliberate locale preference interactions remain native.

Browser artifacts/scripts are under
.runtime/artifacts/playwright/RM-2026-017-point10t: combined-readiness, icons-readiness-initial-frame, icons-readiness-attempt1/2/3,
account-ready and interactions JSON and current screenshots. Failed delayed attempts, the initial-frame
record and account loading screenshot are preserved. Current screenshot sampling
covers desktop/light reference settings and module cards, narrow/dark English
settings and settled account actions. No complete theme/viewport/state matrix,
assistive-technology acceptance or all-workflow PASS is claimed.

## Handoff, instruction products and resources

The acceptance sheet records the readiness blocker, replaces the old source/build identity and adds five
representative icon views plus the directly changed reference/table/dialog/feedback
scenarios. Existing dirty/proof/conflict, grants, omission and PL/EN/theme/keyboard
requirements remain. The owner may refine icon choices after this first manual
review; such UI changes repeat affected acceptance before final verification.

Both frontend instruction products were synchronized in 10s with canonical public
semantic icon guidance. 10s1/10t add no reusable policy requiring another AGENTS/skill
change. Public vocabulary and source examples already describe the completed Home/
Retry repair; operational evidence stays private. Full current-state docs/quality
review remains 12. Platform/module/API candidates and dependencies are unchanged.

No containers, networks, images, volumes or worker databases were created/removed.
Browser contexts/processes closed. Existing development/test data, accounts,
credentials, model/Qdrant/shared caches, backups, prior evidence and operation/
recovery journals are preserved. No global cleanup was performed. The 34 selected
SVG originals are versioned source assets, not disposable runtime resources.
Point 10t, point 11 and point 12 remain open; no readiness completion, owner acceptance
or release is recorded.
