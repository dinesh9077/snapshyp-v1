<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddB2cB2bRateCardIdsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('b2c_rate_card_id')->nullable()->after('rate_card_id');
            $table->integer('b2b_rate_card_id')->nullable()->after('b2c_rate_card_id');
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
            $table->dropColumn(['b2c_rate_card_id', 'b2b_rate_card_id']);
        });
    }
}
