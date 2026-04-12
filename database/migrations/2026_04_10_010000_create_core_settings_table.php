<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group_name', 100);
            $table->string('key_name', 150);
            $table->string('type', 20);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['group_name', 'key_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_settings');
    }
};
