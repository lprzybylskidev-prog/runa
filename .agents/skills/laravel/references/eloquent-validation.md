# Eloquent, Validation, and Data Access

1. Eloquent models must not become god objects containing entire use cases.
2. Do not introduce repositories over Eloquent by default.
3. Keep substantial use cases in Actions; complex reads may use Query classes.
4. Use casts intentionally. Create custom casts only when they model a real reusable value transformation.
5. Define mass-assignment behavior explicitly.
6. Never create/update from unfiltered request payloads (`Model::create($request->all())` and equivalent are forbidden).
7. Use Form Requests for non-trivial writes/filters.
8. Prefer Laravel standard validation rules over custom regex when a standard rule accurately expresses the requirement.
9. Validation is a boundary check; business invariants still belong in application/database layers as appropriate.
10. Use JsonResource for non-trivial API output contracts rather than leaking raw Eloquent serialization.
11. Deliberately eager-load relations required by a path; unexpected lazy-load N+1 is a quality defect.
12. Development/test should enable strict Eloquent behavior supported by the installed Laravel version, including:
    - preventing lazy loading,
    - preventing silently discarded attributes,
    - preventing access to missing attributes,
      where supported and compatible.
13. Do not hide database queries inside accessors/casts.
14. Be conscious of model events/observers; they must not own critical hidden business workflows.
15. Use framework fakes intentionally in tests. Do not over-fake so heavily that integration behavior is no longer exercised.

Auth owns its ModuleModel/Authenticatable implementation and public UUIDv7 identity; ordinary application consumers use guarded identity/lifecycle APIs. Do not add a second users table/model, expose internal auth identifiers or mutate account state directly. Follow the [Auth identity/lifecycle guide](../../../../docs/runa/handbook/modules/auth/README.md) for the authoritative procedure.
