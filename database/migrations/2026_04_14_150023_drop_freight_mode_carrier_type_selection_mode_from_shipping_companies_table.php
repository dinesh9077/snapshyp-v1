<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFreightModeCarrierTypeSelectionModeFromShippingCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('shipping_companies', 'freight_mode')) {
            Schema::table('shipping_companies', function (Blueprint $table) {
                $table->dropColumn('freight_mode');
            });
        }
        if (Schema::hasColumn('shipping_companies', 'carrier_type')) {
            Schema::table('shipping_companies', function (Blueprint $table) {
                $table->dropColumn('carrier_type');
            });
        }
        if (Schema::hasColumn('shipping_companies', 'selection_mode')) {
            Schema::table('shipping_companies', function (Blueprint $table) {
                $table->dropColumn('selection_mode');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            $table->string('freight_mode')->nullable()->default('surface');
            $table->string('carrier_type')->nullable()->default('national');
            $table->string('selection_mode')->nullable()->default('smart_select');
        });
    }
}
