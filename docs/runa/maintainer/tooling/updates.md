# External owner update preflight and plan

The separately supplied updater operates on customer **development checkouts only**. Production receives complete customer commits through deployment. Read-only preflight/plan is delivered; applying updates and journaled recovery are the next RM-2026-005 point. There is no customer update/integrity command, local trusted-source baseline, repair/force option or automatic infrastructure reconciliation.

Keep distribution/update.py, update_contracts.py, update_runtime.py, update_customization.py and bootstrap.py together outside customer source. The standard-library tool package runs independently of the maintenance checkout, using native Linux/Python/Git/Docker/Compose and an existing trusted owner toolchain image for isolated Composer/pnpm validation.

```sh
python3 update.py --plan --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.3.0 --json
```

Select an actually published stable annotated release. The planned 0.3.0 is unavailable until release; earlier pre-separation layouts are unsupported. --preflight performs the same nonmutating checks. Exit 0 indicates a valid planning snapshot; a customization decision may still be required before future execution. Neither mode mutates the installation. Private access uses --token-file or interactive --ask-token; never pass credentials in URLs/arguments. --expected-commit pins an independently supplied target commit.

## Source and identity

Installed identity comes exclusively from src/runa/platform.json. Owner release acquisition additionally verifies the release VERSION against its tag. Customer scaffold VERSION is not a runtime/update authority. Independently acquire original and target releases and compare only the delivered src/runa file set, contents, executable modes and safe paths. Receipt hashes and installed Git HEAD never authorize expected source. Missing, unexpected or changed platform files block without override. Owner-only source and production contexts are rejected. A teammate clone does not need the first workstation's acquisition receipt or installation journal.

Generated private error shells live under src/.runtime/runa-errors, outside protected source. No generated-output exception hides files inside src/runa. Application source, dependency declarations/locks, scaffold, configuration, Git remotes/history and both durable storage owners are preserved.

## Infrastructure customization review

Compare original-release and customer Compose/devcontainer/container definitions separately. Read Compose as data without interpolating environment values or resolving env files; never import installed Python/PHP. Include referenced devcontainer Compose files, conventional override files and Dockerfiles shared by multiple services. Reports contain affected service names, a devcontainer indicator and an exact difference fingerprint; no environment values or file contents. Added/removed services are included. Unsupported/unreadable references produce a review blocker rather than an assumed match.

Customization is supported. An execution decision must explicitly continue or abort, is bound to the reviewed fingerprint and must be renewed when differences change. Continuing cannot waive platform mismatch and preserves customer infrastructure. Read-only modes only report the decision requirement. Reconcile release-required scaffold/Docker/Compose/env changes manually and commit versioned changes to the customer project. Customization may complicate future upgrades.

## Read-only prerequisites

Check existing operation locks/state, private settings, disk/permissions, actual development services/health/images/mounts/environment/ownership and scoped PostgreSQL migration/seed metadata. Extra services are accepted; required runtime failures and unsafe Docker transport/privileges block. Optional AI availability is independent of application readiness. A fixed read-only SQL transaction never executes target migrations/seeds to predict effects.

Compose target platform declarations with application declarations in external staging and validate preserved locks with isolated no-network/no-hooks Composer/pnpm probes. Scaffold pnpm workspace input is preserved. Frozen/offline structural consistency does not replace build/install supply-chain policy or security audits. Conflicting dependencies/locks block instead of being replaced. Downloads/probe containers are external and disposable; handled completion/failure/interruption cleans them. Reports record download paths/cleanup. SIGKILL/power loss can require scoped inspection of leftover external staging.

The plan lists platform source changes, pending migrations, the explicit required platform seeder and its exact-release completion, ordered execution steps and uncertainties. Applied migration edits/removals and unproven downgrades block. Code rollback cannot reverse schema/data. Preflight is a snapshot; execution must revalidate under an exclusive operation lock.

## Verification

```sh
python3 tools/maintainer/testing/update.py
python3 tools/maintainer/testing/installation-host.py --updater-preflight
```

Pure contracts cover independently verified Git acquisition, source ownership, dependency/compatibility failures, read-only preservation and customization decision binding. Run them inside the application toolchain for PHP grammar parity. Native disposable acceptance exercises real Docker Compose/Composer/pnpm/PostgreSQL, checks read-only development plans and production rejection, and preserves active development resources. Synthetic future release fixtures are not evidence of publication.
