<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            
            if (Schema::hasColumn('user_kycs', 'gst_status')) {
                $table->dropColumn('gst_status');
            }
            if (Schema::hasColumn('user_kycs', 'bank_status')) {
                $table->dropColumn('bank_status');
            } 
            if (Schema::hasColumn('user_kycs', 'pancard_image')) {
                $table->dropColumn('pancard_image');
            }

            if (Schema::hasColumn('user_kycs', 'aadhar_back')) {
                $table->dropColumn('aadhar_back');
            }
            
            if (Schema::hasColumn('user_kycs', 'gst')) {
                $table->dropColumn('gst');
            }
            
            if (Schema::hasColumn('user_kycs', 'gst_image')) {
                $table->dropColumn('gst_image');
            }
            
            if (Schema::hasColumn('user_kycs', 'bank_passbook')) {
                $table->dropColumn('bank_passbook');
            }
            
            // if (Schema::hasColumn('user_kycs', 'bank_name')) {
            //     $table->dropColumn('bank_name');
            // }
            
            if (Schema::hasColumn('user_kycs', 'account_holder_name')) {
                $table->dropColumn('account_holder_name');
            }
            
            if (Schema::hasColumn('user_kycs', 'account_number')) {
                $table->dropColumn('account_number');
            }
            
            if (Schema::hasColumn('user_kycs', 'ifsc_code')) {
                $table->dropColumn('ifsc_code');
            }
            
            if (Schema::hasColumn('user_kycs', 'bank_text')) {
                $table->dropColumn('bank_text');
            }
            
            if (Schema::hasColumn('user_kycs', 'gst_text')) {
                $table->dropColumn('gst_text');
            }

            $table->string('pan_role')->nullable();
            $table->string('aadhar_role')->nullable();
            $table->text('pan_reason')->nullable();
            $table->text('aadhar_reason')->nullable();
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
            //
        });
    }
}
