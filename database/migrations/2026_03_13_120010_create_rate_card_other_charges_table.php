<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rate_card_other_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rate_card_id');
            $table->unsignedBigInteger('shipping_company_id');
            $table->unsignedBigInteger('other_charge_master_id');
            $table->integer('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['rate_card_id', 'shipping_company_id', 'other_charge_master_id'], 'rc_other_charge_unique');
            $table->foreign('rate_card_id')->references('id')->on('rate_cards')->onDelete('cascade');
            $table->foreign('shipping_company_id')->references('id')->on('shipping_companies')->onDelete('cascade');
            $table->foreign('other_charge_master_id')->references('id')->on('other_charge_masters')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rate_card_other_charges');
    }
};
