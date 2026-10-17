# Changelog

All release entries are maintained by the agent from roadmap facts.

## [0.1.1] - 2026-10-18

Fix release — RM-2026-002.

- Reconcile current-state documentation with the delivered foundation and preserve historical acceptance evidence.
- Make documentation synchronization part of implementation and require a recorded current-state review during roadmap finalization.
- Validate README version, active roadmap and production-deployment status through existing docs/release gates, with regression coverage and a narrow draft-planning exception for published release tags.
- Classify knowledge roadmap metadata from actual lifecycle files and frontmatter; correct historical roadmap search filters and refresh the development index.

No application behavior, database schema, runtime configuration or dependencies changed. No real production deployment occurred.

## [0.1.0] - 2026-10-18

Feature release — RM-2026-001.

- Establish Laravel 13, PostgreSQL with UTF8/ICU/pl-PL, and the Vue 3/TypeScript/Tailwind application foundation with Polish/English and light/dark support.
- Provide reproducible Docker development, local quality gates, Git hooks, browser tests, Laravel Boost and development-only knowledge retrieval.
- Provide isolated production containers, guided deployment, coordinated backups/restores, guarded rollback, health checks and security scanning.
- Complete host verification and final security review; preserve the exact approved gosu not-affected decision and require scoped cleanup of temporary Docker resources.

This pre-1.0 environment foundation contains no business modules and has never been deployed to real production.
