<?php

use Illuminate\Support\Facades\Route;
use Plugins\Seo\Http\Controllers\PublicSitemapController;

Route::middleware(['web'])
    ->group(function (): void {
        Route::get('/sitemap.xml', PublicSitemapController::class)
            ->name('plugins.seo.public.sitemap');
    });
