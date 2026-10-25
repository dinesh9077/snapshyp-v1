<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); 
            $table->integer('order_id'); 
            $table->integer('product_category')->nullable(); 
            $table->text('product_name')->nullable(); 
            $table->text('product_discription')->nullable(); 
            $table->decimal('amount',25,2)->default(0); 
            $table->integer('quantity')->default(0);  
			$table->softDeletes();
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
        Schema::dropIfExists('order_items');
    }
}
