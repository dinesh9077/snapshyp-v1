<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGstFieldsToUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            $table->string('gst', 15)->nullable();
            $table->string('gst_business_name')->nullable();
            $table->text('gst_address')->nullable();
            $table->string('gstin_status')->nullable();
            $table->string('gst_mobile_no', 15)->nullable();
            $table->string('gst_email')->nullable();
            $table->integer('gst_status')->default(0);
            $table->text('gst_reason')->nullable();
            $table->string('gst_role')->nullable();
            $table->json('gst_text')->nullable();
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
            $table->dropColumn([
                'gst',
                'gst_business_name',
                'gst_address',
                'gstin_status',
                'gst_mobile_no',
                'gst_email',
                'gst_status',
                'gst_reason'
            ]);
        });
    }
}
