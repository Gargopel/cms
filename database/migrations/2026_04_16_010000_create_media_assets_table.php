<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 40)->default('public');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path')->unique();
            $table->string('mime_type', 191);
            $table->unsignedBigInteger('size_bytes');
            $table->string('extension', 20)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['disk', 'created_at']);
            $table->index('mime_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
