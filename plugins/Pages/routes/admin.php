<?php

use Illuminate\Support\Facades\Route;
use Plugins\Pages\Enums\PagesPermission;
use Plugins\Pages\Http\Controllers\Admin\PageController;

Route::middleware(['web'])
    ->prefix(trim((string) config('platform.admin.prefix', 'admin'), '/'))
    ->as('plugins.pages.admin.')
    ->group(function (): void {
        Route::middleware(['core.install.installed', 'core.admin.auth', 'can:access_admin'])->group(function (): void {
            Route::get('/pages', [PageController::class, 'index'])
                ->middleware('can:'.PagesPermission::ViewPages->value)
                ->name('index');

            Route::get('/pages/create', [PageController::class, 'create'])
                ->middleware('can:'.PagesPermission::CreatePages->value)
                ->name('create');

            Route::post('/pages', [PageController::class, 'store'])
                ->middleware('can:'.PagesPermission::CreatePages->value)
                ->name('store');

            Route::get('/pages/{page}/edit', [PageController::class, 'edit'])
                ->middleware('can:'.PagesPermission::EditPages->value)
                ->name('edit');

            Route::put('/pages/{page}', [PageController::class, 'update'])
                ->middleware('can:'.PagesPermission::EditPages->value)
                ->name('update');

            Route::delete('/pages/{page}', [PageController::class, 'destroy'])
                ->middleware('can:'.PagesPermission::DeletePages->value)
                ->name('destroy');
        });
    });
