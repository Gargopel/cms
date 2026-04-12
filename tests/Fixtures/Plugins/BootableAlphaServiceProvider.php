<?php

namespace Tests\Fixtures\Plugins;

use Illuminate\Support\ServiceProvider;

class BootableAlphaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('tests.plugin.alpha', true);
    }
}
