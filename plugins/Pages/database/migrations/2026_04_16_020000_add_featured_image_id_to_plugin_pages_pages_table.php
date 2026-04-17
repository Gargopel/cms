<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plugin_pages_pages', function (Blueprint $table): void {
            $table->foreignId('featured_image_id')
                ->nullable()
                ->after('status')
                ->constrained('media_assets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('plugin_pages_pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('featured_image_id');
        });
    }
};
