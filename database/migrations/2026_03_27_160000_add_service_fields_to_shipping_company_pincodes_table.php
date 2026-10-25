<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_company_pincodes', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_company_pincodes', 'service_mode')) {
                $table->string('service_mode', 20)->nullable()->after('status');
            }

            if (!Schema::hasColumn('shipping_company_pincodes', 'oda_category')) {
                $table->string('oda_category', 20)->default('none')->after('service_mode');
            }

            if (!Schema::hasColumn('shipping_company_pincodes', 'cod_enabled')) {
                $table->boolean('cod_enabled')->default(true)->after('oda_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_company_pincodes', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_company_pincodes', 'cod_enabled')) {
                $table->dropColumn('cod_enabled');
            }

            if (Schema::hasColumn('shipping_company_pincodes', 'oda_category')) {
                $table->dropColumn('oda_category');
            }

            if (Schema::hasColumn('shipping_company_pincodes', 'service_mode')) {
                $table->dropColumn('service_mode');
            }
        });
    }
};
