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
        Schema::create('rate_card_matrices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rate_card_id');
            $table->unsignedBigInteger('shipping_company_id');
            $table->unsignedBigInteger('from_zone_id');
            $table->unsignedBigInteger('to_zone_id');
            $table->decimal('rate_per_kg', 10, 2)->default(0);
            $table->string('status')->default(1);
            $table->timestamps();

            $table->foreign('rate_card_id')->references('id')->on('rate_cards')->onDelete('cascade');
            $table->foreign('shipping_company_id')->references('id')->on('shipping_companies')->onDelete('cascade');
            $table->foreign('from_zone_id')->references('id')->on('zone_settings')->onDelete('cascade');
            $table->foreign('to_zone_id')->references('id')->on('zone_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_card_matrices');
    }
};
