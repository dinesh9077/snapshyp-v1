<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourierWarehousesColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            $table->string('contact_email')->nullable(); 
            $table->json('created')->nullable();
			$table->json('label_options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courier_warehouses', function (Blueprint $table) {
            //
        });
    }
}
