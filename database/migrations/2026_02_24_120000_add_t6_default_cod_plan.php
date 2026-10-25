<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddT6DefaultCodPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Keep existing plans active, set T+4 as default, and remove T+6 completely.
        DB::table('cod_settings')->update(['is_default' => 0]);

        DB::table('cod_settings')
            ->where('days', 4)
            ->update([
                'plan_name' => 'T+4 Days (Default)',
                'is_active' => 1,
                'is_default' => 1,
                'updated_at' => now(),
            ]);

        DB::table('cod_settings')->where('days', 6)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('cod_settings')->update(['is_default' => 0]);

        DB::table('cod_settings')
            ->where('days', 4)
            ->update([
                'plan_name' => 'T+4 Days',
                'is_active' => 1,
                'is_default' => 0,
                'updated_at' => now(),
            ]);

        DB::table('cod_settings')
            ->updateOrInsert(
                ['days' => 6],
                [
                'plan_name' => 'T+6 Days (Default)',
                'days' => 6,
                'percentage' => 0,
                'gst_percentage' => 18.00,
                'description' => 'The applicable transaction charges is 0.00% of the COD amount (Exclusive of GST)',
                'is_active' => 1,
                'is_default' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                ]
            );
    }
}
