<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRtoChargeToRateCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rate_cards', function (Blueprint $table) {
            $table->boolean('is_rto_charge')->default(1)->after('group_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rate_cards', function (Blueprint $table) {
            $table->dropColumn('is_rto_charge');
        });
    }
}
