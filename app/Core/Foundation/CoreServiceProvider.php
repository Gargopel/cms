<?php

namespace App\Core\Foundation;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry;
use App\Core\Extensions\Boot\BootablePluginResolver;
use App\Core\Extensions\Boot\PluginBootstrapReportStore;
use App\Core\Extensions\Boot\PluginProviderBootstrapper;
use App\Core\Admin\Support\AdminExtensionPointService;
use App\Core\Admin\Support\CoreAdminOverviewService;
use App\Core\Audit\AdminAuditLogger;
use App\Core\Health\Checks\AppKeyHealthCheck;
use App\Core\Health\Checks\CacheStoreHealthCheck;
use App\Core\Health\Checks\DatabaseConnectionHealthCheck;
use App\Core\Health\Checks\ExtensionsEcosystemHealthCheck;
use App\Core\Health\Checks\ExtensionsRegistryHealthCheck;
use App\Core\Health\Checks\InstallationHealthCheck;
use App\Core\Health\Checks\WritableDirectoriesHealthCheck;
use App\Core\Health\SystemHealthService;
use App\Core\Extensions\Capabilities\ExtensionCapabilityCatalog;
use App\Core\Extensions\Capabilities\ExtensionCapabilityService;
use App\Core\Extensions\Dependencies\ExtensionDependencyService;
use App\Core\Extensions\Health\ExtensionHealthService;
use App\Core\Extensions\Hooks\ExtensionHookRegistry;
use App\Core\Extensions\Operations\ExtensionOperationEligibilityService;
use App\Core\Extensions\Permissions\PluginPermissionRegistrySynchronizer;
use App\Core\Extensions\Registry\ExtensionLifecycleStateManager;
use App\Core\Extensions\Discovery\ExtensionDiscoveryService;
use App\Core\Extensions\Registry\ExtensionOperationalStateManager;
use App\Core\Extensions\Registry\ExtensionRegistrySynchronizer;
use App\Core\Extensions\Settings\PluginSettingsManager;
use App\Core\Install\Environment\EnvironmentFileManager;
use App\Core\Install\InstallationState;
use App\Core\Install\Setup\InstallApplication;
use App\Core\Install\Support\InstallDatabaseConfigFactory;
use App\Core\Install\Support\InstallDatabaseManager;
use App\Core\Install\Support\InstallRequirementChecker;
use App\Core\Media\MediaManager;
use App\Core\Settings\CoreSettingsCatalog;
use App\Core\Settings\CoreSettingsManager;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeSlotRenderer;
use App\Core\Themes\ThemeViewResolver;
use App\Support\PlatformPaths;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlatformPaths::class, function ($app): PlatformPaths {
            return new PlatformPaths($app->basePath());
        });

        $this->app->singleton(ExtensionCapabilityCatalog::class);
        $this->app->singleton(ExtensionCapabilityService::class);
        $this->app->singleton(ExtensionDiscoveryService::class);
        $this->app->singleton(ExtensionRegistrySynchronizer::class);
        $this->app->singleton(ExtensionLifecycleStateManager::class);
        $this->app->singleton(ExtensionOperationalStateManager::class);
        $this->app->singleton(ExtensionDependencyService::class);
        $this->app->singleton(ExtensionHealthService::class);
        $this->app->singleton(ExtensionHookRegistry::class);
        $this->app->singleton(AdminNavigationRegistry::class, fn ($app): ExtensionHookRegistry => $app->make(ExtensionHookRegistry::class));
        $this->app->singleton(AdminDashboardPanelRegistry::class, fn ($app): ExtensionHookRegistry => $app->make(ExtensionHookRegistry::class));
        $this->app->singleton(ThemeSlotRegistry::class, fn ($app): ExtensionHookRegistry => $app->make(ExtensionHookRegistry::class));
        $this->app->singleton(ExtensionOperationEligibilityService::class);
        $this->app->singleton(PluginPermissionRegistrySynchronizer::class);
        $this->app->singleton(PluginSettingsManager::class);
        $this->app->singleton(BootablePluginResolver::class);
        $this->app->singleton(PluginBootstrapReportStore::class);
        $this->app->singleton(PluginProviderBootstrapper::class);
        $this->app->singleton(AdminExtensionPointService::class);
        $this->app->singleton(CoreAdminOverviewService::class);
        $this->app->singleton(AdminAuditLogger::class);
        $this->app->singleton(SystemHealthService::class, function ($app): SystemHealthService {
            return new SystemHealthService([
                $app->make(InstallationHealthCheck::class),
                $app->make(DatabaseConnectionHealthCheck::class),
                $app->make(WritableDirectoriesHealthCheck::class),
                $app->make(CacheStoreHealthCheck::class),
                $app->make(AppKeyHealthCheck::class),
                $app->make(ExtensionsRegistryHealthCheck::class),
                $app->make(ExtensionsEcosystemHealthCheck::class),
            ]);
        });
        $this->app->singleton(InstallationState::class);
        $this->app->singleton(EnvironmentFileManager::class);
        $this->app->singleton(InstallRequirementChecker::class);
        $this->app->singleton(InstallDatabaseConfigFactory::class);
        $this->app->singleton(InstallDatabaseManager::class);
        $this->app->singleton(InstallApplication::class);
        $this->app->singleton(MediaManager::class);
        $this->app->singleton(CoreSettingsCatalog::class);
        $this->app->singleton(CoreSettingsManager::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(ThemeViewResolver::class);
        $this->app->singleton(ThemeSlotRenderer::class);
    }

    public function boot(
        CoreSettingsManager $settings,
        PluginProviderBootstrapper $bootstrapper,
        ThemeViewResolver $themes,
        AdminExtensionPointService $extensionPoints,
    ): void
    {
        $settings->applyRuntimeConfiguration();
        $themes->registerActiveThemeNamespace();
        $bootstrapper->bootstrap();

        View::composer('components.layouts.admin', function ($view) use ($extensionPoints): void {
            $view->with('extensionNavigationItems', $extensionPoints->visibleNavigationItems(
                auth()->user(),
                request()->route()?->getName(),
            ));
        });
    }
}
