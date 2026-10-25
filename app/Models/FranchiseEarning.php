<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_user_id',
        'seller_user_id',
        'order_id',
        'withdrawal_request_id',
        'amount',
        'status',
        'remarks',
    ];

    public function franchiseUser()
    {
        return $this->belongsTo(User::class, 'franchise_user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function withdrawalRequest()
    {
        return $this->belongsTo(FranchiseWithdrawalRequest::class, 'withdrawal_request_id');
    }
}
