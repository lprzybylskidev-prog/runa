# Python packages and development workflow

The public [Python guide](../../../handbook/development/python-tooling.md) owns
language, package direction and artifact placement. This guide owns private suite selection, owner analysis and artifact preparation.
Shared dependency preparation/configuration is delivered through the public guide.

## Frozen tools

From the owner host checkout or app container:

```sh
python3 tools/maintainer/python-tool setup
python3 tools/maintainer/test-python --scope=unit --scope=contracts
python3 tools/maintainer/test-python --scope=unit --path=unit/test_python_adapter.py
python3 tools/maintainer/test-python --scope=contracts --marker=packaging
python3 tools/maintainer/python-tool format --check
python3 tools/maintainer/python-tool lint
python3 tools/maintainer/python-tool typecheck
```

Implementation points use only necessary focused diagnostics; the commands above
are a catalogue, not instructions to run broad checks after each point. Full
format/lint/strict typing, suites, wheels and live acceptance run in final quality.
Caller retirement and annotation implementation are complete; the migration
[reconciliation](reconciliation.json) records each baseline identity. A clean
full-scope analysis is established only by final verification.

The two stable launchers select fixed module adapters with their own checkout as
cwd; they work from unrelated directories and preserve literal arguments. Help
and parser refusal happen before Docker or installation. Host calls delegate
through the existing configured Compose app service. Production/customer receipt
guards precede locks and tool execution. No owner Artisan command is registered.

Setup creates a stdlib venv without pip, bootstraps from a separately hash-locked
PyPI pip wheel and installs the complete hash-locked wheel graph with isolated pip.
It does not need Debian ensurepip, host pip, npm installation scripts or a new pnpm
manifest. The official Pyright npm tarball is verified against committed SHA-512
integrity and extracted as regular files; optional macOS fsevents is omitted on
Linux. Runtime Python remains stdlib-only. See the
[dependency review](dependency-review.md) and maintenance registry for exact pins.
Lock updates are deliberate roadmap changes, never an implicit setup upgrade.

## Discovery and resource ownership

The owner selector passes explicit private unit/contracts/integration/e2e paths
to `tools/application/python/pytest.ini`, with an explicit workspace root, importlib
mode and strict markers/config. The configuration and frozen locks are shared;
private suites and owner analysis remain excluded from delivery.
There is no import-path injection. Private suites use explicit pytest paths and
ordinary qualified imports. Native acceptance runs through fixed package modules.
Obsolete filename adapters have been removed; the original ledger and scenario
map retain their baseline identities.

Default scopes are unit and contracts. Repeat `--scope`, use a `--filter` pytest
keyword expression, or provide one `--path` relative to `tests/python` (including
its scope directory, optionally followed by `::node`). Unknown scopes, escaping
paths and files outside selected scopes are refused before delegation. Docker,
network, knowledge, database and packaging markers are excluded by default; select
them explicitly with `--marker`. Missing selected prerequisites must fail, not skip.
No fixture starts infrastructure implicitly. Integration/e2e prerequisite routing
is completed alongside the domain migrations before final verification.

The adapter shares `.runtime/tests/suite.lock` with native owner suites. It reaps
runner process groups on interrupt before releasing the lock, restores SIGTERM,
returns native child status and uses 130 for keyboard interruption. Pytest plugin
autoload and ambient pytest options are disabled. Runtime-root temporary fixtures
and caches are explicit; JUnit reports use selection hashes under
`.runtime/python/reports`. Quality also retains each command's own log. Legacy
quality/native suite locks and coordinator semantics are unchanged.

`PythonToolExecutor` now composes the canonical core process runner, stable-inode
suite lock and scoped interrupt handler. Host app delegation uses the shared
development Compose context. The existing suite directory/readable lock uses an
explicit non-private policy; it still refuses other-user writes and unsafe file
identities. No lock inode or running native-suite coordination is replaced.
Environment/receipt discovery uses the shared guarded development context.

Common support provides scripted `ProcessResult` outcomes and records actual
immutable `ProcessSpec` values, plus temporary workspace/Git factories and canonical
config fixtures. These test helpers implement `ProcessRunner`; they are not another
operational executor API. Core unit scenarios cover independent workspaces/releases,
literal argv/stdin/env, native errors, cancellation/timeouts, process group cleanup,
handler restoration, atomic publication failure and unsafe file/lock refusal.

## Physical artifacts

Each ownership domain has explicit setuptools metadata: root core/application,
`tools/maintainer/packaging/pyproject.toml` for owner, and
`distribution/pyproject.toml` for external tooling. One canonical core is copied
into the external build artifact. The owner wheel adds only `tools.maintainer`
and never overwrites customer `tools/__init__.py`. Resource lists are allowlists; shared development locks/configuration ship with
application tooling, while owner suite sources remain private.

```sh
python3 tools/maintainer/python-tool packages --kind=application
python3 tools/maintainer/python-tool packages --kind=maintainer
python3 tools/maintainer/python-tool packages --kind=external
```

The builder stages declared packages/resources under `.runtime/python/build`,
copies canonical VERSION, and invokes the installed locked setuptools backend
without dependency resolution or build isolation downloads. Build/egg metadata
stays in that staging tree. Contract scenarios physically inspect projected files,
layer the two owner/customer wheels without collisions and import package roots
from unrelated cwd with no repository import path; external gets its own directory.
Application projection contracts import every authored module without private
packages. Full wheel, standalone bundle and native transport acceptance run in
final verification; inert imports alone do not establish runtime acceptance.

The tooling quality group and legacy tooling impact selection include toolchain
setup, Python formatting/lint/strict typing, default unit/contracts and separate
packaging contracts. Real prerequisites are graph edges and shared suite ownership
is serialized by `testing-state`. Customer tooling quality includes delivered Python setup/format/lint/type checks;
it never selects private owner tests. Aggregate suite routing uses the shared lifecycle with explicit owner roots.

## Source bundles and private analysis

The external package build additionally writes `runa-external-X.Y.Z.tar.gz` and
`bootstrap.py` alongside its wheel under `.runtime/python/build/wheels/external`.
The archive contains only the declared external/core source and resources from
canonical authored packages. Extract it and invoke its fixed `distribution/*.py`
launchers from any cwd; no pip or project installation is required. The separate
single-file bootstrap remains the reviewed first-acquisition interface. Acquisition
retains its independently enforced projection/credential rules; it never imports
unverified customer code. Bundle generation and wheel builds belong to final
packaging verification, not implementation-point checks.

Private owner Ruff and Pyright additions live under `tools/maintainer/python`.
`python-tool format|lint|typecheck` selects those explicit configurations; root
`pyproject.toml` retains only delivered metadata and shared analysis rules. Owner
Pyright includes all first-party tooling, external distribution, private cases and
fixtures, with the same strict CPython 3.11 rules and frozen tools. No scope is
excluded to conceal annotation work. Public commands still select application
ownership and application-owned extension configuration.

The host executor recognizes exact previous-release daemon command paths only
while stopping an existing owned daemon. This is recovery of persisted process
identity, not a retained source adapter or a second executor implementation.
Serialized deployment journals, receipts and quality evidence retain their
compatibility/read policies. Native PHP/JS runners and source-owned command
registries remain unchanged.
