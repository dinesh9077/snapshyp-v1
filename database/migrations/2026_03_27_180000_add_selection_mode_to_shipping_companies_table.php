<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_companies', 'selection_mode')) {
                $table->string('selection_mode', 30)->default('carriers')->after('mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_companies', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_companies', 'selection_mode')) {
                $table->dropColumn('selection_mode');
            }
        });
    }
};
