<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerRemittance extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'cycle_from_date',
        'cycle_to_date',
        'remittance_id',
        'payment_scheduled_date',
        'payment_date',
        'total_delivery_count',
        'total_cod_remittance_amount',
        'total_prepaid_remittance_amount',
        'total_expenses',
        'previous_cycle_pending_net_amount',
        'amount_transferred_to_seller',
        'amount_transferred_next_cycle_or_wallet',
        'pending_spends_paid_by_seller',
        'settlement_status',
        'bank_account_no',
        'utr',
        'seller_payment_mode',
    ];


    protected $casts = [
        'cycle_from_date' => 'date',
        'cycle_to_date' => 'date',
        'payment_scheduled_date' => 'date',
        'payment_date' => 'date',
    ];

    /**
     * Get the user that owns the remittance
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'remittance_reference_id');
    }
}
