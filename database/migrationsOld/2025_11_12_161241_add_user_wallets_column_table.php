<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserWalletsColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->string('pg_name')->nullable()->after('amount')->comment('Payment gateway name');
            $table->string('utr_no')->nullable()->after('pg_name')->comment('Unique Transaction Reference number');
            $table->enum('amount_type', ['credit', 'debit'])->nullable()->after('utr_no');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            //
        });
    }
}
