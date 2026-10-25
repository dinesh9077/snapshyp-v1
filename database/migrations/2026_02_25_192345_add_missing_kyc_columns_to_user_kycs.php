<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingKycColumnsToUserKycs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kycs', 'pancard_image')) {
                $table->string('pancard_image')->nullable()->after('pancard');
            }
            if (!Schema::hasColumn('user_kycs', 'aadhar_back')) {
                $table->string('aadhar_back')->nullable()->after('aadhar_front');
            }
            if (!Schema::hasColumn('user_kycs', 'gst_image')) {
                $table->string('gst_image')->nullable()->after('gst');
            }
            if (!Schema::hasColumn('user_kycs', 'bank_passbook')) {
                $table->string('bank_passbook')->nullable()->after('bank_name');
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
            $table->dropColumn(['pancard_image', 'aadhar_back', 'gst_image', 'bank_passbook']);
        });
    }
}
