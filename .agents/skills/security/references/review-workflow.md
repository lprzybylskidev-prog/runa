# Security Review Workflow

1. Review the changed attack surface rather than ritualistically auditing the entire application for every roadmap.
2. Focus particularly on changed auth/authz, input, raw SQL, filesystem, network, shell, secrets, and browser policy.
3. Findings include severity, exact evidence/location, plausible impact/path, and concrete remediation.
4. Do not label a theoretical possibility critical without a plausible attack path.
5. Investigate false positives; do not globally disable a rule.
6. Security suppressions are narrow and documented.
7. A known vulnerable dependency cannot be accepted silently.
8. If a CVE must temporarily remain, record explicit risk reasoning, maintenance tracking and a removal inventory/condition. Follow [temporary exception retirement](../../../../docs/runa/handbook/security/baseline.md#temporary-compatibility-and-security-exceptions); retire the exception code, policy/flags, dedicated tests and obsolete guidance when a stable corrected replacement is verified.
9. Add a regression test for a reproducible security bug when feasible.
10. Gitleaks is the pre-commit secret-scanning baseline.
11. When selected directly or as prerequisites, strict Trivy final checks cover applicable filesystem/dependency/secrets/Dockerfile/Compose/built-image concerns.
12. The security group runs `composer audit` and `pnpm audit`; baseline secret protection and hooks remain mandatory for every group.
13. Evaluate high/critical image/dependency findings with actual exploitability while still treating them as blocking until dispositioned.
14. Do not add WAF/Vault/KMS/RLS/SAST/DAST platforms as baseline without a real roadmap need.
15. Full OWASP ASVS and formal threat models are not mandatory for ordinary CRUD; higher-risk work may explicitly require them.
16. Security/data-loss/production-health failures cannot be downgraded merely to close a roadmap.
