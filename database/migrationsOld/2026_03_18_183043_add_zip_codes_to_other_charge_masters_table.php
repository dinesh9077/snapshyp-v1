<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZipCodesToOtherChargeMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('other_charge_masters', function (Blueprint $table) {
            $table->json('zip_codes')->nullable()->after('calc_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('other_charge_masters', function (Blueprint $table) {
            $table->dropColumn('zip_codes');
        });
    }
}
