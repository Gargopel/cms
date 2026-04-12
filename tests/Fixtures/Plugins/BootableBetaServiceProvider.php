<?php

namespace Tests\Fixtures\Plugins;

use Illuminate\Support\ServiceProvider;

class BootableBetaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('tests.plugin.beta', true);
    }
}
