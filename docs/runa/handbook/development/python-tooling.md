# Python tooling

Runa's operational Python source uses the standard library and CPython 3.11
language features. Native Linux with Docker Engine/Compose is the supported host
environment. The maintained runtime contexts are container CPython 3.11 and host
CPython 3.12; `requires-python >=3.11` is a language floor, not a claim that every
future interpreter has passed acceptance. Host operation needs no pip, virtual
environment or package installation. PHP, Node and development tooling stay in
the project containers.

The repository-local `tools` package separates reusable infrastructure in
`tools/core` from delivered application tooling in `tools/application`. Core may
import only the standard library and core; application may additionally import
application modules. Neither may require owner tools or external installer code.
Package initializers perform no operations. New internal integrations use qualified
imports and explicit arguments; preserve existing source transports while the
owning migration replaces their old imports. The public command registry remains
Laravel Artisan; Python package names are not a customer extension API.

Root `pyproject.toml` defines the core/application distribution, explicitly named
packages/resources, Python language target, Ruff rules and strict Pyright policy.
It declares no runtime or developer dependencies. Package versions follow root
`VERSION`; Python packages do not receive independent releases. Private wheel
preparation is an engineering check, not an operational installation requirement.
The production images contain none of these Python tools or development packages.

## Shared infrastructure

`tools.core.context` owns immutable `WorkspaceContext`, `ExecutionContext`,
`ReleaseContext` and `ComposeContext`. Tool source, requested workspace and release
source are explicit identities; constructing them does not detect an installation
role, authorize an operation, follow symlinks or change the working directory.
Application services own receipt, development/production and environment policy.
`tools.application.development_runtime` supplies the development Compose file/env
selection and app transport; production selection remains a separate policy.

`tools.core.process` exposes `ProcessSpec`, `ProcessResult`, `ProcessRunner` and
`SubprocessRunner`. Arguments are literal tuples; cwd is absolute and environment
is an explicit copied mapping. Stdin is explicitly inherited, closed or bytes;
output is inherited or captured as bytes. Timeouts are opt-in finite positive
durations; no timeout means the caller deliberately allows a long operation.
Results retain native return codes. `checked()` raises a safe failure without
including argv, environment or captured output. Inherited streams preserve the
caller's terminal; no shell or pseudo-terminal is added.

The runner owns a new process group. Timeout, cooperative cancellation and raised
exceptions terminate and reap the direct child, kill remaining group members and
close pipes before returning control to an enclosing resource/lock scope. Children
that intentionally detach from that group require a domain ownership journal;
this primitive does not replace Docker endpoint/worker recovery. Cleanup masks
repeated interrupt signals until reaping finishes. `interrupt_signals()` temporarily
maps SIGTERM to interruption in the command's main thread and restores the prior
handler; services do not install handlers at import. `Cancellation` and `Clock`
provide explicit cancellation/time seams without a global registry.

`tools.core.state` supplies bounded JSON and stable-inode advisory locks. Directory
traversal uses no-follow directory descriptors and an explicit trusted anchor
(normally the selected workspace). Below that anchor, ancestors require safe
ownership/modes; leaf directories are operator-owned. This accepts a group-writable
workspace bind mount without permitting writable shared state beneath it. No
component, including the anchor, may be traversed through a symlink. Private state files must be regular, mode 600
or stricter, operator-owned and singly linked. JSON rejects duplicate keys and
non-finite values; services validate their own journal schemas after decoding.
Writes use a unique private temporary, atomic replacement and optional file/directory
fsync. Failed publication preserves the old file and removes only its temporary;
a durability error after replacement is an uncertain commit for the domain to
reconcile, never permission to replay a data operation. State is never cleared
automatically. Callers serialize read/modify/write operations with their domain lock.

Locks are never truncated, replaced or unlinked. The default is a private file;
an explicit non-private lock policy permits existing mode 644 coordination files
while still rejecting foreign ownership, hardlinks, symlinks and writes by other
users. This preserves shared native-suite lock identities during migration.
`tools.core.reporting` offers native command results, fixed safe error boundaries
and caller-supplied exact secret masking. Report only authored safe diagnostics;
masking is not permission to print arbitrary subprocess or exception payloads.

Legacy consumers migrate by family. The owner Python tool executor already uses
these contracts; legacy application transports keep their existing bootstrap until
their package adapters migrate together. Do not introduce new bare imports or
copy the legacy process/Compose/lock implementations into new consumers.

All generated private Python state belongs under the workspace-root
`.runtime/python`: `venv`, `cache`, `build`, `pycache`, `node` and `reports`.
Do not create source-local virtual environments, bytecode, wheel/egg metadata or
pytest caches. Development containers already set `PYTHONPYCACHEPREFIX`; preserve
that setting in new transports. Reports, caches and unrelated operation state are
preserved during cleanup. See [artifact placement](../architecture/project-principles.md#generated-tooling-and-build-state).

Application developers continue using the [supported commands](../tooling/commands.md)
and [testing workflows](../testing/strategy.md). Owner pytest suites, dependency
locks and package assembly are excluded from customer delivery. No developer
dependency is needed to reopen or recover a customer installation.
