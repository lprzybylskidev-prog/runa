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
