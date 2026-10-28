<?php

namespace Runa\Providers;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use Runa\Contracts\Modules\AuthRequestThrottle;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsObserver;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Database\DatabaseScope;
use Runa\Identity\PlatformManifest;
use Runa\Integration\Core\AnonymousAccountPolicy;
use Runa\Integration\Core\NoAuthRequestThrottle;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Console\ModuleConsole;
use Runa\ModuleFramework\Data\ModuleDataPaths;
use Runa\ModuleFramework\Events\EventDefinitions;
use Runa\ModuleFramework\Events\EventSubscriptions;
use Runa\ModuleFramework\Events\ModuleIntegrations;
use Runa\ModuleFramework\Frontend\ModuleFrontendRoutes;
use Runa\ModuleFramework\HealthDeadline;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\Management\ManagementRenderers;
use Runa\ModuleFramework\Management\ModuleManagement;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\ModuleTransitions;
use Runa\ModuleFramework\Settings\IgnoreSettingsChanges;
use Runa\ModuleFramework\Settings\SettingsDefinitions;
use Runa\ModuleFramework\Settings\SettingTypes;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\ModuleFramework\Work\WorkDefinitions;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Logging\LogContext;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Events\ModuleEvents;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Work\ModuleWork;

final class ModuleFrameworkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PrincipalScope::class);
        $this->app->bindIf(HttpAccountPolicy::class, AnonymousAccountPolicy::class);
        $this->app->bindIf(AuthRequestThrottle::class, NoAuthRequestThrottle::class);
        $this->app->singletonIf(DatabaseScope::class, fn () => new DatabaseScope(base_path()));
        $this->app->singleton(ModuleDiscovery::class, fn () => new ModuleDiscovery(base_path(), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleDataPaths::class, fn () => new ModuleDataPaths(base_path(), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleStorage::class);
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleDiscovery::class)));
        $this->app->singleton(ModuleAutoloader::class, fn () => new ModuleAutoloader(base_path(), $this->app->make(ModuleRegistry::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(LifecyclePolicies::class, fn () => new LifecyclePolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleTransitions::class, fn () => new ModuleTransitions($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(LifecyclePolicies::class), $this->app->make(Encrypter::class), PlatformManifest::load(base_path('runa/platform.json'))->version, $this->app->make(ModuleHealth::class)));
        $this->app->singleton(HealthPolicies::class, fn () => new HealthPolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleHealth::class, fn () => new ModuleHealth($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(HealthPolicies::class), new HealthDeadline));
        $this->app->singleton(ModuleAvailability::class, fn () => new ModuleAvailability($this->app->make(ModuleRegistry::class), $this->app->make(ModuleHealth::class), $this->app->make(LifecyclePolicies::class), PlatformManifest::load(base_path('runa/platform.json'))->version));
        $this->app->singleton(MaintenanceState::class, fn () => new MaintenanceState($this->app, $this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleAvailability::class)));
        $this->app->singleton(OperationScope::class);
        $this->app->singleton(ModuleDeclarations::class, fn () => new ModuleDeclarations(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(SettingTypes::class);
        $this->app->singleton(SettingsDefinitions::class);
        $this->app->singleton(ManagementRenderers::class);
        $this->app->singleton(ModuleManagement::class);
        $this->app->singleton(ModuleFrontendRoutes::class, fn () => new ModuleFrontendRoutes($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleFrontend::class), $this->app->make('router')));
        $this->app->bindIf(SettingsObserver::class, IgnoreSettingsChanges::class);
        $this->app->singleton(ModuleApis::class);
        $this->app->singleton(WorkDefinitions::class);
        $this->app->singleton(EventDefinitions::class);
        $this->app->singleton(ModuleIntegrations::class, fn () => new ModuleIntegrations($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(OperationGuard::class)));
        $this->app->singleton(EventSubscriptions::class);
        $this->app->singleton(ModuleEvents::class, fn () => new ModuleEvents($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(EventDefinitions::class), $this->app->make(EventSubscriptions::class), $this->app->make(ModuleDeclarations::class), $this->app->make(OperationGuard::class), $this->app->make(OperationScope::class), $this->app->make(ModuleWork::class), $this->app->make(LoggerInterface::class)));
        $this->app->singleton(ModuleWork::class, fn () => new ModuleWork($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(WorkDefinitions::class), $this->app->make(OperationGuard::class), $this->app->make(OperationScope::class), $this->app->make(Encrypter::class), $this->app->make(Factory::class), $this->app->make(EventSubscriptions::class)));
        $this->app->singleton(ModuleHttp::class, fn () => new ModuleHttp($this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make('router'), $this->app->make(DatabaseManager::class)->connection()));
        $this->app->singleton(ModuleConsole::class, fn () => new ModuleConsole($this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(WorkDefinitions::class), $this->app->make(ModuleWork::class), $this->app->make(DatabaseManager::class)->connection(), $this->app->make(LogContext::class)));
        $this->app->singleton(ModuleUpdates::class, fn () => new ModuleUpdates($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), base_path(), PlatformManifest::load(base_path('runa/platform.json'))->version, $this->app->environment(['local', 'testing']), $this->app->make(PreparationParticipant::class)));
        $this->app->singleton(OperationGuard::class, fn () => new OperationGuard($this->app->make(ModuleRegistry::class), $this->app->make(ModuleAvailability::class), $this->app->make(MaintenanceState::class), $this->app->make(OperationAuthorizer::class), $this->app, $this->app->make(OperationScope::class)));
        $this->app->singleton(ModuleFrontend::class, fn () => new ModuleFrontend($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(ModuleAvailability::class), $this->app->make(OperationAuthorizer::class)));
        $this->app->singleton(ModuleSettings::class, fn () => new ModuleSettings($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(SettingsDefinitions::class), $this->app->make(Encrypter::class), $this->app->make(SettingsObserver::class), $this->app->make(SettingsParticipant::class)));
        ModuleAutoloader::register();
    }
}
