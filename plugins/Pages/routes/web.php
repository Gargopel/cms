<?php

use Illuminate\Support\Facades\Route;
use Plugins\Pages\Http\Controllers\PublicPageController;

Route::middleware(['web'])
    ->group(function (): void {
        Route::get('/pages/{slug}', PublicPageController::class)
            ->name('plugins.pages.public.show');
    });
