<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodWalletExpense extends Model
{
    use HasFactory;

    protected $table = 'cod_wallet_expenses';

    protected $fillable = [
        'user_id',
        'cod_balance_used',
        'wallet_amount_added',
        'transfer_percentage',
        'transaction_type',
        'status',
        'reference_id',
        'remarks',
        'processed_at',
    ];

    protected $casts = [
        'cod_balance_used' => 'decimal:2',
        'wallet_amount_added' => 'decimal:2',
        'transfer_percentage' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the expense
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Generate unique reference ID
     */
    public static function generateReferenceId()
    {
        return 'CWE' . date('YmdHis') . rand(1000, 9999);
    }
}
