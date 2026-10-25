<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePincodeServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pincode_services', function (Blueprint $table) {
            $table->id();
            $table->integer('shipping_id')->nullable();
            $table->string('origin_pincode');
            $table->string('origin_city')->nullable();
            $table->string('origin_state')->nullable();
            $table->string('origin_center')->nullable();
            $table->string('origin_serviceable')->nullable();
            $table->string('des_pincode');
            $table->string('des_city')->nullable();
            $table->string('des_state')->nullable();
            $table->decimal('shipping_charge',25,4)->default(0);
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
        Schema::dropIfExists('pincode_services');
    }
}
