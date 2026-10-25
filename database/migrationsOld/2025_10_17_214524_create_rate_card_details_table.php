<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRateCardDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_card_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rate_card_id')->index();
            $table->unsignedBigInteger('shipping_company_id')->index();
            $table->unsignedBigInteger('zone_setting_id')->index(); 
            $table->decimal('weight', 8, 2)->index();  
            $table->string('zone_slug')->index();
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('extra_rate', 10, 2)->default(0);
            $table->decimal('cod_charges', 10, 2)->default(0);
            $table->decimal('cod_percentage', 5, 2)->default(0);
            $table->tinyInteger('status')->default(1)->index();
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
        Schema::dropIfExists('rate_card_details');
    }
}
