# Account history details

The supplied account-history table opens every listed event through Details in a
shared Dialog, except password events, which retain a summary without value details. Login history remains a separate summary view. The account-history
policy must remain enabled at each read; a hidden button is not authorization.

`GET /api/runa/auth/profile/history/detail?event=<UUIDv7>` is the named
`runa.auth.profile.history_detail` endpoint. It requires the current unrestricted,
tracked session, checks `own_account_history` and uses the guarded Audit
`OwnHistoryDetails` 1.0.0 contract. It never accepts a target account, action filter
or field projection from the browser. Missing, foreign-account and excluded events
all return the same 404; malformed identifiers return 422. Responses are private/
no-store. Summary pagination and cursor authorization are unchanged.

The response contains public_id, occurred_at, action, outcome and a bounded list
of `{field, before, after}` pairs. Values support bounded redacted JSON for declared structured preferences as well as scalars. Auth owns the exact action/field map in
`Support/AccountHistory`. No actor identity, private Audit context, entity graph,
correlation, internal ID, storage path, password, hash, code or token is returned.
Administrative actions identify the operation without revealing the administrator.

## Capture and historical limits

New profile changes record changed name/first_name/surname/login values. Primary
and secondary email events record the relevant old/new address; secondary verification
also records the verification state. Avatar events record presence, not image bytes,
private paths or historical image downloads. Replacing one image with another is
identified by the event even when presence remains true on both sides.

Notification preference events record category, enabled state and selected recipient
addresses at write time; later edits/deletions do not reconstruct or reinterpret old
recipients. Every changed non-display declared preference records its scope and
effective old/new value, including structured navigation/table preferences. A
no-op produces no preference-change event. Language/theme remain excluded across
application namespaces.

Own, scoped and administrative resets record each affected non-display setting
separately. Complete reset additionally records each notification override returning
to enabled/primary and a summary of removed override counts. Removed/unavailable
preference declarations reset to an absent override (null); never invent a current
default. These events and the owning reset commit or roll back together. Existing
Audit structural, key, text and byte limits apply to captured values; providers
must supply bounded safe values and Audit refusal rolls back preference persistence.

Factor operations expose enabled authenticator/email methods, pending setup and
available backup-code counts; rotation states that codes were replaced without
storing their values. Address verification exposes the address and verified state.
Lifecycle history includes approval, activation, deletion/restoration, schedules,
expiry, locks and required password renewal, excluding internal version counters.
Account policy exceptions expose the setting, old/new value, expiry and reason.
Password values, authenticator secrets, codes and tokens never enter the projection. These projections do not
turn history into a general Audit viewer.

All captured values commit in the same owning default PostgreSQL transaction as
mutation and Audit. No schema migration or backfill is required. Existing events
with no captured pair show an explicit historical missing-values message. Existing
lifecycle pairs become visible through the expanded safe action/field list. Never manufacture
old values from current state. Password events retain their summary only; factor resets show safe state changes
without credential material. Language/theme writes
still create no account-history event.

## Public presentation

`useAuthAccount()` exposes historyDetail, historyDetailOpen, historyDetailLoading,
historyDetailFailed, openHistoryDetail(publicId), closeHistoryDetail() and
retryHistoryDetail(). A new selection cancels the prior read, closing/unmounting
clears private values and fences late responses. Failures offer explicit read retry;
no writes are involved or replayed. Account/session disposal retains its existing
ownership boundary.

The Auth presentation provider accepts `historyDetail` as an optional component
replacement. Props: open, loading, failed, detail (AccountHistoryDetail or undefined),
locale (pl/en). Emit close/retry. Preserve loading/error/legacy/security text,
keyboard focus/Escape behavior and proper value escaping. Use the public Dialog
or an equivalent accessible implementation. AccountHistoryDetail and HistoryValue
are exported from `@/runa/auth`; custom presentation stays in src/app. No private
controller or security logic needs copying. The supplied modal inherits Dialog
motion, reduced-motion/print and focus behavior without a second animation layer.
