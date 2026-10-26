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
