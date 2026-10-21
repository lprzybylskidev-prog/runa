# External development platform updates

The separately supplied updater operates on customer **development checkouts only**. Production receives complete customer commits through deployment. The external tool provides read-only preflight/plan, explicit apply and journaled resume. There is no customer update/integrity command, local trusted-source baseline, repair/force option or automatic infrastructure reconciliation.

Keep distribution/update.py, update_contracts.py, update_runtime.py, update_customization.py, update_execution.py and bootstrap.py together outside customer source. The standard-library tool package runs independently of the maintenance checkout, using native Linux/Python/Git/Docker/Compose and an existing trusted owner toolchain image for isolated Composer/pnpm validation.

```sh
python3 update.py --plan --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.3.0 --json
```

Select an actually published stable annotated release. The planned 0.3.0 is unavailable until release; earlier pre-separation layouts are unsupported. --preflight performs the same nonmutating checks. Exit 0 indicates a valid planning snapshot; a customization decision may still be required before execution. Neither mode mutates the installation. Private access uses --token-file or interactive --ask-token; never pass credentials in URLs/arguments. --expected-commit pins an independently supplied target commit.

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

## Execution and recovery

After manual reconciliation and a fresh plan:

```sh
python3 update.py --apply --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.3.0 \
  --customization-decision continue --non-interactive --json
```

Omit the decision for an unmodified environment. Interactive execution asks when customization requires review. Noninteractive/JSON execution blocks without an explicit decision. Supply --review-fingerprint from the reviewed plan to bind initial consent to that snapshot. Abort preserves source/data/services and creates no operation journal.

The tool reacquires original/target releases, revalidates under the shared exclusive operation lock, then records exact release identities and platform database history in the private .runtime/production/in-progress.json journal. It refuses preexisting manual maintenance. It writes an operation-owned maintenance marker and stops Nginx, application, queue and scheduler before source changes. Operators must separately stop any custom service that writes application data.

Source replacement uses independently verified staging and two same-filesystem directory renames. Interrupted partial copies and the gap between renames are recoverable only when retained source matches the original/target releases. Composer install, frozen pnpm install, frontend/error-shell builds and configuration validators operate against the manually reconciled scaffold. The updater never rewrites manifests/locks, Compose/devcontainer/env files, application source or customer Git history.

Forward platform migrations run transactionally; required platform seeds and their exact-release completion record share a transaction. Platform migrations must support PostgreSQL transactions and must not perform external side effects. Application migrations/seeds are not invoked. After source/build/data completion the updater restores its services, verifies actual runtime health and readiness, archives a private completion journal and removes only verified operation staging. Ordinary reopen remains nondestructive.

On failure or interruption, retain the journal, staging, completed database effects and maintenance fencing. Inspect the recorded phase and diagnostics, then explicitly resume the same target:

```sh
python3 update.py --resume --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.3.0 --non-interactive --json
```

Resume independently downloads both recorded releases and verifies tag objects/commits, source and database history before mutations. Completed migration/seed effects are confirmed from database records; completed seeds are not blindly replayed. Builds run again against current inputs. A changed infrastructure review requires fresh continue plus --review-fingerprint from the new report, or an interactive decision. Manual/foreign maintenance, changed protected source or missing history blocks recovery without an override. There is no automatic rollback, backup command or production update mode; review schema/data recovery with the customer. SIGKILL/power loss requires inspection of fencing and external temporary directories before resume.

## Release instructions and propagation

Publish concrete target release requirements for manual reconciliation of dependency composition/locks, bootstrap/build files, Docker/devcontainer/Compose, environment templates/private keys, service activation and data recovery. Use the public [release reconciliation procedure](../../handbook/release/platform-reconciliation.md); avoid automatic infrastructure transitions or module discovery. The customer explicitly commits/pushes the complete reviewed project. Teammates pull and initialize local data; production deploys the same customer revision.

## Verification

```sh
python3 tools/maintainer/testing/update.py
python3 tools/maintainer/testing/update-execution.py
python3 tools/maintainer/testing/installation-host.py --updater-preflight --team-propagation --customer-deployment
```

Pure contracts cover independently verified Git acquisition, source ownership, dependency/compatibility failures, read-only preservation and customization decision binding. Run them inside the application toolchain for PHP grammar parity. Native disposable acceptance exercises real Docker Compose/Composer/pnpm/PostgreSQL, checks read-only development plans, real platform migration/seed execution, failure fencing/resume and production rejection, and preserves active development resources. Synthetic future release fixtures are not evidence of publication.
