<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankDetailsToUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kycs', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('user_kycs', 'account_holder_name')) {
                $table->string('account_holder_name')->nullable();
            }
            if (!Schema::hasColumn('user_kycs', 'account_number')) {
                $table->string('account_number')->nullable();
            }
            if (!Schema::hasColumn('user_kycs', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable();
            }
            if (!Schema::hasColumn('user_kycs', 'bank_status')) {
                $table->integer('bank_status')->default(0); // 0:pending, 1:approved, 2:rejected
            }
            if (!Schema::hasColumn('user_kycs', 'bank_role')) {
                $table->string('bank_role')->nullable();
            }
            if (!Schema::hasColumn('user_kycs', 'bank_reason')) { // ensure bank_reason exists too
                $table->string('bank_reason')->nullable();
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
             $table->dropColumn(['bank_name', 'account_holder_name', 'account_number', 'ifsc_code', 'bank_reason']);
        });
    }
}
