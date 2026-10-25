<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id(); 
            $table->string('shopify_domain')->unique();
            $table->text('shopify_token')->nullable(); 
            $table->string('shop_name')->nullable();
            $table->string('shop_email')->nullable();
            $table->string('shop_owner')->nullable(); 
            $table->integer('is_active')->default(1);
            $table->integer('is_charge')->default(0); 
            $table->text('host')->nullable(); 
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
        Schema::dropIfExists('shops');
    }
}
