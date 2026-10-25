<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchiseEarningsTable extends Migration
{
    public function up()
    {
        Schema::create('franchise_earnings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('franchise_user_id');
            $table->unsignedBigInteger('seller_user_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('withdrawal_request_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('available');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('franchise_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('seller_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->unique(['franchise_user_id', 'order_id'], 'franchise_earnings_user_order_unique');
            $table->index(['franchise_user_id', 'withdrawal_request_id'], 'franchise_earnings_balance_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('franchise_earnings');
    }
}
