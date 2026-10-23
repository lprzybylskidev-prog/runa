# Auth regression coverage

Owner integration regressions live in `src/runa/tests/Feature/AuthIdentityTest.php` and `AuthEntryTest.php`.
Use the guarded maintainer backend runner and explicit filters for a necessary
focused diagnostic. Full identity/concurrency/delivery acceptance belongs to final
roadmap verification. Customer workflows do not depend on this owner harness.

AuthEntryTest additionally prepares operational session proof/deadline/activity,
concurrency-cap eviction, remembered persistence, scoped revocation/Audit rollback,
account/source windows and cleanup regressions. Full acceptance remains the final
roadmap gate. See docs/runa/handbook/modules/auth/sessions.md.
