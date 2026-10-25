# RM-2026-011 temporary runtime risk acceptance — 2026-10-25

## Owner decision

The owner explicitly requests restoring PHP 8.4.26 in development, production and
management after repeated native crashes on PHP 8.4.27, and temporarily allowing
known PHP/PostgreSQL security findings while waiting for stable upstream tools.
This supersedes the earlier wait-only/no-new-acceptance decision for this scope.
It does not establish that PHP caused the Runa crashes or that 8.4.26 fixes them.
Remove diagnostic scratch/debugger shims and the experimental per-file worker
recycling default before running the complete normal verification again.

Owner: Runa maintainer. Status: accepted_risk_pending_upstream. Review by
2026-11-20, or earlier when a stable corrected upstream artifact is available.
Tracking: MT-2026-002; existing PostgreSQL/gosu tracking MT-2026-001 remains separate.

## Exact scope and remaining risk

- Development PHP 8.4.26 FPM Bookworm index:
  sha256:845c7ba3487a47607f12cd35d09ac0e0880e8703f92a54ad517b69faef136072.
- Production/management PHP 8.4.26 FPM Alpine 3.24 index:
  sha256:78cd8de9970a9cd6ff4d98860a94eb5bf37dd2d5776b4785562dbfad01c29d5a.
  Production PHP binary SHA256:
  ce8fa4b145d8e659796ca572443e7629b37868c543c917fa6c221ea80d261000.
- Known PHP core findings: CVE-2026-109618 and CVE-2026-109619, fixed by PHP
  8.4.27. These are accepted temporarily, not classified as false positives or
  declared unreachable. FPM is not exempt. See the official
  [security announcement](https://www.php.net/releases/8_4_27.php) and
  [OOM/RCE advisory](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w).
  Source-built PHP may not be identified by an OS-package scanner; absence from
  Trivy output does not remove the known risk.
- Current PostgreSQL stays at its existing exact 18.6 Alpine 3.24 digest. Existing
  gosu acceptance retains its own exact finding/binary/evidence/expiry checks.
- CVE-2026-111497 in brotli-libs 1.2.0-r1, HIGH, fixed by 1.2.1-r0, is accepted
  only for those PostgreSQL/PHP runtime images. This is a real decoder memory
  corruption risk, not a not-affected claim. See the
  [Brotli fix](https://github.com/google/brotli/releases/tag/v1.2.1).

No new reachability exemption is inferred. Existing application authorization,
validation, secret handling and production isolation remain required. A previous
minimal SimpleXML reproducer confirms a PHP 8.4.27 regression; it does not prove
that regression caused Runa's crashes. The CPU-affinity observations are likewise
inconclusive and authorize no hardware or host power changes.

## Enforcement

The owner fixture explicitly selects tools/maintainer/owner_runtime_disposition.py.
Customer/default scanner execution does not select this owner risk decision, even
when owner files were copied into a receipted customer checkout.

Raw JSON and scanner exit status remain unfiltered. Acceptance writes separate
private evidence with policy/report hashes and actual image identities. It binds
PostgreSQL to the pinned repository digest and live gosu identity; PHP to the saved
archive config/rootfs, actual inspected image, exact live PHP version/binary and
reviewed Dockerfile. It rejects expired or changed evidence, unknown findings,
secrets, scanner operational errors and dependency audit failures. The original
shared not-affected policy cannot accept these additional OS risks.

The review has no authority to skip security groups or relabel a raw vulnerable
scan as clean. A PASS under this temporary decision means the selected security
policy was satisfied with explicitly accepted remaining risk. Future image/package,
policy or additional-CVE changes require a deliberate review. Remove the exceptions
when stable corrected tools are adopted through the roadmap process.

## Instruction products

Root AGENTS and owner security skills already require explicit, narrow, documented
risk decisions with maintenance tracking. No generic permission or new broad
suppression mechanism is added to those instructions. Customer instruction products
need no change: the owner callback/policy is excluded from delivery and public
runtime/security documentation describes the customer decision boundary.
