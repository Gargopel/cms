<?php

use App\Core\Admin\Http\Controllers\AdminDashboardController;
use App\Core\Admin\Http\Controllers\AdminAuditLogsController;
use App\Core\Admin\Http\Controllers\AdminExtensionsController;
use App\Core\Admin\Http\Controllers\AdminMaintenanceController;
use App\Core\Admin\Http\Controllers\AdminPermissionsController;
use App\Core\Admin\Http\Controllers\AdminRolesController;
use App\Core\Admin\Http\Controllers\AdminSettingsController;
use App\Core\Admin\Http\Controllers\AdminSystemHealthController;
use App\Core\Admin\Http\Controllers\AdminThemesController;
use App\Core\Admin\Http\Controllers\AdminUsersController;
use App\Core\Auth\Enums\CorePermission;
use Illuminate\Support\Facades\Route;

Route::get('/', AdminDashboardController::class)
    ->middleware('can:'.CorePermission::ViewDashboard->value)
    ->name('dashboard');

Route::get('/extensions', [AdminExtensionsController::class, 'index'])
    ->middleware('can:'.CorePermission::ViewExtensions->value)
    ->name('extensions.index');

Route::get('/themes', [AdminThemesController::class, 'index'])
    ->middleware('can:'.CorePermission::ViewThemes->value)
    ->name('themes.index');

Route::post('/themes/{extension}/activate', [AdminThemesController::class, 'activate'])
    ->middleware('can:'.CorePermission::ManageThemes->value)
    ->name('themes.activate');

Route::post('/extensions/sync', [AdminExtensionsController::class, 'sync'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.sync');

Route::post('/extensions/{extension}/install', [AdminExtensionsController::class, 'install'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.install');

Route::post('/extensions/{extension}/enable', [AdminExtensionsController::class, 'enable'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.enable');

Route::post('/extensions/{extension}/disable', [AdminExtensionsController::class, 'disable'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.disable');

Route::post('/extensions/{extension}/remove', [AdminExtensionsController::class, 'remove'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.remove');

Route::post('/extensions/{extension}/migrations/run', [AdminExtensionsController::class, 'runMigrations'])
    ->middleware('can:'.CorePermission::ManageExtensions->value)
    ->name('extensions.migrations.run');

Route::get('/audit', AdminAuditLogsController::class)
    ->middleware('can:'.CorePermission::ViewAuditLogs->value)
    ->name('audit.index');

Route::get('/health', AdminSystemHealthController::class)
    ->middleware('can:'.CorePermission::ViewSystemHealth->value)
    ->name('health.index');

Route::get('/maintenance', AdminMaintenanceController::class)
    ->middleware('can:'.CorePermission::ViewMaintenance->value)
    ->name('maintenance');

Route::post('/maintenance/cache/application-clear', [AdminMaintenanceController::class, 'clearApplicationCache'])
    ->middleware('can:'.CorePermission::RunMaintenanceActions->value)
    ->name('maintenance.cache.application-clear');

Route::post('/maintenance/cache/views-clear', [AdminMaintenanceController::class, 'clearCompiledViews'])
    ->middleware('can:'.CorePermission::RunMaintenanceActions->value)
    ->name('maintenance.cache.views-clear');

Route::prefix('/users')
    ->middleware('can:'.CorePermission::ManageUsers->value)
    ->as('users.')
    ->group(function (): void {
        Route::get('/', [AdminUsersController::class, 'index'])->name('index');
        Route::get('/create', [AdminUsersController::class, 'create'])->name('create');
        Route::post('/', [AdminUsersController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminUsersController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUsersController::class, 'update'])->name('update');
    });

Route::prefix('/roles')
    ->middleware('can:'.CorePermission::ManageRoles->value)
    ->as('roles.')
    ->group(function (): void {
        Route::get('/', [AdminRolesController::class, 'index'])->name('index');
        Route::get('/create', [AdminRolesController::class, 'create'])->name('create');
        Route::post('/', [AdminRolesController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [AdminRolesController::class, 'edit'])->name('edit');
        Route::put('/{role}', [AdminRolesController::class, 'update'])->name('update');
    });

Route::get('/permissions', [AdminPermissionsController::class, 'index'])
    ->middleware('can:'.CorePermission::ManagePermissions->value)
    ->name('permissions.index');

Route::get('/settings', [AdminSettingsController::class, 'edit'])
    ->middleware('can:'.CorePermission::ViewSettings->value)
    ->name('settings.edit');

Route::put('/settings', [AdminSettingsController::class, 'update'])
    ->middleware('can:'.CorePermission::ManageSettings->value)
    ->name('settings.update');
