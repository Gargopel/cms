<?php

namespace Tests\Fixtures\Plugins;

use Illuminate\Support\ServiceProvider;
use RuntimeException;

class ExplodingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        throw new RuntimeException('Exploding test provider.');
    }
}
