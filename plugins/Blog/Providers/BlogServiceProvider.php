<?php

namespace Plugins\Blog\Providers;

use App\Core\Contracts\Extensions\Admin\AdminDashboardPanelRegistry;
use App\Core\Contracts\Extensions\Admin\AdminNavigationRegistry;
use App\Core\Contracts\Extensions\Themes\ThemeSlotRegistry;
use App\Core\Extensions\Hooks\AdminDashboardPanel;
use App\Core\Extensions\Hooks\AdminNavigationItem;
use App\Core\Extensions\Hooks\ThemeSlotBlock;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Plugins\Blog\Enums\BlogPermission;
use Plugins\Blog\Models\Post;

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
                themeView: 'plugins.blog.slots.footer-cta',
            )
        );

        $this->app->make(ThemeSlotRegistry::class)->registerThemeSlotBlock(
            new ThemeSlotBlock(
                pluginSlug: 'blog',
                key: 'blog-recent-posts',
                slot: 'sidebar',
                view: 'blog::slots.recent-posts',
                priority: 20,
                data: [
                    'title' => 'Recent Blog Posts',
                    'description' => 'Editorial stream from the official Blog plugin.',
                    'browse_href' => '/blog',
                ],
                themeView: 'plugins.blog.slots.recent-posts',
                dataResolver: function (array $context): ?array {
                    if (($context['surface'] ?? null) !== 'home') {
                        return null;
                    }

                    if (! Schema::hasTable('plugin_blog_posts')) {
                        return null;
                    }

                    $posts = Post::query()
                        ->published()
                        ->with(['category'])
                        ->orderByDesc('published_at')
                        ->orderByDesc('id')
                        ->limit(3)
                        ->get();

                    if ($posts->isEmpty()) {
                        return null;
                    }

                    return [
                        'posts' => $posts,
                    ];
                },
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
