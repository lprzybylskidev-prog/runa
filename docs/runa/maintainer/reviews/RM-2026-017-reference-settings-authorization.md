# RM-2026-017 — Reference settings publication prerequisite

Source assessment, 2026-11-07, baseline `e6be09a3`. The owner has approved the
executable reference repair and maintenance obligation. This record proposes one
additional public integration premise; it is not approval or implementation.

## Demonstrated boundary

`ModuleSettings::update` owns an independent runner and refuses any ambient
transaction. It validates a candidate, opens its default PostgreSQL publication
transaction and invokes the global SettingsAuthorization participant before
storing the transition and candidate. Core AuditParticipation delegates that
authorization to Auth PolicyHistory only for `runa.auth`. Other module settings
providers have no documented owner authorization callback at this point.

Admin's independent Command Action admits a writer under AdminEntry, closes that
transaction and invokes the writer. This deliberately preserves independent native
settings/work protocols. Command metadata advertises password requirements but
does not consume an owning operation's proof. A reference writer must enforce
its own current grants and proof in the actual settings publication transaction.
Calling AuthStepUp before ModuleSettings::update would consume proof separately
from settings/Audit, and wrapping update in a business transaction is refused.
Neither is an acceptable example for customers. Importing Auth PolicyHistory,
AdminOperationalAuthority or private settings engines is also prohibited.

## Proposed owning integration

Extend the existing settings runner to support an optional source-owned
SettingsAuthorization implementation on the already selected SettingsProvider.
Reuse the existing signature:

```php
public function authorizing(
    Illuminate\Database\Connection $connection,
    Runa\Contracts\Modules\SettingsContext $context,
): void;
```

Owner: module framework. Audience: trusted module settings-provider authors.
Supported entrypoint: the native runner invokes this method; HTTP consumers never
choose a callback/class or pass a connection. Implementation home:
`src/runa/Support/Modules/Settings/ModuleSettings.php`; module implementation in
its `backend/Definitions` or an owned Action selected by that provider. Runtime:
ordinary native settings publication only, with no dependency on Admin.

Invoke it inside the default publication transaction after validated candidate/
safe changes are available, before transition/candidate writes, in addition to
existing mandatory global authorization/Audit. No settings values or secret
candidate are added to SettingsContext; sensitive changes stay `replaced=true`.
The reference provider uses supported guarded Permissions/Auth APIs to assert
current declared grants and action-specific proof. Proof consumption, publication
and synchronous Audit then roll back together. Trusted CLI/operator behavior
must remain explicit and distinct from authenticated HTTP handling; no request
can select system authority by supplying actor metadata. Existing Auth behavior
must not consume proof twice or change merely because this hook becomes available.

Before implementation, assess public import/source-check classification, existing
settings admission and authority/lock ordering, runtime descriptor coverage and
Framework API version implications. Add focused regression source for same-
transaction proof/Audit rollback, permission loss, missing proof, safe secret
context, repeated publication, existing Auth behavior and trusted CLI behavior.
The reference remains a consumer of this supported integration rather than a
special-case platform exception. Do not add a second settings store, wrapper,
observer-based authorization, startup shim or callback parameter.

## Decision required

This makes an existing reserved settings-authorization contract available as a
module-owned source extension, changing a public authoring/behavioral premise.
Under the active roadmap's significant-unknown rule and AGENTS, obtain the owner's
explicit decision before implementing it. The already authorized reference repair
does not silently approve a new platform extension seam. Keep point 10a open;
manual acceptance and final verification remain blocked by unfinished repair.

At this checkpoint only roadmap, maintenance guidance and this concrete design
are recorded. No runtime/configuration/dependency/schema/data change or test/build
execution is claimed. Existing example data, assets, reports, caches and resources
are preserved.
