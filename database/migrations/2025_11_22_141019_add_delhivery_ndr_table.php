<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ndr_details', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('upl_id')->nullable();
            $table->string('action')->nullable();
            $table->string('status')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ndr_details', function (Blueprint $table) {
            //
        });
    }
};
