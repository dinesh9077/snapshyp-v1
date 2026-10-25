<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeightDescrepenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weight_descrepencies', function (Blueprint $table) {
            $table->id(); 
            $table->integer('order_id');
            $table->string('awb_number');
            $table->text('product_image'); 
            $table->string('length');
            $table->string('width');
            $table->string('height');
            $table->string('weight');
            $table->string('chargeable_weight');
            $table->string('length_image');
            $table->string('width_image');
            $table->string('height_image');
            $table->string('weight_image');
            $table->string('lable_image'); 
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
        Schema::dropIfExists('weight_descrepencies');
    }
}
