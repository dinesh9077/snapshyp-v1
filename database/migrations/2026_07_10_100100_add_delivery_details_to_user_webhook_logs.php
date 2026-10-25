<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery logs must record the request payload and which retry attempt produced
 * the response, alongside the HTTP status / body already captured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_webhook_logs', function (Blueprint $table) {
            $table->longText('payload')->nullable()->after('url');
            $table->unsignedTinyInteger('attempt')->default(1)->after('status');
            $table->unsignedTinyInteger('max_attempts')->default(1)->after('attempt');
            $table->unsignedInteger('duration_ms')->nullable()->after('http_status');

            $table->index(['user_id', 'created_at'], 'user_webhook_logs_user_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_webhook_logs', function (Blueprint $table) {
            $table->dropIndex('user_webhook_logs_user_created_index');
            $table->dropColumn(['payload', 'attempt', 'max_attempts', 'duration_ms']);
        });
    }
};
