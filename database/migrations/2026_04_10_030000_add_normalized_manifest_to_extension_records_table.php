<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extension_records', function (Blueprint $table): void {
            $table->json('normalized_manifest')->nullable()->after('raw_manifest');
            $table->json('manifest_warnings')->nullable()->after('normalized_manifest');
        });
    }

    public function down(): void
    {
        Schema::table('extension_records', function (Blueprint $table): void {
            $table->dropColumn(['normalized_manifest', 'manifest_warnings']);
        });
    }
};
