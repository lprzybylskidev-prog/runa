# Permissions module tests

Owner regressions live in `src/runa/tests/Feature/PermissionsTest.php` and use
disposable isolated PostgreSQL storage through the supported owner test command.
Coverage includes installation, current direct/role grants, declaration preview
binding, Audit rollback, readiness/eligibility and protected introspection.
Reviewed assignments, protected authority, lifecycle coordination and operator
bootstrap have prepared regressions; broad acceptance runs at final verification.

PermissionsTest prepares real first-administrator role/history rollback,
same-recipient invitation resume (including completed password setup), disabled
recovery, inactive/locked administrator recovery without ordinary grants/lifecycle
authority, native user-selection grants and bulk proof/Audit rollback, safe Core
metadata and historical deleted identity lookup. A guarded two-process initialization
fixture checks competing recipients exactly once during final integrated verification.
Fresh install/customer upgrade/clone/source-resume acceptance remains final-only.

Development corpus regressions prepare real protected administrator setup, safe
local listing/hidden prompts, repeat credential preservation, deterministic count
extension, refusal of existing administrator adoption, contributor ownership and
missing/cyclic graphs, and atomic fixture/provenance/Audit rollback. Production
receipt refusal also lives in SeedingTest. These use disposable owner-test storage;
customer operation uses only delivered local commands and public contributor APIs.
