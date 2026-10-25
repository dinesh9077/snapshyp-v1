<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FranchiseWithdrawalPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_request_id',
        'amount',
        'bank_account_no',
        'utr',
        'payment_date',
        'payment_mode',
        'admin_note',
        'reject_reason',
    ];

    public function withdrawalRequest()
    {
        return $this->belongsTo(FranchiseWithdrawalRequest::class, 'withdrawal_request_id');
    }
}
