<?php

use Illuminate\Support\Facades\Route;
use Plugins\Blog\Http\Controllers\PublicBlogController;

Route::middleware(['web'])
    ->group(function (): void {
        Route::get('/blog', [PublicBlogController::class, 'index'])
            ->name('plugins.blog.public.index');

        Route::get('/blog/category/{slug}', [PublicBlogController::class, 'category'])
            ->name('plugins.blog.public.category');

        Route::get('/blog/tag/{slug}', [PublicBlogController::class, 'tag'])
            ->name('plugins.blog.public.tag');

        Route::get('/blog/{slug}', [PublicBlogController::class, 'show'])
            ->name('plugins.blog.public.show');
    });
