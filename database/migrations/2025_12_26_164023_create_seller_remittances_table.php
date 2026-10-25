<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellerRemittancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_remittances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();

            $table->date('cycle_from_date')->index();
            $table->date('cycle_to_date')->index();

            $table->string('remittance_id')->unique();

            $table->date('payment_scheduled_date')->nullable()->index();
            $table->date('payment_date')->nullable();

            $table->integer('total_delivery_count')->default(0);

            $table->decimal('total_cod_remittance_amount', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('amount_transferred_to_seller', 15, 2)->default(0);
            $table->decimal('amount_transferred_next_cycle_or_wallet', 15, 2)->default(0);
            $table->decimal('previous_cycle_pending_net_amount', 15, 2)->default(0);

            $table->enum('settlement_status', [
                'pending',
                'completed'
            ])->default('pending');

            $table->string('bank_account_no')->nullable();
            $table->string('utr')->nullable();

            $table->string('seller_payment_mode')->nullable();

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
        Schema::dropIfExists('seller_remittances');
    }
}
