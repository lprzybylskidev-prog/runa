# Maintenance

## Files

- `registry.yml` — external/upstream components and review cadence
- `backlog.yml` — durable non-blocking technical maintenance items
- `deprecations.yml` — active deprecation/removal plans

## Maintenance backlog IDs

Format:

`MT-YYYY-NNN`

IDs are immutable within the year.

Allowed statuses:

- `open`
- `planned`
- `resolved`
- `wontfix`

The agent may add a maintenance item autonomously when it discovers a real, non-blocking, out-of-scope technical issue.

The agent may not create a roadmap automatically merely because a maintenance item exists.

Status meanings:

- `open` — real issue recorded, no roadmap decision yet,
- `planned` — user decided it should be handled in a future roadmap/release,
- `resolved` — implemented and released,
- `wontfix` — explicit decision with reason.

Do not create maintenance items just to hide TODO/FIXME/HACK and make a gate green.

## Deprecations

When deprecating configuration, endpoint, CLI, or mechanism:

- document the replacement,
- warn where appropriate,
- provide a transition period when reasonable,
- remove through a separate roadmap,
- track incompatible configuration before runtime/deploy.

For renamed environment/config keys, a temporary alias/fallback may be used when compatibility needs it. Project doctor should warn clearly from the old key to the replacement.

Temporary config aliases/fallbacks must not live forever; removal belongs to a later roadmap.

A significant incompatible/breaking deprecation may require the user's explicit major-version decision under Runa version rules.

## Registry coverage and reminders

Every approved external component is registered when introduced: Composer/pnpm packages, CLI tools, frameworks, runtimes, scanners, VS Code/devcontainer features, database extensions, images, model/runtime artifacts and external services. Record source, exact selected version/revision, review date, interval and next due date (or compute due date from review date plus interval).

Default intervals are in `registry.yml`. Read local due dates periodically at sensible planning/final-review opportunities; do not check on every conversation or fetch upstream automatically. Remind sparingly when overdue. The user chooses whether to plan a review roadmap. Compare changes with pinned sources and discuss selected updates before implementation; after completion update source metadata and the next review date.

Each `MT-YYYY-NNN` item records description, source, priority, creation date, status, related roadmap/release and closure condition. IDs are never reused. `wontfix` requires an explicit decision and reason. `resolved` records the implementing roadmap and released version. Each deprecation records the deprecated item, replacement, introduction date/release, transition/removal plan and related roadmap. Empty initial registries do not imply missing runtime dependencies have already been assessed.

## Local validation

`./bin/artisan runa:doctor:maintenance` validates registry IDs/pins/review intervals/source paths, maps direct Composer/pnpm package names to exact registered versions through the `package` field, validates backlog/deprecation structures, and checks actual TODO/FIXME/HACK source comments for a real maintenance/roadmap ID. It prints overdue review reminders without fetching or upgrading upstream. Version membership alone is not sufficient: the package name must match its registry entry. Full coverage of images, editor extensions and other non-package components is still reviewed explicitly during final verification.

Backlog records use `id`, `description`, `source`, `priority`, `created_at`, `status`, `closure_condition`, plus `roadmap`/`released_version` when resolved and `reason` when wontfix. Deprecation records use `id`, `deprecated`, `replacement`, `introduced_at`, `removal_plan`, `roadmap`. Existing empty files remain valid; do not invent entries to exercise validation. Real items must also preserve the context/release information required above.

`./bin/artisan runa:doctor:docs` checks implemented command documentation; `./bin/artisan runa:doctor:tooling` verifies every executable's help contract, PHP extensions and pinned PHP/Node/pnpm/Composer/Codex/Gitleaks versions. These focused checks do not claim to replace the complete documentation/source review at roadmap closure. Invalid-fixture tests run in memory through `python3 tools/maintainer/testing/maintenance.py`.
