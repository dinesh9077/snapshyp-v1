<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipMozoWarehouseIdToCourierWarehouses extends Migration
{
    public function up()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            // Dedicated column for ShipMozo API warehouse_id (numeric)
            $table->string('shipmozo_warehouse_id')->nullable()->after('shipping_id');
        });
    }

    public function down()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            $table->dropColumn('shipmozo_warehouse_id');
        });
    }
}
