<?php

namespace Plugins\Seo\Providers;

use Illuminate\Support\ServiceProvider;
use Plugins\Seo\Contracts\SeoMetadataResolver;
use Plugins\Seo\Support\DefaultSeoMetadataResolver;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SeoMetadataResolver::class, DefaultSeoMetadataResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'seo');
    }
}
