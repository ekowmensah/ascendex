<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'symbol',
        'direction',
        'amount',
        'entry_price',
        'close_price',
        'payout_rate',
        'payout_amount',
        'status',
        'expiry_time',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'entry_price' => 'decimal:8',
            'close_price' => 'decimal:8',
            'payout_rate' => 'decimal:4',
            'payout_amount' => 'decimal:8',
            'expiry_time' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
