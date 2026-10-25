<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A merchant may register exactly one webhook URL.
 *
 * Accounts that already have several rows keep a single one — the active row with
 * the most recent activity — and the rest are removed. NOTE: `user_webhook_logs`
 * has an ON DELETE CASCADE foreign key on `webhook_id`, so the delivery history of
 * the discarded webhooks is removed with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicateUserIds = DB::table('user_webhooks')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('user_webhooks')
                ->where('user_id', $userId)
                ->orderByDesc('status')      // prefer an enabled webhook
                ->orderByDesc('updated_at')  // then the most recently touched
                ->orderByDesc('id')
                ->value('id');

            DB::table('user_webhooks')
                ->where('user_id', $userId)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('user_webhooks', function (Blueprint $table) {
            $table->unique('user_id', 'user_webhooks_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_webhooks', function (Blueprint $table) {
            $table->dropUnique('user_webhooks_user_id_unique');
        });
    }
};
