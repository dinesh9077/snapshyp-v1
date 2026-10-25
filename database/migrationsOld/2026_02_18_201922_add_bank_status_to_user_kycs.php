<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankStatusToUserKycs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kycs', 'bank_status')) {
                $table->integer('bank_status')->default(0)->after('ifsc_code');
            }
            if (!Schema::hasColumn('user_kycs', 'bank_role')) {
                $table->string('bank_role')->nullable()->after('bank_status');
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
            $table->dropColumn(['bank_status', 'bank_role']);
        });
    }
}
