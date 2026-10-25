<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPennyDropFieldsToUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kycs', 'bank_text')) {
                $table->json('bank_text')->nullable()->after('bank_reason');
            }
            if (!Schema::hasColumn('user_kycs', 'amount_deposited')) {
                $table->float('amount_deposited')->nullable()->after('bank_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            $table->dropColumn(['bank_text', 'amount_deposited']);
        });
    }
}
