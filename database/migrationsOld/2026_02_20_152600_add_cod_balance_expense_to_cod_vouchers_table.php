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
        Schema::table('cod_vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('cod_vouchers', 'cod_balance_expense')) {
                $table->decimal('cod_balance_expense', 10, 2)->default(0)->after('plan_percentage');
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
        Schema::table('cod_vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('cod_vouchers', 'cod_balance_expense')) {
                $table->dropColumn('cod_balance_expense');
            }
        });
    }
};

