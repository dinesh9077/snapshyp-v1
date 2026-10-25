<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_kycs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); 
            $table->string('pancard')->nullable(); 
            $table->string('pancard_image')->nullable();  
            $table->integer('pancard_status')->default(0);  
			$table->string('aadhar')->nullable(); 
            $table->string('aadhar_front')->nullable();  
            $table->string('aadhar_back')->nullable();  
            $table->integer('aadhar_status')->default(0); 
			$table->string('bank_passbook')->nullable(); 
			$table->string('bank_name')->nullable(); 
            $table->string('account_number')->nullable();  
            $table->string('ifsc_code')->nullable();  
            $table->integer('bank_status')->default(0);  
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
        Schema::dropIfExists('user_kycs');
    }
}
