<?php

namespace Plugins\Blog\Providers;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use App\Core\Extensions\Hooks\ThemeSlotBlock;
use Illuminate\Support\ServiceProvider;
use Plugins\Blog\Enums\BlogPermission;

class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $adminPrefix = '/'.trim((string) config('platform.admin.prefix', 'admin'), '/');

        $this->app->make(AdminNavigationRegistry::class)->registerAdminNavigationItem(
            new AdminNavigationItem(
                pluginSlug: 'blog',
                key: 'blog-posts',
                label: 'Blog',
                description: 'Posts editoriais simples do plugin oficial Blog.',
                href: $adminPrefix.'/blog/posts',
                requiredPermission: BlogPermission::ViewPosts->value,
                activeWhen: 'plugins.blog.admin.*',
            )
        );

        $this->app->make(AdminDashboardPanelRegistry::class)->registerAdminDashboardPanel(
            new AdminDashboardPanel(
                pluginSlug: 'blog',
                key: 'blog-overview',
                title: 'Blog Posts',
                description: 'Entrada oficial do ecossistema para conteudo editorial publicado.',
                href: $adminPrefix.'/blog/posts',
                requiredPermission: BlogPermission::ViewPosts->value,
                badge: 'Official Plugin',
            )
        );

        $this->app->make(ThemeSlotRegistry::class)->registerThemeSlotBlock(
            new ThemeSlotBlock(
                pluginSlug: 'blog',
                key: 'blog-footer-cta',
                slot: 'footer_cta',
                view: 'blog::slots.footer-cta',
                priority: 40,
                data: [
                    'title' => 'Editorial plugin pronto para crescer com o ecossistema',
                    'description' => 'O Blog oficial prova como plugins validos e habilitados podem contribuir com regioes de tema sem virar page builder.',
                    'href' => '/blog',
                    'cta_label' => 'Explorar Blog',
                ],
            )
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes/web.php';
            require __DIR__.'/../routes/admin.php';
        }
    }
}
