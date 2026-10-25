<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_companies', 'freight_mode')) {
                $table->string('freight_mode', 20)->default('surface')->after('mode');
            }

            if (!Schema::hasColumn('shipping_companies', 'carrier_type')) {
                $table->string('carrier_type', 30)->default('national')->after('freight_mode');
            }
        });

        if (Schema::hasColumn('shipping_companies', 'selection_mode')) {
            DB::table('shipping_companies')->update([
                'selection_mode' => 'smart_select',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_companies', 'carrier_type')) {
                $table->dropColumn('carrier_type');
            }

            if (Schema::hasColumn('shipping_companies', 'freight_mode')) {
                $table->dropColumn('freight_mode');
            }
        });
    }
};
