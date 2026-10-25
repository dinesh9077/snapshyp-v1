<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zone_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('zone_settings', 'zone_code')) {
                $table->string('zone_code', 50)->nullable()->after('heading');
            }

            if (!Schema::hasColumn('zone_settings', 'zone_name')) {
                $table->string('zone_name')->nullable()->after('zone_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zone_settings', function (Blueprint $table) {
            if (Schema::hasColumn('zone_settings', 'zone_name')) {
                $table->dropColumn('zone_name');
            }

            if (Schema::hasColumn('zone_settings', 'zone_code')) {
                $table->dropColumn('zone_code');
            }
        });
    }
};
