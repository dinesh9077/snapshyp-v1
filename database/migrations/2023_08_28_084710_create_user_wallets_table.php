<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
			$table->integer('user_id');
			$table->decimal('amount',25,2)->default(0);
			$table->integer('status')->default(0);
			$table->string('transaction_type')->nullable();
			$table->string('txn_number')->nullable();
			$table->text('payable_response')->nullable();
			$table->text('payment_receipt')->nullable();
			$table->text('note')->nullable();
			$table->text('reject_note')->nullable();
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
        Schema::dropIfExists('user_wallets');
    }
}
