<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TotalPrepaidRemittanceAmountSellerRemittancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seller_remittances', function (Blueprint $table) {
            $table->decimal('total_prepaid_remittance_amount', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seller_remittances', function (Blueprint $table) {
            $table->dropColumn('total_prepaid_remittance_amount');
        });
    }
}
