<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_company_pincodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_company_id');
            $table->integer('pincode_id');
            $table->enum('direction', ['from', 'to']);
            $table->tinyInteger('status')->default(2)->comment('1 = Active, 2 = In-Active');
            $table->timestamps();

            $table->unique(
                ['shipping_company_id', 'pincode_id', 'direction'],
                'shipping_company_pincode_unique'
            );

            $table->foreign('shipping_company_id')
                ->references('id')
                ->on('shipping_companies')
                ->onDelete('cascade');

            $table->foreign('pincode_id')
                ->references('id')
                ->on('zipcodes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_company_pincodes');
    }
};
