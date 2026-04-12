<?php

namespace Plugins\ExamplePlugin\Providers;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use Illuminate\Support\ServiceProvider;

class ExamplePluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('plugins.example.loaded', static fn (): bool => true);

        $adminPrefix = '/'.trim((string) config('platform.admin.prefix', 'admin'), '/');

        $this->app->make(AdminNavigationRegistry::class)->registerAdminNavigationItem(
            new AdminNavigationItem(
                pluginSlug: 'example-plugin',
                key: 'example-plugin-console',
                label: 'Example Plugin',
                description: 'Superficie administrativa minima publicada pelo plugin tecnico de exemplo.',
                href: $adminPrefix.'/extensions',
                requiredPermission: 'example-plugin.view_example_dashboard',
                activeWhen: 'admin.extensions.*',
            )
        );

        $this->app->make(AdminDashboardPanelRegistry::class)->registerAdminDashboardPanel(
            new AdminDashboardPanel(
                pluginSlug: 'example-plugin',
                key: 'example-plugin-panel',
                title: 'Example Plugin Surface',
                description: 'Painel simples usado apenas para validar o primeiro ponto de extensao do core.',
                href: $adminPrefix.'/extensions',
                requiredPermission: 'example-plugin.view_example_dashboard',
                badge: 'Plugin',
            )
        );
    }
}
