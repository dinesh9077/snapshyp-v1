<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $exists = DB::table('roles')->where('name', 'master_admin')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'user_id' => 1,
                'name' => 'master_admin',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->where('name', 'master_admin')->delete();
    }
};

