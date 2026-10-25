<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiService\OrderServiceController;

/*
|--------------------------------------------------------------------------
| API Service Routes
|--------------------------------------------------------------------------
|
| Here are the API service routes for external integrations.
| These routes are separate from the main API routes.
|
*/

Route::prefix('api-service')->middleware('checkApiToken')->group(function () {
    // Order Service Routes — full order lifecycle
    Route::prefix('order')->group(function () {
        Route::get('/warehouse/list', [OrderServiceController::class, 'warehouseList']);

        // 1. Store new order
        Route::post('/store', [OrderServiceController::class, 'store']);

        // 1b. Update an order — only while its status is still "New"
        Route::post('/update/{order_id}', [OrderServiceController::class, 'update']);

        // 2. Courier list + serviceability / rate check for an order
        Route::get('/courier-list/{order_id}', [OrderServiceController::class, 'courierList']);

        // 3. Ship the order with the courier chosen from /courier-list
        Route::post('/ship', [OrderServiceController::class, 'ship']);

        // 4. Get order / shipment details
        Route::get('/details/{order_id}', [OrderServiceController::class, 'details']);

        // 5. Current order status (no courier API call)
        Route::get('/status/{order_id}', [OrderServiceController::class, 'status']);

        // 6. Track order by AWB or Order ID (live courier scans)
        Route::post('/tracking', [OrderServiceController::class, 'tracking']);

        // 7. Download the shipping label as PDF
        Route::get('/label/{order_id}', [OrderServiceController::class, 'label']);

        // 8. Cancel the order / shipment
        Route::post('/cancel/{order_id}', [OrderServiceController::class, 'cancel']);
    });
});
