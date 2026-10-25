<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourierCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courier_commissions', function (Blueprint $table) {
            $table->id();
            $table->integer('shipping_company')->index();
            $table->integer('courier_id')->nullable()->index();
            $table->string('courier_name')->nullable();
            $table->enum('type', ['fix', 'percentage']);
            $table->decimal('value', 8, 2);
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
        Schema::dropIfExists('courier_commissions');
    }
}
