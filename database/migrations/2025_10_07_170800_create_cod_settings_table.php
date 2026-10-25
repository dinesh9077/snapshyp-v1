<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cod_settings', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name'); // T+1 Day, T+2 Days, etc.
            $table->integer('days'); // 1, 2, 3, 4
            $table->decimal('percentage', 8, 2); // COD percentage
            $table->decimal('gst_percentage', 8, 2)->default(18.00); // GST percentage
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cod_settings');
    }
};
