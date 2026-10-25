<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ShopifyColumnOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('is_shopify_order')->default(0)->index();
            $table->string('shopify_order_number')->nullable()->index();
            $table->string('shopify_order_id')->nullable()->index();
            $table->decimal('total_gst_amount', 25,2)->default(0);
            $table->decimal('total_shipping_cost', 25,2)->default(0);
            $table->integer('queue')->default(0);
            $table->integer('confirm_shopify_order')->nullable();
            $table->longText('send_whatsapp_notify')->nullable();
            $table->string('shopify_order_name')->nullable();
            $table->json('shopify_tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
}
