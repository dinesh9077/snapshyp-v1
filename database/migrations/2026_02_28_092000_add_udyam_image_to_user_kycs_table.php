<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUdyamImageToUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_kycs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_kycs', 'udyam_image')) {
                $table->string('udyam_image')->nullable()->after('udyam_number');
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
            $table->dropColumn('udyam_image');
        });
    }
}
