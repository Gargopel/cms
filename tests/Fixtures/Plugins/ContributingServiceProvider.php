<?php

namespace Tests\Fixtures\Plugins;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use Illuminate\Support\ServiceProvider;

class ContributingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $adminPrefix = '/'.trim((string) config('platform.admin.prefix', 'admin'), '/');

        $this->app->make(AdminNavigationRegistry::class)->registerAdminNavigationItem(
            new AdminNavigationItem(
                pluginSlug: 'reporting-suite',
                key: 'reporting-console',
                label: 'Reporting Console',
                description: 'Entrada administrativa publicada por plugin habilitado.',
                href: $adminPrefix.'/extensions',
                requiredPermission: 'reporting-suite.view_console',
                activeWhen: 'admin.extensions.*',
            )
        );

        $this->app->make(AdminDashboardPanelRegistry::class)->registerAdminDashboardPanel(
            new AdminDashboardPanel(
                pluginSlug: 'reporting-suite',
                key: 'reporting-snapshot',
                title: 'Reporting Snapshot',
                description: 'Painel tecnico simples publicado pelo primeiro ponto de extensao do core.',
                href: $adminPrefix.'/extensions',
                requiredPermission: 'reporting-suite.view_console',
                badge: 'Plugin',
            )
        );
    }
}
