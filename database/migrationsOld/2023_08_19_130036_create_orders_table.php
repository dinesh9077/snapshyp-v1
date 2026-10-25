<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('order_prefix');
            $table->string('order_type');
            $table->integer('vendor_id');
            $table->integer('vendor_address_id');
            $table->integer('customer_id');
            $table->integer('customer_address_id');
            $table->integer('shipping_company_id')->nullable();
            $table->string('shipping_mode');
            $table->decimal('shipping_charge',25,2)->default(0);
            $table->decimal('total_amount',25,2)->default(0);
            $table->string('status_courier')->nullable();
            $table->text('reason_cancel')->nullable(); 
            $table->date('order_date');
            $table->date('order_cancel_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->integer('status')->default(1);
            $table->integer('is_online')->default(0);
			$table->float('weight')->default(0); 
            $table->float('length')->default(0); 
            $table->float('width')->default(0); 
            $table->float('height')->default(0); 
            $table->integer('remittance_reference_id')->nullable()->index(); 
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
        Schema::dropIfExists('orders');
    }
}
