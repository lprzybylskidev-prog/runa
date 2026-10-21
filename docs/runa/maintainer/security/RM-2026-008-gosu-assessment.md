# RM-2026-008 gosu reassessment — 2026-10-21

## Decision and boundary

The owner explicitly accepts pending-upstream risk for owner-only development/release verification, allowing RM-008 release 0.3.1 to close without repeatedly blocking on the identical assessed set. This is not a clean raw scan, not a claim that gosu was updated, and not automatic customer production acceptance. Owner: Runa maintainer. Review by 2026-11-20, or sooner when an upstream fix, changed artifact/finding/evidence, mount/trust change or relevant new advisory is observed. Tracking: MT-2026-001.

## Artifact and fresh evidence

PostgreSQL 18.6 Alpine 3.24 index sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873 is unchanged in the official registry. gosu 1.19 binary SHA256 is 52c8749d0142edd234e9d6bd5237dff2d81e71f43537e2f4f66f75dd4b243dd0, embedding Go 1.24.6 and github.com/moby/sys/user 0.1.0. Its source and official entrypoint match the previously verified artifact. Latest upstream gosu remains 1.19.

Private reproducibility evidence in .runtime/ownership/RM-2026-008/: inventory.json, postgres-registry.json, postgres-scan.json/log, unwrapped govulncheck.json/stderr, postgres-gosu-identity-with-setid.log, postgres-mounts.json and postgres-compatibility.log. The analyzed binary exactly matches the running-image hash. The existing govulncheck 1.9.0 binary uses the previously checksum-verified analysis toolchain; it analyzes original binary Go 1.24.6. Raw reports are retained.

Trivy 0.76.0 reports exactly 26 HIGH/CRITICAL stdlib findings at usr/local/bin/gosu, with no OS-package or secret findings. The previous 25 standard-library assessments remain relevant individually. New CVE-2026-111943 / GO-2026-6809 is module-level x509 matching; gosu uses neither TLS nor certificate verification. Its applicability is distinct from the overall pending-upstream acceptance. The version/finding-specific policy also binds severity and fixed-version details.

Unwrapped govulncheck additionally reports GO-2026-6686 / CVE-2026-61801 in GetExecUser, GetExecUserPath and ParseGroupFilter. This resource-exhaustion issue is fixed in moby/sys/user 0.4.1, not in the available gosu binary. The standard root entrypoint invokes a fixed postgres identity and drops privileges before database startup. User/group files are root-owned 0644, gosu is normal 0755, and the database UID 70 cannot write any of them. Inspected development mounts contain only the database volume and read-only initialization script; production definitions similarly do not replace /etc/passwd or /etc/group. Remote SQL/application input does not select gosu arguments or supply user/group file content in baseline operation. Custom privileged administration, malicious images/initialization scripts or user/group mounts invalidate that trust boundary. No blanket non-applicability claim is made for customers.

The exact 26 Trivy signatures are accepted as pending upstream, not relabeled not affected. The additional parser advisory is tracked even though absent from the HIGH/CRITICAL Trivy set; its later appearance in that set must trigger review rather than automatic acceptance. Public tooling retains the original exact 25-finding default and rejects changed findings. Owner acceptance is selected explicitly by the owner native fixture, requires a receipt-free owner checkout, and is neither delivered nor selected by customer commands.

## Upstream tracking and prepared inquiry

Primary references: [gosu release](https://github.com/tianon/gosu/releases/latest), [official PostgreSQL Dockerfile](https://github.com/docker-library/postgres/blob/master/18/alpine3.24/Dockerfile), [gosu security guidance](https://github.com/tianon/gosu/blob/1.19/SECURITY.md), [Moby advisory](https://github.com/moby/sys/security/advisories/GHSA-mjcv-p78q-w5fw), [x509 record](https://pkg.go.dev/vuln/GO-2026-6809). Public issue search for CVE-2026-61801 in tianon/gosu returned zero matches; this is not proof that no private or differently titled report exists. No message was posted upstream.

Prepared inquiry for the owner to submit after checking for duplicates: Does the gosu release plan include moby/sys/user 0.4.1 for GO-2026-6686? Official gosu 1.19 amd64 embeds 0.1.0, and unwrapped govulncheck identifies GetExecUser/GetExecUserPath/ParseGroupFilter. Our fixed postgres entrypoint reads root-owned local user/group files; we are tracking residual risk for customized mounts/startup input. Please confirm upstream applicability and whether a corrected official binary is planned.

## Verification

Fresh image hash/version/mode and privilege-drop probe passed with only required SETUID/SETGID capabilities. An initial cap-drop-all probe correctly could not change UID; it was repeated with the required capabilities without changing product permissions. Disposable PostgreSQL startup, ICU pl-PL casing/order/ranges and index/sequential agreement passed without mounting existing data. Policy regression tests and final real-archive scanner binding are recorded in the active roadmap. Historical RM-005 and earlier assessments remain unchanged.
