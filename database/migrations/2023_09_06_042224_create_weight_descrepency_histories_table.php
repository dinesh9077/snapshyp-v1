<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeightDescrepencyHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weight_descrepency_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id'); 
            $table->string('status_descrepency'); 
            $table->string('action_by'); 
            $table->string('remarks'); 
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
        Schema::dropIfExists('weight_descrepency_histories');
    }
}
