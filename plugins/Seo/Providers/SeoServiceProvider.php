<?php

namespace Plugins\Seo\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\Seo\Contracts\SeoMetadataResolver;
use Plugins\Seo\Support\DefaultSeoMetadataResolver;
use Plugins\Seo\Support\SeoSitemapGenerator;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoMetadataResolver::class, DefaultSeoMetadataResolver::class);
        $this->app->singleton(SeoSitemapGenerator::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'seo');

        if (! $this->app->routesAreCached()) {
            require __DIR__.'/../routes/web.php';
        }
    }
}
