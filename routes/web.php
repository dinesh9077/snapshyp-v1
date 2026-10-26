
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\GdprWebhookController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\StatusMappingController;
use App\Http\Controllers\NdrController;
/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register web routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | contains the "web" middleware group. Now create something great!
 |
 */

//   Local:  http://localhost/shipfigo/public/webhook/receive
//   Live:   https://shipfigo.sharenow.live/webhook/receive
Route::post('/webhook/receive', [\App\Http\Controllers\WebhookReceiverController::class , 'handle'])
	->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Public Rate Card Download (for mobile app link sharing)
Route::get('/download-rate-card-pdf/{token}', [App\Http\Controllers\PublicDownloadController::class, 'downloadPriceListPdf'])->name('public.rate-card.download');

Route::get('/', function () {
	return redirect()->route('login');
});

//Woocommerce
Route::post('/woocommerce/order/create', [App\Http\Controllers\WoocommerceController::class, 'orderCreated']);
Route::post('/woocommerce/order/cancelled', [App\Http\Controllers\WoocommerceController::class, 'orderCancelled']);

//Status Update Webhook
Route::match (
['post'],
	'/delhivery/webhook',
[WebhookController::class , 'delhiveryWebhook']
)->middleware('courier.webhook');

// UrbaneBolt Status Update Webhook
Route::match (
['post'],
	'/urbanebolt/webhook',
[WebhookController::class , 'urbaneboltWebhook']
)->middleware('courier.webhook');

// Shipmozo Status Update Webhook
Route::match (
['post'],
	'/shipmozo/webhook',
[WebhookController::class , 'shipmozoWebhook']
)->middleware('courier.webhook');

// Shadowfax Status Update Webhook
Route::match (
['post'],
	'/shadowfax/webhook',
[WebhookController::class , 'shadowfaxWebhook']
)->middleware('courier.webhook');

// Shopify App Landing Route (after installation completes)
Route::get('/shopify', [ShopifyController::class , 'home'])
	->name('shopify.home')
	->middleware('shopifyCsp', 'shopify.embedded.auth');

// Install route (first OAuth initiation)
Route::get('/install', [ShopifyController::class , 'install'])
	->name('shopify.install')
	->middleware('shopifyCsp');

// OAuth callback after merchant approves installation
Route::get('/auth/callback', [ShopifyController::class , 'callback'])
	->name('shopify.callback')
	->middleware('shopifyCsp');

// Shopify Billing callback (after approval of plan)
Route::get('/shopify-billing/callback', [ShopifyController::class , 'billingCallback'])
	->name('shopify.billing.confirm')
	->middleware('shopifyCsp');

// Embedded Authentication Routes
Route::get('/shopify/login', [ShopifyController::class , 'showLogin'])
	->name('shopify.embedded.login')
	->middleware(['shopifyCsp']);

Route::post('/shopify/login', [ShopifyController::class , 'postLogin'])
	->name('shopify.embedded.login.post')
	->middleware(['shopifyCsp', 'throttle:10,1']);

Route::get('/shopify/register', [ShopifyController::class , 'showRegister'])
	->name('shopify.embedded.register')
	->middleware(['shopifyCsp']);

Route::post('/shopify/register', [ShopifyController::class , 'postRegister'])
	->name('shopify.embedded.register.post')
	->middleware(['shopifyCsp', 'throttle:10,1']);

// Main embedded app home (loaded via AppBridge)
Route::get('/apps/home', [ShopifyController::class , 'index'])
	->name('embedded.home')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::get('/shopify-order-create', [ShopifyController::class , 'shopifyOrderCreate'])
	->name('shopify.order.create')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

// Shopify Orders List (for Ship Now button)
Route::get('/shopify/orders', [ShopifyController::class , 'shopifyOrdersList'])
	->name('shopify.orders.list.page')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::get('/shopify/orders/list', [ShopifyController::class , 'getShopifyOrders'])
	->name('shopify.orders.list')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::get('/shopify/orders/get-order-id', [ShopifyController::class , 'getOrderIdFromShopify'])
	->name('shopify.orders.get-order-id')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

// Webhooks (authenticated via shared secret)
Route::post('/webhooks/customers/data_request', [ShopifyController::class , 'customersDataRequest'])
	->middleware(['shopifyCsp']);

Route::post('/webhooks/customers/redact', [ShopifyController::class , 'customersRedact'])
	->middleware(['shopifyCsp']);

Route::post('/webhooks/shop/redact', [ShopifyController::class , 'shopRedact'])
	->middleware(['shopifyCsp']);

Route::post('/webhooks/gdpr', [GdprWebhookController::class , 'handle'])->middleware(['shopifyCsp']);

Route::post('/webhook/app-uninstalled', [ShopifyController::class , 'shopUninstalled'])
	->middleware(['shopifyCsp']);

Route::post('/shopify-webhook/orders-create', [ShopifyController::class , 'orderCreate'])
	->middleware('shopifyCsp');


Route::post('/shopify-webhook/orders-cancelled', [ShopifyController::class , 'orderCancelled'])
	->middleware('shopifyCsp');

Route::post('/shopify-webhook/orders-updated', [ShopifyController::class , 'orderUpdated'])
	->middleware('shopifyCsp');

Route::get('/shopify/orders/action', [ShopifyController::class , 'orderManualFulfill'])
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::post('/shopify/bulk-shipment/now', [ShopifyController::class , 'orderShopifyBulkShipNow'])
	->name('shopify.bulk-shipment.now')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::post('/api/update-sync-setting', [ShopifyController::class , 'updateSync'])
	->name('shopify.sync.update')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::get('/api/get-sync-mode', [ShopifyController::class , 'getSyncMode'])
	->name('shopify.sync.get')
	->middleware(['shopifyCsp', 'shopify.embedded.auth']);

Route::get('edit-address', [ShopifyController::class , 'editAddress'])->name('address.edit');
Route::post('/update-address', [ShopifyController::class , 'updateAddress'])->name('address.update');

Auth::routes();

// Forgot Password - OTP-based flow
Route::get('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'forgotPassword'])->name('forgot-password');
Route::post('forgot-password', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'forgotPasswordSend'])->name('forgot-password.post')->middleware('throttle:10,1');
Route::post('forgot-password/send-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'sendOtp'])->name('forgot-password.send-otp')->middleware('throttle:10,1');
Route::get('forgot-password/verify-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'showVerifyOtp'])->name('forgot-password.verify-otp');
Route::post('forgot-password/verify-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'verifyOtp'])->name('forgot-password.verify-otp.post')->middleware('throttle:10,1');
Route::get('forgot-password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'showResetPassword'])->name('forgot-password.reset');
Route::post('forgot-password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'resetPassword'])->name('forgot-password.reset.post')->middleware('throttle:10,1');
Route::post('forgot-password/resend-otp', [App\Http\Controllers\Auth\ForgotPasswordController::class , 'resendOtp'])->name('forgot-password.resend-otp')->middleware('throttle:10,1');

Route::post('post-login', [App\Http\Controllers\Auth\LoginController::class , 'postLogin'])->name('login.post')->middleware('throttle:10,1');

Route::post('register', [App\Http\Controllers\Auth\RegisterController::class , 'postRegister'])->name('register.store')->middleware('throttle:10,1');
Route::post('verfiy-otp', [App\Http\Controllers\Auth\RegisterController::class , 'verifyOTP'])->name('register.verify-otp')->middleware('throttle:10,1');
Route::post('resend-otp', [App\Http\Controllers\Auth\RegisterController::class , 'resend'])->name('register.resend-otp')->middleware('throttle:10,1');

Route::get('/home', [App\Http\Controllers\HomeController::class , 'index'])->name('home');
Route::post('/courier-wise-count-ajax', [App\Http\Controllers\HomeController::class , 'courierWiseTotalAjax'])->name('home.courier-wise-count-ajax');
Route::post('/home/chart-data', [App\Http\Controllers\HomeController::class , 'getLogisticsChartData'])->name('home.chart-data');
Route::get('/notification', [App\Http\Controllers\HomeController::class , 'notification'])->name('notification');
Route::get('/notification/clear-all', [App\Http\Controllers\HomeController::class , 'notificationClearAll'])->name('notification.clear-all');
Route::get('/check-amount', [App\Http\Controllers\HomeController::class , 'checkAmount'])->name('check-amount');
Route::get('/lowbalance', [App\Http\Controllers\HomeController::class , 'lowbalance'])->name('lowbalance');
Route::get('/kycpending', [App\Http\Controllers\HomeController::class , 'kycpending'])->name('kycpending');


//Profile
Route::get('/profile', [App\Http\Controllers\SettingController::class , 'profile'])->name('profile');
Route::post('/update', [App\Http\Controllers\SettingController::class , 'updateProfile'])->name('profile.update');


//Change Password
Route::group(['prefix' => 'change-password'], function () {
	Route::get('/', [App\Http\Controllers\SettingController::class , 'changePassword'])->name('change-password');
	Route::post('/update', [App\Http\Controllers\SettingController::class , 'updatePassword'])->name('update.change-password');
});

//Setting
Route::group(['prefix' => 'setting'], function () {
	Route::get('/general', [App\Http\Controllers\SettingController::class , 'index'])->name('general-setting')->middleware('permission:general_setting.view');
	Route::post('/general/update', [App\Http\Controllers\SettingController::class , 'updateGeneral'])->name('setting.update')->middleware('permission:general_setting.view');
	Route::get('/lable-preferance', [App\Http\Controllers\SettingController::class , 'lablePreferance'])->name('lable.preferance');
	Route::post('/update-preferance', [App\Http\Controllers\SettingController::class , 'updateLablePref']);
	Route::get('/bank-details', [App\Http\Controllers\SettingController::class , 'bankDetails'])->name('setting.bank-details');
	Route::post('/bank-details', [App\Http\Controllers\SettingController::class , 'updateBankDetails'])->name('setting.bank-details.update');

	// New Label Settings Module
	Route::get('/label-settings', [App\Http\Controllers\SettingController::class , 'labelSettings'])->name('label.settings');
	Route::post('/label-settings/update', [App\Http\Controllers\SettingController::class , 'updateLabelSettings'])->name('label.settings.update');
});

// User COD Remittance - List all users
Route::get('/user/cod-remittance', [App\Http\Controllers\CodRemittanceController::class , 'usersList'])->name('user.cod-remittance.list');
Route::post('/user/cod-remittance/users-ajax', [App\Http\Controllers\CodRemittanceController::class , 'usersListAjax'])->name('user.cod-remittance.users-ajax');

// User COD Remittance - Individual user pages
Route::get('/user/cod-remittance/{userId}', [App\Http\Controllers\CodRemittanceController::class , 'userRemittance'])->name('user/cod-remittance');
Route::post('/user/cod-remittance/{userId}/ajax', [App\Http\Controllers\CodRemittanceController::class , 'userRemittanceAjax'])->name('user.cod-remittance.ajax');
Route::get('user/cod-remittance/{id}/view', [App\Http\Controllers\CodRemittanceController::class , 'show'])->name('cod-remittance.show');
Route::post('user/cod-remittance/{id}/orders-ajax', [App\Http\Controllers\CodRemittanceController::class , 'remittanceOrdersAjax'])->name('cod-remittance.orders-ajax');
Route::get('/user/order-detail/{orderId}', [App\Http\Controllers\CodRemittanceController::class , 'orderDetail'])->name('order.detail');
Route::get('/user/cod-remittance/{id}/export-excel', [App\Http\Controllers\CodRemittanceController::class , 'exportExcel'])->name('cod-remittance.export-excel');
Route::post('/cod-remittance/process-payment', [App\Http\Controllers\CodRemittanceController::class , 'processPayment'])->name('cod-remittance.process-payment');

// Razorpay Payment Routes
// Razorpay Payment Routes
Route::post('/recharge/wallet/create-razorpay-order', [App\Http\Controllers\RechargeController::class , 'createRazorpayOrder'])->name('recharge.wallet.create-razorpay-order');
Route::post('/recharge/wallet/verify-razorpay-payment', [App\Http\Controllers\RechargeController::class , 'verifyRazorpayPayment'])->name('recharge.wallet.verify-razorpay-payment');
Route::post('/recharge/wallet/payment-failed', [App\Http\Controllers\RechargeController::class , 'paymentFailed'])->name('recharge.wallet.payment-failed');

// Zoho Payment Routes (commented/disabled)
// Route::post('/recharge/wallet/create-zoho-payment-link', [App\Http\Controllers\RechargeController::class, 'createZohoPaymentLink'])->name('recharge.wallet.create-zoho-payment-link');
// Route::get('/zoho/payment/callback', [App\Http\Controllers\RechargeController::class, 'zohoPaymentCallback'])->name('zoho.payment.callback');
// Route::post('/zoho/payment/webhook', [App\Http\Controllers\RechargeController::class, 'zohoWebhook'])->name('zoho.payment.webhook');
// Route::get('/zoho/debug', [App\Http\Controllers\RechargeController::class, 'verifyZohoAuth']);
// Route::get('/zoho/get-token/{code}', [App\Http\Controllers\RechargeController::class, 'exchangeZohoCode']);

//Setting

Route::get('/integration/index', [App\Http\Controllers\SettingController::class , 'integrations'])->name('integrations.index');
Route::get('/integration/wordpress', [App\Http\Controllers\SettingController::class, 'wordpressIntegration'])->name('integrations.wordpress');
Route::group(['prefix' => 'api'], function () {
	Route::get('/developer', [App\Http\Controllers\SettingController::class , 'developerApiPage'])->name('api.developer');

	// User Webhook (exactly one per merchant — no {id} needed)
	Route::prefix('webhooks')->name('webhooks.')->group(function () {
		    Route::get('/', [App\Http\Controllers\UserWebhookController::class , 'index'])->name('index');
		    Route::get('/logs', [App\Http\Controllers\UserWebhookController::class , 'logs'])->name('logs');
		    Route::post('/store', [App\Http\Controllers\UserWebhookController::class , 'store'])->name('store');
		    Route::post('/update', [App\Http\Controllers\UserWebhookController::class , 'update'])->name('update');
		    Route::delete('/delete', [App\Http\Controllers\UserWebhookController::class , 'destroy'])->name('destroy');
		    Route::post('/toggle', [App\Http\Controllers\UserWebhookController::class , 'toggleStatus'])->name('toggle');
		    Route::post('/regenerate-secret', [App\Http\Controllers\UserWebhookController::class , 'regenerateSecret'])->name('regenerate-secret');
		    Route::post('/test', [App\Http\Controllers\UserWebhookController::class , 'test'])->name('test');
	    }
	    );
    });

//Customer
Route::group(['prefix' => 'customer'], function () {
	Route::get('/', [App\Http\Controllers\CustomerController::class , 'index'])->name('customer')->middleware('permission:client.view');
	Route::post('/ajax', [App\Http\Controllers\CustomerController::class , 'ajaxCustomer'])->name('customer.ajax');
	Route::get('/add', [App\Http\Controllers\CustomerController::class , 'addCustomer'])->name('customer.add');
	Route::post('/store', [App\Http\Controllers\CustomerController::class , 'storeCustomer'])->name('customer.store');
	Route::get('/delete/{id}', [App\Http\Controllers\CustomerController::class , 'deleteCustomer']);
	Route::get('/edit/{id}', [App\Http\Controllers\CustomerController::class , 'editCustomer']);
	Route::post('/update/{id}', [App\Http\Controllers\CustomerController::class , 'updateCustomer'])->name('customer.update');
});

//Vendor
Route::group(['prefix' => 'vendor'], function () {
	Route::get('/', [App\Http\Controllers\VendorController::class , 'index'])->name('vendor');
	Route::post('/ajax', [App\Http\Controllers\VendorController::class , 'ajaxVendor'])->name('vendor.ajax');
	Route::get('/add', [App\Http\Controllers\VendorController::class , 'addVendor'])->name('vendor.add');
	Route::post('/store', [App\Http\Controllers\VendorController::class , 'storeVendor'])->name('vendor.store');
	Route::get('/delete/{id}', [App\Http\Controllers\VendorController::class , 'deleteVendor']);
	Route::get('/edit/{id}', [App\Http\Controllers\VendorController::class , 'editVendor']);
	Route::post('/update/{id}', [App\Http\Controllers\VendorController::class , 'updateVendor'])->name('vendor.update');
});

//Warehouse
Route::group(['prefix' => 'warehouse'], function () {
	Route::get('/', [App\Http\Controllers\WarehouseController::class , 'index'])->name('warehouse.index')->middleware('permission:warehouse.view');
	Route::post('/ajax', [App\Http\Controllers\WarehouseController::class , 'ajaxWarehouse'])->name('warehouse.ajax');
	Route::get('/add', [App\Http\Controllers\WarehouseController::class , 'createWarehouse'])->name('warehouse.add');
	Route::post('/store', [App\Http\Controllers\WarehouseController::class , 'storeWarehouse'])->name('warehouse.store');
	Route::get('/edit/{id}', [App\Http\Controllers\WarehouseController::class , 'editWarehouse'])->name('warehouse.edit');
	Route::post('/update/{id}', [App\Http\Controllers\WarehouseController::class , 'updateWarehouse'])->name('warehouse.update');
	Route::get('/delete/{id}', [App\Http\Controllers\WarehouseController::class , 'deleteWarehouse'])->name('warehouse.delete');
});

//Pikup Request
Route::group(['prefix' => 'pickup-request'], function () {
	Route::get('/', [App\Http\Controllers\WarehouseController::class , 'pickupRequestList'])->name('pickup.request.index')->middleware('permission:pickup_request.view');
	Route::post('/ajax', [App\Http\Controllers\WarehouseController::class , 'pickupRequestAjax'])->name('pickup.request.ajax');
	Route::post('/create', [App\Http\Controllers\WarehouseController::class , 'createPickupRequest'])->name('pickup.request.create');
//Route::get('/cancel/{id}', [App\Http\Controllers\WarehouseController::class, 'cancelPickupRequest'])->name('pickup.request.cancel');
});

Route::get('courier-commission', [App\Http\Controllers\CourierCommissionController::class , 'index'])->name('courier.commission')->middleware('permission:general_setting.view');
Route::post('/verify-passkey', [App\Http\Controllers\CourierCommissionController::class , 'verifyPasskey'])->name('verify.passkey');

Route::post('courier-commission', [App\Http\Controllers\CourierCommissionController::class , 'update'])->name('courier.commission.store')->middleware('permission:general_setting.view');
Route::get('users/commission/{userId}', [App\Http\Controllers\CourierCommissionController::class , 'userCommission'])->name('courier.commission.user')->middleware('permission:general_setting.view');
Route::post('user-courier-commission', [App\Http\Controllers\CourierCommissionController::class , 'userCommissionUpdate'])->name('users.courier.commission.store')->middleware('permission:general_setting.view');

//User
Route::group(['prefix' => 'users'], function () {
	Route::get('/', [App\Http\Controllers\UserController::class , 'index'])->name('users')->middleware('permission:clients.view');
	Route::post('/ajax', [App\Http\Controllers\UserController::class , 'ajaxUser'])->name('users.ajax')->middleware('permission:clients.view');
	Route::get('/create', [App\Http\Controllers\UserController::class , 'createUser'])->name('users.create')->middleware('permission:clients.add');
	Route::post('/store', [App\Http\Controllers\UserController::class , 'storeUser'])->name('users.store')->middleware('permission:clients.add');
	Route::get('/edit/{id}', [App\Http\Controllers\UserController::class , 'editUser'])->middleware('permission:clients.edit');
	Route::post('/update/{id}', [App\Http\Controllers\UserController::class , 'updateUser'])->name('users.update')->middleware('permission:clients.edit');
	Route::get('/delete/{id}', [App\Http\Controllers\UserController::class , 'deleteUser'])->middleware('permission:clients.delete');

	Route::post('/recharge/offline', [App\Http\Controllers\UserController::class , 'rechargeOffline'])->middleware('permission:clients.edit');

	//KYC USER
	Route::get('/kyc/edit', [App\Http\Controllers\UserController::class , 'kycUser'])->name('users.kyc.edit');
	Route::post('/kyc/pancard/update', [App\Http\Controllers\UserController::class , 'kycUserPancardUpdate'])->name('users.pancard.update');
	Route::post('/kyc/aadhar/send-otp', [App\Http\Controllers\UserController::class , 'kycUserAadharOtp'])->name('users.aadhar.sendOtp');
	Route::post('/kyc/aadhar/update', [App\Http\Controllers\UserController::class , 'kycUserAadharUpdate'])->name('users.aadhar.update');
	Route::post('/kyc/gstin/send-otp', [App\Http\Controllers\UserController::class , 'kycUserGSTINSendOtp'])->name('users.gstin.sendOtp');
	Route::post('/kyc/gstin/update', [App\Http\Controllers\UserController::class , 'kycUserGSTINUpdate'])->name('users.gst.update');
	Route::post('/kyc/udyam/update', [App\Http\Controllers\UserController::class , 'kycUserUdyamUpdate'])->name('users.udyam.update');
	Route::post('/kyc/bank/penny-drop', [App\Http\Controllers\UserController::class , 'kycUserBankPennyDropUpdate'])->name('users.bank.penny-drop');

	//KYC ADMIN
	Route::get('/kyc/request', [App\Http\Controllers\UserController::class , 'kycUserRequest'])->name('users.kyc.request')->middleware('permission:client_kyc_request.view');
	Route::post('/kyc/request-ajax', [App\Http\Controllers\UserController::class , 'kycUserRequestAjax'])->name('users.kyc-request.ajax')->middleware('permission:client_kyc_request.view');
	Route::post('/kyc/update-status', [App\Http\Controllers\UserController::class , 'kycUserUpdateStatus'])->name('users.kyc.update-status')->middleware('permission:client_kyc_request.edit');
	Route::post('/kyc/update-user-status', [App\Http\Controllers\UserController::class , 'updateUserKycStatus'])->name('users.kyc.update-user-status')->middleware('permission:client_kyc_request.edit');
	Route::get('/kyc/verified/{id}', [App\Http\Controllers\UserController::class , 'kycUserRequestVerified'])->middleware('permission:client_kyc_request.edit');
	Route::post('/kyc/verified', [App\Http\Controllers\UserController::class , 'kycUserVerified'])->name('users.kyc.verified')->middleware('permission:client_kyc_request.edit');
	Route::post('/kyc/rejected', [App\Http\Controllers\UserController::class , 'kycUserRejected'])->name('users.kyc.rejected')->middleware('permission:client_kyc_request.edit');

	// API Verification Routes (Admin Side)
	Route::post('/kyc/verify-pan-api', [App\Http\Controllers\UserController::class , 'verifyPanApi'])->name('users.kyc.verify-pan-api');
	Route::post('/kyc/verify-gst-api', [App\Http\Controllers\UserController::class , 'verifyGstApi'])->name('users.kyc.verify-gst-api');
	Route::post('/kyc/verify-bank-penny-drop-api', [App\Http\Controllers\UserController::class , 'verifyBankPennyDropApi'])->name('users.kyc.verify-bank-penny-drop-api');
	Route::post('/kyc/verify-aadhaar-api', [App\Http\Controllers\UserController::class , 'verifyAadhaarApi'])->name('users.kyc.verify-aadhaar-api');
	Route::post('/kyc/verify-aadhaar-otp-api', [App\Http\Controllers\UserController::class , 'verifyAadhaarOtpApi'])->name('users.kyc.verify-aadhaar-otp-api');
	Route::post('/kyc/verify-udyam-api', [App\Http\Controllers\UserController::class , 'verifyUdyamApi'])->name('users.kyc.verify-udyam-api');

	Route::prefix('webhooks')->name('users.webhooks.admin.')->group(function () {
		Route::get('/list/{user_id}', [App\Http\Controllers\AdminUserWebhookController::class, 'index'])->name('index');
		Route::post('/store/{user_id}', [App\Http\Controllers\AdminUserWebhookController::class, 'store'])->name('store');
		Route::post('/update/{id}', [App\Http\Controllers\AdminUserWebhookController::class, 'update'])->name('update');
		Route::delete('/delete/{id}', [App\Http\Controllers\AdminUserWebhookController::class, 'destroy'])->name('destroy');
	});
});

//Recharge Amount
Route::group(['prefix' => 'recharge'], function () {
	Route::get('/thank-you', [App\Http\Controllers\RechargeController::class , 'thankYou'])->name('recharge.thankyou');
	Route::get('/wallet', [App\Http\Controllers\RechargeController::class , 'walletRecharge'])->name('recharge.wallet');
	Route::post('/wallet/cod-expense', [App\Http\Controllers\RechargeController::class , 'storeCodWalletExpense'])->name('recharge.wallet.cod-expense');

	Route::get('/list', [App\Http\Controllers\RechargeController::class , 'rechargeList'])->name('recharge.list');
	Route::post('/ajax', [App\Http\Controllers\RechargeController::class , 'rechargeListAjax'])->name('recharge.list.ajax');

	Route::get('/list/history', [App\Http\Controllers\RechargeController::class , 'rechargeListAdmin'])->name('recharge.list.history');
	Route::post('/ajax/history', [App\Http\Controllers\RechargeController::class , 'rechargeListAjaxAdmin'])->name('recharge.list.ajax.history');

	Route::post('/wallet/action', [App\Http\Controllers\RechargeController::class , 'rechargeWalletAction'])->name('recharge.wallet.action');
});

//staffs
Route::get('/staff', [App\Http\Controllers\StaffController::class , 'staff'])->name('staff');
Route::post('/staff/ajax', [App\Http\Controllers\StaffController::class , 'staffAjax'])->name('staff.ajax');
Route::get('/staff/create', [App\Http\Controllers\StaffController::class , 'staffCreate'])->name('staff.create');
Route::post('/staff/store', [App\Http\Controllers\StaffController::class , 'staffStore'])->name('staff.store');
Route::get('/staff/edit/{id}', [App\Http\Controllers\StaffController::class , 'staffEdit'])->name('staff.edit');
Route::post('/staff/update/{id}', [App\Http\Controllers\StaffController::class , 'staffUpdate'])->name('staff.update');
Route::get('/staff/delete/{id}', [App\Http\Controllers\StaffController::class , 'staffDelete'])->name('staff.delete');
Route::get('/staff/permission/{id}', [App\Http\Controllers\StaffController::class , 'staffPermission'])->name('staff.permission');
Route::post('/staff/permission/{id}', [App\Http\Controllers\StaffController::class , 'staffPermissionUpdate'])->name('staff.permission-update');

//Roles
Route::get('/roles', [App\Http\Controllers\StaffController::class , 'roles'])->name('roles');
Route::post('/roles/ajax', [App\Http\Controllers\StaffController::class , 'rolesAjax'])->name('roles.ajax');
Route::get('/roles/create', [App\Http\Controllers\StaffController::class , 'rolesCreate'])->name('roles.create');
Route::post('/roles/store', [App\Http\Controllers\StaffController::class , 'rolesStore'])->name('roles.store');
Route::get('/roles/edit/{id}', [App\Http\Controllers\StaffController::class , 'rolesEdit'])->name('roles.edit');
Route::post('/roles/update/{id}', [App\Http\Controllers\StaffController::class , 'rolesUpdate'])->name('roles.update');
Route::get('/roles/delete/{id}', [App\Http\Controllers\StaffController::class , 'rolesDelete'])->name('roles.delete');
Route::get('roles/groups/{id}', [App\Http\Controllers\StaffController::class , 'rolesGroups']);


//Shippping Company
Route::group(['prefix' => 'shipping/company'], function () {
	Route::get('/', [App\Http\Controllers\ShippingController::class , 'index'])->name('shipping.company')->middleware('permission:shipping_company.view');
	Route::post('/store', [App\Http\Controllers\ShippingController::class , 'storeShipping'])->name('shipping.company.store')->middleware('not.seller');
	Route::post('/update', [App\Http\Controllers\ShippingController::class , 'updateShipping'])->name('shipping.company.update')->middleware('not.seller');
	Route::get('/{id}/workspace', [App\Http\Controllers\ShippingController::class, 'companyWorkspace'])->name('shipping.company.workspace')->middleware('permission:shipping_company.edit');
	Route::get('/{id}/manage-pincode', [App\Http\Controllers\ShippingController::class, 'managePincode'])->name('shipping.company.manage-pincode')->middleware('permission:shipping_company.edit');
	Route::post('/{id}/manage-pincode/ajax', [App\Http\Controllers\ShippingController::class, 'managePincodeAjax'])->name('shipping.company.manage-pincode.ajax')->middleware('permission:shipping_company.edit');
	Route::post('/{id}/manage-pincode/row-update', [App\Http\Controllers\ShippingController::class, 'managePincodeRowUpdate'])->name('shipping.company.manage-pincode.row-update')->middleware('permission:shipping_company.edit');
	Route::post('/{id}/manage-pincode/bulk-update', [App\Http\Controllers\ShippingController::class, 'managePincodeBulkUpdate'])->name('shipping.company.manage-pincode.bulk-update')->middleware('permission:shipping_company.edit');
	Route::post('/{id}/manage-pincode/oda-charges', [App\Http\Controllers\ShippingController::class, 'managePincodeOdaChargesSave'])->name('shipping.company.manage-pincode.oda-charges.save')->middleware('permission:shipping_company.edit');
	Route::post('/{id}/manage-pincode/opa-charges', [App\Http\Controllers\ShippingController::class, 'managePincodeOpaChargesSave'])->name('shipping.company.manage-pincode.opa-charges.save')->middleware('permission:shipping_company.edit');
});

//Shippping Packaging
Route::group(['prefix' => 'shipment/packaging'], function () {
	Route::get('/', [App\Http\Controllers\ShippingController::class , 'shipmentPackaging'])->name('shipment.packaging');
	Route::post('/ajax', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingAjax'])->name('shipping.packaging.ajax');
	Route::get('/add', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingAdd'])->name('shipment.packaging.add');
	Route::post('/store', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingStore'])->name('shipment.packaging.store');
	Route::get('/edit/{id}', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingEdit']);
	Route::post('/update', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingUpdate'])->name('shipment.packaging.update');
	Route::get('/delete/{id}', [App\Http\Controllers\ShippingController::class , 'shipmentPackagingDelete']);
});

//Shippping Rate
Route::prefix('rate')->name('rate.')->group(function () {
	//Zone Setting
	Route::get('zone-setting', [App\Http\Controllers\ShippingController::class , 'rateZoneSetting'])->name('zone-setting');
	Route::post('zone-setting/ajax', [App\Http\Controllers\ShippingController::class , 'rateZoneAjaxList'])->name('zone-setting.ajax');
	Route::post('zone-setting/store', [App\Http\Controllers\ShippingController::class , 'storeZoneSetting'])->name('zone-setting.store')->middleware('not.seller');
	Route::post('zone-setting/update/{id}', [App\Http\Controllers\ShippingController::class , 'updateZoneSetting'])->name('zone-setting.update')->middleware('not.seller');
	Route::get('zone-setting/edit/{id}', [App\Http\Controllers\ShippingController::class , 'getZoneSetting'])->name('zone-setting.get'); // fetch single for edit
	Route::get('zone-setting/options', [App\Http\Controllers\ShippingController::class , 'getZoneOptions'])->name('zone-setting.options');
	Route::post('pdate-sort-order', [App\Http\Controllers\ShippingController::class , 'updateZoneSettingSortOrder'])->name('zone-setting.update-sort-order')->middleware('not.seller'); // fetch single for edit
	Route::delete('zone-setting/delete/{id}', [App\Http\Controllers\ShippingController::class , 'deleteZoneSetting'])->name('zone-setting.delete')->middleware('not.seller');

	//Rate Card
	Route::get('card', [App\Http\Controllers\ShippingController::class , 'rateCard'])->name('card');
	Route::post('card/ajax', [App\Http\Controllers\ShippingController::class , 'rateCardAjax'])->name('card.ajax');
	Route::get('card/add', [App\Http\Controllers\ShippingController::class , 'rateCardAdd'])->name('card.add');
	Route::post('card/store', [App\Http\Controllers\ShippingController::class , 'rateCardStore'])->name('card.store')->middleware('not.seller');
	Route::get('card/edit/{id}', [App\Http\Controllers\ShippingController::class , 'rateCardEdit'])->name('card.edit');
	Route::post('card/update/{id}', [App\Http\Controllers\ShippingController::class , 'rateCardUpdate'])->name('card.update')->middleware('not.seller');
	Route::post('card/delete/{id}', [App\Http\Controllers\ShippingController::class , 'rateCardDelete'])->name('card.delete')->middleware('not.seller');

	// Price List
	Route::get('price-list', [App\Http\Controllers\ShippingController::class , 'priceList'])->name('price-list');
	Route::post('price-list-ajax', [App\Http\Controllers\ShippingController::class , 'priceListAjax'])->name('price-list.ajax');
	Route::post('price-list/matrix-save', [App\Http\Controllers\ShippingController::class , 'priceListMatrixSave'])->name('price-list.matrix-save')->middleware('not.seller');
	Route::post('price-list/b2c-save', [App\Http\Controllers\ShippingController::class , 'priceListB2cSave'])->name('price-list.b2c-save')->middleware('not.seller');
	Route::post('overhead-charges/update', [App\Http\Controllers\ShippingController::class , 'updateOverheadCharges'])->name('overhead-charges.update')->middleware('not.seller');
	Route::get('overhead-charges', [App\Http\Controllers\ShippingController::class , 'overheadChargesShow'])->name('overhead-charges.show');
	Route::post('overhead-charges/save', [App\Http\Controllers\ShippingController::class , 'overheadChargesSave'])->name('overhead-charges.save')->middleware('not.seller');
	Route::post('overhead-charges/delete', [App\Http\Controllers\ShippingController::class , 'overheadChargesDelete'])->name('overhead-charges.delete')->middleware('not.seller');

	// Other Charges Master
	Route::get('other-charges-master', [App\Http\Controllers\ShippingController::class , 'otherChargesMasterIndex'])->name('other-charges-master');
	Route::post('other-charges-master', [App\Http\Controllers\ShippingController::class , 'otherChargesMasterStore'])->name('other-charges-master.store')->middleware('not.seller');
	Route::post('other-charges-master/{id}', [App\Http\Controllers\ShippingController::class , 'otherChargesMasterUpdate'])->name('other-charges-master.update')->middleware('not.seller');
	Route::post('other-charges-master/{id}/delete', [App\Http\Controllers\ShippingController::class , 'otherChargesMasterDelete'])->name('other-charges-master.delete')->middleware('not.seller');

	// Rate Card Other Charges (B2B)
	Route::get('other-charges/assignments', [App\Http\Controllers\ShippingController::class , 'rateCardOtherChargesShow'])->name('other-charges.assignments');
	Route::post('other-charges/assignments', [App\Http\Controllers\ShippingController::class , 'rateCardOtherChargesSave'])->name('other-charges.assignments.save')->middleware('not.seller');

	//Rate Group
	Route::get('card/group', [App\Http\Controllers\ShippingController::class , 'rateCardGroup'])->name('card.group');
	Route::post('card/groupAjax', [App\Http\Controllers\ShippingController::class , 'rateCardGroupAjax'])->name('card.group.ajax');

	//Shipping Weight
	Route::get('shipping-weight', [App\Http\Controllers\ShippingController::class , 'shippingWeight'])->name('shipping-weight');
	Route::post('shipping-weight/ajax', [App\Http\Controllers\ShippingController::class , 'shippingWeightAjax'])->name('shipping-weight.ajax');
	Route::post('shipping-weight/store', [App\Http\Controllers\ShippingController::class , 'storeShippingWeight'])->name('shipping-weight.store')->middleware('not.seller');
	Route::get('shipping-weight/edit/{id}', [App\Http\Controllers\ShippingController::class , 'getShippingWeight'])->name('shipping-weight.get');
	Route::post('shipping-weight/update/{id}', [App\Http\Controllers\ShippingController::class , 'updateShippingWeight'])->name('shipping-weight.update')->middleware('not.seller');
	Route::post('shipping-weight/delete/{id}', [App\Http\Controllers\ShippingController::class , 'deleteShippingWeight'])->name('shipping-weight.delete')->middleware('not.seller');


	Route::get('calculator', [App\Http\Controllers\ShippingController::class , 'rateCalculator'])->name('calculator')
		->middleware('permission:rate_calculator.view');
	Route::post('calculator/show', [App\Http\Controllers\ShippingController::class , 'rateCalculatorShow'])->name('calculator.create');
	Route::get('pincode-check/{pincode}', [App\Http\Controllers\ShippingController::class , 'pincodeCheck'])
		->name('pincode.check');
	Route::post('freight-breakup', [App\Http\Controllers\ShippingController::class , 'rateFreightBreakup'])->name('freight-breakup');
});

Route::get('check-serviceability', [App\Http\Controllers\ShippingController::class, 'checkPincodeServiceability'])->name('rate.shipping.pincode.serviceability');

//Zipcode CRUD
Route::group(['prefix' => 'zipcode'], function () {
	Route::get('/', [App\Http\Controllers\ZipcodeController::class , 'index'])->name('zipcode.index')->middleware('permission:zipcode.view');
	Route::post('/ajax', [App\Http\Controllers\ZipcodeController::class , 'ajaxZipcode'])->name('zipcode.ajax');
	Route::get('/select2-ajax', [App\Http\Controllers\ZipcodeController::class , 'select2Ajax'])->name('zipcode.select2');
	Route::get('/filter-options', [App\Http\Controllers\ZipcodeController::class , 'getFilterOptions'])->name('zipcode.filter-options');
	Route::post('/store', [App\Http\Controllers\ZipcodeController::class , 'store'])->name('zipcode.store')->middleware('permission:zipcode.add');
	Route::get('/edit/{id}', [App\Http\Controllers\ZipcodeController::class , 'edit'])->name('zipcode.edit')->middleware('permission:zipcode.edit');
	Route::post('/update/{id}', [App\Http\Controllers\ZipcodeController::class , 'update'])->name('zipcode.update')->middleware('permission:zipcode.edit');
	Route::get('/delete/{id}', [App\Http\Controllers\ZipcodeController::class , 'delete'])->name('zipcode.delete')->middleware('permission:zipcode.delete');
});


//Product Category
Route::group(['prefix' => 'product-category'], function () {
	Route::get('/', [App\Http\Controllers\ShippingController::class , 'productCategory'])->name('product-category');
	Route::post('/store', [App\Http\Controllers\ShippingController::class , 'storeProductCategory'])->name('product-category.store');
	Route::post('/update', [App\Http\Controllers\ShippingController::class , 'updateProductCategory'])->name('product-category.update');
	Route::get('/delete/{id}', [App\Http\Controllers\ShippingController::class , 'deleteProductCategory']);
});

Route::group(['prefix' => 'ndr'], function () {
	Route::get('/', [NdrController::class , 'index'])->name('ndr')->middleware('permission:ndr.view');
	Route::post('/ndrajax', [NdrController::class , 'ndrAjax'])->name('ndr.ajax');
	Route::post('/raise-ndr-request', [NdrController::class , 'raiseRequest'])->name('raise-ndr-request')->middleware('permission:ndr.add');
});

//Order
Route::group(['prefix' => 'order'], function () {
	Route::get('/', [App\Http\Controllers\OrderController::class , 'index'])->name('order')->middleware('permission:order.view');
	Route::post('/ajax', [App\Http\Controllers\OrderController::class , 'orderAjax'])->name('order.ajax');
	Route::post('/export-excel', [App\Http\Controllers\OrderController::class , 'exportExcel'])->name('order.export-excel')->middleware('permission:order.view');
	Route::get('/status-counts', [App\Http\Controllers\OrderController::class , 'getStatusCounts'])->name('order.status-counts');
	Route::get('/filter-options', [App\Http\Controllers\OrderController::class , 'getFilterOptions'])->name('order.filter-options');

	Route::get('/search', [App\Http\Controllers\OrderController::class , 'searchByAwb'])->name('order.search');
	Route::get('/details/{id}', [App\Http\Controllers\OrderController::class , 'orderDetails']);

	Route::get('/create', [App\Http\Controllers\OrderController::class , 'orderCreate'])->name('order.create')->middleware('permission:order.add');
	Route::post('/store', [App\Http\Controllers\OrderController::class , 'orderStore'])->name('order.store');
	Route::get('/edit/{id}', [App\Http\Controllers\OrderController::class , 'orderEdit']);
	Route::post('/update/{id}', [App\Http\Controllers\OrderController::class , 'orderUpdate'])->name('order.update');
	Route::post('/delete/{id}', [App\Http\Controllers\OrderController::class , 'orderDelete']);
	Route::get('/clone/{id}', [App\Http\Controllers\OrderController::class , 'orderClone']);
	Route::post('/cancel/{id}', [App\Http\Controllers\OrderController::class , 'orderCancel']);
	Route::post('/credit-lost-order', [App\Http\Controllers\OrderController::class , 'creditLostOrder'])->name('order.credit-lost-order');
	Route::post('/manual-status-update', [App\Http\Controllers\OrderController::class , 'manualStatusUpdate'])->name('order.manual-status-update');

	Route::get('/bulk-create', [App\Http\Controllers\OrderController::class , 'orderBulkCreate'])->name('order.bulk-create')
		->middleware('permission:bulk_order.add');
	Route::get('/bulk-sample', [App\Http\Controllers\OrderController::class , 'orderBulkSampleDownload'])->name('order.bulk-sample');
	Route::post('/bulk-store', [App\Http\Controllers\OrderController::class , 'orderBulkStore'])->name('order.bulk-store');

	Route::get('/shipment/charge/{orderId}', [App\Http\Controllers\OrderController::class , 'orderShipCharge']);
	Route::post('/ship/now', [App\Http\Controllers\OrderController::class , 'orderShipNow'])->name('order.ship.now');

	Route::post('/cancel-api/{id}', [App\Http\Controllers\OrderController::class , 'orderCancelApi']);
	Route::get('/tracking-history/{id}', [App\Http\Controllers\OrderController::class , 'orderTrackingHistory']);
	Route::post('/manual-status/{id}', [App\Http\Controllers\OrderController::class , 'orderManualStatusUpdate'])->name('order.manual-status');

	Route::get('/shipping-lable/{orderId}', [App\Http\Controllers\OrderController::class , 'orderShippingLableDownload'])->name('order.shipping-download');
	Route::get('/waybill-copy/{orderId}', [App\Http\Controllers\OrderController::class , 'orderWayBillCopy']);

	Route::get('/download-label/{id}', [App\Http\Controllers\OrderController::class , 'orderLableDownload'])->name('order.downloadLabel');
	Route::get('/download/all-lable', [App\Http\Controllers\OrderController::class , 'alllabeldownload'])->name('order.download-all-lable');

	Route::post('/warehouse/add', [App\Http\Controllers\OrderController::class , 'orderwarehouseAdd'])->name('order.warehouse.address');

	Route::post('/package-dimension/update', [App\Http\Controllers\OrderController::class , 'orderPackageDimensionUpdate'])->name('order.package-dimension.update');
	Route::post('/package-dimension/bulk-update', [App\Http\Controllers\OrderController::class , 'orderPackageDimensionBulkUpdate'])->name('order.package-dimension.bulk-update');

	Route::post('/bulk-shipment/now', [App\Http\Controllers\OrderController::class , 'orderBulkShipNow'])->name('order.bulk-shipment.now');
	Route::post('/bulk-shipment-cancel', [App\Http\Controllers\OrderController::class , 'orderBulkCancel'])->name('order.bulk-shipment-cancel');
	Route::post('/bulk-sync-channel', [App\Http\Controllers\OrderController::class , 'orderBulkSyncChannel'])->name('order.bulk-sync-channel');

	// Warehouse Pickup
	Route::get('/warehouse/list', [App\Http\Controllers\OrderController::class , 'orderWarehouseList'])->name('order.warehouse.list');
	Route::get('/warehouse/create', [App\Http\Controllers\OrderController::class , 'orderWarehouseCreate'])->name('order.warehouse.create');

	//Customer
	Route::get('/customer/list', [App\Http\Controllers\OrderController::class , 'orderCustomerList'])->name('order.customer.list');
	Route::get('/customer/search-by-mobile', [App\Http\Controllers\OrderController::class , 'orderCustomerSearchByMobile'])->name('order.customer.search-by-mobile');
	Route::get('/customer/address-list/{customerId}', [App\Http\Controllers\OrderController::class , 'orderCustomerAddressList'])->name('order.customer.address-list');
	Route::get('/customer/create', [App\Http\Controllers\OrderController::class , 'orderCustomerCreate'])->name('order.customer.create');
	Route::get('/customer-address/create/{customerId}', [App\Http\Controllers\OrderController::class , 'orderCustomerAddressCreate'])->name('order.customer-address.create');
	Route::post('/customer-address/store', [App\Http\Controllers\OrderController::class , 'orderCustomerAddressStore'])->name('order.customer-address.store');
});

// Remmitance Reports
Route::group(['prefix' => 'cod-remmitance'], function () {
	Route::get('/', [App\Http\Controllers\OrderController::class , 'codOrderRemittance'])->name('cod-remmitance')->middleware('permission:remittance.view');
	Route::post('/ajax', [App\Http\Controllers\OrderController::class , 'remmitanceAjax'])->name('remmitance.ajax');
	Route::get('/download-excel/{id}', [App\Http\Controllers\OrderController::class , 'downloadRemittanceExcel'])->name('remmitance.download.excel');
});

Route::group(['prefix' => 'cod-payout'], function () {
	Route::get('/', [App\Http\Controllers\OrderController::class , 'codPayout'])->name('cod-payout')->middleware('permission:cod_payout.view');
	Route::post('/ajax', [App\Http\Controllers\OrderController::class , 'codPayoutAjax'])->name('cod-payout-ajax');
	Route::post('/store', [App\Http\Controllers\OrderController::class , 'storePayout'])->name('cod-payout-store');
});

// COD Settings
Route::group(['prefix' => 'cod-settings'], function () {
	Route::get('/', [App\Http\Controllers\CodSettingController::class , 'index'])->name('cod-settings.index')->middleware('permission:cod_setting.view');
	Route::get('/create', [App\Http\Controllers\CodSettingController::class , 'create'])->name('cod-settings.create')->middleware('permission:cod_setting.add');
	Route::post('/store', [App\Http\Controllers\CodSettingController::class , 'store'])->name('cod-settings.store')->middleware('permission:cod_setting.add');
	Route::get('/{codSetting}/edit', [App\Http\Controllers\CodSettingController::class , 'edit'])->name('cod-settings.edit')->middleware('permission:cod_setting.edit');
	Route::put('/{codSetting}', [App\Http\Controllers\CodSettingController::class , 'update'])->name('cod-settings.update')->middleware('permission:cod_setting.edit');
	Route::delete('/{codSetting}', [App\Http\Controllers\CodSettingController::class , 'destroy'])->name('cod-settings.destroy')->middleware('permission:cod_setting.delete');
	;
	Route::post('/{codSetting}/toggle-status', [App\Http\Controllers\CodSettingController::class , 'toggleStatus'])->name('cod-settings.toggle-status')->middleware('permission:cod_setting.edit');
	Route::post('/{codSetting}/set-default', [App\Http\Controllers\CodSettingController::class , 'setDefault'])->name('cod-settings.set-default')->middleware('permission:cod_setting.edit');

	// Holiday routes
	Route::post('/holiday/store', [App\Http\Controllers\CodSettingController::class , 'storeHoliday'])->name('cod-settings.holiday.store')->middleware('permission:cod_setting.add');
	Route::delete('/holiday/{id}', [App\Http\Controllers\CodSettingController::class , 'destroyHoliday'])->name('cod-settings.holiday.delete')->middleware('permission:cod_setting.delete');

	// Terms and Conditions routes
	Route::get('/terms', [App\Http\Controllers\CodSettingController::class , 'getTerms'])->name('cod-settings.terms.get');
	Route::post('/terms', [App\Http\Controllers\CodSettingController::class , 'storeTerms'])->name('cod-settings.terms.store')->middleware('permission:cod_setting.add');
});


// User COD Plans
Route::group(['prefix' => 'user/cod-plans', 'middleware' => 'auth'], function () {
	Route::get('/', [App\Http\Controllers\User\CodPlanController::class , 'index'])->name('user.cod-plans.index');
	Route::post('/activate', [App\Http\Controllers\User\CodPlanController::class , 'activate'])->name('user.cod-plans.activate');
	Route::post('/deactivate', [App\Http\Controllers\User\CodPlanController::class , 'deactivate'])->name('user.cod-plans.deactivate');
	Route::get('/current', [App\Http\Controllers\User\CodPlanController::class , 'getCurrentPlan'])->name('user.cod-plans.current');

	// Debug route to test activation
	Route::post('/test-activate', function (\Illuminate\Http\Request $request) {
		    return response()->json([
		    'status' => 'success',
		    'msg' => 'Test route working',
		    'data' => $request->all(),
		    'user' => auth()->user()->only(['id', 'name', 'email'])
		    ]);
	    }
	    )->name('user.cod-plans.test-activate');
    });

//WhatsApp Settings (Admin/Staff)
Route::group(['prefix' => 'whatsapp-settings'], function () {
	Route::get('/', [App\Http\Controllers\WhatsappSettingController::class , 'index'])->name('whatsapp.settings')->middleware('permission:whatsapp_setting.view');
	Route::post('/update', [App\Http\Controllers\WhatsappSettingController::class , 'updateSettings'])->name('whatsapp.settings.update')->middleware('permission:whatsapp_setting.edit');
	Route::get('/notification-type/{id}', [App\Http\Controllers\WhatsappSettingController::class , 'getNotificationType'])->name('whatsapp.notification-type.get');
	Route::post('/notification-type/{id}', [App\Http\Controllers\WhatsappSettingController::class , 'updateNotificationType'])->name('whatsapp.notification-type.update')->middleware('permission:whatsapp_setting.edit');
	Route::post('/notification-type/{id}/toggle', [App\Http\Controllers\WhatsappSettingController::class , 'toggleNotificationType'])->name('whatsapp.notification-type.toggle')->middleware('permission:whatsapp_setting.edit');
	Route::post('/test-connection', [App\Http\Controllers\WhatsappSettingController::class , 'testConnection'])->name('whatsapp.test-connection');
	Route::get('/logs', [App\Http\Controllers\WhatsappSettingController::class , 'getLogs'])->name('whatsapp.logs');
});

//User WhatsApp Services
Route::group(['prefix' => 'user/whatsapp-services'], function () {
	Route::get('/', [App\Http\Controllers\WhatsappSettingController::class , 'userIndex'])->name('user.whatsapp.services');
	Route::post('/add', [App\Http\Controllers\WhatsappSettingController::class , 'addService'])->name('user.whatsapp.add');
	Route::post('/{id}/remove', [App\Http\Controllers\WhatsappSettingController::class , 'removeService'])->name('user.whatsapp.remove');
	Route::post('/{id}/toggle', [App\Http\Controllers\WhatsappSettingController::class , 'toggleUserService'])->name('user.whatsapp.toggle');
});

// Daily Reports
Route::get('/report/order', [App\Http\Controllers\ReportController::class , 'index'])->name('report.order')->middleware('permission:order_report.view');
Route::post('/report/order/ajax', [App\Http\Controllers\ReportController::class , 'reportOrderAjax'])->name('report.order.ajax');
Route::get('/report/export-orders', [App\Http\Controllers\ReportController::class , 'reportOrderExport'])->name('report.order.export');
Route::get('/report/user-wise', [App\Http\Controllers\ReportController::class , 'userWiseReport'])->name('report.user-wise')->middleware('permission:order_report.view');
Route::post('/report/user-wise/ajax', [App\Http\Controllers\ReportController::class , 'userWiseReportAjax'])->name('report.user-wise.ajax');

Route::get('/passbook-user', [App\Http\Controllers\ReportController::class , 'passbookUser'])->name('report.passbook.user')
	->middleware('permission:passbook_report.view');
Route::post('/passbook-user/ajax', [App\Http\Controllers\ReportController::class , 'passbookUserAjax'])->name('report.passbook-user.ajax');
Route::get('/passbook/{user_id}', [App\Http\Controllers\ReportController::class , 'passbookReport'])->name('report.passbook')->middleware('permission:passbook_report.view');
Route::post('/passbook/ajax', [App\Http\Controllers\ReportController::class , 'passbookReportAjax'])->name('report.passbook.ajax');

Route::get('/report/shipping-charge', [App\Http\Controllers\ReportController::class , 'shippingCharge'])->name('report.shipping-charge')->middleware('permission:shipping_charge.view');
Route::post('/report/shipping-charge/ajax', [App\Http\Controllers\ReportController::class , 'shippingChargeAjax'])->name('report.shipping-charge.ajax');

Route::get('report/billing-invoice', [App\Http\Controllers\ReportController::class , 'billingInvoice'])->name('report.billing-invoice');
Route::post('report/billing-invoice/ajax', [App\Http\Controllers\ReportController::class , 'billingInvoiceAjax'])->name('report.billing-invoice.ajax');
Route::get('report/billing-invoice/pdf/{id}', [App\Http\Controllers\ReportController::class , 'billingInvoicePdf'])->name('report.billing-invoice.pdf');
Route::get('report/billing-invoice/excel/{id}', [App\Http\Controllers\ReportController::class , 'billingInvoiceExcel'])->name('report.billing-invoice.excel');

//Ticket
Route::group(['prefix' => 'ticket'], function () {
	Route::get('/list', [App\Http\Controllers\TicketController::class , 'index'])->name('ticket');
	Route::post('/ajax', [App\Http\Controllers\TicketController::class , 'ticketListAjax'])->name('ticket.list.ajax');
	Route::get('/add', [App\Http\Controllers\TicketController::class , 'ticketAdd'])->name('ticket.add');
	Route::post('/store', [App\Http\Controllers\TicketController::class , 'ticketStore'])->name('ticket.store');
	Route::get('/view/{id}', [App\Http\Controllers\TicketController::class , 'ticketView']);
	Route::post('/remark/store', [App\Http\Controllers\TicketController::class , 'remarkStore'])->name('ticket.remark.store');

	Route::get('/all/list', [App\Http\Controllers\TicketController::class , 'ticketList'])->name('ticket.admin')->middleware('permission:ticket_request.view');
	Route::post('/all/ajax', [App\Http\Controllers\TicketController::class , 'ticketAllListAjax'])->name('ticket.admin.ajax')->middleware('permission:ticket_request.view');
	Route::get('/revert_ticket/{id}', [App\Http\Controllers\TicketController::class , 'revert_ticket'])->name('ticket.admin.revert_ticket')->middleware('role');
	Route::post('/update/{id}', [App\Http\Controllers\TicketController::class , 'ticketupdate'])->name('ticket.admin.update_revert')->middleware('permission:ticket_request.update');
	Route::get('/close/{id}', [App\Http\Controllers\TicketController::class , 'ticketClose'])->middleware('permission:ticket_request.update');
	Route::get('/delete/{id}', [App\Http\Controllers\TicketController::class , 'ticketDelete'])->middleware('permission:ticket_request.delete');
});

//Weight Management
Route::group(['prefix' => 'weight'], function () {
	//ADMIN
	Route::get('/descripencies/all', [App\Http\Controllers\WeightController::class , 'descrepenciesAll'])->name('weight.admin.descripencies')->middleware('permission:weight_descripencies.view');
	Route::post('/raise/excess-weight', [App\Http\Controllers\WeightController::class , 'raiseExcessWeight'])->name('weight.raise.excess-weight');
	Route::get('/descripencies/history/{id}', [App\Http\Controllers\WeightController::class , 'weightHoistory']);
	Route::get('/descripencies/bulk-import/sample', [App\Http\Controllers\WeightController::class , 'downloadBulkImportSample'])->name('weight.bulk-import.sample');
	Route::get('/descripencies/bulk-import/dummy', [App\Http\Controllers\WeightController::class , 'downloadBulkImportDummy'])->name('weight.bulk-import.dummy');
	Route::post('/descripencies/bulk-import', [App\Http\Controllers\WeightController::class , 'bulkImportExcessWeight'])->name('weight.bulk-import.excess-weight');
	Route::post('/descripencies/update-excess-weight', [App\Http\Controllers\WeightController::class , 'updateExcessWeight'])->name('weight.update.excess-weight');

	Route::get('/descripencies/view_image/{id}', [App\Http\Controllers\WeightController::class , 'weightview_image']);

	Route::post('/descripencies/bycourier', [App\Http\Controllers\WeightController::class , 'weightbycourier']);
	Route::get('/descripencies/auto-accepted', [App\Http\Controllers\WeightController::class , 'autoAccepted'])->name('weight.auto-accepted');

	//User
	Route::get('/descripencies/list', [App\Http\Controllers\WeightController::class , 'index'])->name('weight.descripencies');
	Route::get('/descripencies/accepted/{id}', [App\Http\Controllers\WeightController::class , 'weightAccepted']);
	Route::get('/descripencies/remark/{id}', [App\Http\Controllers\WeightController::class , 'weightRemark']);
	Route::post('/descripencies/remark/store', [App\Http\Controllers\WeightController::class , 'remarkStore'])->name('weight.remark.store');
});

// App Banner CRUD
Route::prefix('app-banner')->name('app.banner.')->middleware('permission:general_setting.view')->group(function () {
	Route::get('/', [App\Http\Controllers\AppBannerController::class , 'index'])->name('index');
	Route::get('/create', [App\Http\Controllers\AppBannerController::class , 'create'])->name('create');
	Route::post('/store', [App\Http\Controllers\AppBannerController::class , 'store'])->name('store');
	Route::get('/edit/{id}', [App\Http\Controllers\AppBannerController::class , 'edit'])->name('edit');
	Route::post('/update/{id}', [App\Http\Controllers\AppBannerController::class , 'update'])->name('update');
	Route::delete('/delete/{id}', [App\Http\Controllers\AppBannerController::class , 'destroy'])->name('delete');
});

// Status Mapping Settings
Route::group(['prefix' => 'status-mapping'], function () {
	Route::get('/', [StatusMappingController::class , 'index'])->name('status-mapping.index')->middleware('permission:status_mapping.view');
	Route::post('/store', [StatusMappingController::class , 'store'])->name('status-mapping.store')->middleware('permission:status_mapping.add');
});

// Weight Dimensions
Route::group(['prefix' => 'weight-dimension', 'middleware' => 'permission:general_setting.view'], function () {
	Route::get('/', [App\Http\Controllers\WeightDimensionController::class , 'index'])->name('weight-dimension.index');
	Route::post('/ajax', [App\Http\Controllers\WeightDimensionController::class , 'getWeightDimensionAjax'])->name('weight-dimension.ajax');
	Route::post('/store', [App\Http\Controllers\WeightDimensionController::class , 'store'])->name('weight-dimension.store');
	Route::get('/get-by-slab', [App\Http\Controllers\WeightDimensionController::class , 'getBySlab'])->name('weight-dimension.get-by-slab');
	Route::get('/{id}/edit', [App\Http\Controllers\WeightDimensionController::class , 'edit'])->name('weight-dimension.edit');
	Route::delete('/{id}', [App\Http\Controllers\WeightDimensionController::class , 'destroy'])->name('weight-dimension.destroy');
});

Route::get('card/download-sample', [App\Http\Controllers\ShippingController::class , 'downloadSampleRateCardTemplate'])->name('card.download-sample-template');
Route::get('card/download-sample/{id}', [App\Http\Controllers\ShippingController::class , 'downloadSampleRateCard'])->name('card.download-sample');
Route::post('card/import/{id}', [App\Http\Controllers\ShippingController::class , 'importRateCard'])->name('card.import');
Route::post('card/import-preview', [App\Http\Controllers\ShippingController::class , 'previewRateCardImport'])->name('card.import-preview');
Route::post('card/import-create', [App\Http\Controllers\ShippingController::class , 'rateCardCreateForImport'])->name('card.import-create');

