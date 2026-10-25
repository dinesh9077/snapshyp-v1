<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_companies', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); 
            $table->string('name')->nullable(); 
            $table->string('api_key')->nullable(); 
            $table->string('email')->nullable(); 
            $table->string('password')->nullable();  
            $table->text('url')->nullable();  
            $table->text('mode')->nullable();  
            $table->integer('status')->default(1);  
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
        Schema::dropIfExists('shipping_companies');
    }
}
