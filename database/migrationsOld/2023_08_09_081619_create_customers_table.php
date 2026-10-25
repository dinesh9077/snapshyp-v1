<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id('id');
            $table->integer('user_id');
            $table->string('first_name');
            $table->string('last_name'); 
            $table->string('email')->unique();
            $table->string('mobile')->unique();   
            $table->boolean('status')->default(0)->nullable()->comment('0-In-Active|1-Active');  
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
        Schema::dropIfExists('customers');
    }
};