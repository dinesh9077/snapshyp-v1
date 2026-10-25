<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('woocommerce_order_id')->nullable()->after('shopify_tags');
            $table->string('woocommerce_order_number')->nullable()->after('woocommerce_order_id');
            $table->string('woocommerce_order_key')->nullable()->after('woocommerce_order_number');
            $table->tinyInteger('is_woocommerce_order')->default(0)->after('woocommerce_order_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'woocommerce_order_id',
                'woocommerce_order_number',
                'woocommerce_order_key',
                'is_woocommerce_order'
            ]);
        });
    }
};
