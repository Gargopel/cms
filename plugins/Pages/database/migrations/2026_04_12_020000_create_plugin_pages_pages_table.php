<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Plugins\Pages\Enums\PageStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_pages_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 180)->unique();
            $table->longText('content');
            $table->string('status', 24)->default(PageStatus::Draft->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_pages_pages');
    }
};
