<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Live progress of a bulk "Ship Now" run: one batch per submit, one item per order
class CreateBulkShipBatchesTables extends Migration
{
    public function up()
    {
        Schema::create('bulk_ship_batches', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 20)->default('queued'); // queued, running, done
            $table->unsignedInteger('total')->default(0);
            $table->text('settings')->nullable(); // the submitted form, reused by "Retry failed"
            $table->timestamps();
        });

        Schema::create('bulk_ship_batch_items', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36)->index();
            $table->unsignedBigInteger('order_id');
            $table->string('status', 20)->default('queued'); // queued, processing, success, failed, skipped
            $table->string('awb')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
            $table->unique(['batch_id', 'order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_ship_batch_items');
        Schema::dropIfExists('bulk_ship_batches');
    }
}
