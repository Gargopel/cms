<?php

namespace Plugins\Forms\Providers;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use Illuminate\Support\ServiceProvider;
use Plugins\Forms\Enums\FormsPermission;
use Plugins\Forms\Support\FormSubmissionService;

class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormSubmissionService::class);

        $adminPrefix = '/'.trim((string) config('platform.admin.prefix', 'admin'), '/');

        $this->app->make(AdminNavigationRegistry::class)->registerAdminNavigationItem(
            new AdminNavigationItem(
                pluginSlug: 'forms',
                key: 'forms-library',
                label: 'Forms',
                description: 'Formularios publicos simples com submissao persistida no plugin oficial Forms.',
                href: $adminPrefix.'/forms',
                requiredPermission: FormsPermission::ViewForms->value,
                activeWhen: 'plugins.forms.admin.*',
            )
        );

        $this->app->make(AdminDashboardPanelRegistry::class)->registerAdminDashboardPanel(
            new AdminDashboardPanel(
                pluginSlug: 'forms',
                key: 'forms-overview',
                title: 'Forms Inbox',
                description: 'Entrada oficial do ecossistema para formularios publicos simples e submissões persistidas.',
                href: $adminPrefix.'/forms',
                requiredPermission: FormsPermission::ViewForms->value,
                badge: 'Official Plugin',
            )
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'forms');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes/web.php';
            require __DIR__.'/../routes/admin.php';
        }
    }
}
