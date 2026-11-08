# RM-2026-017 readiness after health publication correction

Point 10t, 2026-11-08. Runtime source:
`bff56568eeaac9128e8390fe3047f79a8d3eb4f9`, with a subsequent documentation-only
handoff commit. The [owning correction](RM-2026-017-health-admission-design.md#implemented-correction-and-bounded-evidence--10s2)
is committed, and renewed bounded readiness succeeds. Point 11 now awaits the
owner's manual acceptance at [localhost:8080](http://localhost:8080); final automated
verification/release remains point 12. This report supersedes the readiness stop in
the earlier [icon runtime record](RM-2026-017-icon-readiness.md), preserving its
failed observations and exact older source identity.

## Current environment and assets

Operational preparation passes, each with native exit 0:

- `pnpm --dir src run build`, including TypeScript and native error shell generation.
- `pnpm --dir src exec vite build --mode development`, including the opted-in reference.
- `php src/runa/build/errors/build.php`, emitting current standalone error assets.
- `php artisan runa:doctor:development`, checking configuration, database/migrations,
  queue/scheduler, actual Nginx/Mailpit and effective resources.

Existing chunk-size and ineffective-dynamic-import warnings remain recorded.
These are the manual runtime's actual preparation/prerequisites, not full automated
frontend/backend/native-installation/security/image/AI or release PASS.

The SPA still uses `/build/assets/index-B-Ok1op7.js`; index SHA-256 remains
`92d803ec4af3ac64f44c4ee78affa4fdedec447129191b2734c2c0bdd61de472`.
The repair changes backend admission/publication, so unchanged frontend hashes are
expected. Fresh build identity records the corrected source, branch/time and hashes
of the actual public build/native-error files. Browser responses confirm this entry.

Platform remains 0.7.0 with approved feature/0.8.0 projection; Framework API 2.1.0.
Both local registry inspection and rendered module-card values confirm source and
installed matches: Admin 2.0.0, Auth 6.0.1, Audit 1.1.2, Permissions 2.1.2,
reference 0.4.1. Effective maintenance reasons are empty. Existing authorized login
and native full-page Admin password confirmation work; no substitute access is used.

## Actual bounded observations

The icon route observation exits 0: login submit, Admin/module symbols, sample
refresh, reference settings, user-creation dialog opened/cancelled with the keyboard,
account actions and native 404 Home icon. PL/EN, light/dark, desktop 1440px and
390px mobile samples show no horizontal page overflow, page errors or unexpected
API refusal. Existing guest session 401 and initial Admin step_up_required/403 are
expected handshake evidence; successful login/password confirmation follows them.
These narrow expected refusals are retained explicitly, never swallowed in product code.

Eight rounds send actual concurrent Nginx requests for reference samples GET,
Admin activity POST and Auth session activity POST: **24/24 return 200**. Across
that interval generation remains **1015**, while health_revision advances **58 → 74**.
This verifies actual observation publication and preserved admission together,
beyond merely seeing no error in a screenshot. The following controlled response
hold uses a real completed backend response to inspect local busy presentation:
the spinner replaces the leading icon, the label remains and button dimensions
stay identical. Protected content remains present after release.

The interaction observation exits 0: trusted devices start collapsed and support
keyboard expansion; passive tab return makes zero business reads and retains the
draft; explicit refresh retains it; review/password-confirmation cancellation retains
it and sends zero publication requests. PL/EN mobile settings have no page overflow.
The combined route observation covers reference inspect/refresh/dashboard, account,
samples/settings/history/work and users, plus current asset/module versions.
Screenshots were visually inspected for module identity, dark mobile settings and
busy sample refresh. Agent observation never constitutes owner manual acceptance.

The earlier 10s2 deterministic native regression remains 13 cases / 73 assertions,
exit 0 / cleanup 0. It covers real hard fences and original evidence expiry as well
as healthy renewal. No further broad suite was run during readiness.

## Evidence and preservation

Current browser records/screenshots/scripts and build identity live under
`.runtime/artifacts/playwright/RM-2026-017-point10t-after-health/`:
icons-readiness.json, overlap.json, interactions.json, combined-readiness.json and
build-identity.json. Operational logs are under `.runtime/dev/RM-2026-017-10t/`,
using the after-health names. The actual database/module inspection is
inspect-after-health.json. Prior failed 10t and all 10r evidence remain preserved.

Intermediate browser-harness failures are retained separately: an initial assertion
incorrectly classified expected pre-login/pre-proof refusals, a busy assertion
expected SVG instead of the existing CSS spinner, and the combined script lacked
its added assertion import. Corrected observations pass against unchanged product
source. No guard, deadline, retry or frontend withdrawal change resolved those
harness errors.

Only the identified technical singleton reconstruction recorded in 10s2 was needed;
10t performs no further storage reset, migration, account creation or credential
change. Existing accounts/settings/reference records/Core history remain. Normal
login/activity/preferences requests use their real owning behavior. No Docker
container/network/image/volume was created or deleted; existing development/test
storage, Qdrant/model caches, backups and evidence remain. Native focused-run worker
databases were removed by their owning guarded runner, preserving runa_test.

The [current acceptance sheet](RM-2026-017-current-acceptance.md) now identifies this
candidate and the owner's remaining scenarios. Existing icon contracts/guidance and
both instruction products were synchronized in 10s/10s1/10s2; this operational handoff
introduces no reusable rule or further module/API version change.
