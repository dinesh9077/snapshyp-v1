<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cod_wallet_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('cod_balance_used', 10, 2)->default(0)->comment('Amount deducted from COD balance');
            $table->decimal('wallet_amount_added', 10, 2)->default(0)->comment('Amount added to wallet');
            $table->decimal('transfer_percentage', 5, 2)->default(0)->comment('Percentage used for transfer');
            $table->string('transaction_type')->default('cod_to_wallet')->comment('Type of transaction');
            $table->string('status')->default('completed')->comment('Transaction status: pending, completed, failed');
            $table->string('reference_id')->nullable()->comment('Unique reference ID for transaction');
            $table->text('remarks')->nullable()->comment('Additional remarks or notes');
            $table->timestamp('processed_at')->nullable()->comment('When the transaction was processed');
            $table->timestamps();

            // Indexes for better query performance
            $table->index('user_id');
            $table->index('status');
            $table->index('processed_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cod_wallet_expenses');
    }
};
