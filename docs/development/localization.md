# Localization data and UI language

## Vendored Laravel validation messages

The user approved a pinned translation-data snapshot on 2026-10-17 for RM-2026-001 point 5. Neither `laravel-lang/lang` nor `laravel-lang/common` is a project dependency. Their installers, service providers and transitive dependencies are not imported.

Only `lang/pl/validation.php` is imported: 137 framework validation messages, selected by the installed Laravel 13.37.0 English validation catalog from upstream `locales/pl/php.json`. Laravel's example custom rule is removed and custom/attribute sections are empty. Authentication, password-reset, pagination, JSON UI catalogs and third-party integration translations are not used/imported. The framework's built-in English catalog remains the fallback.

`lang/SOURCE.json` records repository, exact tag/version, commit SHA, import date, source/output/license hashes, framework version and imported file list. `lang/LICENSE.laravel-lang` preserves the upstream MIT notice in full. Both the PHP and Nginx build paths use the pinned source files; only the PHP image needs the Laravel catalog/license. The snapshot is runtime data consumed by Laravel's native translator, without a new package/framework/provider.

Technical/backend and administration output stays English by default. Future user-facing validation flows explicitly select the appropriate user locale at their request boundary. Do not globally change the backend default or translate administration based on the public UI preference. The frontend's PL-default/EN-secondary catalog is project-owned and separate from the vendored framework messages.

## Offline checks

Inside the devcontainer:

```sh
python3 tools/localization/snapshot.py check
php tools/testing/localization.php
```

The first command validates catalog keys and placeholders against installed Laravel and verifies the imported license hash. It reports local edits since import without overwriting them. The integration check exercises native Laravel validation, Polish message loading and nested parameter interpolation, while preserving the English technical default. Re-run both when upgrading Laravel or editing the snapshot.

## Deliberate update workflow

An update requires an active maintenance/feature roadmap, upstream/license/security review and an exact new tag plus commit. The tool does not search for or install a newer package automatically.

1. Before changing the current framework/catalog, prepare the currently recorded source with its exact metadata, keeping the result as a baseline:

   ```sh
   python3 tools/localization/snapshot.py prepare --version 15.37.4 --commit a3a9ef9b039d10db442bfb3bed0a97c9b50f30a7
   ```

2. Prepare the reviewed new version with `prepare --version X.Y.Z --commit <40-character-SHA>`. The tool resolves the exact tag, fetches only translation JSON and license over HTTPS, selects required validation keys, validates placeholders and writes candidate data/metadata/license under `.runtime/localization/<SHA>/`. It never writes `lang/` or project manifests.
3. Inspect the printed current-to-candidate diff. Compare old/new candidate files with `git diff --no-index` to distinguish upstream changes from local corrections. Also inspect the license diff and source metadata. If framework keys have changed, use the old catalog from Git history as the old baseline and resolve any missing translations explicitly; a missing message or changed placeholder is a hard failure.
4. Merge selected upstream changes into `lang/pl/validation.php` manually. Preserve local corrections unless the review deliberately replaces them. Never blindly copy a new candidate over a modified local catalog.
5. Update source/version/SHA/import date and hashes in `lang/SOURCE.json`, preserve the complete MIT notice, update maintenance metadata and record any local deviations. Candidate metadata has `imported_at: null` because preparation is not adoption. If the merged catalog differs from the pure candidate, retain the pure candidate hash as `catalog_sha256` so checks continue to report local modifications.
6. Re-run offline checks and user-facing validation checks, inspect the Git diff, and commit the reviewed change. Do not add a Composer localization dependency as an update shortcut.

The source has a 90-day review cadence in the maintenance registry. Review is deliberate; ordinary conversations/builds do not fetch upstream translation updates.

## Frontend preferences and transport

`resources/js/locales/messages.ts` defines matching typed PL/EN keys. `useLocale` owns the root user locale, updates the document language and persists a preference when browser storage allows it. `useTheme` supports light, dark and system preferences and cleans up the system-theme listener. Storage denial falls back to session-only preferences. The application owns each composable once; these are not hidden global stores.

`resources/js/lib/http.ts` is the only fetch boundary. It prefixes relative resource paths with `/api`, requests JSON, uses same-origin credentials, forwards an existing framework XSRF cookie on writes, carries abort signals and exposes HTTP failures without global notifications. Response bodies remain `unknown` until the caller validates the known contract. It does not invent authentication/session endpoints.

The root welcome page does not make health requests; API readiness remains independently testable. Its preference cover demonstrates a local visual transition, not network loading. `AppForm` supplies `novalidate`, controlled submission and a busy guard; actual fields add their known frontend rules when an owning feature introduces them. No business form or admin module is fabricated for this foundation.
