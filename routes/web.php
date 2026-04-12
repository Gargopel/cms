<?php

use App\Core\Auth\Http\Controllers\AdminAuthController;
use App\Core\Install\Http\Controllers\InstallWizardController;
use App\Core\Themes\Http\Controllers\ThemeHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', ThemeHomeController::class)->name('home');

Route::prefix((string) config('platform.install.prefix', 'install'))
    ->as('install.')
    ->group(function (): void {
        Route::middleware('core.install.not-installed')->group(function (): void {
            Route::get('/', [InstallWizardController::class, 'welcome'])->name('welcome');
            Route::get('/requirements', [InstallWizardController::class, 'requirements'])->name('requirements');
            Route::get('/database', [InstallWizardController::class, 'database'])->name('database');
            Route::post('/database', [InstallWizardController::class, 'storeDatabase'])->name('database.store');
            Route::get('/administrator', [InstallWizardController::class, 'administrator'])->name('admin');
            Route::post('/administrator', [InstallWizardController::class, 'storeAdministrator'])->name('admin.store');
            Route::post('/run', [InstallWizardController::class, 'install'])->name('run');
        });

        Route::get('/complete', [InstallWizardController::class, 'complete'])
            ->middleware('core.install.installed')
            ->name('complete');
    });

Route::prefix((string) config('platform.admin.prefix', 'admin'))
    ->as('admin.')
    ->group(function (): void {
        Route::middleware(['core.install.installed', 'guest'])->group(function (): void {
            Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'store'])->name('login.attempt');
        });

        Route::post('/logout', [AdminAuthController::class, 'destroy'])
            ->middleware(['core.install.installed', 'core.admin.auth'])
            ->name('logout');

        Route::middleware(['core.install.installed', 'core.admin.auth', 'can:access_admin'])
            ->group(base_path('routes/admin.php'));
    });
