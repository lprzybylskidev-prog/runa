---
id: RM-2026-008
title: PHP security updates and runtime vulnerability remediation
status: draft
branch: roadmap/RM-2026-008-runtime-security-remediation
created_at: 2026-10-21
activated_at: null
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

This is a planning-only draft, requested by the owner on 2026-10-21. It is not activated or approved for implementation. No runtime/image/dependency or scanner-policy change is authorized merely by creating this file. Determine the actual patch release version from VERSION and the eventual integration order at activation; do not assume RM-005 has released 0.3.0.

### Execution order and release dependency

RM-2026-005 remains the single active roadmap, with unresolved security findings and incomplete final verification. Moving remediation here does not waive its hard gates, mark point 13 complete or authorize an unsafe release. RM-2026-006 module work must not bypass unresolved release/security gates either.

Before activation, agree how this security roadmap will integrate with the unreleased RM-005 candidate: explicit pause/resume if necessary, branch/base and transfer of fixes, verification and release ownership. Follow the existing workflow; do not silently introduce an exception to branch-from-main or merge an unfinished roadmap. Do not make closing RM-005 with failed checks a prerequisite for this security work. The owner must explicitly approve the activation/execution arrangement.

### Exclusions

No module framework, unrelated application features, broad dependency upgrades, new security platform/CI, weakened hard gates, global CVE suppressions, automatic production rollout, or destructive database/storage transition. Preserve historical assessments and raw reports. New Composer/pnpm dependencies still require explicit approval.

## Specification

### Decisions

- 2026-10-21 — The owner requested a separate roadmap for the security findings discovered during RM-005 acceptance and the linked NCSC PHP alert. Do not expand RM-005 with their remediation. Keep a dependency/disposition link there and retain its original evidence.
- 2026-10-21 — Draft creation does not activate this roadmap, pause RM-005, change runtime configuration or approve a new not-affected decision. Resolve execution order explicitly before implementation.

### PHP advisory and affected inventory

Sources checked on 2026-10-21:

- [NCSC alert](https://www.ncsc.gov.uk/news/critical-security-issue-affecting-php).
- [PHP security announcement and release information](https://www.php.net/).
- [CVE-2026-109618 advisory](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w).
- [CVE-2026-109619 advisory](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).

PHP identifies two critical unauthenticated remote-code-execution issues, CVE-2026-109618 and CVE-2026-109619. Its guidance includes PHP-FPM among affected web-serving deployments; Nginx/FPM is not an exemption. The announced fixed releases are 8.4.27, 8.5.12, 8.3.36 and 8.2.35. Refresh upstream advisories and vendor availability at implementation time. Do not equate a demonstrated mod_php exploit with proof of exploitation in this Runa installation.

At draft creation docker/php/Dockerfile selects PHP 8.4.26 for development and production stages, and the maintenance registry records that version. Inventory exact running versions, image IDs/digests, inherited management/build/test stages and any deployed customer installations before choosing the update. Prefer a supported fix on the existing PHP branch; a major/minor migration needs separate justification. Verify actual official image availability and immutable artifacts rather than guessing a tag/digest. Upstream mitigations are not a substitute for a verified fixed runtime; do not apply speculative memory/request-limit changes while drafting.

### gosu and PostgreSQL assessment

Retain the existing exact-artifact policy and original historical evidence. The approved PostgreSQL digest is 77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873; gosu 1.19 embeds Go 1.24.6 and github.com/moby/sys/user v0.1.0. Reconfirm all identities before acting.

The new Trivy set contains CVE-2026-111943 / GO-2026-6809, outside the approved 25 findings. Fresh unwrapped govulncheck 1.9.0 binary analysis reports that x509 item at module level, and additionally reports GO-2026-6686 / CVE-2026-61801 in GetExecUser, GetExecUserPath and ParseGroupFilter. The previous zero-function finding premise cannot simply be reused.

The [Moby advisory](https://github.com/moby/sys/security/advisories/GHSA-mjcv-p78q-w5fw) describes resource exhaustion when parsing malicious user/group database files; [gosu upstream guidance](https://github.com/tianon/gosu/blob/1.19/SECURITY.md) requires assessing actual functionality. Determine whether untrusted input can influence the parsed files through image content, mounts, permissions, entrypoint behavior or customer customization. Symbol presence alone is not proof of a remotely exploitable path. Compare a verified fixed upstream artifact with a narrowly evidenced not-affected disposition where justified. Preserve entrypoint privilege dropping, non-superuser PostgreSQL/runtime behavior and data compatibility; no database-volume replacement just to clear a scanner result.

Any revised disposition must retain exact artifact/binary/finding/architecture/evidence/expiry binding and tests rejecting unrelated or changed findings. Update current operational policy and its tests deliberately without rewriting immutable dated evidence or automatically approving a new finding set.

### Redis fixtures and scanner scope

RM-005's existing Redis Bookworm fixture at digest c22af04bb576503bf16b3e34a1fd2fd82de0f765afd866d2e380145e0af30d78 produced 81 HIGH/CRITICAL package findings. A diagnostic scan of the preexisting Alpine digest c5e375abb885e6b2021c0377879e4890bf76f9065b8922ffc113f2b226b9fc17 produced 10; it was not selected as a fix. Counts describe retained reports, not current advisory truth or demonstrated exploitability. Select and scan a maintained exact fixture artifact, synchronizing update/team/deployment acceptance inputs. Retain the real additional-service acceptance, read-only filesystem, dropped capabilities and no host port. Do not remove custom-service coverage to obtain PASS.

Repository scanning reported nested dependency package-lock findings and a dependency fixture Dockerfile under src/vendor. Audit the stale root-relative vendor/node_modules exclusions against actual scanner path semantics after relocation. Preserve scanning of first-party source/configuration, composed committed locks including development dependencies, and exact runtime images. Demonstrate intended dependency/generated-directory exclusions without hiding first-party findings or adding CVE-specific blanket ignores.

### Evidence and acceptance

Baseline implementation is RM-005 candidate 6bc2480; refresh branch/worktree/runtime context before execution. Relevant local evidence is under .runtime/ownership/RM-2026-005-point-13-security/, installation-host-29d4e1251e.json, installation-host-e587276e60.json and RM-2026-005-point-13-*.log. Those native reports are FAIL, not release acceptance. Their task resources were removed; active dev/test data, preexisting images, caches, backups and evidence were preserved.

Record source/registry provenance, exact artifacts, raw scan results, reachability analysis, compatibility checks and all changes to current maintenance/public operational guidance. Use containerized application tools and dedicated customer production fixtures. Preserve development/test data, rollback resources, model/shared caches and external backups; inspect and print scoped task cleanup identities before removal.

## Implementation plan

- [ ] 1. Confirm execution/integration order and refresh the security inventory.
  - Prerequisites: explicit owner activation and resolution of the RM-005 branch/release dependency above.
  - Recheck advisories, exact running/build/management/runtime versions, affected paths and available fixed artifacts. Record the selected remedies and any significant unresolved assumptions before implementation.
- [ ] 2. Update PHP to verified fixed artifacts on the supported branch.
  - Requires 1. Synchronize development, production, management/build inheritance and maintenance metadata. Rebuild and verify actual versions/extensions, Laravel/configuration, HTTP/FPM, tests, image provenance and runtime isolation. Preserve configuration/data and document required recreation.
- [ ] 3. Resolve PostgreSQL gosu findings with current reachability evidence.
  - Requires 1; verify against the candidate from 2. Select a fixed official artifact or explicitly justified narrowly bound disposition. Test rejection of finding/artifact drift and PostgreSQL startup/privileges/data compatibility. Stop for discussion if the trust model must change.
- [ ] 4. Correct repository scan paths and secure custom-service test inputs.
  - Requires 1–3. Verify source/lock/image coverage, choose scanned exact Redis fixture inputs and retain real custom-service/team/deployment acceptance. Synchronize scanner/policy contracts, quality tooling, maintenance records and current docs.
- [ ] 5. Full verification, release finalization, merge and final report.
  - Requires all previous points and the approved integration arrangement. Run full applicable quality/security/AI/production gates, fresh native acceptance and current-state documentation review. No unresolved security hard failure or stale PASS. Follow release/merge/main sanity/tag/publication procedures and synchronize RM-005's dependency/verification state; never relabel its earlier FAIL reports. Clean only scoped task resources and report preserved data/caches/backups.
