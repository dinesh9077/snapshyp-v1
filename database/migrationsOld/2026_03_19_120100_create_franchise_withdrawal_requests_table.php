<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchiseWithdrawalRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('franchise_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->text('payment_details')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('franchise_withdrawal_requests');
    }
}
