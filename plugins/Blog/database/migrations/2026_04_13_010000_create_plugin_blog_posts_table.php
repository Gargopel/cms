<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 320);
            $table->longText('content');
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_blog_posts');
    }
};
