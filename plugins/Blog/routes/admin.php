<?php

use Illuminate\Support\Facades\Route;
use Plugins\Blog\Enums\BlogPermission;
use Plugins\Blog\Http\Controllers\Admin\CategoryController;
use Plugins\Blog\Http\Controllers\Admin\PostController;
use Plugins\Blog\Http\Controllers\Admin\TagController;

Route::middleware(['web'])
    ->prefix(trim((string) config('platform.admin.prefix', 'admin'), '/'))
    ->as('plugins.blog.admin.')
    ->group(function (): void {
        Route::middleware(['core.install.installed', 'core.admin.auth', 'can:access_admin'])->group(function (): void {
            Route::get('/blog/posts', [PostController::class, 'index'])
                ->middleware('can:'.BlogPermission::ViewPosts->value)
                ->name('index');

            Route::get('/blog/posts/create', [PostController::class, 'create'])
                ->middleware('can:'.BlogPermission::CreatePosts->value)
                ->name('create');

            Route::post('/blog/posts', [PostController::class, 'store'])
                ->middleware('can:'.BlogPermission::CreatePosts->value)
                ->name('store');

            Route::get('/blog/posts/{post}/edit', [PostController::class, 'edit'])
                ->middleware('can:'.BlogPermission::EditPosts->value)
                ->name('edit');

            Route::put('/blog/posts/{post}', [PostController::class, 'update'])
                ->middleware('can:'.BlogPermission::EditPosts->value)
                ->name('update');

            Route::delete('/blog/posts/{post}', [PostController::class, 'destroy'])
                ->middleware('can:'.BlogPermission::DeletePosts->value)
                ->name('destroy');

            Route::get('/blog/categories', [CategoryController::class, 'index'])
                ->middleware('can:'.BlogPermission::ManageCategories->value)
                ->name('categories.index');

            Route::get('/blog/categories/create', [CategoryController::class, 'create'])
                ->middleware('can:'.BlogPermission::ManageCategories->value)
                ->name('categories.create');

            Route::post('/blog/categories', [CategoryController::class, 'store'])
                ->middleware('can:'.BlogPermission::ManageCategories->value)
                ->name('categories.store');

            Route::get('/blog/categories/{category}/edit', [CategoryController::class, 'edit'])
                ->middleware('can:'.BlogPermission::ManageCategories->value)
                ->name('categories.edit');

            Route::put('/blog/categories/{category}', [CategoryController::class, 'update'])
                ->middleware('can:'.BlogPermission::ManageCategories->value)
                ->name('categories.update');

            Route::get('/blog/tags', [TagController::class, 'index'])
                ->middleware('can:'.BlogPermission::ManageTags->value)
                ->name('tags.index');

            Route::get('/blog/tags/create', [TagController::class, 'create'])
                ->middleware('can:'.BlogPermission::ManageTags->value)
                ->name('tags.create');

            Route::post('/blog/tags', [TagController::class, 'store'])
                ->middleware('can:'.BlogPermission::ManageTags->value)
                ->name('tags.store');

            Route::get('/blog/tags/{tag}/edit', [TagController::class, 'edit'])
                ->middleware('can:'.BlogPermission::ManageTags->value)
                ->name('tags.edit');

            Route::put('/blog/tags/{tag}', [TagController::class, 'update'])
                ->middleware('can:'.BlogPermission::ManageTags->value)
                ->name('tags.update');
        });
    });
