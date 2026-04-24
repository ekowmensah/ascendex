<?php

namespace App\Models;

use App\Support\WalletCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $deposit): void {
            if ($deposit->currency === null || $deposit->currency === '') {
                $deposit->currency = WalletCurrency::DEFAULT;
            }

            if ($deposit->local_currency === null || $deposit->local_currency === '') {
                $deposit->local_currency = 'GHS';
            }

            if ($deposit->local_amount === null || $deposit->local_amount === '') {
                $deposit->local_amount = $deposit->amount;
            }

            if ($deposit->conversion_rate === null || $deposit->conversion_rate === '') {
                $deposit->conversion_rate = '1.00000000';
            }

            if ($deposit->fee_type === null || $deposit->fee_type === '') {
                $deposit->fee_type = 'flat';
            }

            if ($deposit->fee_value === null || $deposit->fee_value === '') {
                $deposit->fee_value = '0.00000000';
            }

            if ($deposit->fee_amount === null || $deposit->fee_amount === '') {
                $deposit->fee_amount = '0.00000000';
            }

            if ($deposit->net_amount === null || $deposit->net_amount === '') {
                $deposit->net_amount = $deposit->amount;
            }
        });
    }

    protected $fillable = [
        'user_id',
        'currency',
        'amount',
        'local_currency',
        'local_amount',
        'conversion_rate',
        'fee_type',
        'fee_value',
        'fee_amount',
        'net_amount',
        'payment_method',
        'mobile_provider',
        'sender_name',
        'sender_phone',
        'transaction_reference',
        'proof_path',
        'status',
        'approved_at',
        'approved_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'currency' => 'string',
            'amount' => 'decimal:8',
            'local_amount' => 'decimal:2',
            'conversion_rate' => 'decimal:8',
            'fee_value' => 'decimal:8',
            'fee_amount' => 'decimal:8',
            'net_amount' => 'decimal:8',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
