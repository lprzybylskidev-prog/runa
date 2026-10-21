# Standalone GitHub bootstrap

The repository is currently public. The same bootstrap supports future private
GitHub access; public acquisition does not need a token. It does not change
repository visibility. Keep the reviewed owner tool directory containing `distribution/bootstrap.py` and `distribution/installation.py` outside the destination. Do not pipe an unreviewed network script into a shell.
The bootstrap uses only Python's standard library; PHP, Composer, Node and pnpm
remain containerized.

## Host and destination

Require native Linux, Python 3.11+, Git 2.39+, Docker Engine 26+ and Compose 2.35+
with daemon access. Checks apply even when installation is postponed. The new
destination must have an existing, writable parent owned by the current user,
without symlink components, and at least 5 GiB free space. Container image builds
and development AI models may need substantially more space. Never run this as
root merely to work around ownership or Docker permissions.

Interactive invocation:

```sh
python3 bootstrap.py
```

It asks for the authorized GitHub owner/name (default: the canonical Runa
repository), optional private access, an accessible published stable release,
new destination and development installation `now`/`later`. Release listing is
bounded to 1000 entries; use an explicit tag for larger repositories.

Automation must provide every selection and never prompts:

```sh
python3 bootstrap.py --non-interactive \
  --repository lprzybylskidev-prog/runa --tag v0.2.1 \
  --destination "$PWD/runa-source" --install later
```

Use an actually published tag; development branch changes are delivered only
after their release. The example downloads the existing 0.2.1 release and does
not assert that its source contains the unfinished RM-2026-005 features.

## Private access

For interactive private access, use the hidden prompt or `--ask-token`. For
automation, add `--token-file /absolute/path/to/private-credential`. The file must
be a regular file owned by the current user, mode 600 or 400, at most 4 KiB;
symlinks and group/other access are rejected. Supply a GitHub read credential
authorized for the selected repository (fine-grained Contents read access).
Create/manage it through the operator's approved secret workflow, outside the
checkout. Do not put the token in a command argument, URL, shell history or source.

Tokens exist only in bootstrap memory, the HTTPS Authorization header and the
short-lived Git fetch process environment. They are never written into Git
configuration, remotes, source, receipts or diagnostics. Git runs without inherited
global/system configuration, credential helpers, tracing, proxy or TLS overrides;
certificate verification remains enabled. Credential-free `origin` retains the
canonical HTTPS repository URL. Bootstrap HTTP and Git requests refuse redirects,
including canonical repository renames; explicitly select the canonical owner/name
after independently checking it. No credentials are forwarded to another host.
Denied/revoked access fails with fixed messages, without echoing server bodies or
Git stderr. Private live acceptance requires authorized access; synthetic tests
are not evidence of a successful real private-repository deployment.

## Source verification and publication

The selected tag must identify a published stable GitHub release and an annotated
`vX.Y.Z` Git tag pointing directly to a commit. Branches, lightweight tags,
prereleases and arbitrary URLs are refused. Bootstrap checks the API repository
ID/name/clone URL, resolves the tag object and commit, fetches only that exact tag
using Git HTTPS, and compares the fetched identities with the API. Git object
checking is enabled. The repository/release/tag identities are read again before
publication to detect changes during the download. Root VERSION must match the tag.

Before checkout, reject unsafe paths, submodules, unsupported file types and source
larger than 512 MiB or 50000 files. After checkout, verify every tracked file's Git
blob hash and executable mode. Relative symlinks used by existing bin transports
are allowed only when they resolve to tracked source inside the checkout; escaping,
absolute, dangling, cyclic and directory-parent links are rejected.

The printed SHA-256 digest covers each tracked path, mode and SHA-256 content hash
in Git tree order, encoded as compact UTF-8 JSON arrays followed by LF. Symlink
content is its literal target. Supply independently delivered `--expected-commit`
(40 lowercase hex characters) and/or `--expected-digest` (64 lowercase hex
characters) when available. A digest obtained from the same account/source is
corruption detection, not an independent signature. Trust remains with the reviewed
bootstrap, local host tools, TLS and the authorized GitHub account/release. Checking
a bound tag does not turn a mutable GitHub ref into an independently signed release;
independent pinning also detects changes made before acquisition.

Download into a private sibling staging directory; published source stays mode
700, owned by the current user. Linux `renameat2` with
`RENAME_NOREPLACE` publishes the verified checkout atomically. Existing files,
directories (even empty ones) and symlinks are never overwritten, including a
destination created concurrently. Failed/interrupted acquisition removes staging
and publishes no partial source. Git subprocess groups are killed on interruption
or timeout. SIGINT/SIGTERM receive cleanup; a hard kill or power loss may leave a
private `.runa-bootstrap-*` directory requiring operator inspection/removal. It is
never presented as an installed destination.

## Installation and resume

Release acquisition initializes development only. Production uses distribution/initialize.py with the customer repository, not a Runa release. Teammates clone the customer project and use runa:dev:initialize; they do not reacquire upstream or copy .git metadata from another workstation. See [initialization](installation.md).

Successful acquisition writes credential-free `.git/runa-bootstrap.json` with
repository ID/name, tag object, commit, source digest, mode, trust and state
`downloaded`. The checkout is clean and detached. This is acquisition evidence,
not an installation-success marker or trusted file baseline. No local ownership inventory or customer integrity checker is generated. The external development updater independently obtains original/target release source for preflight, apply and verified journaled resume.

The owner runs the external launcher personally; neither the launcher nor its adjacent installation helper is delivered as customer tooling. It is never installed into bin or customer source.
After verifying the **complete** release, acquisition applies a fixed non-cone
sparse projection before atomic publication. Both now/later exclude
`distribution/`, `tools/maintainer/`, `src/runa/tests/`, `src/runa/tools/`, install/project aliases and old owner entrypoints. Customer hooks remain available under tools/application.
The credential-free receipt records the full source digest and projection version
plus exact exclusions. Git remains clean and detached at the verified commit;
subsequent checkouts retain the sparse projection. Git objects/history may contain
excluded sources: this is delivery layout, not confidentiality or purchase enforcement.

The upstream fetch URL stays canonical; its push URL is explicitly disabled.
Installation/deployment refuses missing/inconsistent acquisition roles, restored
owner source or changed remotes rather than modifying an unknown/maintenance/customer
repository. Existing customer remotes/history are never automatically repointed.
A distinct customer-source remote lifecycle is not implemented by this acquisition flow.
The local receipt records ownership context; it is not a signature or a security
boundary against an operator deliberately rewriting the installation.

`--install later` prints a shell-quoted fixed Python invocation of the
external installation helper, with the destination as its working directory. `--install now` invokes exactly that
helper and propagates failure. No installed standalone launcher or public
`runa:install` command is required. Keep the separately supplied launcher outside
the destination; retain its adjacent installation.py helper for postponed installation and resume.
Optional profile/settings/noninteractive options are forwarded. After a failed
install preserve source/settings and rerun the printed command with `--resume`;
do not reacquire into that directory. Ordinary lifecycle/recovery uses
`php artisan` and the restricted host executor. External development updater preflight/plan, apply and journaled resume are separate from acquisition. See [owner updates](updates.md).
See [installation](installation.md) for settings, preservation and recovery.

Offline acceptance: `python3 tools/maintainer/testing/bootstrap.py`. The local quality graph
includes `bootstrap-source-contracts`; tests use synthetic credentials and real
temporary Git repositories/atomic filesystem operations. Live public acquisition
checks the real API/ref/file identities without provisioning services. See
[GitHub releases](https://docs.github.com/en/rest/releases/releases),
[references](https://docs.github.com/en/rest/git/refs) and
[annotated tags](https://docs.github.com/en/rest/git/tags) for the API contract.
