# External owner update preflight and plan

The separately supplied owner updater currently provides **read-only preflight and planning**. Applying updates, journaled resume and standard infrastructure transitions remain RM-2026-005 points 11–12. There is no customer Artisan update/integrity command, local trusted-source baseline, repair/force option, license service or backup prerequisite.

Keep the reviewed `distribution/update.py`, `update_contracts.py`, `update_runtime.py` and `bootstrap.py` together outside the customer installation. This standard-library Python tool package also runs outside a maintenance checkout. Host prerequisites are the bootstrap's native Linux/Git/Docker/Compose/Python requirements. A pre-existing trusted owner toolchain image supplies Composer/pnpm for isolated lock validation; the updater never pulls/builds one automatically and never installs development tooling into the production project.

```sh
python3 update.py --plan --installation /absolute/customer-installation \
  --repository lprzybylskidev-prog/runa --target v0.3.0 --json
```

Use an actually published release. The planned 0.3.0 example is unavailable until this roadmap is released. Existing pre-RM-005 releases do not contain the separated schema-1 manifest/projection contract; unsupported layouts fail closed rather than adopting a local checkout as the original source. `--preflight` performs the same complete nonmutating checks and emits the structured plan. Human output is the default; JSON has schema version 1. Exit 0 means the snapshot is ready for the currently supported plan; blockers return nonzero. Neither mode applies anything. No default execution mode exists.

Private GitHub access uses `--token-file` or interactive `--ask-token`, with the bootstrap's private-file checks, no redirects and transient credential handling. Never pass credentials in URLs/arguments. `--expected-commit` optionally pins the independently supplied target commit. Repository selection is explicit owner authorization, checked against acquisition role metadata; customer remotes and Git history are untouched.

## Source authority and ownership

Read the installed platform manifest and canonical VERSION, resolve the original published annotated release independently through GitHub, download/verify its tag/commit/files using the bootstrap, then compare actual protected file sets/content/executable modes. Receipt hashes and installed Git HEAD are not expected-source authorities. For production, inspect both the operator source and the exact successful active release. Unknown versions, source modifications/deletions/additions, unsafe paths/links and owner material in an installation block with relative path/change-kind diagnostics. No consent can waive protected-source differences.

Application source/documentation and composed dependency manifests/locks are preserved. Generated outputs have explicit exceptions: dependency/build output roots, operational state, known environment inputs, exact framework cache names and hashed compiled views. Arbitrary PHP under writable storage or unknown framework-cache files is not an exception. Source-directory symlinks and symlinks masquerading as generated files block. Production projection is read as literal data from verified original release source, never by executing its Python. No installed application/platform PHP or mutation hooks are executed to discover effects.

Original/target Git downloads and candidate manifests live in a private temporary directory outside the installation. TMPDIR inside the installation is rejected before creation. Normal completion, failure and handled interruption remove staging; SIGKILL/power loss may leave an operator-owned `runa-update-plan-*` directory requiring scoped inspection/removal. Reports record temporary paths and cleanup status. Reports go to stdout; do not redirect them into the installation when asserting filesystem preservation.

## Prerequisites and planned work

Existing operation locks are observed without creating lock/state files; running/unresolved operations and incomplete installation block. Private input permissions, available disk and write access are checked without write probes. Preflight holds a shared existing operation lock where present; it is a snapshot, not an execution reservation. Mutation must revalidate under an exclusive lock.

Compose resolution, exact running image IDs, service health/ownership, actual mounts/environment/privileges and production isolation are checked using read-only Docker operations. Supported generated TLS structure/content is validated, and development overlays may only change documented resources, workers, UID/GID and the exact standard GPU profile. The GPU descriptor is read as literal data from verified generator source without importing it. Unsupported topology and operator/active customer-input drift block for explicit review. Bind mounts must be declared by the verified base source or the supported TLS overlay; Docker transport and unsafe privileges are always refused. A fixed PostgreSQL query authenticates as the runtime role over TCP in an explicit read-only transaction and reads only platform migration/seed metadata.

Target platform/application dependency declarations are composed in external staging with the customer's existing locks. Production validates compatibility and dependency composition independently for the operator checkout and active application release, which may contain different customer revisions. Composer validates lock consistency without plugins/scripts. pnpm uses frozen/offline lockfile-only validation with `--trust-lockfile` solely to separate structural consistency from registry-dependent supply-chain checks; it installs no packages and runs no hooks. This is **not** a security audit or approval to skip installation policy. Actual builds/installations and existing release security gates retain all supply-chain policies/audits. Incompatible declarations or locks block instead of downloading replacement platform locks. Probe containers have no network, Docker socket, application state or credentials and are removed after use, including handled failures.

The plan lists pending platform migrations, the explicitly required platform DatabaseSeeder and its exact-release completion status, protected-source/infrastructure differences, ordered maintenance/build/data/switch/health steps and uncertainty. Applied migration edits/removals and downgrades with unproven data compatibility block. New migration/seed bodies are never executed during planning. Application migrations/seeds are excluded. Infrastructure changes require the reviewed release-declared transition mechanism in point 12; they are reported and currently block rather than being implicitly accepted. Customer-input review never overrides protected-source mismatch. Code rollback cannot reverse schema/data.

## Verification

```sh
python3 tools/maintainer/testing/update.py
python3 tools/maintainer/testing/installation-host.py --updater-preflight
```

Run the first command in the application toolchain too for PHP compatibility-grammar parity. Offline tests include actual Git/tag/blob verification with a synthetic API/local transport and parity with the trusted GPU generator. Native acceptance uses isolated development/production services, real Composer/pnpm and PostgreSQL, verifies platform-only plans and snapshots source/config/locks/database rows/container identities before and after. `--production-only` selects the shorter production-only acceptance. Reference releases are synthetic fixtures, not evidence that an unpublished GitHub release exists. Both paths verify scoped external staging cleanup. No real production deployment or data reset is part of these checks.
