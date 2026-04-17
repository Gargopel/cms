<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin_blog_posts', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->after('featured_image_id')
                ->constrained('plugin_blog_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plugin_blog_posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
