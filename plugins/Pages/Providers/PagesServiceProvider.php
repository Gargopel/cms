<?php

namespace Plugins\Pages\Providers;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use Illuminate\Support\ServiceProvider;
use Plugins\Pages\Enums\PagesPermission;

class PagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $adminPrefix = '/'.trim((string) config('platform.admin.prefix', 'admin'), '/');

        $this->app->make(AdminNavigationRegistry::class)->registerAdminNavigationItem(
            new AdminNavigationItem(
                pluginSlug: 'pages',
                key: 'pages-library',
                label: 'Pages',
                description: 'Paginas publicas simples do plugin oficial.',
                href: $adminPrefix.'/pages',
                requiredPermission: PagesPermission::ViewPages->value,
                activeWhen: 'plugins.pages.admin.*',
            )
        );

        $this->app->make(AdminDashboardPanelRegistry::class)->registerAdminDashboardPanel(
            new AdminDashboardPanel(
                pluginSlug: 'pages',
                key: 'pages-overview',
                title: 'Pages Library',
                description: 'Entrada oficial do ecossistema para gerenciar paginas publicas simples.',
                href: $adminPrefix.'/pages',
                requiredPermission: PagesPermission::ViewPages->value,
                badge: 'Official Plugin',
            )
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pages');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes/web.php';
            require __DIR__.'/../routes/admin.php';
        }
    }
}
