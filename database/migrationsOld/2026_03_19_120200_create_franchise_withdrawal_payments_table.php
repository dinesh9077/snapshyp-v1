<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFranchiseWithdrawalPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('franchise_withdrawal_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('withdrawal_request_id');
            $table->decimal('amount', 15, 2);
            $table->string('bank_account_no')->nullable();
            $table->string('utr');
            $table->date('payment_date');
            $table->string('payment_mode');
            $table->text('admin_note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();

            $table->foreign('withdrawal_request_id')->references('id')->on('franchise_withdrawal_requests')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('franchise_withdrawal_payments');
    }
}
