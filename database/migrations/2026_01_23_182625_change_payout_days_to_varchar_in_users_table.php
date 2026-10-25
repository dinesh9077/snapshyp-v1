<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangePayoutDaysToVarcharInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Use raw SQL to change column type from INT to VARCHAR
        DB::statement('ALTER TABLE users MODIFY COLUMN payout_days VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert payout_days back to integer
        DB::statement('ALTER TABLE users MODIFY COLUMN payout_days INT NULL');
    }
}
