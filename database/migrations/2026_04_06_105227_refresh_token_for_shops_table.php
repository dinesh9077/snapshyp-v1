<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefreshTokenForShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
                $table->string('access_token_expires_at')->nullable()->after('shopify_token');
                $table->string('refresh_token')->nullable()->after('access_token_expires_at');
                $table->string('refresh_token_expires_at')->nullable()->after('refresh_token');
                $table->text('scopes')->nullable()->after('refresh_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            //
        });
    }
}
