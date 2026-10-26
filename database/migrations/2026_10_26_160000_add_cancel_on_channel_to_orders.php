<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A cancellation the courier only queued ("cancel requested") remembers whether the seller asked to
// cancel on the sales channel too, so the Shopify order is cancelled once the courier confirms it
class AddCancelOnChannelToOrders extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('cancel_on_channel')->default(false);
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cancel_on_channel');
        });
    }
}
