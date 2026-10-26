# Targeted Auth request throttling

Auth's request cap protects selected entry and proof operations, not ordinary
business application work. Classification uses trusted module route metadata
(`_runa_module` and `_runa_operation`) and POST, never a client flag, URL prefix
alone or the fact that an operation uses POST. New Auth entry/proof endpoints must
be reviewed against the source-owned list in `ThrottleAuthEntry` and covered by
regression tests.

## Built-in fallback

The protected operations are registration, invitation acceptance, verification
completion/resend, password recovery/reset/change, step-up, trust issuance and
second-factor challenge/send/enrollment/confirmation/disable/recovery rotation.
Each operation has its own 30-per-minute source bucket. One exhausted operation
cannot consume another operation's allowance. Denials return 429, Retry-After and
no-store headers. Source addresses are hashed for cache keys; never log
request bodies, credential material or limiter fingerprints.

Login is classified for the integration seam below but does not use the generic
30/minute fallback. Its transactional account/source abuse windows and generic
credential refusals remain under [sessions and abuse](sessions.md). Auth still
owns protocol safeguards: credential verification, proof budgets, token expiry,
one-time use, replay prevention, account/session eligibility and authorization.
Request throttling never replaces them.

Preferences (snapshots and writes), notification delivery preferences, ordinary
profile/history/avatar operations, session activity, logout and revocation do not
consume the entry cap. Their session, CSRF, validation, permission, concurrency and
Audit requirements remain in force. There is no platform-wide business request
limit supplied by this fallback.

## Optional Throttling ownership

The general Throttling module is deferred. The neutral platform contract
`Runa\Contracts\Modules\AuthRequestThrottle` supplies only its Auth request seam:

```php
public function enforce(Request $request, string $operation): bool;
```

The platform binds a no-integration default through `bindIf`. A future trusted
module integration replaces that binding with a runtime-aware bridge. It must
recheck the module's current lifecycle/availability for each request, including in
long-lived processes; binding at boot is not proof that the module is enabled.

- Absent, unregistered or explicitly disabled integration returns false before
  counting or enforcement. Auth then applies its targeted local fallback.
- An enabled, available integration enforces its policy and returns true. Auth
  does not also consume the fallback bucket. The hook includes login; ordinary
  business routes belong to any separate policy the future module explicitly adds.
- Rejection throws the normal bounded 429 response. An enabled but broken,
  unavailable or invalid integration fails closed; it must not disguise failure as
  false or silently switch to a weaker local policy. Exceptions propagate.

Use guarded public module adapters; the bridge must not import another module's
private tables, identity resolvers or implementation. The future module must use
this hook for classified Auth requests rather than counting them again in generic
middleware. There is no request-controlled or configuration toggle to skip both
owners. Auth's intrinsic credential/proof safeguards remain independent; relocating
their persistence/ownership would require a separately agreed protocol migration.

## Preference reconciliation

Entering the Preferences tab uses one account refresh pipeline. Its own preference
snapshot notification does not trigger another delivery read because the pipeline
already reads delivery next. External snapshot notifications still reconcile
visible delivery rows, and dirty drafts/conflict decisions remain local. Saves
share the existing revision-ordered mutation queue; failed writes are not replayed.
The cap correction does not change which Audit events account history displays.
