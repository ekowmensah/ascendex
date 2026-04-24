<?php

namespace App\Models;

use App\Support\WalletCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $withdrawal): void {
            if ($withdrawal->currency === null || $withdrawal->currency === '') {
                $withdrawal->currency = WalletCurrency::DEFAULT;
            }

            if ($withdrawal->local_currency === null || $withdrawal->local_currency === '') {
                $withdrawal->local_currency = 'GHS';
            }

            if ($withdrawal->local_amount === null || $withdrawal->local_amount === '') {
                $withdrawal->local_amount = $withdrawal->amount;
            }

            if ($withdrawal->conversion_rate === null || $withdrawal->conversion_rate === '') {
                $withdrawal->conversion_rate = '1.00000000';
            }

            if ($withdrawal->fee_type === null || $withdrawal->fee_type === '') {
                $withdrawal->fee_type = 'flat';
            }

            if ($withdrawal->fee_value === null || $withdrawal->fee_value === '') {
                $withdrawal->fee_value = '0.00000000';
            }

            if ($withdrawal->fee_amount === null || $withdrawal->fee_amount === '') {
                $withdrawal->fee_amount = '0.00000000';
            }

            if ($withdrawal->net_amount === null || $withdrawal->net_amount === '') {
                $withdrawal->net_amount = $withdrawal->amount;
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
        'destination',
        'mobile_provider',
        'account_name',
        'account_number',
        'account_phone',
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
