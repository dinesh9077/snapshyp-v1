<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNdrDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ndr_details', function (Blueprint $table) {
            $table->id();
            $table->string('shipping_id')->nullable();
            $table->string('awb_number')->unique(); 
            $table->string('ndr_date')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('total_attempts')->default(1);
            $table->json('response')->nullable();
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
        Schema::dropIfExists('ndr_details');
    }
}
