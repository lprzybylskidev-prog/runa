# Project Principles

## Purpose

Runa is built to remain understandable and maintainable while the application grows. The project deliberately favors explicit behavior, measured complexity, and durable project knowledge over framework fashion.

### Project rules beat generic advice

Generic best practices and upstream agent skills are advisory. Runa-specific decisions are authoritative.

### Adopt before invent

Before designing a reusable project convention, inspect current project patterns and high-quality upstream guidance. Adapt what is useful. Do not import an upstream architecture wholesale.

### Explicit over magical for important behavior

Prefer behavior that can be followed through code and documentation. Avoid hidden business behavior in global scopes, observers, model hooks, service locators, implicit environment reads, and similar mechanisms.

### Measure before optimizing

Do not add caches, indexes, partitioning, rerankers, queues, global stores, abstractions, or infrastructure because they might help someday. Establish the problem first.

### Simple until complexity earns its place

Do not add:

- repositories over Eloquent by default,
- state-machine frameworks before repeated real need,
- Redis/Meilisearch without an active use case,
- microfrontends,
- Kubernetes,
- CI just because tools commonly expect it,
- plugin ecosystems without a concrete requirement.

### Rule of Three for abstractions

Tolerate limited duplication until a stable reusable pattern is visible. Avoid speculative generalization.

### Failure paths are part of the design

Design expected failures, conflicts, retries, partial states, and recovery behavior with the happy path.

### One clear source of truth

A datum or decision should have a clear authority. Avoid competing state representations and duplicated configuration.

### Side effects are explicit

Network calls, messages, jobs, state transitions, file operations, and other effects should be easy to identify and test.

### Keep transport/framework details at boundaries

When reasonable, business logic should not depend on HTTP Request/Response objects or other transport concerns deep in the application.

### Comments explain why

Comments should explain constraints, non-obvious decisions, and reasons. Do not narrate code or leave AI change-history commentary in source.

### One term per concept

Use stable names. Avoid interchangeable synonyms for the same business or technical concept.

### Readability beats cleverness

Do not replace straightforward application behavior with dense SQL, metaprogramming, or abstractions solely because they are technically possible.

### Dependency approval boundary

Use the dependency assessment and approval policy in `AGENTS.md`. Requirements for a tool family do not approve unrelated convenience packages. The initial roadmap explicitly identifies mandated families; unresolved provider/package choices remain unresolved until user approval. Every installed external component joins the maintenance registry. Normal installs use committed Composer/pnpm lockfiles; updates are deliberate roadmap work.

## Generated tooling and build state

Use the single workspace-root `.runtime/<area>/` tree for generated private
build/tooling state, caches, locks and operational journals. Resolve its
location from the workspace boundary; tools launched from src must target the same
root. Do not create src/.runtime, module-local .runtime trees or generated files
inside protected source. Public frontend assets still belong in src/public/build
and src/public/runa-error; Laravel storage/cache and durable application data retain
their documented locations. Supported verification evidence also retains its
documented artifact paths. This rule does not move those existing contracts.

Browser screenshots, traces, videos, HTML reports and Vitest attachments use
.runtime/artifacts/playwright/. The pnpm workspace config selects
.runtime/pnpm-store/; development Python uses PYTHONPYCACHEPREFIX at
/workspace/.runtime/python/pycache. The devcontainer remote environment also
sets this prefix; existing terminals/processes need a reload or an explicit
export before subsequent Python invocations. Native host Python commands may
set PYTHONPYCACHEPREFIX to the absolute checkout .runtime/python/pycache path.
Preserve package caches and browser evidence during relocation and cleanup.

Private error build output uses .runtime/frontend/errors/renderer and generated
HTML templates use .runtime/frontend/errors/templates. Only required generated
HTML is copied explicitly into production PHP images at the matching workspace
path, outside persistent storage mounts. Renderer bundles, development/tool caches,
reports, backups and operation state must not enter runtime images. Docker context
excludes host .runtime; builds produce fresh artifacts in isolated stages.

An ignored directory is not necessarily disposable. Preserve backups, reports,
knowledge/model caches and operation/recovery state unless their exact cleanup is
authorized. Relocation must preserve live consumers and refuse collisions rather
than overwrite unrelated artifacts. Do not delete the whole root to clean one task.
