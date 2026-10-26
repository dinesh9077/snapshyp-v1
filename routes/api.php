<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserKycController;
use App\Http\Controllers\Api\WeightController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\CodRemittanceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\NdrController;
use App\Http\Controllers\Api\RateCardController;
use App\Http\Controllers\Api\CodPlanController;
use App\Http\Controllers\SecureController;
/*
		|--------------------------------------------------------------------------
		| API Routes
		|--------------------------------------------------------------------------
		|
		| Here is where you can register API routes for your application. These
		| routes are loaded by the RouteServiceProvider within a group which
		| is assigned the "api" middleware group. Enjoy building your API!
		|
	*/

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:10,1');
Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('login',    [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:10,1');
Route::post('forgot-password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

Route::middleware('verify.shopify.session')->get('/secure-endpoint', [SecureController::class, 'verifySessionToken']);
Route::post('chatieons-webhoook', [WhatsappController::class, 'whatsappChatieonsWebhook']);

Route::get('/tracking', [App\Http\Controllers\PublicTrackingController::class, 'track']);

// authenticated routes
Route::middleware('auth:api')->group(function () {
	Route::post('logout', [AuthController::class, 'logout']);
	Route::get('user',   [AuthController::class, 'user']);

	Route::post('profile-update/{id?}', [CommonController::class, 'updateProfile']);
	Route::post('change-password', [CommonController::class, 'updatePassword']);

	Route::post('wallet/store', [CommonController::class, 'rechargeWalletStore']);
	Route::post('wallet/update', [CommonController::class, 'rechargeWalletRazorpay']);
	Route::post('wallet/failed', [CommonController::class, 'rechargeWalletFailed']);
	Route::post('wallet/cod-transfer', [CommonController::class, 'codToWalletTransfer']);

	Route::get('notification',   [CommonController::class, 'notification']);
	Route::get('notification/clear',   [CommonController::class, 'notificationClearAll']);
	Route::get('dashboard',   [CommonController::class, 'dashboard']);
	Route::get('banners', [CommonController::class, 'banners']);
	Route::get('shipping-companies', [CommonController::class, 'shippingCompanies']);
	Route::post('rate-calculator',   [CommonController::class, 'rateCalculator']);
	Route::post('pincode-serviceability', [CommonController::class, 'pincodeServiceabilityCheck']);
	Route::post('courier-wise-count', [CommonController::class, 'courierWiseCount']);

	Route::group(['prefix' => 'warehouse'], function () {
		Route::post('/list', [WarehouseController::class, 'index']);
		Route::post('/create', [WarehouseController::class, 'storeWarehouse']);
		Route::post('/update/{id}', [WarehouseController::class, 'updateWarehouse']);
	});

    Route::group(['prefix' => 'pickup-request'], function () {
        Route::get('/', [WarehouseController::class, 'pickupRequestList']);
        Route::post('/create', [WarehouseController::class, 'createPickupRequest']);
    });

	Route::group(['prefix' => 'customer'], function () {
		Route::post('/list', [WarehouseController::class, 'customerList']);
		Route::post('/create', [WarehouseController::class, 'storeCustomer']);
		Route::post('/create-address', [WarehouseController::class, 'storeCustomerAddress']);
		Route::post('/update/{id}', [WarehouseController::class, 'updateCustomer']);
		Route::post('/delete/{id}', [WarehouseController::class, 'deleteCustomer']);
	});

	Route::group(['prefix' => 'order'], function () {
		Route::post('/list', [OrderController::class, 'index']);
		Route::get('/filter', [OrderController::class, 'filterList']);

		Route::post('search-by-awb',   [OrderController::class, 'searchByAwb']);

		Route::get('/details/{id}', [OrderController::class, 'orderDetails']);

		Route::post('/create', [OrderController::class, 'orderStore']);
		Route::get('/edit/{id}', [OrderController::class, 'orderEdit']);
		Route::post('/update/{id}', [OrderController::class, 'orderUpdate']);
		Route::post('/cancel/{id}', [OrderController::class, 'orderCancel']);
		Route::post('/shipment-cancel/{id}', [OrderController::class, 'orderCancelApi']);

		Route::post('/shipment/charge/{orderId}', [OrderController::class, 'orderShipCharge']);
		Route::post('/ship/now', [OrderController::class, 'orderShipNow']);

		Route::post('/tracking-history/{id}', [OrderController::class, 'orderTrackingHistory']);

		Route::get('/download-label/{id}', [OrderController::class, 'orderLableDownload']);
		Route::post('/download/all-lable', [OrderController::class, 'alllabeldownload']);

		Route::post('/bulk-store', [OrderController::class, 'orderBulkStore']);
		Route::get('/download-bulk-sample', [OrderController::class, 'downloadBulkSample']);
	});

    Route::group(['prefix' => 'ndr'], function () {
		Route::post('/list', [NdrController::class, 'index']);
		Route::post('/raise-request', [NdrController::class, 'raiserequest']);
	});


    Route::group(['prefix' => 'rate-card'], function () {
		Route::post('/list', [RateCardController::class, 'rateCard']);
		Route::get('/price-list-pdf', [CommonController::class, 'downloadPriceListPdf']);
	});


    // Remittance Reports (Matching Web Logic)
    Route::get('user/cod-remittance/{userId}', [CodRemittanceController::class, 'userRemittance']);

    Route::group(['prefix' => 'cod-remmitance'], function () {
        Route::post('/list', [CodRemittanceController::class, 'list']);
        Route::get('/detail/{id}', [CodRemittanceController::class, 'show']);
        Route::post('/orders/{id}', [CodRemittanceController::class, 'remittanceOrders']);
        Route::get('/download-excel/{id}', [CodRemittanceController::class, 'downloadExcel']);

        // Also add correct spelling alias if needed, but keeping existing prefix for compatibility
    });

    Route::group(['prefix' => 'cod-payout'], function () {
        Route::post('/list', [CodRemittanceController::class, 'usersList']);
        Route::post('/store', [CodRemittanceController::class, 'processPayment']);
    });

	//Weight Management
	Route::group(['prefix' => 'weight'], function () {
		Route::post('/shipping-weight', [CommonController::class, 'shippingWeight']);
		Route::post('/descripencies/list', [WeightController::class, 'index']);
		Route::get('/descripencies/remark/{id}', [WeightController::class, 'weightRemark']);
		Route::post('/descripencies/remark/store', [WeightController::class, 'remarkStore']);
		Route::post('/descripencies/accepted/{id}', [WeightController::class, 'weightAccepted']);
	});

	//Ticket
	Route::group(['prefix' => 'ticket'], function () {
		Route::post('/list', [TicketController::class, 'index']);
		Route::post('/store', [TicketController::class, 'ticketStore']);
		Route::get('/view-ticket/{id}', [TicketController::class, 'ticketView']);
		Route::post('/remark/store', [TicketController::class, 'remarkStore']);
	});

	Route::prefix('report')->name('report.')->group(function () {
		Route::post('/order/list', [ReportController::class, 'reportOrderList']);
		Route::post('/export-orders', [ReportController::class, 'reportOrderExport']);

		Route::post('/user-wise/list', [ReportController::class, 'userWiseReportList']);

		Route::post('/passbook-user/list', [ReportController::class, 'passbookUserList']);
		Route::post('/passbook/list', [ReportController::class, 'passbookReportList']);

		Route::post('/shipping-charge/list', [ReportController::class, 'shippingChargeList']);

		Route::post('billing-invoice/list', [ReportController::class, 'billingInvoiceList']);
		Route::get('billing-invoice/pdf/{id}', [ReportController::class, 'billingInvoicePdf']);
		Route::get('billing-invoice/excel/{id}', [ReportController::class, 'billingInvoiceExcel']);

		Route::post('/recharge/list', [ReportController::class, 'rechargeList']);
	});

    // Settings
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/label-settings', [SettingController::class, 'labelSettings']);
        Route::post('/label-settings/update', [SettingController::class, 'updateLabelSettings']);
    });

    // KYC Routes
    Route::group(['prefix' => 'user-kyc'], function () {
        Route::get('/details', [UserKycController::class, 'details']);
        Route::post('/pancard/update', [UserKycController::class, 'kycUserPancardUpdate']);
        Route::post('/aadhar/send-otp', [UserKycController::class, 'kycUserAadharOtp']);
        Route::post('/aadhar/update', [UserKycController::class, 'kycUserAadharUpdate']);
        Route::post('/gst/send-otp', [UserKycController::class, 'kycUserGSTOtp']);
        Route::post('/gst/update', [UserKycController::class, 'kycUserGSTUpdate']);
        Route::post('/udyam/update', [UserKycController::class, 'kycUserUdyamUpdate']);
        Route::post('/bank/penny-drop', [UserKycController::class, 'kycUserBankPennyDropUpdate']);
    });

    // COD Plan Routes
    Route::group(['prefix' => 'cod-plans'], function () {
        Route::get('/', [CodPlanController::class, 'index']);
        Route::post('/activate', [CodPlanController::class, 'activate']);
        Route::post('/deactivate', [CodPlanController::class, 'deactivate']);
        Route::get('/current', [CodPlanController::class, 'getCurrentPlan']);
    });
});
