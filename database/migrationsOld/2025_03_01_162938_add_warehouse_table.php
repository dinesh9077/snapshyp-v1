<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarehouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            $table->integer('delhivery_status')->default(0);
            $table->integer('delhivery_status1')->default(0);
            $table->json('api_response1')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            //
        });
    }
}
