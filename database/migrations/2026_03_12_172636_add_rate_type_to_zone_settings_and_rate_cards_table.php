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
            $table->enum('rate_type', ['b2c', 'b2b'])->default('b2c')->after('shipping_company_id');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->enum('rate_type', ['b2c', 'b2b'])->default('b2c')->after('group_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zone_settings', function (Blueprint $table) {
            $table->dropColumn('rate_type');
        });

        Schema::table('rate_cards', function (Blueprint $table) {
            $table->dropColumn('rate_type');
        });
    }
};
