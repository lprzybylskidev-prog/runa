# External development platform updates

The separately supplied updater operates on customer **development checkouts only**. Production receives complete customer commits through deployment. The external tool provides read-only preflight/plan, explicit apply and journaled resume. There is no customer update/integrity command, local trusted-source baseline, repair/force option or automatic infrastructure reconciliation.

Keep distribution/update.py, update_contracts.py, update_runtime.py, update_customization.py, update_execution.py, update_modules.py, update_files.py, module-plan.php and bootstrap.py together outside customer source. The standard-library tool package runs independently of the maintenance checkout, using native Linux/Python/Git/Docker/Compose and an existing trusted owner toolchain image for isolated Composer/pnpm validation.

```sh
python3 update.py --plan --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.4.1 --json
```

Select an actually published stable annotated release. The example target 0.4.1 requires publication before use; earlier pre-separation layouts are unsupported. --preflight performs the same nonmutating checks. Exit 0 indicates a valid planning snapshot; a customization decision may still be required before execution. Neither mode mutates the installation. Private access uses --token-file or interactive --ask-token; never pass credentials in URLs/arguments. --expected-commit pins an independently supplied target commit.

## Source and identity

Installed identity comes exclusively from src/runa/platform.json. Owner release acquisition additionally verifies the release VERSION against its tag. Customer scaffold VERSION is not a runtime/update authority. Independently acquire original and target releases and compare only the delivered src/runa file set, contents, executable modes and safe paths. Receipt hashes and installed Git HEAD never authorize expected source. Missing, unexpected or changed platform files block without override. Owner-only source and production contexts are rejected. A teammate clone does not need the first workstation's acquisition receipt or installation journal.

Generated private error shells live under .runtime/frontend/errors/templates, outside protected source. No generated-output exception hides files inside src/runa. Application source, composed dependency declarations/locks/workspace, private configuration, Git remotes/history and both durable storage owners are preserved. Other delivered scaffold/configuration files follow the exact reviewed plan.

## Infrastructure customization review

Compare original-release and customer Compose/devcontainer/container definitions separately. Read Compose as data without interpolating environment values or resolving env files; never import installed Python/PHP. Include referenced devcontainer Compose files, conventional override files and Dockerfiles shared by multiple services. Reports contain affected service names, a devcontainer indicator and an exact difference fingerprint; no environment values or file contents. Added/removed services are included. Unsupported/unreadable references produce a review blocker rather than an assumed match.

Customization is supported. An execution decision must explicitly continue or abort, is bound to the reviewed fingerprint and must be renewed when differences change. Continuing cannot waive platform mismatch and applies exactly reviewed eligible delivered-file changes, including approved infrastructure replacements/removals. Local instruction extensions remain preserved with explicit reconciliation requirements. Read-only modes only report the decision requirement. Review release-required scaffold/Docker/Compose/env changes and reconcile preserved conflicts manually and commit versioned changes to the customer project. Customization may complicate future upgrades.

## Read-only prerequisites

Check existing operation locks/state, private settings, disk/permissions, actual development services/health/images/mounts/environment/ownership and scoped PostgreSQL migration/seed metadata. Extra services are accepted; required runtime failures and unsafe Docker transport/privileges block. Optional AI availability is independent of application readiness. A fixed read-only SQL transaction never executes target migrations/seeds to predict effects.

Compose target platform declarations with application declarations in external staging and validate preserved locks with isolated no-network/no-hooks Composer/pnpm probes. Scaffold pnpm workspace input is preserved. Frozen/offline structural consistency does not replace build/install supply-chain policy or security audits. Conflicting dependencies/locks block instead of being replaced. Downloads/probe containers are external and disposable; handled completion/failure/interruption cleans them. Reports record download paths/cleanup. SIGKILL/power loss can require scoped inspection of leftover external staging.

The plan lists platform source changes, pending migrations, the explicit required platform seeder and its exact-release completion, owner-scoped module versions/dependency order/migrations/upgrade paths, transaction/idempotency/recovery declarations, ordered execution steps and uncertainties. Module metadata is evaluated with read-only target/dependency mounts, no installed private settings, network or Laravel boot; only source-owned pure descriptors and class reflection run, never migrations or handlers. Applied migration edits/removals and unproven downgrades block. Code rollback cannot reverse schema/data. Preflight is a snapshot; execution must revalidate under an exclusive operation lock.

## Execution and recovery

After manual reconciliation and a fresh plan:

```sh
python3 update.py --apply --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.4.1 \
  --customization-decision continue --review-fingerprint <reviewed-sha256> --non-interactive --json
```

A file plan with pending target actions requires consent even when local defaults are unmodified. Interactive execution asks when customization requires review. Noninteractive/JSON execution blocks without an explicit decision. Unattended consent requires --review-fingerprint from the inspected plan. Abort preserves source/data/services and creates no operation journal.

The tool reacquires original/target releases, revalidates under the shared exclusive operation lock, then records exact release identities and platform database history in the private .runtime/production/in-progress.json journal. It refuses preexisting manual maintenance. It writes an operation-owned maintenance marker and stops Nginx, application, queue and scheduler before source changes. Operators must separately stop any custom service that writes application data.

Source replacement uses independently verified staging and two same-filesystem directory renames. Interrupted partial copies and the gap between renames are recoverable only when retained source matches the original/target releases. Composer install, frozen pnpm install, frontend/error-shell builds and configuration validators operate against the manually reconciled scaffold. The updater preserves composed manifests/locks/workspace, private env files, application source and customer Git history. Compose/devcontainer/environment templates and other eligible delivered files change only through the exact approved actions.

Forward platform migrations run transactionally; required platform seeds and their exact-release completion record share a transaction. Platform migrations must support PostgreSQL transactions and must not perform external side effects. The following platform-modules phase explicitly synchronizes and upgrades every supplied Runa module, including disabled ones. Application migrations/seeds/module installers are not invoked. After source/build/data completion the updater restores its services, verifies actual runtime health and readiness, archives a private completion journal and removes only verified operation staging. Ordinary reopen remains nondestructive.

On failure or interruption, retain the journal, staging, completed database effects and maintenance fencing. Inspect the recorded phase and diagnostics, then explicitly resume the same target:

```sh
python3 update.py --resume --installation /absolute/customer-development \
  --repository lprzybylskidev-prog/runa --target v0.4.1 --non-interactive --json
```

Resume independently downloads both recorded releases and verifies tag objects/commits, source and database history before mutations. Completed migration/seed/module effects are confirmed from database records; completed steps are not replayed. Module source/owner/fingerprint fences survive failure. Transactional steps retry after rollback; ambiguous nontransactional work requires declared idempotency or documented operator reconciliation. The first failed module stops later modules. Builds run again against current inputs. A changed delivered-file review requires fresh continue plus --review-fingerprint from the new report, or an interactive decision. Manual/foreign maintenance, changed protected source or missing history blocks recovery without an override. There is no automatic rollback, backup command or production update mode; review schema/data recovery with the customer. SIGKILL/power loss requires inspection of fencing and external temporary directories before resume.

## Release instructions and propagation

Publish concrete target release requirements for manual reconciliation of dependency composition/locks, bootstrap/build files, Docker/devcontainer/Compose, environment templates/private keys, service activation and data recovery. Use the public [release reconciliation procedure](../../handbook/release/platform-reconciliation.md); avoid automatic infrastructure transitions or application-module installer execution. Runa module discovery/updates belong to the explicit forward module phase. The customer explicitly commits/pushes the complete reviewed project. Teammates pull and initialize local data; production deploys the same customer revision.

## Verification

```sh
python3 tools/maintainer/testing/update.py
python3 tools/maintainer/testing/update-execution.py
python3 tools/maintainer/testing/installation-host.py --updater-preflight --team-propagation --customer-knowledge --customer-deployment
```

Pure contracts cover independently verified Git acquisition, source ownership, dependency/compatibility failures, read-only preservation and customization decision binding. Run them inside the application toolchain for PHP grammar parity. Native disposable acceptance exercises real Docker Compose/Composer/pnpm/PostgreSQL, checks read-only development plans, real platform migration/seed execution, failure fencing/resume and production rejection, and preserves active development resources. Synthetic future release fixtures are not evidence of publication.

## Journaled delivered-file actions

The complete file review binds original/target release identities, local before-state, modes/types and proposed actions. Unattended continue requires both --customization-decision=continue and --review-fingerprint from the inspected plan. Under the operation lock the updater repeats preflight before creating a journal. It stages independent target bytes, retains previous bytes and records each file rename transition durably. Resume validates all retained identities, normalizes known completed changes, and requests a fresh exact review for new local differences. Unknown staging or ambiguous changed transitions block for inspection. Protected-source refusal remains absolute on every resume.

Completion reports manual instruction reconciliation and affected service/devcontainer follow-up. Review image rebuild/recreation, custom writers/services and editor reopen before committing the complete customer project and deploying it. Updater file recovery never claims database rollback.
