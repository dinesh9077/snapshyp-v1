<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NewColumnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('shopify_grandfathered')->default(0);
            $table->integer('shopify_freemium')->default(0);
            $table->integer('plan_id')->nullable();
            $table->integer('cod_plan_id')->nullable();
            $table->integer('cod_plan_active')->default(0);
            $table->string('shopify_namespace')->nullable(); 
            $table->date('password_updated_at')->nullable();
            $table->string('shop')->nullable();
            $table->string('api_token')->nullable();
            $table->string('shopify_sync_mode')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
