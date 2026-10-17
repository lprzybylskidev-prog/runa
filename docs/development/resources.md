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

## Sibling service budgets

The generated override also applies bounded sibling allocations. These are initial verified configuration budgets, not benchmarks of future business workloads:

| Service | CPU low / standard / high | Memory low / standard / high |
|---|---|---|
| PostgreSQL | 0.5 / 1 / 2 | 256M / 512M / 1G |
| Queue worker | 1 / 1 / 1 | 256M / 512M / 512M |
| Scheduler | 0.25 / 0.25 / 0.25 | 128M / 128M / 128M |
| Nginx | 0.25 / 0.25 / 0.25 | 64M / 64M / 64M |
| Mailpit | 0.25 / 0.25 / 0.25 | 128M / 128M / 128M |

The one-shot setup service uses the selected app budget because dependency installation and frontend compilation run there. Custom retains standard sibling budgets and accepts the documented app/test-worker overrides. Limits are ceilings rather than reservations; do not interpret their sum as guaranteed dedicated CPUs. Production budgets are configured separately in the production-finalization points, not inherited from these development presets.

## Knowledge service budgets

Qdrant uses 0.5 CPU/256 MB (low), 1 CPU/512 MB (standard/custom), or 2 CPU/1 GB (high). CPU embeddings uses 1/2/4 CPUs respectively and 2 GB memory. Optional CUDA embeddings uses 2 CPUs/4 GiB system memory in addition to GPU memory; it is a separate sibling so CPU fallback remains available after a GPU failure. These are initial limits verified through service startup, not workload benchmarks. Both embedding services share a persistent model cache. Select hardware with host-only `./bin/dev embeddings auto|cpu|cuda`; automatic detection requires NVIDIA compute capability >=7.5 and the Docker NVIDIA runtime. See `knowledge.md`.

The first CUDA startup exceeded its initial 2 GiB container RAM limit: Docker recorded OOM events and exit code 137. Startup succeeded with a 4 GiB limit and a smaller 2048-token server batch (CPU remains at 8192). The post-startup snapshot showed 482.1 MiB CUDA-service RAM and 270.1 MiB CPU-service RAM; startup peak usage has not been measured.
