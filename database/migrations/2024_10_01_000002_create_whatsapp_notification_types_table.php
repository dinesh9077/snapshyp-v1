<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappNotificationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // order_confirmation, shipment_booked, etc.
            $table->string('name'); // Display name
            $table->text('message_template')->nullable(); // WhatsApp message template
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_free')->default(0);
            $table->boolean('is_enabled')->default(0);
            $table->boolean('is_recommended')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_notification_types');
    }
}
