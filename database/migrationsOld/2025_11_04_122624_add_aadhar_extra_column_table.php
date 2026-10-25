<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAadharExtraColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            $table->string('pancard_category')->nullable();   
            $table->text('aadhar_address')->nullable();
            $table->date('aadhar_dob')->nullable();
            $table->string('aadhar_gender', 10)->nullable();
            $table->string('aadhar_zip', 10)->nullable();
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
            $table->dropColumn(['aadhar_address', 'aadhar_dob', 'aadhar_gender', 'aadhar_zip']);
        });
    }
}
