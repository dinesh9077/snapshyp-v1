<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodRemittanceFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('cod_remittance_cycle_days')->nullable()->after('kyc_status');
            $table->integer('payout_days')->nullable()->after('cod_remittance_cycle_days');
            $table->enum('remittance_service_fee_type', ['fixed', 'percentage'])->nullable()->after('payout_days');
            $table->decimal('remittance_service_fee_value', 10, 2)->nullable()->after('remittance_service_fee_type');
            $table->decimal('cod_wallet_transfer_value', 10, 2)->nullable()->after('remittance_service_fee_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cod_remittance_cycle_days',
                'payout_days',
                'remittance_service_fee_type',
                'remittance_service_fee_value',
                'cod_wallet_transfer_value',
            ]);
        });
    }
}
