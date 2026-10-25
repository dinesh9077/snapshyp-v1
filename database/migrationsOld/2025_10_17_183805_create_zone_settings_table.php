<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoneSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zone_settings', function (Blueprint $table) {
            $table->id();
            $table->string('heading');                     // heading text
            $table->json('zone')->nullable();              // JSON array: e.g. ["Maharashtra","Karnataka"]
            $table->unsignedBigInteger('shipping_company_id')->nullable();
            $table->unsignedBigInteger('is_default')->default(0);
            $table->unsignedBigInteger('sort_order')->nullable();
            $table->string('slug')->nullable();
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
        Schema::dropIfExists('zone_settings');
    }
}
