<?php

namespace App\Http\Controllers\ApiService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CourierWarehouse;
use App\Models\User;
use App\Models\OrderActivity;
use App\Models\ShippingCompany;
use App\Models\LabelPreference;
use App\Models\Billing;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use App\Services\RateCardService;
use App\Services\ShipmentProcessor;
use App\Services\ShadowfaxService;
use App\Services\WebhookService;
use App\Support\OrderType;
use Helper;
use PDF;
use DNS1D;
use DNS2D;
use App\Traits\ApiResponse;

class OrderServiceController extends Controller
{
    use ApiResponse;

    protected $rateCardService;
    protected $shipmentProcessor;
    protected $shadowfaxService;

    public function __construct()
    {
        $this->rateCardService = new RateCardService();
        $this->shipmentProcessor = new ShipmentProcessor();
        $this->shadowfaxService = new ShadowfaxService();
    }

    /**
     * Resolve the order for the token's owner. Returns null when the order does
     * not exist or belongs to another account.
     */
    private function findOwnedOrder($orderId, $userId, array $with = [])
    {
        return Order::with($with)
            ->where('user_id', $userId)
            ->find($orderId);
    }

    public function warehouseList(Request $request)
    {
        $user_id = $request->user_id;
        $warehouse = CourierWarehouse::where('user_id', $user_id)->get(['id', 'warehouse_name', 'company_name', 'address', 'city', 'state', 'zip_code', 'country']);
        return $this->successResponse($warehouse, 'Warehouse list.');
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Normalise order_type via the shared rule so "COD"/"Prepaid" are accepted
            // and the canonical lowercase value is what gets validated and stored.
            if ($request->has('order_type')) {
                $request->merge(['order_type' => OrderType::sanitize($request->input('order_type'))]);
            }

            // Validation rules based on your Postman request
            $validator = Validator::make($request->all(), [
                // Order basic info
                'weight_order' => 'required|in:1',
                'shipping_mode' => 'required|in:Surface,Air',
                'order_date' => 'required|date',
                'order_type' => 'required|in:cod,prepaid',
                'cod_amount' => 'required_if:order_type,cod|numeric|min:0',
                'warehouse_id' => 'required|exists:courier_warehouses,id',

                // Customer info
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'gst_number' => 'nullable|string',
                'mobile' => 'required|string|min:10|max:15',

                // Address info
                'address' => 'required|string',
                'country' => 'required|string',
                'state' => 'required|string',
                'city' => 'required|string',
                'zip_code' => 'required|string|size:6',

                // Product info (arrays)
                'product_category' => 'required|array|min:1',
                'product_category.*' => 'required|string',
                'product_name' => 'required|array|min:1',
                'product_name.*' => 'required|string',
                'sku_number' => 'nullable|array',
                'hsn_number' => 'nullable|array',
                'amount' => 'required|array|min:1',
                'amount.*' => 'required|numeric|min:0',
                'quantity' => 'required|array|min:1',
                'quantity.*' => 'required|integer|min:1',

                // Package dimensions (for B2C - weight_order=1)
                'weight' => 'required_if:weight_order,1|nullable|numeric|min:0',
                'length' => 'required_if:weight_order,1|nullable|numeric|min:0',
                'width' => 'required_if:weight_order,1|nullable|numeric|min:0',
                'height' => 'required_if:weight_order,1|nullable|numeric|min:0',

                // Invoice info
                'invoice_no' => 'nullable|string',
                'invoice_amount' => 'required|numeric|min:0',
                'ewaybillno' => 'nullable|string',
                'total_weight' => 'required|numeric|min:0',
                'dimension_type' => 'nullable|in:cm,inch',
            ], [

                'weight_order.required' => 'Weight order is required.',
                'weight_order.in'       => 'Weight order must be 1.',

                'shipping_mode.required' => 'Shipping mode is required.',
                'shipping_mode.in'       => 'Shipping mode must be Surface or Air.',

                'order_type.required' => 'Order type is required.',
                'order_type.in'       => 'Order type must be COD or Prepaid.',

                'cod_amount.required_if' => 'COD amount is required for COD orders.',
                'cod_amount.numeric'     => 'COD amount must be a valid number.',

                'warehouse_id.required' => 'Warehouse selection is required.',
                'warehouse_id.exists'   => 'Selected warehouse does not exist.',

                'zip_code.size' => 'Zip code must be exactly 6 digits.',

                'product_category.required' => 'At least one product category is required.',
                'product_name.required'     => 'At least one product name is required.',
                'amount.*.numeric'          => 'Each product amount must be numeric.',
                'quantity.*.integer'        => 'Each product quantity must be an integer.',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first());
            }

            $user_id = $request->user_id;

            // Create or get customer
            $customer = Customer::firstOrCreate(
                ['mobile' => $request->mobile, 'user_id' => $user_id],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'gst_number' => $request->gst_number,
                    'status' => 1,
                ]
            );

            // Create customer address
            $customerAddress = CustomerAddress::create([
                'customer_id' => $customer->id,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'country' => $request->country,
            ]);

            // Prepare order data
            $exclude = ['_token', 'product_name', 'product_category', 'sku_number', 'hsn_number', 'amount', 'quantity', 'order_image', 'invoice_document', 'first_name', 'last_name', 'email', 'gst_number', 'mobile', 'address', 'country', 'state', 'city', 'zip_code'];
            if ($request->weight_order == 2) {
                $exclude = array_merge($exclude, ['weight', 'length', 'width', 'height']);
            }
            $data = $request->except($exclude);

            $data['user_id'] = $user_id;
            $data['customer_id'] = $customer->id;
            $data['customer_address_id'] = $customerAddress->id;
            $data['order_prefix'] = Order::generateOrderNumber($user_id);
            $data['status_courier'] = 'New';
            $data['weight'] = $request->total_weight;
            $data['is_fragile_item'] = $request->is_fragile_item ?? 0;
            $data['total_amount'] = $request->invoice_amount;
            $data['dimension_type'] = 'cm';
            $data['status'] = 1;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $order = Order::create($data);

            // Handle file uploads
            $imagePaths = [];
            if ($request->hasFile('order_image')) {
                foreach ($request->file('order_image') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $path = $image->storeAs('public/orders/' . $order->id, $filename);
                    $imagePaths[] = $filename;
                }
            }

            $invoiceDocPaths = [];
            if ($request->hasFile('invoice_document')) {
                foreach ($request->file('invoice_document') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $path = $image->storeAs('public/orders/' . $order->id, $filename);
                    $invoiceDocPaths[] = $filename;
                }
            }
            $order->update(['order_image' => $imagePaths, 'invoice_document' => $invoiceDocPaths]);

            // Store order items based on weight_order type
            if ($request->weight_order == 2) {
                $this->b2bStore($order, $request);
            } else {
                $this->b2cStore($order, $request);
            }

            // Insert order status
            OrderStatus::insert([
                'order_id' => $order->id,
                'order_status' => 'New',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Helper::orderActivity($order->id, 'Order created via API.');

            DB::commit();

            return $this->successResponse([
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'invoice_no' => $order->invoice_no,
                'customer_id' => $customer->id,
                'status' => $order->status_courier,
            ], 'The order has been successfully added.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Failed to create order: ' . $e->getMessage());
        }
    }

    private function b2cStore($order, $request)
    {
        $orderItems = [];
        if (!empty($request->product_category)) {
            foreach ($request->product_category as $key => $productCategory) {
                $amount = $request->amount[$key] ?? 0;
                $quantity = $request->quantity[$key] ?? 1;

                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_category' => $productCategory,
                    'product_name' => $request->product_name[$key] ?? null,
                    'sku_number' => $request->sku_number[$key] ?? null,
                    'hsn_number' => $request->hsn_number[$key] ?? null,
                    'amount' => $amount,
                    'ewaybillno' => null,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            OrderItem::insert($orderItems);
        }
    }

    private function b2bStore($order, $request)
    {
        $orderItems = [];
        $height = $width = $length = $weight = 0;

        if (!empty($request->product_category)) {
            foreach ($request->product_category as $key => $productCategory) {
                $amount = $request->amount[$key] ?? 0;
                $quantity = $request->quantity[$key] ?? 1;

                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_category' => $productCategory,
                    'product_name' => $request->product_name[$key] ?? null,
                    'sku_number' => $request->sku_number[$key] ?? null,
                    'hsn_number' => $request->hsn_number[$key] ?? null,
                    'amount' => $amount,
                    'ewaybillno' => null,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'dimensions' => json_encode([
                        'no_of_box' => $request->no_of_box[$key] ?? 0,
                        'weight' => $request->weight[$key] ?? 0,
                        'length' => $request->length[$key] ?? 0,
                        'width' => $request->width[$key] ?? 0,
                        'height' => $request->height[$key] ?? 0,
                    ]),
                ];

                // Accumulate totals
                $height += $request->height[$key] ?? 0;
                $width  += $request->width[$key] ?? 0;
                $length += $request->length[$key] ?? 0;
            }

            OrderItem::insert($orderItems);
        }

        $order->update([
            'length' => $length,
            'width'  => $width,
            'height' => $height
        ]);
        return;
    }

    /**
     * Track order by AWB number or Order ID
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tracking(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'awb_number' => 'nullable|string|required_without:order_id',
                'order_id' => 'nullable|string|required_without:awb_number',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first());
            }

            $userId = $request->user_id;
            $awbNumber = trim((string) $request->input('awb_number', ''));
            $orderIdInput = trim((string) $request->input('order_id', ''));

            // Find order
            $query = Order::with(['shippingCompany'])->where('user_id', $userId);
            if ($awbNumber !== '') {
                $query->where('awb_number', $awbNumber);
            } else {
                $query->where(function ($q) use ($orderIdInput) {
                    if (is_numeric($orderIdInput)) {
                        $q->orWhere('id', (int) $orderIdInput);
                    }
                    $q->orWhere('order_prefix', $orderIdInput);
                });
            }
            $order = $query->first();

            if (!$order) {
                return $this->errorResponse('Order not found');
            }
            $trackingHistories = [];
            return $this->successResponse([
                'trackingHistories' => $trackingHistories,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function commonData($order, $status, $history, $expectedDeliveryDate)
    {
        return [
            'order_id' => $order->id,
            'order_prefix' => $order->order_prefix,
            'invoice_no' => $order->invoice_no,
            'created' => $order->order_date,
            'awb_number' => $order->awb_number,
            'status' => $status ?? $order->status_courier,
            'history' => $history,
            'expected_delivery_date' => $expectedDeliveryDate,
        ];
    }

    /** Customer identity fields accepted by update(). */
    private const CUSTOMER_FIELDS = ['first_name', 'last_name', 'email', 'gst_number', 'mobile'];

    /** Delivery address fields accepted by update(). */
    private const ADDRESS_FIELDS = ['address', 'city', 'state', 'zip_code', 'country'];

    /**
     * Reconcile the order's customer during an update.
     *
     * `customers.mobile` is globally unique and one customer row is shared by every
     * order placed for that mobile, so we never blindly overwrite it:
     *
     *  - New mobile  → attach the order to the customer that owns that mobile
     *                  (creating them when they do not exist). No existing row is mutated.
     *  - Same mobile → update that customer's details in place.
     *
     * Returns the customer id the order should point at.
     *
     * @throws \InvalidArgumentException when the customer belongs to another seller.
     */
    private function syncCustomerForUpdate($order, Request $request, $userId): int
    {
        $current = $order->customer;

        // Nothing to do when the caller sent no customer field at all.
        if (!$request->hasAny(self::CUSTOMER_FIELDS)) {
            return (int) $order->customer_id;
        }

        $newMobile = $request->has('mobile') ? trim((string) $request->mobile) : null;
        $mobileChanged = $newMobile !== null && $current && $newMobile !== trim((string) $current->mobile);

        if ($mobileChanged || !$current) {
            $mobile = $newMobile ?? ($current->mobile ?? null);
            $existing = Customer::where('mobile', $mobile)->first();

            if ($existing) {
                // Re-point the order. Never rewrite an existing customer's identity here.
                return (int) $existing->id;
            }

            return (int) Customer::create([
                'user_id' => $userId,
                'first_name' => $request->input('first_name', $current->first_name ?? ''),
                'last_name' => $request->input('last_name', $current->last_name ?? ''),
                'email' => $request->input('email', $current->email ?? null),
                'gst_number' => $request->input('gst_number', $current->gst_number ?? null),
                'mobile' => $mobile,
                'status' => 1,
            ])->id;
        }

        // Same mobile — edit in place, but only if this seller owns the customer.
        if ((int) $current->user_id !== (int) $userId) {
            throw new \InvalidArgumentException(
                'This customer belongs to another account and cannot be edited. Send a different mobile number to attach a new customer.'
            );
        }

        $customerData = [];
        foreach (self::CUSTOMER_FIELDS as $field) {
            if ($request->has($field)) {
                $customerData[$field] = $request->input($field);
            }
        }

        if (!empty($customerData)) {
            $current->update($customerData);
        }

        return (int) $current->id;
    }

    /**
     * Update an order — allowed only while it is still in the `New` state.
     *
     * Once a shipment is booked the AWB, courier and wallet debit are already
     * committed, so the order is frozen. This is a partial update: only the
     * fields present in the request are applied.
     */
    public function update(Request $request, $order_id)
    {
        try {
            DB::beginTransaction();

            $userId = $request->user_id;
            $order = $this->findOwnedOrder($order_id, $userId);

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            if (strtolower((string) $order->status_courier) !== 'new') {
                return $this->errorResponse(
                    "Only orders with status 'New' can be updated. This order is '{$order->status_courier}'.",
                    422
                );
            }

            // Accept "COD"/"Prepaid" in any case, persist the canonical lowercase value.
            if ($request->has('order_type')) {
                $request->merge(['order_type' => OrderType::sanitize($request->input('order_type'))]);
            }

            // `sometimes` — every field is optional, but validated when supplied.
            $validator = Validator::make($request->all(), [
                'shipping_mode' => 'sometimes|nullable|in:Surface,Air',
                'order_date' => 'sometimes|required|date',
                'order_type' => 'sometimes|required|in:cod,prepaid',
                'cod_amount' => 'sometimes|required_if:order_type,cod|numeric|min:0',
                'warehouse_id' => 'sometimes|required|exists:courier_warehouses,id',
                'dimension_type' => 'sometimes|nullable|in:cm,inch',

                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|nullable|email',
                'gst_number' => 'sometimes|nullable|string',
                'mobile' => 'sometimes|required|string|min:10|max:15',

                'address' => 'sometimes|required|string',
                'country' => 'sometimes|required|string',
                'state' => 'sometimes|required|string',
                'city' => 'sometimes|required|string',
                'zip_code' => 'sometimes|required|string|size:6',

                'product_category' => 'sometimes|required|array|min:1',
                'product_category.*' => 'required|string',
                'product_name' => 'sometimes|required|array|min:1',
                'product_name.*' => 'required|string',
                'sku_number' => 'sometimes|nullable|array',
                'hsn_number' => 'sometimes|nullable|array',
                'amount' => 'sometimes|required|array|min:1',
                'amount.*' => 'required|numeric|min:0',
                'quantity' => 'sometimes|required|array|min:1',
                'quantity.*' => 'required|integer|min:1',

                'weight' => 'sometimes|required|numeric|min:0',
                'length' => 'sometimes|required|numeric|min:0',
                'width' => 'sometimes|required|numeric|min:0',
                'height' => 'sometimes|required|numeric|min:0',
                'total_weight' => 'sometimes|required|numeric|min:0',

                'invoice_no' => 'sometimes|nullable|string',
                'invoice_amount' => 'sometimes|required|numeric|min:0',
                'ewaybillno' => 'sometimes|nullable|string',
                'is_fragile_item' => 'sometimes|in:0,1',
            ], [
                'order_type.in' => 'Order type must be COD or Prepaid.',
                'cod_amount.required_if' => 'COD amount is required for COD orders.',
                'warehouse_id.exists' => 'Selected warehouse does not exist.',
                'zip_code.size' => 'Pin code must be exactly 6 digits.',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            // The warehouse must still belong to this account.
            if ($request->filled('warehouse_id')) {
                $warehouseExists = CourierWarehouse::where('id', $request->warehouse_id)
                    ->where('user_id', $userId)
                    ->where('warehouse_status', 1)
                    ->exists();

                if (!$warehouseExists) {
                    return $this->errorResponse('The selected warehouse is invalid or does not belong to your account.', 422);
                }
            }

            $order->load(['customer', 'customerAddress']);

            // Only copy across the fields the caller actually sent.
            $updatable = [
                'shipping_mode', 'order_date', 'order_type', 'cod_amount', 'warehouse_id',
                'length', 'width', 'height',
                'invoice_no', 'invoice_amount', 'ewaybillno', 'is_fragile_item',
            ];

            $data = [];
            foreach ($updatable as $field) {
                if ($request->has($field)) {
                    $data[$field] = $request->input($field);
                }
            }

            // `orders.weight` is driven by total_weight, exactly as in store().
            if ($request->has('total_weight')) {
                $data['weight'] = $request->total_weight;
            } elseif ($request->has('weight')) {
                $data['weight'] = $request->weight;
            }

            if ($request->has('invoice_amount')) {
                $data['total_amount'] = $request->invoice_amount;
            }

            // dimension_type is accepted for parity with store(), which always stores `cm`.
            if ($request->has('dimension_type')) {
                $data['dimension_type'] = 'cm';
            }

            // Customer: switch to another customer on a new mobile, otherwise edit in place.
            $customerId = $this->syncCustomerForUpdate($order, $request, $userId);
            $customerChanged = $customerId !== (int) $order->customer_id;
            if ($customerChanged) {
                $data['customer_id'] = $customerId;
            }

            // Delivery address. The order owns its own address row, so it is safe to edit —
            // unless the order just moved to a different customer, in which case we clone it.
            $addressData = [];
            foreach (self::ADDRESS_FIELDS as $field) {
                if ($request->has($field)) {
                    $addressData[$field] = $request->input($field);
                }
            }

            if ($customerChanged) {
                $existingAddress = $order->customerAddress;
                $data['customer_address_id'] = CustomerAddress::create(array_merge([
                    'customer_id' => $customerId,
                    'address' => $existingAddress->address ?? '',
                    'city' => $existingAddress->city ?? '',
                    'state' => $existingAddress->state ?? '',
                    'zip_code' => $existingAddress->zip_code ?? '',
                    'country' => $existingAddress->country ?? '',
                ], $addressData))->id;
            } elseif (!empty($addressData) && $order->customer_address_id) {
                CustomerAddress::where('id', $order->customer_address_id)->update($addressData);
            }

            if (!empty($data)) {
                $order->update($data);
            }

            // Replace the line items only when a new set was supplied.
            if ($request->has('product_category')) {
                OrderItem::where('order_id', $order->id)->delete();
                $this->b2cStore($order, $request);
            }

            Helper::orderActivity($order->id, 'Order updated via API.');

            DB::commit();

            $order->refresh()->load(['orderItems', 'customer', 'customerAddress']);

            return $this->successResponse([
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'status' => $order->status_courier,
                'customer_id' => $order->customer_id,
                'customer_changed' => $customerChanged,
                'order' => $order,
            ], 'The order has been updated successfully.');
        } catch (\InvalidArgumentException $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Failed to update order: ' . $e->getMessage());
        }
    }

    /**
     * Internal-only fields on a courier quote. They are stripped from the public
     * courier list (raw rate-card payload, commission breakup, RTO and the freight
     * split) but are re-derived server-side in ship() so the order still records them.
     */
    private const INTERNAL_COURIER_FIELDS = [
        'responseData',
        'percentage_amount',
        'commission_type',
        'commission_value',
        'rto_charge',
        'freight_charges',
        'cod_charges',
    ];

    /**
     * Fields re-injected into the ship request from a fresh quote. Covers the hidden
     * internal fields plus `tax` (which stays visible in the list but the minimal
     * ship payload does not post back), so the order records an accurate breakup no
     * matter which fields the caller sends. `responseData` is excluded — nothing reads it.
     */
    private const RESTORED_COURIER_FIELDS = [
        'percentage_amount',
        'commission_type',
        'commission_value',
        'rto_charge',
        'freight_charges',
        'cod_charges',
        'tax',
    ];

    /**
     * Build every serviceable courier quote for an order, sorted by total_charges.
     * Mirrors the web flow in Api\OrderController::orderShipCharge.
     */
    private function buildCourierQuotes($order, $user): array
    {
        $couriers = [];
        foreach (ShippingCompany::availableFor($user) as $shippingCompany) {
            $rateRequest = new Request();
            $rateRequest->merge([
                'volumdata' => [[
                    'length' => $order->length ?? 0,
                    'width' => $order->width ?? 0,
                    'height' => $order->height ?? 0,
                ]],
                'pickup_code' => $order->warehouse->zip_code,
                'delivery_code' => $order->customerAddress->zip_code,
                'weight' => $order->weight,
                'payment_type' => $order->order_type,
                'cod_amount' => $order->cod_amount,
                'order_id' => $order->id,
                'type_of_package' => $order->weight_order,
                'orderItems' => $order->orderItems,
            ]);

            $response = $this->rateCardService->getRate($user, $shippingCompany, $rateRequest, 1);
            if ($response && is_array($response)) {
                // getRate() returns an array of courier quotes for the company —
                // flatten so the API exposes one flat, pickable list (not a list of lists).
                $couriers = array_merge($couriers, $response);
            }
        }

        return collect($couriers)->sortBy('total_charges')->values()->all();
    }

    /**
     * Courier List / Serviceability + Rate Check for an order.
     *
     * Returns every serviceable courier with its chargeable weight and rate
     * breakup. The caller picks one entry and posts it back to /order/ship.
     */
    public function courierList(Request $request, $order_id)
    {
        $userId = $request->user_id;
        $user = User::find($userId);

        if ($user->role != 'admin' && $user->kyc_status == 0) {
            return $this->errorResponse('Your order cannot be placed until your KYC is approved.');
        }

        $order = $this->findOwnedOrder($order_id, $userId, ['warehouse', 'user', 'customerAddress', 'orderItems']);
        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        if (!$order->warehouse || !$order->customerAddress) {
            return $this->errorResponse('Pickup warehouse or delivery address is missing on this order.', 422);
        }

        if (
            empty($order->weight) || $order->weight <= 0 ||
            empty($order->length) || $order->length <= 0 ||
            empty($order->width) || $order->width <= 0 ||
            empty($order->height) || $order->height <= 0 ||
            empty($order->warehouse->zip_code) ||
            empty($order->customerAddress->zip_code)
        ) {
            return $this->errorResponse('Package dimensions (length, width, height, weight) and pickup/delivery pincodes are required before fetching couriers.', 422);
        }

        $couriers = collect($this->buildCourierQuotes($order, $user))
            ->map(function ($courier) {
                $clean = Arr::except($courier, self::INTERNAL_COURIER_FIELDS);
                // Always expose courier_id so the caller knows what to post to /order/ship.
                // Direct couriers (Shadowfax) have none → null.
                $clean['courier_id'] = $courier['courier_id'] ?? null;
                return $clean;
            })
            ->values();

        return $this->successResponse([
            'order_id' => $order->id,
            'total_couriers' => $couriers->count(),
            'couriers' => $couriers,
        ], 'Couriers fetched successfully.');
    }

    /**
     * Re-fetch the courier quotes for an order and pull the internal fields
     * (commission breakup, RTO and freight split) for the selected courier, so the
     * shipment records them even though they are hidden from /order/courier-list.
     *
     * Returns an empty array when the courier can no longer be quoted; the shipment
     * then falls back to ShipmentProcessor's defaults.
     */
    private function resolveInternalCourierFields($order, $shippingCompanyId, $courierId): array
    {
        try {
            $order->loadMissing(['warehouse', 'user', 'customerAddress', 'orderItems']);
            $quotes = $this->buildCourierQuotes($order, $order->user);
        } catch (\Throwable $e) {
            Log::warning('Developer API: could not re-quote couriers while shipping.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        foreach ($quotes as $quote) {
            $sameCompany = (int) ($quote['shipping_company_id'] ?? 0) === (int) $shippingCompanyId;
            // courier_id is '' for couriers that do not expose one — treat blank as a wildcard.
            $sameCourier = $courierId === null
                || (string) ($quote['courier_id'] ?? '') === (string) $courierId;

            if ($sameCompany && $sameCourier) {
                return Arr::only($quote, self::RESTORED_COURIER_FIELDS);
            }
        }

        return [];
    }

    /**
     * Ship Order — books the shipment with the courier chosen from /order/courier-list.
     * Post back the selected courier entry (shipping_company_id, total_charges,
     * applicable_weight, courier_id/name, freight/cod/tax) exactly as returned there.
     */
    public function ship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer',
            'shipping_company_id' => 'required|integer|exists:shipping_companies,id',
            'total_charges' => 'required|numeric|min:0',
            'applicable_weight' => 'required|numeric|min:0',
            'courier_id' => 'nullable',
            'courier_name' => 'nullable|string',
            'freight_charges' => 'nullable|numeric',
            'cod_charges' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
        ], [
            'order_id.required' => 'Order id is required.',
            'shipping_company_id.required' => 'Shipping company id is required. Use the value returned by /order/courier-list.',
            'total_charges.required' => 'Total charges is required. Use the value returned by /order/courier-list.',
            'applicable_weight.required' => 'Applicable weight is required. Use the value returned by /order/courier-list.',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $userId = $request->user_id;
        $order = $this->findOwnedOrder($request->order_id, $userId);
        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        DB::beginTransaction();
        try {
            $requestData = $request->all();
            $requestData['is_bulk_shipment'] = 0;

            // freight/cod/commission/rto are hidden from /order/courier-list, so the
            // caller cannot post them back. Re-derive them from a fresh quote for the
            // selected courier; server values override anything sent by the caller.
            $requestData = array_merge($requestData, $this->resolveInternalCourierFields(
                $order,
                $request->shipping_company_id,
                $request->courier_id
            ));

            $response = $this->shipmentProcessor->orderShipNow($requestData);

            // ShipmentProcessor returns a JsonResponse; surface its failure reason as-is.
            if ($response instanceof \Illuminate\Http\JsonResponse && $response->getData()->status === 'error') {
                DB::rollBack();
                Order::where('id', $order->id)->update([
                    'shipment_failure_reason' => $response->getData()->msg ?? 'An error occurred.'
                ]);
                return $this->errorResponse($response->getData()->msg ?? 'Shipment failed.', 422);
            }

            DB::commit();

            // Re-read so the caller gets the booked AWB instead of the UI's HTML message.
            $order->refresh();

            return $this->successResponse([
                'order_id' => $order->id,
                'order_prefix' => $order->order_prefix,
                'awb_number' => $order->awb_number,
                'lr_no' => $order->lr_no,
                'shipment_id' => $order->shipment_id,
                'courier_name' => $order->courier_name,
                'shipping_company_id' => $order->shipping_company_id,
                'shipping_charge' => $order->shipping_charge,
                'status' => $order->status_courier,
            ], 'Shipment booked successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to ship order: ' . $e->getMessage());
        }
    }

    /**
     * Order Status — lightweight current state, without calling the courier API.
     */
    public function status(Request $request, $order_id)
    {
        $order = $this->findOwnedOrder($order_id, $request->user_id);
        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        $statusHistory = OrderStatus::where('order_id', $order->id)
            ->orderBy('id')
            ->get(['order_status', 'created_at']);

        return $this->successResponse([
            'order_id' => $order->id,
            'order_prefix' => $order->order_prefix,
            'awb_number' => $order->awb_number,
            'status' => $order->status_courier,
            'courier_name' => $order->courier_name,
            'shipment_failure_reason' => $order->shipment_failure_reason,
            'status_history' => $statusHistory,
        ], 'Order status fetched successfully.');
    }

    /**
     * Label Download — returns the shipping label as a PDF stream (not JSON).
     */
    public function label(Request $request, $order_id)
    {
        $order = $this->findOwnedOrder($order_id, $request->user_id, [
            'shippingCompany:id,logo',
            'customer:id,first_name,last_name,mobile',
            'customerAddress',
            'warehouse',
            'orderItems',
            'user',
        ]);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        if (empty($order->awb_number)) {
            return $this->errorResponse('Label is not available until the order is shipped.', 422);
        }

        $preference = LabelPreference::where('user_id', $order->user_id)->first();
        $view = ($preference && $preference->label_size === 'desktop') ? 'desktop-label' : 'thermal-label';

        $htmlView = view('order.' . $view, [
            'shipping' => $order->shippingCompany ?? null,
            'order' => $order,
            'customer' => $order->customer ?? null,
            'customerAddr' => $order->customerAddress ?? null,
            'products' => $order->orderItems ?? null,
            'barcodePng' => DNS2D::getBarcodePNG($order->awb_number, 'QRCODE', 6, 6),
            'orderIdBarcodePng' => DNS1D::getBarcodePNG($order->awb_number, 'C128'),
            'hideLabel' => $order->warehouse ? $order->warehouse->label_options : [],
            'preference' => $preference,
        ])->render();

        $paperSize = 'A4';
        if ($preference && $preference->label_size === 'thermal') {
            $paperSize = [0, 0, 288, 432];
        } elseif ($preference && $preference->label_size === 'desktop') {
            $paperSize = 'letter';
        }

        $pdf = PDF::loadHtml($htmlView)
            ->setPaper($paperSize, 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $companyName = !empty($order->user->company_name)
            ? str_replace(' ', '_', $order->user->company_name)
            : 'Snapshyp';

        return $pdf->download($companyName . '_' . $order->id . '_label_' . date('d-m-Y') . '.pdf');
    }

    /**
     * Cancel Order — closes an unshipped order, or asks the courier to cancel the
     * shipment and then refunds the wallet. Mirrors Api\OrderController's split
     * between orderCancel (no AWB) and orderCancelApi (booked shipment).
     */
    public function cancel(Request $request, $order_id)
    {
        $order = $this->findOwnedOrder($order_id, $request->user_id, ['user']);
        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        if ($order->status_courier === 'cancelled') {
            return $this->errorResponse('The order is already cancelled.');
        }

        DB::beginTransaction();
        try {
            // Unshipped order — just close it, no courier call.
            if (empty($order->awb_number)) {
                $order->update([
                    'status_courier' => 'cancelled',
                    'reason_cancel' => 'The client has cancelled this order before it was shipped.',
                    'order_cancel_date' => now(),
                ]);

                OrderStatus::create([
                    'order_id' => $order->id,
                    'order_status' => 'cancelled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Helper::orderActivity($order->id, 'Order cancelled via API.');

                DB::commit();
                $this->dispatchCancelWebhook($order);

                return $this->successResponse($order, 'The order has been successfully cancelled.');
            }

            // Booked shipment — ask the courier to cancel, then refund the wallet.
            $user = $order->user;
            $shippingCompany = ShippingCompany::where('id', $order->shipping_company_id)
                ->where('status', 1)
                ->first();

            if (!$shippingCompany) {
                return $this->errorResponse('Shipment not found.');
            }

            $orderCancelledData = [];
            if ($shippingCompany->isShadowfax()) {
                $response = $this->shadowfaxService->cancel(
                    $order->awb_number,
                    $shippingCompany,
                    'Request cancelled by customer',
                    $order->order_prefix
                );

                if (!($response['success'] ?? false)) {
                    return $this->errorResponse($response['message'] ?? 'An error occurred during cancellation.');
                }

                $isQueuedCancellation = (bool) ($response['queued'] ?? false);
                $orderCancelledData = [
                    'status_courier' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
                    'order_cancel_date' => now(),
                    'reason_cancel' => $response['message'] ?? "Shipment cancelled in Shadowfax for AWB: {$order->awb_number}.",
                ];
            }

            if (!$orderCancelledData) {
                throw new \Exception('Cancellation is not supported for this shipping company.');
            }

            $order->update($orderCancelledData);

            $isQueuedCancellation = strtolower((string) ($orderCancelledData['status_courier'] ?? '')) === 'cancel requested';

            if (!$isQueuedCancellation && $user->role == 'user') {
                $walletRefund = $order->shipping_charge ?? 0;
                $user->increment('wallet_amount', $walletRefund);
                // Take back franchise earning / creator margin paid for this shipment
                app(\App\Services\ShipmentProcessor::class)->reverseShippingCredits($order);

                Billing::create([
                    'user_id' => $order->user_id,
                    'billing_type' => 'Order',
                    'billing_type_id' => $order->id,
                    'transaction_type' => 'credit',
                    'amount' => $walletRefund,
                    'note' => 'Order canceled with AWB number: ' . $order->awb_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => $isQueuedCancellation ? 'cancel requested' : 'cancelled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Helper::orderActivity(
                $order->id,
                $isQueuedCancellation
                    ? 'Shadowfax cancellation queued for AWB number: ' . $order->awb_number
                    : 'Order canceled with AWB number: ' . $order->awb_number
            );

            DB::commit();
            $this->dispatchCancelWebhook($order->fresh());

            return $this->successResponse(
                $order,
                $isQueuedCancellation
                    ? 'The cancellation request has been accepted and queued for the next facility scan.'
                    : 'The order has been successfully cancelled.'
            );
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Fire the order.deleted webhook after a cancellation. Best-effort — a webhook
     * failure must never roll back the cancellation the caller already saw succeed.
     */
    private function dispatchCancelWebhook($order): void
    {
        try {
            app(WebhookService::class)->dispatch((int) $order->user_id, 'order.deleted', $order);
        } catch (\Throwable $e) {
            Log::error('Webhook dispatch failed (order.deleted/api-service): ' . $e->getMessage());
        }
    }

    public function details(Request $request, $order_id)
    {
        $userId = $request->user_id;

        $order = Order::with('customer:id,first_name,last_name,email,mobile', 'customerAddress:id,address,country,state,city,zip_code', 'warehouse', 'orderItems')
            ->select(
                'id',
                'order_prefix',
                'invoice_no',
                'awb_number',
                'status_courier',
                'shipping_mode',
                'order_type',
                'cod_amount',
                'invoice_amount',
                'courier_name',
                'weight',
                'length',
                'width',
                'height',
                'order_date',
                'customer_id',
                'customer_address_id',
                'warehouse_id',
                'user_id'
            )
            ->where('user_id', $userId)
            ->find($order_id);

        if (!$order) {
            return $this->errorResponse('Order not found');
        }

        $orderActivities = OrderActivity::where('order_id', $order_id)->latest()->get();

        $trackingHistories = [];
        return $this->successResponse(compact('order', 'orderActivities', 'trackingHistories'), 'success');
    }
}
