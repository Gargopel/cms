<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_forms_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->text('success_message')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
        });

        Schema::create('plugin_forms_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('plugin_forms_forms')->cascadeOnDelete();
            $table->string('label', 180);
            $table->string('name', 180);
            $table->string('type', 32);
            $table->string('placeholder', 255)->nullable();
            $table->text('help_text')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(10);
            $table->timestamps();

            $table->unique(['form_id', 'name']);
        });

        Schema::create('plugin_forms_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained('plugin_forms_forms')->cascadeOnDelete();
            $table->timestamp('submitted_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('plugin_forms_submission_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('plugin_forms_submissions')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('plugin_forms_fields')->cascadeOnDelete();
            $table->string('field_name', 180);
            $table->string('field_label', 180);
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_forms_submission_values');
        Schema::dropIfExists('plugin_forms_submissions');
        Schema::dropIfExists('plugin_forms_fields');
        Schema::dropIfExists('plugin_forms_forms');
    }
};
