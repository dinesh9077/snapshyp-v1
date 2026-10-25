<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'order_prefix',
		'reference_number',
		'order_type',
		'vendor_id',
		'vendor_address_id',
		'customer_id',
		'customer_address_id',
		'shipping_company_id',
		'shipping_mode',
		'tax',
		'tax_percentage',
		'round_off',
		'shipping_charge',
		'total_amount',
		'status_courier',
		'woocommerce_order_id',
		'woocommerce_order_number',
		'woocommerce_order_key',
		'is_woocommerce_order',
		'reason_cancel',
		'shipment_failure_reason',
		'order_date',
		'order_cancel_date',
		'delivery_date',
		'status',
		'is_online',
		'weight',
		'length',
		'width',
		'height',
		'packaging_id',
		'shipment_id',
		'awb_number',
		'courier_name',
		'courier_id',
		'weight_status',
		'weight_update_date',
		'applicable_weight',
		'percentage_amount',
		'is_voucher',
		'voucher_no',
		'is_remmitance',
		'is_payout',
		'remittance_reference_id',
		'remittance_amount',
		'remittance_date',
		'cod_charges',
		'rto_charge',
		'pickup_location_name',
		'pickup_time',
		'cod_voucher_id',
		'pickup_date',
		'expected_package_count',
		'on_pro_id',
		'warehouse_id',
		'freight_mode',
		'cod_amount',
		'dimension_type',
		'invoice_no',
		'invoice_amount',
		'ewaybillno',
		'lr_no',
		'api_response',
		'api_response1',
		'invoice_document',
		'weight_order',
		'insurance_type',
		'is_fragile_item',
		'is_raise_weight',
		'rto_charge_applied',
		'invoice_id',
		'is_invoice_count',
		'courier_logo',
		'is_shopify_order',
		'shopify_order_number',
		'shopify_order_id',
		'total_gst_amount',
		'total_shipping_cost',
		'queue',
		'confirm_shopify_order',
		'send_whatsapp_notify',
		'shopify_order_name',
		'shopify_tags',
		// New platform-agnostic columns
		'platform_source',
		'is_platform_order',
		'platform_order_id',
		'platform_order_number',
		'platform_order_name',
		'platform_order_confirmed',
		'platform_tags',
		'platform_metadata',
	];


	protected $casts = [
		'api_response' => 'array',
		'api_response1' => 'array',
		'invoice_document' => 'array',
		'shopify_tags' => 'array',
		'send_whatsapp_notify' => 'array',
		'is_platform_order' => 'boolean',
		'platform_order_confirmed' => 'boolean',
		'platform_tags' => 'array',
		'platform_metadata' => 'array',
	];

	/**
	 * Accessor: When reading is_shopify_order, check platform columns first
	 */
	public function getIsShopifyOrderAttribute($value)
	{
		// If platform columns are set, use them
		if ($this->attributes['platform_source'] ?? null) {
			return ($this->attributes['platform_source'] === 'shopify' && $this->attributes['is_platform_order']) ? 1 : 0;
		}
		// Otherwise use old column
		return $value;
	}

	/**
	 * Mutator: When setting is_shopify_order, also update platform columns
	 */
	public function setIsShopifyOrderAttribute($value)
	{
		$this->attributes['is_shopify_order'] = $value;
		if ($value == 1) {
			$this->attributes['platform_source'] = 'shopify';
			$this->attributes['is_platform_order'] = 1;
		}
	}

	/**
	 * Accessor: When reading shopify_order_id, check platform column first
	 */
	public function getShopifyOrderIdAttribute($value)
	{
		return $this->attributes['platform_order_id'] ?? $value;
	}

	/**
	 * Mutator: When setting shopify_order_id, also update platform column
	 */
	public function setShopifyOrderIdAttribute($value)
	{
		$this->attributes['shopify_order_id'] = $value;
		$this->attributes['platform_order_id'] = $value;
	}

	/**
	 * Accessor: When reading shopify_order_number, check platform column first
	 */
	public function getShopifyOrderNumberAttribute($value)
	{
		return $this->attributes['platform_order_number'] ?? $value;
	}

	/**
	 * Mutator: When setting shopify_order_number, also update platform column
	 */
	public function setShopifyOrderNumberAttribute($value)
	{
		$this->attributes['shopify_order_number'] = $value;
		$this->attributes['platform_order_number'] = $value;
	}

	/**
	 * Accessor: When reading shopify_order_name, check platform column first
	 */
	public function getShopifyOrderNameAttribute($value)
	{
		return $this->attributes['platform_order_name'] ?? $value;
	}

	/**
	 * Mutator: When setting shopify_order_name, also update platform column
	 */
	public function setShopifyOrderNameAttribute($value)
	{
		$this->attributes['shopify_order_name'] = $value;
		$this->attributes['platform_order_name'] = $value;
	}

	/**
	 * Accessor: When reading confirm_shopify_order, check platform column first
	 */
	public function getConfirmShopifyOrderAttribute($value)
	{
		return $this->attributes['platform_order_confirmed'] ?? $value;
	}

	/**
	 * Mutator: When setting confirm_shopify_order, also update platform column
	 */
	public function setConfirmShopifyOrderAttribute($value)
	{
		$this->attributes['confirm_shopify_order'] = $value;
		$this->attributes['platform_order_confirmed'] = $value;
	}

	/**
	 * Helper method: Check if order is from Shopify
	 */
	public function isShopifyOrder()
	{
		return $this->platform_source === 'shopify' && $this->is_platform_order;
	}

	/**
	 * Helper method: Check if order is from WooCommerce
	 */
	public function isWooCommerceOrder()
	{
		return $this->platform_source === 'woocommerce' && $this->is_platform_order;
	}

	/**
	 * Helper method: Check if order is from any platform
	 */
	public function isPlatformOrder()
	{
		return (bool) $this->is_platform_order;
	}

	/**
	 * Helper method: Get platform name
	 */
	public function getPlatformName()
	{
		return ucfirst($this->platform_source ?? 'manual');
	}


	public static function generateOrderNumber($userId = null)
	{
		$nextSequence = ((int) self::max('id')) + 1;

		do {
			$orderNumber = str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
			$exists = self::where('order_prefix', $orderNumber)->exists();
			$nextSequence++;
		} while ($exists);

		return $orderNumber;
	}

	protected $appends = [
		'courier_image_url',
	];

	public function getCourierImageUrlAttribute()
	{
		return $this->courier_id ? url("storage/courier-logo/{$this->courier_id}.png") : null;
	}

	public function customer()
	{
		return $this->belongsTo(Customer::class, 'customer_id');
	}

	public function customerAddress()
	{
		return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
	}

	public function warehouse()
	{
		return $this->belongsTo(CourierWarehouse::class, 'warehouse_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	public function shippingCompany()
	{
		return $this->belongsTo(ShippingCompany::class, 'shipping_company_id');
	}

	public function orderItems()
	{
		return $this->hasMany(OrderItem::class, 'order_id');
	}

	public function excessWeight()
	{
		return $this->hasOne(ExcessWeight::class, 'order_id');
	}

	public function weightDescripencies()
	{
		return $this->hasOne(weightDescrepency::class, 'order_id');
	}
}
