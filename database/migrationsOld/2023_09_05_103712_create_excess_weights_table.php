<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExcessWeightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('excess_weights', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');  
            $table->integer('order_id');  
            $table->string('chargeable_weight');  
            $table->string('excess_weight');  
            $table->string('excess_charge');  
            $table->string('status');  
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
        Schema::dropIfExists('excess_weights');
    }
}
