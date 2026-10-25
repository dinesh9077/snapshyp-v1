<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AwbNumberPhoneNdrRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ndr_requests', function (Blueprint $table) {
            $table->text('address_1')->nullable();
            $table->string('phone')->nullable();
            $table->string('awb_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ndr_requests', function (Blueprint $table) {
            //
        });
    }
}
