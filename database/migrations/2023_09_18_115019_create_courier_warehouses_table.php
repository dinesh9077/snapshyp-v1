<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourierWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courier_warehouses', function (Blueprint $table) {
            $table->id();
            $table->integer('vendor_address_id');
            $table->string('warehouse_name')->nullable();
            $table->integer('shipping_id')->nullable();
            $table->integer('warehouse_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courier_warehouses');
    }
}
