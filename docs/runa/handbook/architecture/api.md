# API Conventions

## General

The Vue SPA uses Laravel endpoints under `/api/...`.

Do not introduce `/api/v1` until a real external/public API contract requires versioning.

Named routes are always required.

## Success responses

Do not add meaningless wrappers such as `success: true`.

Preferred conventions:

- create: `201` with the created resource,
- update: consistently return the updated resource with `200` or use `204` when no body is intentionally returned,
- delete/no-content operation: `204`,
- collection: `data` plus explicit `meta` when paginated.

Non-trivial response contracts use `JsonResource`.

## Errors

Use one stable error envelope and explicit categories:

- validation,
- authentication,
- authorization,
- not found,
- conflict,
- business rule,
- rate limit,
- server/dependency unavailable.

Errors contain a stable English machine code.

User-facing messages are localized by the user-facing application layer. Technical details and stack traces belong in logs, not production responses.

Use standard HTTP status semantics, including:

- 400 bad request,
- 401 unauthenticated,
- 403 forbidden,
- 404 not found,
- 409 conflict,
- 422 validation,
- 429 rate limited,
- 500 server error,
- 503 temporary dependency/service unavailability.

Map dedicated application exceptions centrally. Avoid controller `try/catch` forests and never catch `Throwable` merely to turn everything into a generic 500.

Expected normal outcomes should not use exceptions when a result type/explicit branch is clearer.

## Input and validation

Use Form Requests for non-trivial writes and validated filters/sorts.

Never use request data wholesale for model creation or update. Do not use patterns equivalent to:

`Model::create($request->all())`.

Validation protects the boundary, but business invariants still belong in the appropriate application/domain behavior and durable simple invariants in the database.

## Lists

Potentially growing lists are paginated.

Use a stable project-owned pagination contract rather than exposing raw Laravel paginator JSON.

Validate:

- page,
- per-page,
- sort,
- filters.

Define default and maximum page sizes.

Filter/sort fields are explicit whitelists.

Use cursor pagination later for genuinely large continuous datasets when it provides a clear benefit.

## Bulk operations

Use bulk endpoints only when bulk work is a natural user/business action.

Validate all public IDs and preconditions.

Define semantics explicitly:

- all-or-nothing, or
- partial success with per-item results.

Large operations may become jobs.

## Identity

External routes/API payloads/events/integrations identify durable business entities by UUIDv7 `public_id`, never internal bigint IDs.

Internal relations continue to use bigint foreign keys.

## Concurrency

Business edit forms use optimistic concurrency by default. Detect stale writes and return an explicit conflict; never silently overwrite newer data.

Exact implementation may use a version column, timestamp, or token as appropriate to the specific model.

Last-write-wins is allowed only when explicitly safe.

## Authentication

The preferred SPA baseline is Laravel session/cookie authentication with CSRF protection.

Do not use JWT by default.

Do not store authentication tokens in `localStorage`.

Cookies use appropriate `HttpOnly`, `Secure`, and `SameSite` behavior.

The [Auth module](../modules/auth/README.md) supplies UUIDv7 identity/display and independent eligibility/lifecycle contracts. Consume guarded APIs without foreign models or tables; identifiers and lifecycle reasons never belong in anonymous authentication errors. Cookie/CSRF/Fortify transport and operational sessions follow in later implementation points. Roles/permissions integration remains assigned to the Permissions module; protected operations currently deny by default.

## CORS

Production is same-origin by default.

If CORS is required:

- origins are explicit,
- configuration comes from environment/config,
- local development origins are not permitted in production,
- wildcard credentials behavior is not accepted.

Project doctor should detect suspicious production CORS configuration.

## Time and numbers

Timestamps representing moments use UTC and API ISO 8601 values with `Z` or an explicit offset.

Date-only values remain dates.

User/organization timezone is a future business setting, not a reason to store ambiguous local timestamps.

API numeric values are raw machine values. Frontend performs locale display formatting.

Money is never represented as binary floating point:

- use smallest integer units by default, or
- use exact decimal/numeric when the business domain needs fractional precision beyond that model.

## TypeScript contracts

Frontend payloads have explicit TypeScript types. Avoid `any`.

Share/generate contracts where it clearly reduces drift; otherwise maintain focused frontend types and contract tests. Do not introduce a heavy code-generation stack by default.

## Internal vs external API documentation

The internal SPA API does not require a separate formal external API manual. Application documentation covers both sides.

A future public/external API requires explicit versioning and formal documentation such as OpenAPI as part of that later scope.

## Contract details

A single-resource success returns the resource without an unnecessary outer success/data envelope; paginated lists use `data` plus `meta`. Configure Resource wrapping consciously rather than relying on a framework default accidentally.

Canonical list parameters are `page`, `per_page`, `sort` and `filter[...]`. Reject or deliberately ignore unknown parameters consistently; never pass arbitrary identifiers through to SQL.

Rate limits target abuse-prone operations and are configured through config/env. Do not impose one aggressive global limit on normal authenticated application use. Return retry information with 429 where applicable.

Use a request/correlation ID through application logs and related jobs where useful. Validate a client-provided ID or generate one; do not log untrusted unlimited values.

For optimistic concurrency, multiple users may open the record. A stale write returns a stable conflict code (for example `resource_conflict`) and the frontend preserves the draft and offers recovery. Exact token/diff design belongs to the relevant feature roadmap.

### Optional table-wide search

The shared table provider transports its optional normalized search text as `filter[search]`, alongside ordinary allowlisted per-column filters, sort and pagination. Endpoints using this facility must explicitly validate/allow it and search only authorized fields/records before counting and paginating. Applications without a global search implementation disable it on both the frontend Table and API adapter. This contract does not create a backend search endpoint or fetch whole datasets.

## Export and operation frontend contracts

The optional UI facade provides typed explicit/all-matching selections, export scope/capabilities, BulkProvider and ExportOperation contracts plus createApiExportProvider over the shared HTTP wrapper. Applications own named API routes, authorization, request validation, query snapshots, idempotency, durable jobs/downloads, cancellation and partial retry semantics; no backend export/bulk endpoint is installed by UI foundations. Send selection metadata and exclusions, never fetch every server record/ID into the browser. Status/download/retry/cancel require the same server permission checks as start. See the [wire contract and limits](../development/frontend/tables.md#server-export-transport).

## Platform/application route ownership

Platform health/readiness routes are registered from `src/runa/routes/api.php`, with Runa namespace controllers. The customer `src/app/routes/web.php` owns only the named `/` welcome entry (`spa`), served by App namespace SpaController. API URLs/names, JSON negotiation, status codes and request IDs are unchanged by RM-005 backend relocation. Unknown paths remain real 404s.

## Module APIs

Module interfaces and immutable DTOs live under their owning `Contracts` namespace. Resolve them through `ModuleApis`; every typed proxy method rechecks current availability, even for retained references. `PublicReferenceLookup` can be implemented by such a guarded contract, without foreign SQL. HTTP descriptors register named `/api/<owner>/<code>/...` routes behind the same operation identity and a framework dispatcher; check availability, declared account/session policy and authorization before constructing module controllers or resolving Form Requests. Protected operations default to denial until a trusted authorization provider is supplied. See the [identity and transaction foundations](module-foundations.md) for HttpAccess declarations and non-recursive trusted identity scopes. Management writes remain trusted CLI-only. Exact versions, supported controller signatures, safe availability errors, cache/recovery behavior and concurrency limits are defined by the [module public boundary](module-framework.md#delivered-typed-php-gateway).
