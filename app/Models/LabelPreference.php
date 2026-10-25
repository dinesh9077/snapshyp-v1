<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label_size',
        'label_show_invoice_value',
        'label_show_invoice_number',
        'label_show_seller_name',
        'label_show_company_name',
        'label_show_seller_address',
        'label_show_seller_mobile',
        'label_show_quantity',
        'label_show_item_price',
        'label_show_taxes',
        'label_show_product_name',
        'label_show_sku',
        'label_print_multiple',
        'label_product_name_limit',
        'label_sku_limit',
        'label_print_limit',
        'label_company_logo',
        'label_show_weight',
        'label_show_cod_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
