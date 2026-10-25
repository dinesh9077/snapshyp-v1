<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('overhead_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_company_id')->unique();
            $table->decimal('flat_mile_per_kg', 10, 2)->default(0);
            $table->decimal('flat_mile_min', 10, 2)->default(0);
            $table->decimal('rov_owner_percent', 5, 2)->default(0);
            $table->decimal('rov_owner_min', 10, 2)->default(0);
            $table->decimal('rov_carrier_percent', 5, 2)->default(0);
            $table->decimal('rov_carrier_min', 10, 2)->default(0);
            $table->decimal('green_per_kg', 10, 2)->default(0);
            $table->decimal('green_min', 10, 2)->default(0);
            $table->decimal('oda_per_kg', 10, 2)->default(0);
            $table->decimal('oda_min', 10, 2)->default(0);
            $table->decimal('cod_percent', 5, 2)->default(0);
            $table->decimal('cod_min', 10, 2)->default(0);
            $table->integer('divisor')->default(5000);
            $table->decimal('minimum_charge', 10, 2)->default(0);
            $table->decimal('gst_percent', 5, 2)->default(18);
            $table->timestamps();

            $table->foreign('shipping_company_id')
                ->references('id')
                ->on('shipping_companies')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('overhead_charges');
    }
};
