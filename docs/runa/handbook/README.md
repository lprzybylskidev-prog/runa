# Runa handbook

This is the supplied guidance for building and operating a customer application on Runa.

- [Application architecture](architecture/application-architecture.md), [database](architecture/database.md), [API](architecture/api.md), [frontend](architecture/frontend.md) and [configuration](architecture/configuration.md).
- [Development environment](development/environment.md), [editor and agents](development/editor-and-agents.md), [localization](development/localization.md), [documentation](development/documentation.md) and [knowledge](development/knowledge.md), including supported evidence reading, intent and applicability.
- [Roadmaps](workflows/roadmaps.md), [Git](workflows/git.md), [releases](workflows/releases.md), [template](workflows/roadmap-template.md) and [quality](quality/README.md).
- [Application tests](testing/strategy.md), [security](security/baseline.md), [seeding](data/seeding.md) and [commands](tooling/commands.md).
- [Production architecture](production/architecture.md), [deployment](production/deployment.md), [Boost](ai/laravel-boost.md), [knowledge policy](ai/knowledge-system.md) and [skills policy](ai/skills-policy.md).

Put application-specific documents and roadmaps under `docs/app`. See [placement rules](../../README.md). Runa platform maintenance/installation/update tools and their records are not part of customer delivery.

## Developer path

Follow the owning guide at each step; commands below are entrypoints, not evidence
that a new installation has been verified.

| Step                   | Guide and expected result                                                                                                                                                                                                                                                           |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Understand ownership   | [Application architecture](architecture/application-architecture.md) and [public API boundary](architecture/public-api.md): put business code in src/app and consume supported Runa contracts.                                                                                      |
| Prepare a clone        | [Environment setup](development/environment.md#customer-repository-and-local-initialization): reconcile local inputs and run `./bin/artisan runa:dev:initialize` from the host checkout root. It explicitly prepares forward data; reopen does not.                                 |
| Open the editor        | [Editor and agents](development/editor-and-agents.md): attach to the app container, confirm services and configure private credentials. Application readiness is independent of optional knowledge services.                                                                        |
| Build a feature        | Use ordinary Laravel App code or the optional [module generator](development/modules.md#generate-and-review). Review its preview before `--write`; installing data and enabling a module are separate operations.                                                                   |
| Integrate APIs         | [API conventions](architecture/api.md) and [module contracts](architecture/module-framework.md): use named HTTP routes, guarded ModuleApis and current independent API versions.                                                                                                    |
| Compose the UI         | [Frontend adoption](development/frontend/customization.md): retain defaults, compose or replace presentation through public facades; preserve shared session/security orchestration.                                                                                                |
| Verify the application | [Application testing](testing/strategy.md#default-and-focused-commands) and [quality selection](quality/README.md#manual-group-selection): select supported scopes and retain actual results. Private platform suites are not customer prerequisites.                               |
| Update and deploy      | [Release reconciliation](release/platform-reconciliation.md), [platform updates](production/platform-updates.md) and [deployment](production/deployment.md): review the external development update, commit the complete customer project and deploy that tested customer revision. |

See [command locations and transport](tooling/commands.md#command-location-and-transport)
before copying command examples. Application plans and domain documentation belong
in [docs/app](../../app/README.md).
