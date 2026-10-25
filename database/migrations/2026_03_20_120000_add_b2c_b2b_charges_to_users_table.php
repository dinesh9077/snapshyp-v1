<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddB2cB2bChargesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('b2c_charge_type')->nullable()->after('charge');
            $table->decimal('b2c_charge', 10, 2)->nullable()->after('b2c_charge_type');
            $table->unsignedTinyInteger('b2b_charge_type')->nullable()->after('b2c_charge');
            $table->decimal('b2b_charge', 10, 2)->nullable()->after('b2b_charge_type');
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
            $table->dropColumn(['b2c_charge_type', 'b2c_charge', 'b2b_charge_type', 'b2b_charge']);
        });
    }
}
