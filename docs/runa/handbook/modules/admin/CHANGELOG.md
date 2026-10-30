# Admin module changelog

## 0.1.0 — 2026-10-30

- Add optional framework page/section/field/binding/widget/contribution declarations and bounded handler values without changing legacy metadata constructors or schema.
- Supply frozen App bootstrap registration, source/dependency/API/component validation and deterministic discovery with owner isolation.
- Project authorized bootstrap/page/section reads through AdminAccess 1.0.0; remove denied values and controls, refuse forbidden filter/sort/lookup/write inputs, and require explicit secret set-state.
- Preserve entry proof and native owner Policies/grants; business handlers, write coordinators, rendered presentation and remaining HTTP routes follow in later points. Remain at development 0.1.0; no intermediate source-only edge.

- Establish the Core module with explicit initialization history and required Audit/Auth/Permissions prerequisites; no Admin storage or automatic request-time installation.
- Supply guarded AdminAccess 1.0.0 bootstrap and named `/api/admin` transport, current native grants, operational session and reusable 300-second password proof.
- Add fixed private entry/diagnostics framework adapters with bounded safe projections; no management mutation or generic engine dispatch.
- Declare later API methods fail closed until their owning implementation points. Rendered UI, atomic Save and settings remain planned; release acceptance is pending.

- Require native Auth/Permissions ^2.0.0 under the approved transaction redesign; Audit remains ^1.0.0.

### Declaration/read projection

Capability-specific read/write handler interfaces replace the unreleased universal
handler. Options identify and authorize one field; widget results carry explicit
observation/expiry. Optional target availability is isolated per binding, preserving
unrelated pages. Page/section/field/widget components now project owned manifest/build identity,
with explicit unavailable build state and safe isolation. The existing build
registry emits matching public evidence; production needs no Vue source. Compiled
resolution/rendering and creation acceptance remain pending under RM-2026-016; this does not add a version or upgrade edge.
