<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabelPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('label_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('label_size')->default('thermal');
            $table->boolean('label_show_invoice_value')->default(1);
            $table->boolean('label_show_invoice_number')->default(1);
            $table->boolean('label_show_seller_name')->default(1);
            $table->boolean('label_show_company_name')->default(1);
            $table->boolean('label_show_seller_address')->default(1);
            $table->boolean('label_show_seller_mobile')->default(1);
            $table->boolean('label_show_quantity')->default(1);
            $table->boolean('label_show_item_price')->default(1);
            $table->boolean('label_show_taxes')->default(1);
            $table->boolean('label_show_product_name')->default(1);
            $table->boolean('label_show_sku')->default(1);
            $table->boolean('label_print_multiple')->default(1);
            $table->integer('label_product_name_limit')->default(128);
            $table->integer('label_sku_limit')->default(32);
            $table->integer('label_print_limit')->default(2);
            $table->string('label_company_logo')->nullable();
            $table->boolean('label_show_weight')->default(1);
            $table->boolean('label_show_cod_amount')->default(1);
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
        Schema::dropIfExists('label_preferences');
    }
}
