<?php

use Illuminate\Support\Facades\Route;
use Plugins\Forms\Http\Controllers\PublicFormController;

Route::middleware(['web'])
    ->group(function (): void {
        Route::get('/forms/{slug}', [PublicFormController::class, 'show'])
            ->name('plugins.forms.public.show');

        Route::post('/forms/{slug}', [PublicFormController::class, 'submit'])
            ->name('plugins.forms.public.submit');
    });
