<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_company_pincodes', function (Blueprint $table) {
            $table->index(
                ['shipping_company_id', 'direction', 'status', 'pincode_id'],
                'shipping_company_pincode_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('shipping_company_pincodes', function (Blueprint $table) {
            $table->dropIndex('shipping_company_pincode_lookup_idx');
        });
    }
};
