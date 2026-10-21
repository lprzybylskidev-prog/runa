---
id: RM-2026-008
title: PHP security updates and runtime vulnerability remediation
status: active
branch: roadmap/RM-2026-008-runtime-security-remediation
created_at: 2026-10-21
activated_at: 2026-10-21
closed_at: null
release_type: fix
impact_areas:
  - backend
  - development
  - production
  - tooling
  - security
  - documentation
---

# RM-2026-008 — PHP security updates and runtime vulnerability remediation

## Overview

### Goal and scope

Address the October 2026 PHP critical security release, reassess the PostgreSQL gosu findings, replace vulnerable custom-service test image inputs where needed, and correct scanner source paths after the src relocation. Keep this security work separate from RM-2026-005's platform ownership/install/update delivery.

The owner explicitly authorized activation and execution on 2026-10-21, with target patch release 0.3.1. Runa is an early-stage reusable application foundation, including for customers who will expose their applications publicly. Local-only development is not a substitute for safe customer defaults. Determine the actual patch release version from VERSION at activation; the current released baseline is 0.3.0.

### Execution order and release dependency

RM-2026-005 is closed and merged into main as platform version 0.3.0 (observed main commit ecb113a). Its security results remain qualified by the owner-only [bounded exception record](../../security/RM-2026-005-deferred-findings.json), not a clean-security PASS. RM-008 is now the single active roadmap. Preserve its closed contents and original evidence.

Activated normally from refreshed main after explicit owner approval. No pause/resume, candidate-branch transfer or reopening of RM-005 is needed. This roadmap owns remediation, current policy synchronization and a new patch release. RM-006 remains a separate draft.

### Exclusions

No module framework, unrelated application features, unrelated broad dependency upgrades, new security platform/CI, global CVE suppressions, automatic production rollout, or destructive database/storage transition. Preserve historical assessments and raw reports. The owner permits necessary runtime/image version changes to achieve compatibility and security; justify major/minor changes and verify their compatibility rather than upgrading for its own sake. New Composer/pnpm dependencies still require explicit approval. The only newly authorized security-gate exception is the bounded pending-upstream gosu arrangement below; it is not a general weakening of security gates.

## Specification

### Decisions

- 2026-10-21 — The owner authorizes execution through release 0.3.1, including closure with the bounded pending-upstream PostgreSQL/gosu arrangement if no fixed official artifact is available.

- 2026-10-21 — The owner confirms Runa is a reusable foundation whose customer applications may be public. No external installation was reported; inventory must verify local context without assuming a particular future customer's trust model.
- 2026-10-21 — Work currently runs from the host checkout. The owner permits local container interruption, recreation and rebuilds as needed. Keep application tools containerized and preserve development/test data, rollback resources, backups and model/shared caches.
- 2026-10-21 — The owner delegates runtime/image version selection, including necessary major/minor changes, to the agent with compatibility/security evidence. No Redis service is authorized in the baseline product; existing isolated additional-service test fixtures are assessed separately.
- 2026-10-21 — If no corrected official PostgreSQL/gosu artifact is available, the owner permits closing this roadmap with explicitly tracked pending-upstream gosu findings instead of repeatedly blocking unrelated work on the identical assessed findings. This is an accepted residual risk, not proof of non-applicability or remediation. Apply the exact-artifact, review and customer-boundary requirements below before using this exception.

- 2026-10-21 — The owner authorizes RM-005 point 14 with an exception limited to the findings assigned here. This roadmap stays draft; it owns their remediation before external exposure. The dated RM-005 exception does not authorize additional findings, a general weakened security policy or automatic activation here.

- 2026-10-21 — The owner requested a separate roadmap for the security findings discovered during RM-005 acceptance and the linked NCSC PHP alert. Do not expand RM-005 with their remediation. Keep a dependency/disposition link there and retain its original evidence.
- 2026-10-21 — Draft creation and planning answers do not activate this roadmap, change runtime configuration or approve a new not-affected decision. The earlier RM-005 execution-order dependency is resolved by its completed release.

### PHP advisory and affected inventory

Sources checked on 2026-10-21:

- [NCSC alert](https://www.ncsc.gov.uk/news/critical-security-issue-affecting-php).
- [PHP security announcement and release information](https://www.php.net/).
- [CVE-2026-109618 advisory](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w).
- [CVE-2026-109619 advisory](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).

PHP identifies two critical unauthenticated remote-code-execution issues, CVE-2026-109618 and CVE-2026-109619. Its guidance includes PHP-FPM among affected web-serving deployments; Nginx/FPM is not an exemption. The announced fixed releases are 8.4.27, 8.5.12, 8.3.36 and 8.2.35. Refresh upstream advisories and vendor availability at implementation time. Do not equate a demonstrated mod_php exploit with proof of exploitation in this Runa installation.

The draft baseline used PHP 8.4.26; point 2 now pins official PHP 8.4.27 for development and production stages and synchronizes maintenance metadata and runtime checks. Inventory exact running versions, image IDs/digests, inherited management/build/test stages and any deployed customer installations before choosing the update. Prefer a supported fix on the existing PHP branch; the owner also permits a justified compatible major/minor migration if needed. Verify actual official image availability and immutable artifacts rather than guessing a tag/digest. Upstream mitigations are not a substitute for a verified fixed runtime; do not apply speculative memory/request-limit changes while drafting.

### gosu and PostgreSQL assessment

Retain the existing exact-artifact policy and original historical evidence. The approved PostgreSQL digest is 77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873; gosu 1.19 embeds Go 1.24.6 and github.com/moby/sys/user v0.1.0. Reconfirm all identities before acting.

The new Trivy set contains CVE-2026-111943 / GO-2026-6809, outside the approved 25 findings. Fresh unwrapped govulncheck 1.9.0 binary analysis reports that x509 item at module level, and additionally reports GO-2026-6686 / CVE-2026-61801 in GetExecUser, GetExecUserPath and ParseGroupFilter. The previous zero-function finding premise cannot simply be reused.

The [Moby advisory](https://github.com/moby/sys/security/advisories/GHSA-mjcv-p78q-w5fw) describes resource exhaustion when parsing malicious user/group database files; [gosu upstream guidance](https://github.com/tianon/gosu/blob/1.19/SECURITY.md) requires assessing actual functionality. Determine whether untrusted input can influence the parsed files through image content, mounts, permissions, entrypoint behavior or customer customization. Symbol presence alone is not proof of a remotely exploitable path. Compare a verified fixed upstream artifact with a narrowly evidenced not-affected disposition where justified. Preserve entrypoint privilege dropping, non-superuser PostgreSQL/runtime behavior and data compatibility; no database-volume replacement just to clear a scanner result.

Any revised disposition must retain exact artifact/binary/finding/architecture/evidence/expiry binding and tests rejecting unrelated or changed findings. Update current operational policy and its tests deliberately without rewriting immutable dated evidence or automatically approving a new finding set.

#### Pending-upstream closure arrangement

Planning check on 2026-10-21: the [upstream latest-release API](https://api.github.com/repos/tianon/gosu/releases/latest) returns gosu 1.19, published 2025-09-23. Official PostgreSQL 18 Dockerfiles for [Alpine 3.24](https://github.com/docker-library/postgres/blob/master/18/alpine3.24/Dockerfile) and [Alpine 3.23](https://github.com/docker-library/postgres/blob/master/18/alpine3.23/Dockerfile) both select PostgreSQL 18.6 and gosu 1.19. This source check identifies no newer gosu in those official variants; it is not a registry-wide artifact scan or a current reachability determination. Recheck actual registry artifacts at point 1 and before final verification.

Prefer a verified corrected official image. If unavailable, assess every carried finding and use either evidence-supported not-affected status or explicit accepted-risk/pending-upstream status; never label an unresolved reachable or uncertain issue not affected. A deferral must bind the exact image, architecture, binary, findings and evidence; state the actual trust assumptions and impact, record upstream tracking links, an owner, a review date and triggers for re-evaluation. Reuse an appropriate upstream issue when available; otherwise prepare a concise report for the owner to submit.

The exact unchanged accepted set must be recognized consistently by normal owner verification, without repeated permission prompts or duplicate warnings on every scan. Keep raw scanner results visible and distinguish qualified roadmap/release acceptance from a native clean scan. Changed/new findings, artifact drift, expired review or changed trust assumptions require fresh assessment. Test both recognition and rejection paths. The exception may unblock this roadmap's closure and subsequent owner development; it must not silently become a blanket customer production exception. Public operational guidance must explain applicability and any pending risk so a customer can assess their own deployment. No automatic production rollout is included.

### Redis fixtures and scanner scope

Redis is absent from the baseline compose.yaml and from the running development containers inspected on 2026-10-21. It is an isolated maintainer acceptance fixture for real customer-added-service customization, update, team and deployment tests, not an application cache/queue/session requirement. Preserve that distinction and verify baseline development/production remain Redis-free. Do not add Redis to the product as part of this roadmap.

RM-005's existing Redis Bookworm fixture at digest c22af04bb576503bf16b3e34a1fd2fd82de0f765afd866d2e380145e0af30d78 produced 81 HIGH/CRITICAL package findings. A diagnostic scan of the preexisting Alpine digest c5e375abb885e6b2021c0377879e4890bf76f9065b8922ffc113f2b226b9fc17 produced 10; it was not selected as a fix. Counts describe retained reports, not current advisory truth or demonstrated exploitability. Select and scan a maintained exact fixture artifact, synchronizing update/team/deployment acceptance inputs. Retain the real additional-service acceptance, read-only filesystem, dropped capabilities and no host port. Do not remove custom-service coverage to obtain PASS.

Repository scanning reported nested dependency package-lock findings and a dependency fixture Dockerfile under src/vendor. Audit the stale root-relative vendor/node_modules exclusions against actual scanner path semantics after relocation. Preserve scanning of first-party source/configuration, composed committed locks including development dependencies, and exact runtime images. Demonstrate intended dependency/generated-directory exclusions without hiding first-party findings or adding CVE-specific blanket ignores.

### Evidence and acceptance

The historical first assessment used RM-005 candidate 6bc2480; the execution baseline is now released main 0.3.0. Refresh branch/worktree/runtime context before execution. Relevant local evidence is under .runtime/ownership/RM-2026-005-point-13-security/, installation-host-29d4e1251e.json, installation-host-e587276e60.json and RM-2026-005-point-13-*.log, with final RM-005 evidence under .runtime/ownership/RM-2026-005-final-9675afb/. Those native security reports retain their FAIL status and the dated owner-qualified acceptance. Their task resources were removed; active dev/test data, preexisting images, caches, backups and evidence were preserved.

Record source/registry provenance, exact artifacts, raw scan results, reachability analysis, compatibility checks and all changes to current maintenance/public operational guidance. Use containerized application tools and dedicated customer production fixtures. Preserve development/test data, rollback resources, model/shared caches and external backups; inspect and print scoped task cleanup identities before removal.

## Implementation plan

- [x] 1. Refresh the released baseline and security inventory.
  - Prerequisites: explicit owner activation from refreshed main; RM-005 is already closed/released.
  - Recheck advisories, exact running/build/management/runtime versions, affected paths and available fixed artifacts. Record the selected remedies and any significant unresolved assumptions before implementation.
- [x] 2. Update PHP to verified fixed artifacts on the supported branch.
  - Requires 1. Synchronize development, production, management/build inheritance and maintenance metadata. Rebuild and verify actual versions/extensions, Laravel/configuration, HTTP/FPM, tests, image provenance and runtime isolation. Preserve configuration/data and document required recreation.
- [x] 3. Resolve PostgreSQL gosu findings with current reachability evidence.
  - Requires 1; verify against the candidate from 2. Select a fixed official artifact, evidenced not-affected disposition or the explicitly authorized pending-upstream arrangement above. Synchronize owner verification, customer-facing applicability guidance and tracking. Test recognition of the accepted unchanged set, rejection of finding/artifact/evidence drift and expired review, and PostgreSQL startup/privileges/data compatibility. Stop for discussion if newly discovered facts invalidate the agreed trust assumptions.
- [ ] 4. Correct repository scan paths and secure custom-service test inputs.
  - Requires 1–3. Verify source/lock/image coverage, choose scanned exact Redis fixture inputs and retain real custom-service/team/deployment acceptance. Confirm Redis remains absent from baseline product services. Synchronize scanner/policy contracts, quality tooling, maintenance records and current docs.
- [ ] 5. Full verification, release finalization, merge and final report.
  - Requires all previous points. Run full applicable quality/security/AI/production gates, fresh native acceptance and current-state documentation review. No unassessed/unaccepted security hard failure or stale PASS; any pending-upstream gosu acceptance must meet the exact bounded arrangement above and remain explicit in release evidence. Follow release/merge/main sanity/tag/publication procedures and synchronize current remediation/dependency records without editing closed RM-005 or relabeling its earlier FAIL reports. Clean only scoped task resources and report preserved data/caches/backups.

## Execution evidence

### Point 1 — baseline and selected remedies

- Activated from clean main d28a5bf, one planning commit ahead of origin/main; fetch confirmed no incoming/divergent remote commits. Target platform release is 0.3.1. No external deployment was reported.
- Private inventory: .runtime/ownership/RM-2026-008/inventory.json records the current container identities, PHP versions/extensions and upstream responses. app, app-test, queue and scheduler use PHP 8.4.26. No Redis runs in baseline development. Existing PostgreSQL/data remain unchanged.
- PHP upstream release metadata confirms security release 8.4.27 on 2026-10-21. Official registry manifests are available: Bookworm sha256:6d2c08c2ce0548e9bc55e21aab68f5a2613e635e4225fdea84290459728995e7 and Alpine 3.24 sha256:8193f7c711715c88452ebe51d4ee7769172a3fa917446a8820cf84192da2c555. Select these on the existing 8.4 branch for development and production/management inheritance.
- gosu upstream latest remains 1.19; official PostgreSQL 18.6 Alpine 3.24 source selects it. Retain exact PostgreSQL artifact/data and perform current finding/reachability assessment with the authorized owner-only pending-upstream option.
- Official Redis library advertises maintained 8.4.7 Alpine 3.22 fixtures on the same 8.4 branch. Resolve and scan its immutable artifact before selection; no Redis product service is added.
- Scanner currently excludes root vendor/node_modules only; point 4 will demonstrate relocated dependency-directory exclusions while preserving first-party/lock coverage. Original RM-005 reports remain untouched.

### Point 2 — fixed PHP runtime

- Official PHP 8.4.27 Bookworm development and Alpine production/management base images are pinned to the verified digests from point 1. Runtime checks, production probe, maintenance registry and current toolchain/production guidance are synchronized. No Composer/pnpm dependency changed.
- Built the management and development targets and isolated runa-rm008-php-probe production image. Management reports 8.4.27. Production capability/isolation probe passes all extensions, Unicode/ICU/iconv, decimal/XML/ZIP/HTTPS/process, non-root user and absent development/AI material; fresh HIGH/CRITICAL vulnerability/secret scan has zero findings.
- Recreated only app, app-test, queue and scheduler; restarted Nginx to refresh its FPM resolution. All four services report 8.4.27 and healthy; HTTP /api/health returns 200. PostgreSQL, model/Qdrant and mail services/data are preserved.
- PASS: backend Health tests (4 tests, 15 assertions), owner tooling pins/extensions, development/configuration/templates/database/queue/scheduler/Nginx/Mailpit checks, docs and maintenance checks. Evidence: .runtime/ownership/RM-2026-008/php-*.log and php-development-runtime.json. Full application/native production acceptance remains point 5.
- Knowledge query during changed documentation correctly reported staleness; focused authoritative source reads were used until the staged incremental refresh.

### Point 3 — exact owner pending-upstream acceptance

- PostgreSQL artifact, official entrypoint, gosu binary and existing data remain unchanged. Fresh direct and archive Trivy reports contain exactly 26 HIGH/CRITICAL stdlib findings; unwrapped govulncheck confirms the additional x509 module event and moby user/group parser function events. Root-owned user/group files, fixed postgres entrypoint and actual mount inspection establish the baseline input boundary; customization requires reassessment.
- Current public applicability guidance is synchronized without extending the default 25-finding customer acceptance. An explicit owner-only callback selects accepted_risk_pending_upstream for the exact 26 signatures, binding image/archive config/architecture/binary/version/evidence/finding details and expiry 2026-11-20. Acquisition receipts reject owner acceptance even with copied owner files. No global CVE ignore or automatic customer production exception was added. Raw reports remain unchanged.
- Tracking MT-2026-001 is open. Dated owner evidence and prepared upstream inquiry: docs/runa/maintainer/security/RM-2026-008-gosu-assessment.md. Upstream search found no matching public gosu issue for CVE-2026-61801; no external message was posted.
- PASS: 7 gosu policy contracts (including drift/expiry/evidence/binary/customer-boundary rejection), 5 security contracts, 10 quality-runner contracts in the application container, real saved-image scanner/config/digest/binary binding, isolated PostgreSQL startup/ICU/index compatibility, docs/maintenance checks. A direct-image report was correctly rejected when paired with a saved archive; rescanning that archive passed. Host quality fixture paths were inaccessible to the container; the intended container execution passed without changing the test. Evidence: .runtime/ownership/RM-2026-008/{govulncheck,postgres*,gosu-*}.

## Execution handoff

- Context: RM-008 point 2, host checkout /home/lprzybylski/Documents/php/runa, roadmap/RM-2026-008-runtime-security-remediation, base d175d7b, 2026-10-21. Host execution is owner-authorized; no editor transition is needed.
- Scope: rebuild development app image from pinned PHP 8.4.27, recreate app/app-test/queue/scheduler, verify FPM/HTTP/extensions and management/production targets. Use canonical bin/artisan dev build/recreate wrappers with .env.dev.local and .devcontainer/compose.resources.yaml; Compose project runa-dev.
- Preserve all current PostgreSQL/test/storage data, Qdrant/model/shared dependency/build caches and backups. Inventory before work: .runtime/ownership/RM-2026-008/inventory.json. No volume reset or database migration is planned.
- Host outcome: PHP 8.4.27 build/recreation and point 2 checks passed; see evidence above. Workspace is usable from host or by reopening the rebuilt devcontainer (GUI switching was not performed). Temporary production probe image is retained until scoped cleanup.
- Remaining: point 4 scanner/fixture corrections; full final gate/release 0.3.1. Record check outcomes below before completion.
