<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_records', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->string('slug')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('detected_version')->nullable();
            $table->string('path');
            $table->string('manifest_path')->nullable();
            $table->string('discovery_status', 20);
            $table->string('operational_status', 20);
            $table->json('discovery_errors')->nullable();
            $table->json('raw_manifest')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'path']);
            $table->index(['type', 'slug']);
            $table->index(['type', 'discovery_status']);
            $table->index(['type', 'operational_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_records');
    }
};
