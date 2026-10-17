# Development Resources

Profiles are selected through `./bin/dev profile` and persisted in ignored local configuration such as `.env.dev.local`. The portable default is `standard`; use `high` for the initial project owner's machine.

| Profile | Intent |
|---|---|
| low | Smaller machines; reduce service memory and test concurrency together |
| standard | Portable default; leave capacity for the host/editor/browser |
| high | Accepted initial high-performance preset below |
| custom | Deliberate local overrides for measured hardware/workload needs |

Accepted high values:

```dotenv
DEV_CPU_LIMIT=20
DEV_MEMORY_LIMIT=16G
TEST_WORKERS=12
PLAYWRIGHT_WORKERS=4
```

Tune PostgreSQL, Qdrant, embeddings and Nginx coherently with the application limits. Do not allocate the entire host budget independently to every container. The environment roadmap establishes and documents measured low/standard values and each variable's scope; those values are not architectural constants.

Doctor reports the selected profile and effective CPU/memory/worker limits. Change the profile without editing versioned Compose files. CPU-only embeddings must work on hosts without NVIDIA support. Diagnose memory pressure, I/O or shared-state failures before increasing limits or retrying a full parallel suite.

## Implemented allocation and commands

The application/devcontainer has these initial limits. These are portable starting allocations, verified through resolved Compose configuration, rather than workload benchmark results:

| Profile | App CPU limit | App memory limit | Backend test workers | Playwright workers |
|---|---:|---:|---:|---:|
| low | 2 | 2G | 2 | 1 |
| standard | 4 | 4G | 4 | 2 |
| high | 20 | 16G | 12 | 4 |

`DEV_CPU_LIMIT` and `DEV_MEMORY_LIMIT` apply to the app/devcontainer, not independently to every sibling service. Service budgets are established with their services. `TEST_WORKERS` and `PLAYWRIGHT_WORKERS` configure test runner concurrency when those runners are introduced.

Use `./bin/dev profile` to report effective local settings, `./bin/dev profile high` to select a preset, or for example:

```sh
./bin/dev profile custom DEV_CPU_LIMIT=6 DEV_MEMORY_LIMIT=6G TEST_WORKERS=4 PLAYWRIGHT_WORKERS=2
```

Custom starts from the current values and changes only supplied resource keys. Invalid input fails without replacing the local settings. No shell expressions are evaluated. Profile values and the host UID/GID are persisted in `.env.dev.local` (mode 0600). `./bin/dev prepare` idempotently generates the ignored `.devcontainer/compose.resources.yaml` used by VS Code and the host wrapper. It does not start containers, remove volumes or migrate databases.

Apply changed limits with `./bin/dev up` in a host terminal, or Rebuild/Reopen in Container. The CLI and VS Code use the same generated override. Docker commands must run on the host; the devcontainer has no Docker socket. The global doctor will report these settings when its roadmap point implements it.
