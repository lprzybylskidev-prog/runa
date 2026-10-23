# Account preferences and security delivery

Auth 0.6.0 adds `AuthPreferences` and `AuthDelivery` 1.0.0. Auth owns persistence;
originating modules own preference schemas, notification categories and content.
These are user preferences, separate from global versioned `ModuleSettings`.
There is no Notifications runtime or operational in-app channel.

## Preference declarations and API

An existing module-owned `SettingsProvider` may additionally implement
`Runa\Modules\Auth\Contracts\PreferenceProvider`. No manifest category or framework
API change is needed. Return bounded `PreferenceDefinition` entries with an exact
view/key, strict type, default, positive version, source-owned Laravel rules and
optional PL/EN names. Implement `validPreference` for domain validation and
`migratePreference` for old versions; returning null discards an obsolete value.
Default and migrated values undergo the same validation as writes. No secrets,
credentials, drafts, permission decisions or arbitrary undeclared JSON belong here.
The provider is trusted source code, never supplied through database configuration.

Resolve the guarded API through `ModuleApis`:

```php
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Contracts\PreferenceValue;
use Runa\Support\Modules\Api\ModuleApis;

$preferences = app(ModuleApis::class)->resolve('runa.auth', AuthPreferences::class, '1.0.0');
$scope = new PreferenceScope('my-app', 'app.projects', 'list', 'statistics.mode');
$current = $preferences->read($scope);
$preferences->write($scope, new PreferenceValue('filtered'), $current['revision']);
```

The trusted current principal selects the account; own methods accept no target
account. `PreferenceScope` adds application/module/view/key. Only the source-configured
application ID and shared `runa` display namespace are accepted; callers cannot
create unbounded arbitrary application namespaces. Modules must be
registered, synchronized and available; disabled source retains its rows but is
not readable/writable through the live declaration. JSON values are bounded to
32 KiB. There are at most 256 definitions per provider and 1024 snapshot entries.
One account revision serializes preference and delivery-preference writes under
registry → authority → user locks. Stale revisions return HTTP 409; never retry a
write silently with a newer revision. Reload and let the user deliberately retry.
`reset(module, scope, revision)` resets one key, a module, or all presentation
preferences. `resetAccount(publicId, revision)` is permissioned by
`runa.auth.preferences.reset` and emits synchronous `preferences.admin_reset`
Audit history. It stays denied until trusted authorization is installed.

Named `POST /api/runa/auth/preferences` accepts operation snapshot/write/reset/adopt
and application; write also needs module/view/key/value/revision. Reset accepts
optional module and key scope. This POST read surface uses the normal cookie/CSRF
transport. Both ingress and the Action recheck unrestricted tracked sessions.
Responses are private/no-store and include account/revision. The snapshot projects
current source defaults as well as stored values for the selected application and
shared `runa` display namespace. Other devices converge on reload or new login;
there is no polling or WebSocket synchronization.

## Browser integration and legacy ownership

`defaultPreferenceStore`, `usePreference`, language/theme, navigation,
`useStatisticsMode` and `useTableViews` use the same account adapter. Signed-in
values are memory-only caches; PostgreSQL values/defaults are authoritative.
The shell probes the session once and binds successful unrestricted login/session
responses through the shared HTTP wrapper. A custom authentication host may call
`accountPreferences.connect(publicId, applicationId)` after its verified session
response, and `disconnect()` at logout. Restricted sessions hold defaults until
an unrestricted session refresh. Refresh session after completing a restricted flow.
Each application module declares its exact statistics/table preference keys;
frontend validation alone does not declare a server preference. Table declarations
use `table-views` version 2 and must validate allowed columns, filters, named views,
limits and current snapshots against their actual source descriptors. No wildcard
JSON store is provided. Existing table consumers are development-only fixtures;
there is no shipped application domain table to migrate.

Inventory and mapping:

| Consumer                                      | Browser scope/control                                  | Auth declaration                                 |
| --------------------------------------------- | ------------------------------------------------------ | ------------------------------------------------ |
| Language/theme                                | runa / display / global / runa.locale, runa.theme      | runa.auth / global / locale, theme, v1           |
| Navigation                                    | application / shell / navigation / collapsed, expanded | runa.auth / navigation / collapsed, expanded, v1 |
| Statistics                                    | application / owning module / view / statistics.mode   | Exact module view/key, v1                        |
| Columns, filters, current/named/default views | application / owning module / view / table-views       | Exact module view/key, v2                        |

Guest `createPreferenceStore` retains validated versioned scoped localStorage and
legacy `runa.locale`/`runa.theme` fallback; denied storage uses lifetime memory.
Reset writes a tombstone so legacy data cannot reappear. Only guest changes write
legacy keys. Account values never enter guest/legacy storage. All mounted consumers
subscribe to account changes, and incoming hydration does not generate new saves.
Writes enqueue immediately in revision order and expose pending/error status;
both layouts present PL/EN save/load/conflict feedback with explicit reload. A failed
save is never reported as persisted. Logout/account switch aborts requests, clears
account caches and fences late responses and unsent writes. Components using
injected custom stores retain responsibility for their own account boundaries.

There is **no automatic legacy import**: localStorage cannot prove which person
created old values. After the user explicitly confirms that unclaimed guest data
belongs to the current account, a host may call `accountPreferences.adoptGuest`
with validated locale/theme/navigation values. Auth allowlists those four keys,
retains existing server values and marks adoption claimed atomically. Resets also
close adoption permanently for that account. Never collect the last signed-in
account's memory cache as guest data or automatically claim browser settings for
a new account. The later own-account UI owns presenting this explicit choice.

## Categories, addresses and routing

A module `SettingsProvider` may implement `DeliveryProvider` and return source-owned
`DeliveryCategory` entries (key, PL/EN names, mandatory and sensitive). The origin
module retains content and classification authority. `AuthDelivery.recipients`
requires `runa.auth.delivery.resolve`, rechecks current account eligibility and
returns safe current email addresses only. Senders must resolve again immediately
before sending; never cache recipients across jobs or read Auth tables directly.

Named `POST /api/runa/auth/delivery-preferences` reads categories or writes a
category's enabled state and selected recipients with the shared account revision.
The only channel is email. Recipient identities are `primary` and verified owned
secondary UUIDv7 IDs; disabled/deleted/unverified secondary identities do not route.
Mandatory or sensitive categories require primary-only delivery and cannot be
user-disabled or redirected. Recovery/password/OTP/link flows retain their separate
primary-only direct mail path. Delivery-preference changes are manually audited
in the same transaction; failure rolls back changes and revision.

## Security notices and reminders

Auth declares new_browser, failed_login, locked, password_changed, account_expiry
and password_expiry. Lock/password-change notices default enabled and are mandatory,
sensitive, primary-only. Other notices default disabled and may use ordinary
notification routing. Global `notice_<category>` settings control availability;
`notice_interval_minutes` bounds per-user/category frequency. Password replacement
covers own/admin/reset flows. Failed-login counting emits optional failure notices
and configured lock notices. An opaque HttpOnly same-site browser cookie deduplicates
new-browser notices; it conveys no authentication or trust, and only an HMAC is
stored with a bounded 100-browser inventory per account.

`expiry_start_days`, `expiry_interval_hours`, `expiry_max_count` bound reminders
for each exact current account/password deadline. Optional `expiry_admin_copy` is
a UUIDv7 Auth account reference, re-resolved for current verified eligible delivery;
no raw email or cross-module SQL is accepted. The copy contains only notice kind
and deadline. `runa:auth:security.remind` and its minute schedule scan 100 users
with a durable rotating cursor. Repeated work deduplicates the exact deadline/count;
changed deadlines, password renewal, deletion, ineligibility and obsolete recipients
are rechecked before SMTP. Account expiry reminders do not alter expiry or access.

`security.deliver` carries only a notice UUIDv7. The private outbox commits with the
originating Auth mutation; work admission rolls back with that mutation. A persisted
single-attempt claim precedes SMTP. A second locked check validates current policy,
recipient/deadline and state. One attempt may deliver to deduplicated recipients
separately; an ambiguous/partial SMTP result is not retried automatically. Inspect
failed managed work before an explicit operator action. No success/exactly-once
SMTP guarantee is claimed. Notice bodies contain fixed localized copy and an
optional UTC deadline, never credentials or submitted login/source data. A trusted
optional `SecurityMailDelivery` binding can later adapt to Notifications; false
means no send attempted and uses the direct Laravel Mail fallback. Throw after
an ambiguous attempt; do not return false. Credential recovery remains direct.

Session cleanup removes up to 500 notice rows whose expiry is over 30 days old;
this is technical outbox cleanup, never Audit retention. Audit history, current
preference/address rows, developer databases and caches are preserved. Apply the
forward migration and reserved 0.5.0 → 0.6.0 upgrade through supported module update
or installation; normal application startup never migrates storage.
