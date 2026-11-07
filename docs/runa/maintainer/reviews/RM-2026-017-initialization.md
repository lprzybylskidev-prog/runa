# RM-2026-017 point 10l — current initialization and supported updates

Date: 2026-11-07. Implementation review; no installation, owner acceptance or
final verification is claimed. Source baseline: point 10k; published tags remain
immutable. P01/P02 are owned by this point.

## Pre-edit effects and published baselines

The five providers declare 122 edges through 76 Upgrade classes (64 empty).
The twelve nonempty historical handlers have these complete effects:

| Owner/handler                          | Current disposition                                                                                         |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Auth UpgradeEntry                      | entry_tokens presence; missing immediate settings defaults                                                  |
| Auth UpgradeSessions                   | sessions/trusted_devices/login_windows presence; defaults                                                   |
| Auth UpgradeCredentials                | password_history/step_up_proofs presence; defaults                                                          |
| Auth UpgradeProfiles                   | secondary_emails/email_changes/profile_mail presence; defaults                                              |
| Auth UpgradePreferences                | preferences/delivery_preferences/security_notices/notice_frequencies/known_browsers presence; defaults      |
| Auth UpgradePolicy                     | policy_versions/policy_exceptions/operator_tokens/initialization presence; defaults; PolicyHistory baseline |
| Auth UpgradeAccountUi                  | missing immediate settings defaults                                                                         |
| Auth UpgradeDevelopmentFixtures        | empty development_fixtures storage presence                                                                 |
| Auth UpgradeSectionActivity            | step_up_proofs.idle_expires_at presence                                                                     |
| Permissions UpgradeAccess              | presets/preset_roles/preset_permissions/user_access presence                                                |
| Permissions UpgradeDevelopmentFixtures | empty development_fixtures storage presence                                                                 |
| Permissions UpgradeAuthority           | live protected system-role presence                                                                         |

Current initialization additionally owns Auth notice_scan/initialization singleton
rows, Audit audit.store_ready, Permissions protected role creation/Audit and catch-up
of registered source declarations (including disabled modules), and reference's
welcome sample. Admin's explicit transactional step has no domain data; native
preparation participation owns declaration synchronization and Audit.

Direct Git inspection of every published tag from the documented v0.5 update
baseline establishes these distinct Core installed versions:

| Platform tags        | Audit | Auth  | Permissions | Admin  |
| -------------------- | ----- | ----- | ----------- | ------ |
| v0.5.0               | 1.0.0 | 1.0.0 | 1.0.0       | absent |
| v0.6.0/v0.6.1/v0.6.2 | 1.0.4 | 1.1.4 | 1.0.4       | absent |
| v0.7.0               | 1.1.0 | 5.0.0 | 2.1.0       | 1.0.0  |

The initial source hypothesis was that these published versions could update
directly. Comparing the retained migration fixture and actual published source
against current files disproved that: all 13 historical Core migration files in
history-before-execution-layout.json were already replaced by current CREATE files
before this point. ModuleStepHistory correctly refuses them. Merely adding edges
would not support an actual published database and would retain misleading debt.
The documented 0.5-to-0.6 transport remains a historical supported contract, not a
promise to convert those databases into today's development schema. No production
or customer deployment inventory is established.

Retain only real current-schema paths: Auth 6.0.0 → 6.0.1, Audit 1.1.1 → 1.1.2,
Permissions 2.1.1 → 2.1.2 and Admin 1.1.0 → 1.1.1. Admin additionally supports
published 1.0.0 → 1.1.1: it has no domain migration to convert. Each also initializes
null → its new current patch directly. Reference initializes null → 0.4.1 only.
These patch candidates advance the previously installed development versions,
preserve all public signatures and ordinary dependency constraints, and make
current explicit preparation run once. No migration conversion, compatibility
engine, ledger bypass or published-history rewrite is added. Platform 0.7.0 and
approved feature/0.8.0 remain unchanged. Generic customer upgrades are unchanged.

Core migrations remain byte-identical in this point, including their current
fingerprints. Old successful historical ledger rows remain evidence, not steps to
replay or forge. Existing active operations must resume their original source or
be reconciled first. Current versions other than the supported baseline/target
still fail missing-path preflight normally.

Reference published versions 0.1.0/0.1.2/0.2.0 lack the current revision column.
Its consolidated CREATE changes a recorded migration fingerprint. There is no
honest nondestructive data-upgrade edge for that schema: retain only current
initialization and require identified development/test storage reconciliation.
Do not advertise a source-only edge as a conversion for this development example.

## Owning implementation

Current Auth initialization verifies the union of historical storage prerequisites,
creates only its two technical singleton rows and records the effective policy
baseline. Null encrypted settings already selects validated current source defaults;
no default-user, credential, grant, operator token or fixture data is created.
UpdateAuth adds missing immediate defaults with the existing reserved publication
contract, preserving explicit values and normal revision/policy/Audit consequences.
It does not recreate installation effects during an update.

Permissions initialization retains protected-role conflict refusal and registered
source synchronization, with historical access/fixture presence checks before
writes. UpdatePermissions requires the existing live protected role; native
preparation synchronizes declarations and records Audit atomically. UpdateAudit
and UpdateAdmin are explicit source-publication steps, so reserved preparation
participation remains active without replaying audit.store_ready or initial data.

Reference samples owns revision bigint NOT NULL DEFAULT 1 and the positive CHECK
in its original CREATE. Its welcome row relies on that default. The separate
ALTER migration and all retired historical Upgrade classes are removed. Generic
UpgradeDefinition, planner, execution, fingerprints, ledgers, transactional publication
and ambiguous non-idempotent recovery refusal are unchanged.

## Operational reconciliation reserved for 10r

This point performs no storage/ledger changes. Inspect actual identities, active
reservations, migration hashes and stored versions again in 10r. For development
runa and base runa_test, reference samples plus its two historical migration ledger
entries are affected; a native application wipe alone preserves shared module
history and installed_version, so it cannot constitute reconciliation. Use the
supported identified development/test rebuild procedure in the database guide,
including all selected-owner domain storage, ledgers and installed provenance;
show its exact scope before destructive execution. Preserve Core/account/settings
state when a supported narrower scope is available; do not invent SQL provenance
or an upgrade workaround. If the supported reset requires a wider rebuild, record
that actual scope and recreate usable access/data before handoff. Disposable test
workers remain runner-owned; base test/dev databases, volumes and credentials are
not deleted in this point. Real customer/production data is outside this reset.

Core installed at the preceding current-schema candidates runs the explicit patch
updates without initializer replay; preserve successful earlier ledger evidence.
Published Admin executes its retained preparation edge. Published older Core
history refuses changed/missing migration files; unsupported installed versions
fail missing-path preflight and require reviewed dev/test reconciliation.
No history validation, recovery fence or dependency ordering is disabled.

## Consumers, versions and instruction products

Current source has ten definitions (five initializers, four current-schema updates,
one published Admin update) and no retained Upgrade*.php classes. All five module
manifests and central/module changelogs record the compatible patch candidates.
Required/optional dependency ranges already accept these patches; public API versions
and Framework API 2.0.0 are unchanged. Generated customer modules keep their generic
upgrade definitions, source-owned examples and APIs; no generator engine change is
needed. The generated-shapes/public descriptor test now checks current initialization
rather than requiring a retired verification_contracts edge.

Updated the owning Core guides, frontend-independent native transaction version
references, reference README/capability matrix, module-development example version,
public API placement text and historical-updater applicability. Historical tags,
closed roadmaps, dated reviews/source-map inventories and the original pre-layout
history fixture are unchanged. Current source tests treat that fixture as authentic
historical evidence and assert refusal for its replaced migrations, not a supported
conversion. Native current-schema tests assert preservation and actual publication;
source-planning evidence alone is not installation acceptance.

Owner root AGENTS/agent-contract and backend/Laravel/PostgreSQL/security skills
already route to the canonical module/database/public-API guidance and authorize
this pre-1.0 correction; their invariants and selection rules need no new text.
Customer AGENTS/CLAUDE/skills remain application-scoped, preserve generic app-module
upgrade authoring and reference the same updated public guides. No new public
capability/signature or rule requires duplicating module version lists in either
instruction product. Customer source never receives owner reset authority.

## Focused evidence and remaining work

Knowledge status was current (233 documents, 13270 chunks); the bounded current-intent
query returned the owning public API and Permissions guidance plus the audit. The
original sources were opened. Laravel Boost supplied installed Laravel 13 migration
documentation before reusing bigint/default syntax. No new dependency was introduced.

The concrete uncertainty was whether direct initialization retained technical,
policy, protected-role and Audit effects, and whether source planning/refusal still
worked after removing the chains. Supported bounded diagnostics:

```bash
python3 tools/maintainer/test-backend --parallel --processes=1 --filter='BuiltInModuleBoundaryTest|isolated_target_planner|initializes Audit then Auth exactly once|updates the current Core schema once'
python3 tools/maintainer/test-python --scope=contracts --path=contracts/test_api_boundary.py --filter=generated_public_consumers_and_module_upgrade_history_without_execution
```

Results: backend 6 passed, 84 assertions, native exit 0 / cleanup 0; tooling 1
passed, 6 deselected, exit 0 / cleanup 0. Backend scenarios cover direct initial
plans/dependency order, current update planning, step tampering/unsupported-version
refusal, published table-free Admin planning, genuine historical migration refusal,
initial singleton/policy/protected-role/Audit effects, and current-schema default
publication without overwriting explicit values or replaying initial effects.
Repeated native application is a no-op. These are bounded diagnostics, not complete
backend, published installation/update or security acceptance.

Additional authored application regression covers welcome initialization/default
revision, duplicate initialization and the nonpositive revision CHECK; it remains
for final supported application verification after prepared Core/storage. Existing
generic upgrade tests retain transactional rollback, preparation participation and
ambiguous non-idempotent refusal scenarios; the engine is unchanged. Full affected
suites, security/delivery, knowledge doctor/evals and release checks remain 12,
combined actual runtime/data/assets readiness 10r and sole owner acceptance 11.

The runner removed only its runa_test_test_1 disposable database under the suite
lock. Development runa/base runa_test, all Docker resources/volumes, model caches,
credentials and previous reports remain preserved. No containers, networks, images
or volumes were created or removed by this point. No operational reset occurred.
