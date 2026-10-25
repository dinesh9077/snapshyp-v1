<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseWithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_details',
        'admin_note',
        'transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function earnings()
    {
        return $this->hasMany(FranchiseEarning::class, 'withdrawal_request_id');
    }

    public function payments()
    {
        return $this->hasMany(FranchiseWithdrawalPayment::class, 'withdrawal_request_id');
    }
}
