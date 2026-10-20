# Installed source integrity

Run `php artisan runa:integrity` in the configured development/operator PHP context,
or add `--json` for structured output. It uses the restricted host executor and the
cold management registry: no application bootstrap, database, key, PHP on the host,
Docker socket in the app or running target application is required. Initial setup
and unavailable-environment recovery still use `./bin/artisan`. The command is
available to installed operators, including production management; HTTP/worker
runtime images contain neither management tooling nor this Python/Git engine.

Exit 0 means no source, infrastructure or composed-input differences. Exit 1 means
findings or an unavailable/invalid trust baseline. Unknown options fail before host
execution. `--plan` previews transport only. There are no exclusion, fix, accept,
refresh-baseline or network-fetch options. Reports go to stdout, include paths and
change kinds, and never contain source contents, environment values or credentials.
The core report does not write files, rotate settings, build, migrate, start services
or repair anything. Normal executor operation receipts/logs remain transport state.

## Trusted baseline and projections

Verified standalone acquisition writes `.git/runa-ownership.json` alongside the
acquisition receipt before publishing source. It records schema/policy version,
exact commit, complete release SHA256, fixed delivery projection, installed profile
and every committed file's path, Git mode and SHA256. Customer scaffold files are
present in this release inventory but remain customer-owned after acquisition.

Hashes come from the complete verified release before owner-only exclusions, never
from accepting installed file contents. Each report independently reconstructs the
inventory from that exact commit's Git objects and checks the complete digest and
baseline/receipt agreement. Missing objects, changed HEAD, stale/modified baselines,
unknown fields/exclusions/profiles and unsafe metadata block the check. Git object
replacement, lazy fetch and inherited Git target/config overrides cannot redefine
the source silently. No network access or Git/config/index writes are needed.

Initial dev/prod acquisition uses the source profile (all source except the fixed
owner exclusions). Real production installation and immutable deployment staging
record the production profile using the fixed production allowlist. Staging records
its own exact target commit/digest; customer and full-release ownership stay distinct.
Neither a missing source file nor an existing dirty checkout can teach the baseline
new hashes. Baseline establishment is an internal acquisition/install/staging action,
not an operator report command. An older installation without this evidence needs
an explicit owner migration/reacquisition, not automatic acceptance of live files.

The trust root remains the owner-supplied launcher, authorized GitHub release and
local operator-controlled receipt/Git object store. This is not an independent
signature, tamper-resistant attestation or protection against an operator deliberately
replacing both the checker and its trust records. Public Git history may contain
excluded distribution tools; projection is not purchase/access enforcement.

## Ownership and findings

- Protected: platform code/resources/routes/config, technical bootstrap/public entry,
  tooling, release/version and other platform source. Modified, missing, mode/type
  changes, unsafe/unreadable paths and unexpected files block protected integrity.
  Ignored/untracked files are inspected directly; Git ignore rules cannot hide them.
- Infrastructure: Docker/devcontainer, standard Compose, Docker context and env
  examples. Drift is reported separately, preserved, and blocks automatic update
  readiness until the later preflight reviews the actual diff/topology and consent.
- Composition: the four composed manifests/lockfiles under src. Manifests are also
  validated against both owners' declarations without resolving dependencies. Differences are
  preserved and reported separately, requiring dependency graph/frozen-lock review
  in preflight; integrity never replaces customer-composed locks with release locks.
- Customer: src/app and its initial scaffold/manifests/code/docs/assets are outside
  protected replacement/hash checks. Customization belongs here, not in Runa source.
- Generated: explicit environment/settings names, resource overlay, dependency/build
  output roots, private runtime state and the approved storage/cache/error outputs.
  Framework PHP cache filenames and generated error HTML names are enumerated;
  an arbitrary PHP file in bootstrap cache, public build or generated error views
  remains an unexpected protected file. CPython cache files are recognized only for
  a corresponding known selected tooling source file and standard cache filename;
  reports disable bytecode writes and do not exempt unknown cached modules.
  Generated artifacts themselves are not source attestation; their runtime/build
  verification is separate from protected source comparison.

Reports include `ok`, `protected_ok`, `update_blocked`, commit/digest/profile,
checked-file count, composition_valid, release ownership counts and sorted `changes` with path/category/
change. Infrastructure/composition drift can leave protected_ok true while ok is
false and update_blocked is true; it is not permission to update. The internal
require_protected guard rejects every protected mismatch for later preflight use.
Update preflight, concrete infrastructure consent and the updater remain subsequent
roadmap points, not capabilities supplied by this report.

Directory traversal uses anchored descriptors without following symlinks. Expected
tracked symlinks are compared as links; their contents are never dereferenced.
Unexpected links, linked parent directories, special files, excessive depth/count
and unreadable source block rather than escape/hang. Customer/runtime/dependency
subtrees are not recursively traversed; their root cannot be a substituted link.

Offline regression suite: `python3 tools/testing/integrity.py`. Native cold operator
acceptance: `python3 tools/testing/integrity-host.py` (no app/database required). It uses real Git
objects, baseline tampering, source/mode/missing/unexpected/ignored files, cache
injection, symlinks/FIFOs, production sparse source and file/metadata fingerprints
proving report nonmutation. `tools/testing/installation-host.py` also exercises real
normal Artisan reporting after isolated dev setup and production recovery/staging.
