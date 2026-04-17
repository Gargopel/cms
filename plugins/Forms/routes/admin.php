<?php

use Illuminate\Support\Facades\Route;
use Plugins\Forms\Enums\FormsPermission;
use Plugins\Forms\Http\Controllers\Admin\FormController;
use Plugins\Forms\Http\Controllers\Admin\FormFieldController;
use Plugins\Forms\Http\Controllers\Admin\FormSubmissionController;

Route::middleware(['web'])
    ->prefix(trim((string) config('platform.admin.prefix', 'admin'), '/'))
    ->as('plugins.forms.admin.')
    ->group(function (): void {
        Route::middleware(['core.install.installed', 'core.admin.auth', 'can:access_admin'])->group(function (): void {
            Route::get('/forms', [FormController::class, 'index'])
                ->middleware('can:'.FormsPermission::ViewForms->value)
                ->name('index');

            Route::get('/forms/create', [FormController::class, 'create'])
                ->middleware('can:'.FormsPermission::CreateForms->value)
                ->name('create');

            Route::post('/forms', [FormController::class, 'store'])
                ->middleware('can:'.FormsPermission::CreateForms->value)
                ->name('store');

            Route::get('/forms/{form}/edit', [FormController::class, 'edit'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('edit');

            Route::put('/forms/{form}', [FormController::class, 'update'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('update');

            Route::delete('/forms/{form}', [FormController::class, 'destroy'])
                ->middleware('can:'.FormsPermission::DeleteForms->value)
                ->name('destroy');

            Route::get('/forms/{form}/fields', [FormFieldController::class, 'index'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('fields.index');

            Route::get('/forms/{form}/fields/create', [FormFieldController::class, 'create'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('fields.create');

            Route::post('/forms/{form}/fields', [FormFieldController::class, 'store'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('fields.store');

            Route::get('/forms/{form}/fields/{field}/edit', [FormFieldController::class, 'edit'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('fields.edit');

            Route::put('/forms/{form}/fields/{field}', [FormFieldController::class, 'update'])
                ->middleware('can:'.FormsPermission::EditForms->value)
                ->name('fields.update');

            Route::get('/forms/{form}/submissions', [FormSubmissionController::class, 'index'])
                ->middleware('can:'.FormsPermission::ViewFormSubmissions->value)
                ->name('submissions.index');
        });
    });
