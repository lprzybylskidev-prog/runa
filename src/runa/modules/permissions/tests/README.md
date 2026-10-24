# Permissions module tests

Owner regressions live in `src/runa/tests/Feature/PermissionsTest.php` and use
disposable isolated PostgreSQL storage through the supported owner test command.
Coverage includes installation, current direct/role grants, declaration preview
binding, Audit rollback, readiness/eligibility and protected introspection.
Cross-module authority and assignment mutation acceptance belongs to the later
owning roadmap points; full suites run at final verification.
