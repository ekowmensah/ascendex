<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'bonus',
        'locked_balance',
    ];

    protected function casts(): array
    {
        return [
            'currency' => 'string',
            'balance' => 'decimal:8',
            'bonus' => 'decimal:8',
            'locked_balance' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }
}
