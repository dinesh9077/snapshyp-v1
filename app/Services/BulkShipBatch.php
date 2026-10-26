<?php

namespace App\Services;

use App\Jobs\ProcessBulkShipment;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tracks a bulk "Ship Now" run per order so the order page can show live progress
 * (queued -> processing -> success / failed). Counters are derived from the items.
 */
class BulkShipBatch
{
    /**
     * Queue a tracked bulk run. Only orders in $ownedOrders (the caller's ownership scope) that are
     * not shipped and not already queued are booked; the rest are listed as skipped with the reason.
     * Returns the JSON body for the caller: status, msg and, on success, batch_id / queued / skipped.
     */
    public static function queue(array $orderIds, array $settings, $user, $ownedOrders, array $jobData = []): array
    {
        $orders = $ownedOrders->get(['id', 'awb_number', 'queue'])->keyBy('id');
        $eligibleIds = [];
        $skipped = [];
        foreach (array_unique(array_map('intval', array_filter($orderIds))) as $orderId) {
            $order = $orders->get($orderId);
            if (!$order) {
                $skipped[$orderId] = 'Order not found.';
            } elseif (!empty($order->awb_number)) {
                $skipped[$orderId] = 'Already shipped (AWB ' . $order->awb_number . ').';
            } elseif ((int) $order->queue === 1) {
                $skipped[$orderId] = 'Already being processed.';
            } else {
                $eligibleIds[] = $orderId;
            }
        }

        if (empty($eligibleIds)) {
            return [
                'status' => 'error',
                'msg' => 'Nothing to ship: the selected orders are already shipped or being processed.',
            ];
        }

        try {
            DB::beginTransaction();

            $batchId = self::create($user->id, $eligibleIds, $skipped, $settings);
            ProcessBulkShipment::dispatch($eligibleIds, $jobData + $settings, $user, $batchId);
            Order::whereIn('id', $eligibleIds)->update(['queue' => 1, 'shipment_failure_reason' => null]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'msg' => 'Something went wrong while queuing the bulk shipment. Please try again.',
            ];
        }

        return [
            'status' => 'success',
            'msg' => 'Bulk shipment queued successfully. Processing will continue in the background.',
            'batch_id' => $batchId,
            'queued' => count($eligibleIds),
            'skipped' => count($skipped),
        ];
    }

    public static function create(int $userId, array $orderIds, array $skipped = [], array $settings = []): string
    {
        $id = (string) Str::uuid();
        $now = now();

        DB::table('bulk_ship_batches')->insert([
            'id' => $id,
            'user_id' => $userId,
            'status' => empty($orderIds) ? 'done' : 'queued',
            'total' => count($orderIds) + count($skipped),
            'settings' => json_encode($settings),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $items = [];
        foreach ($orderIds as $orderId) {
            $items[] = ['batch_id' => $id, 'order_id' => (int) $orderId, 'status' => 'queued', 'error' => null, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach ($skipped as $orderId => $reason) {
            $items[] = ['batch_id' => $id, 'order_id' => (int) $orderId, 'status' => 'skipped', 'error' => $reason, 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($items, 500) as $chunk) {
            DB::table('bulk_ship_batch_items')->insert($chunk);
        }

        return $id;
    }

    public static function processing(?string $batchId, int $orderId): void
    {
        if (!$batchId) {
            return;
        }
        DB::table('bulk_ship_batches')->where('id', $batchId)->where('status', 'queued')
            ->update(['status' => 'running', 'updated_at' => now()]);
        DB::table('bulk_ship_batch_items')->where('batch_id', $batchId)->where('order_id', $orderId)
            ->update(['status' => 'processing', 'started_at' => now(), 'updated_at' => now()]);
    }

    public static function result(?string $batchId, int $orderId, string $status, ?string $awb = null, ?string $error = null): void
    {
        if (!$batchId) {
            return;
        }
        DB::table('bulk_ship_batch_items')->where('batch_id', $batchId)->where('order_id', $orderId)
            ->update([
                'status' => $status,
                'awb' => $awb,
                'error' => $error !== null ? Str::limit($error, 480) : null,
                'updated_at' => now(),
            ]);
    }

    // Close the run; anything never reached (crash, missing courier) is marked failed
    public static function finish(?string $batchId, string $leftoverReason = 'Bulk shipment stopped before this order.'): void
    {
        if (!$batchId) {
            return;
        }
        DB::table('bulk_ship_batch_items')->where('batch_id', $batchId)->whereIn('status', ['queued', 'processing'])
            ->update(['status' => 'failed', 'error' => Str::limit($leftoverReason, 480), 'updated_at' => now()]);
        DB::table('bulk_ship_batches')->where('id', $batchId)->update(['status' => 'done', 'updated_at' => now()]);
    }

    // Progress for the batch owner only; null when it is not theirs or no longer exists
    public static function status(string $batchId, int $userId): ?array
    {
        $batch = DB::table('bulk_ship_batches')->where('id', $batchId)->where('user_id', $userId)->first();
        if (!$batch) {
            return null;
        }

        $items = DB::table('bulk_ship_batch_items as i')
            ->leftJoin('orders as o', 'o.id', '=', 'i.order_id')
            ->leftJoin('shipping_companies as s', 's.id', '=', 'o.shipping_company_id')
            ->where('i.batch_id', $batchId)
            ->orderBy('i.id')
            ->get(['i.order_id', 'i.status', 'i.awb', 'i.error', 's.name as courier']);

        $count = fn ($status) => $items->where('status', $status)->count();
        $started = $items->whereNotIn('status', ['queued', 'skipped'])->count();
        $waitSeconds = Carbon::parse($batch->created_at)->diffInSeconds(now());

        return [
            'status' => $batch->status,
            'total' => $items->where('status', '!=', 'skipped')->count(),
            'success' => $count('success'),
            'failed' => $count('failed'),
            'skipped' => $count('skipped'),
            'started' => $started,
            // Jobs queued but none picked up after a while: the queue worker / cron is likely not running
            'stalled' => $batch->status !== 'done' && $started === 0 && $waitSeconds > 75,
            'items' => $items->map(function ($item) {
                return [
                    'order_id' => (int) $item->order_id,
                    'status' => $item->status,
                    'awb' => $item->awb,
                    'courier' => $item->status === 'success' ? $item->courier : null,
                    'error' => $item->error,
                ];
            })->values(),
        ];
    }

    public static function settings(string $batchId, int $userId): ?array
    {
        $batch = DB::table('bulk_ship_batches')->where('id', $batchId)->where('user_id', $userId)->first();
        return $batch ? (json_decode($batch->settings ?? '[]', true) ?: []) : null;
    }

    public static function failedOrderIds(string $batchId): array
    {
        return DB::table('bulk_ship_batch_items')->where('batch_id', $batchId)->where('status', 'failed')
            ->pluck('order_id')->map(fn ($id) => (int) $id)->all();
    }
}
