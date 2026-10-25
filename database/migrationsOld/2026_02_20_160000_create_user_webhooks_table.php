<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWebhooksTable extends Migration
{
    public function up()
    {
        Schema::create('user_webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('url');
            $table->json('events')->comment('Array of subscribed events: order.created, order.updated, order.deleted');
            $table->string('secret_key')->nullable()->comment('HMAC secret for signature verification');
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_webhooks');
    }
}
