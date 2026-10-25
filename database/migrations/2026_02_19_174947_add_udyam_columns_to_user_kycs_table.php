<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUdyamColumnsToUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            $table->string('udyam_number')->nullable()->after('gst_reason');
            $table->string('udyam_enterprise_name')->nullable()->after('udyam_number');
            $table->tinyInteger('udyam_status')->default(0)->after('udyam_enterprise_name'); // 0: Pending, 1: Approved, 2: Rejected
            $table->text('udyam_text')->nullable()->after('udyam_status'); // Store full API response
            $table->string('udyam_role')->default('user')->after('udyam_text');
            $table->text('udyam_reason')->nullable()->after('udyam_role');
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
                'udyam_number',
                'udyam_enterprise_name',
                'udyam_status',
                'udyam_text',
                'udyam_role',
                'udyam_reason'
            ]);
        });
    }
}
