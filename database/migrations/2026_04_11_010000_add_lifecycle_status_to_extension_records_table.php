<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extension_records', function (Blueprint $table): void {
            $table->string('lifecycle_status')->nullable()->after('operational_status');
        });

        DB::table('extension_records')
            ->whereNull('lifecycle_status')
            ->update([
                'lifecycle_status' => DB::raw("
                    CASE
                        WHEN operational_status IN ('enabled', 'disabled', 'installed') THEN 'installed'
                        ELSE 'discovered'
                    END
                "),
            ]);
    }

    public function down(): void
    {
        Schema::table('extension_records', function (Blueprint $table): void {
            $table->dropColumn('lifecycle_status');
        });
    }
};
